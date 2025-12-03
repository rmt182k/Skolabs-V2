$(document).ready(function () {
    // ========================================================================
    // 1. SETUP PERMISSION GLOBAL
    // ========================================================================

    const PAGE_MENU_NAME = 'Class';
    const MY_PERMISSIONS = (window.globalPermissionsByMenu && window.globalPermissionsByMenu[PAGE_MENU_NAME])
        ? window.globalPermissionsByMenu[PAGE_MENU_NAME]
        : [];

    function can(permissionName) {
        return MY_PERMISSIONS.includes(permissionName);
    }

    // ================================================create-task-btn========================
    // 2. KONFIGURASI & VARIABEL UTAMA
    // ========================================================================
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    function getClassId() {
        const pathParts = window.location.pathname.split('/').filter(Boolean);
        let urlId = 0;

        const roleName = window.globalRoles[0] ? window.globalRoles[0].name : '';

        if (roleName === 'student') {
            const studentClassId = window.globalStudentClasses[0].id ? window.globalStudentClasses[0].id : null;
            return studentClassId;
        } else {
            urlId = pathParts.pop();
        }

        return urlId;
    }

    const CLASS_ID = getClassId();
    const STUDENT_ROLE_ID = 3;

    // Endpoint API
    const API = {
        // Siswa
        GET_CLASS_STUDENTS: (classId) => `/api/classes/${classId}/students`,
        SEARCH_USERS_FOR_ASSIGNMENT: (classId) => `/api/roles/${STUDENT_ROLE_ID}/users?exclude_class_id=${classId}`,
        ASSIGN_STUDENT_TO_CLASS: (classId) => `/api/classes/${classId}/assign-students`,
        REMOVE_STUDENT_FROM_CLASS: (classId, userId) => `/api/classes/${classId}/remove-student/${userId}`,

        // Jadwal
        GET_CLASS_SCHEDULE: (classId) => `/api/classes/${classId}/schedule`,
        STORE_SCHEDULE_ENTRY: (classId) => `/api/classes/${classId}/schedule/store`,
        DESTROY_SCHEDULE_ENTRY: (classId, scheduleId) => `/api/classes/${classId}/schedule/${scheduleId}/destroy`,
        GET_ASSIGNED_SUBJECTS: () => `/api/subjects/assigned/search`,
        GET_TEACHERS_FOR_SUBJECT: (subjectId) => `/api/subjects/${subjectId}/teachers/search`,

        // Bahan Ajar
        GET_CLASS_MATERIALS: (classId) => `/api/classes/${classId}/materials`,
        STORE_MATERIAL: (classId) => `/api/classes/${classId}/materials/store`,
        DESTROY_MATERIAL: (classId, materialId) => `/api/classes/${classId}/materials/${materialId}/destroy`,

        // Tugas & Ujian
        GET_CLASS_TASKS: (classId) => `/api/classes/${classId}/tasks`,
        DESTROY_TASK: (classId, taskId) => `/api/classes/${classId}/tasks/${taskId}/destroy`
    };

    let studentModal, scheduleModal, materialModal;
    let studentTable;
    let fullScheduleData = [];
    let fullMaterialsData = [];
    let hasAssignedSubjects = false;
    const subjectsAdminUrl = '/subjects';

    function checkAssignedSubjects() {
        return $.get(API.GET_ASSIGNED_SUBJECTS(), { term: '', page: 1 })
            .done(response => {
                hasAssignedSubjects = (response.data && Array.isArray(response.data) && response.data.length > 0);
            });
    }

    // ========================================================================
    // 3. INISIALISASI APLIKASI
    // ========================================================================
    function initializeApp() {
        if (!CLASS_ID || isNaN(CLASS_ID)) {
            Swal.fire('Error Kritis', 'ID Kelas tidak ditemukan.', 'error');
            return;
        }

        $.get(API.GET_CLASS_SCHEDULE(CLASS_ID))
            .done(response => {
                if (response.success && Array.isArray(response.data)) {
                    fullScheduleData = response.data;
                }
            })
            .always(() => {
                studentModal = new bootstrap.Modal($('#studentModal')[0]);
                scheduleModal = new bootstrap.Modal($('#scheduleModal')[0]);
                materialModal = new bootstrap.Modal($('#materialModal')[0]);

                setupDataTable();
                loadClassStudents();
                setupStudentSearch();
                setupScheduleModalSelect2();
                setupMaterialModalSelects();
                attachEventListeners();

                const activeTab = $('button[data-bs-toggle="tab"].active').attr('id');
                if (activeTab === 'schedule-tab-btn') renderSchedule(fullScheduleData);
                else if (activeTab === 'materials-tab-btn') loadLearningMaterials();
                else if (activeTab === 'assignments-tab-btn') loadClassTasks();
            });

        checkAssignedSubjects();
    }

    // ========================================================================
    // 4. LOGIC: TUGAS & UJIAN (FIXED PERMISSIONS)
    // ========================================================================

    function loadClassTasks() {
        $('#tasks-loading').show();
        $('#tasks-container').empty();
        $('#tasks-error').hide();

        $.get(API.GET_CLASS_TASKS(CLASS_ID))
            .done(response => {
                const tasks = (response.success && Array.isArray(response.data)) ? response.data : [];
                renderClassTasks(tasks);
            })
            .fail(error => {
                $('#tasks-error').text('Gagal memuat tugas.').show();
                renderClassTasks([]);
            })
            .always(() => $('#tasks-loading').hide());
    }

    function renderClassTasks(data) {
        const container = $('#tasks-container');
        container.empty();

        if (data.length === 0) {
            container.html('<div class="col-12"><div class="tasks-empty"><i class="fas fa-file-signature me-2"></i>Belum ada tugas atau ujian</div></div>');
            return;
        }

        data.sort((a, b) => new Date(b.end_time) - new Date(a.end_time));

        data.forEach(task => {
            const { status, badgeClass } = getTaskStatus(task.start_time, task.end_time);
            const isExpired = new Date() > new Date(task.end_time);

            let iconClass = 'fa-file-alt';
            if (task.type === 'quiz') iconClass = 'fa-question-circle';
            if (task.type === 'exam') iconClass = 'fa-graduation-cap';

            // --- BUILD BUTTONS DENGAN PERMISSION DARI SEEDER ---
            let buttonsHtml = '';

            // 1. Permission Siswa: submit_assignment
            if (can('view_assignment')) {
                const isClosed = status === 'Ditutup' || isExpired;
                const btnClass = isClosed ? 'btn-secondary disabled' : 'btn-success';
                const btnUrl = isClosed ? 'javascript:void(0)' : `/classes/${CLASS_ID}/tasks/${task.id}/answer`;

                buttonsHtml += `<a href="${btnUrl}" class="btn btn-sm ${btnClass} me-1"><i class="fas fa-pen-square me-1"></i> ${isClosed ? 'Ditutup' : 'Jawab'}</a>`;
            }

            // 2. Permission Guru: grade_assignment
            if (can('view_submissions')) {
                buttonsHtml += `<a href="/classes/${CLASS_ID}/tasks/${task.id}/submissions" class="btn btn-sm btn-outline-secondary me-1"><i class="fas fa-clipboard-check"></i> View Submission</a>`;
            }

            if (can('view_grades') && task.submission_id) {
                const gradeUrl = `/classes/${CLASS_ID}/tasks/${task.id}/submissions/${task.submission_id}/grade`;
                buttonsHtml += `<a href="${gradeUrl}" class="btn btn-sm btn-outline-info me-1">
                                <i class="fas fa-star me-1"></i> View Grades
                            </a>`;
            } else {
                buttonsHtml += `<a href="javascript:void(0)"
                                   onclick="alert('Maaf, tombol ini hanya untuk siswa yang sudah mengumpulkan tugas.')"
                                   class="btn btn-sm btn-outline-secondary me-1">
                                    <i class="fas fa-star me-1"></i> View Grades
                                </a>`;
            }

            // 3. Permission Edit: edit_assignment
            if (can('edit_assignment')) {
                buttonsHtml += `<a href="/classes/${CLASS_ID}/tasks/${task.id}/edit" class="btn btn-sm btn-outline-primary me-1"><i class="fas fa-pencil-alt"></i> Edit</a>`;
            }

            // 4. Permission Delete: delete_assignment
            if (can('delete_assignment')) {
                buttonsHtml += `<button class="btn btn-sm btn-outline-danger btn-delete-task" data-id="${task.id}" data-title="${task.title}"><i class="fas fa-trash"></i> Hapus</button>`;
            }

            const taskHtml = `
            <div class="col-xl-6">
                <div class="task-card" data-id="${task.id}">
                    <div class="task-header">
                        <div class="task-icon type-${task.type}"><i class="fas ${iconClass}"></i></div>
                        <div>
                            <h6 class="task-title">${task.title}</h6>
                            <p class="task-subject">${task.subject_name || 'Mapel Dihapus'}</p>
                        </div>
                    </div>
                    <div class="task-body">
                        <p class="task-description">${task.description ? task.description.substring(0, 150) + 'Tidak ada deskripsi.' : 'Tidak ada deskripsi.'}</p>
                        <div class="task-meta">
                            <div><span class="badge ${badgeClass}">${status}</span></div>
                            <small class="text-muted"><i class="fas fa-clock"></i> Deadline: ${new Date(task.end_time).toLocaleString('id-ID')}</small>
                        </div>
                    </div>
                    <div class="task-footer">${buttonsHtml}</div>
                </div>
            </div>`;
            container.append(taskHtml);
        });
    }

    function getTaskStatus(start, end) {
        const now = new Date();
        if (now < new Date(start)) return { status: 'Akan Datang', badgeClass: 'bg-info text-dark' };
        if (now <= new Date(end)) return { status: 'Berlangsung', badgeClass: 'bg-success' };
        return { status: 'Ditutup', badgeClass: 'bg-danger' };
    }

    function confirmDeleteTask(taskId, title) {
        Swal.fire({
            title: 'Hapus Tugas?',
            text: `Hapus ${title}? Semua jawaban siswa juga akan terhapus!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: API.DESTROY_TASK(CLASS_ID, taskId),
                    method: 'DELETE',
                    success: (res) => {
                        Swal.fire('Berhasil', res.message, 'success');
                        loadClassTasks();
                    },
                    error: (err) => Swal.fire('Gagal', 'Gagal menghapus tugas', 'error')
                });
            }
        });
    }

    // ========================================================================
    // 5. LOGIC: BAHAN AJAR (FIXED PERMISSIONS)
    // ========================================================================
    function loadLearningMaterials() {
        $('#materials-loading').show();
        $('#materials-container').empty();

        $.get(API.GET_CLASS_MATERIALS(CLASS_ID))
            .done(response => {
                fullMaterialsData = (response.success && Array.isArray(response.data)) ? response.data : [];
                renderLearningMaterials(fullMaterialsData);
            })
            .always(() => $('#materials-loading').hide());
    }

    function renderLearningMaterials(data) {
        const container = $('#materials-container');
        container.empty();

        if (data.length === 0) {
            container.html('<div class="col-12"><div class="materials-empty">Belum ada bahan ajar</div></div>');
            return;
        }

        data.forEach(material => {
            let actionButtons = '';

            // CATATAN: Karena di Seeder kamu TIDAK ADA 'edit_material' (hanya create/delete/view),
            // maka logika Edit kita pakai 'create_material' saja (Asumsi: yang bisa create, bisa edit).
            // Jika ingin ketat, harus tambahkan 'edit_material' di database.
            if (can('create_material')) {
                actionButtons += `<button class="btn btn-sm btn-outline-primary btn-edit-material" data-id="${material.id}"><i class="fas fa-pencil-alt"></i></button>`;
            }
            if (can('delete_material')) {
                actionButtons += `<button class="btn btn-sm btn-outline-danger btn-delete-material" data-id="${material.id}" data-title="${material.title}"><i class="fas fa-trash"></i></button>`;
            }

            const html = `
            <div class="col-lg-6">
                <div class="material-card">
                    <div class="material-header">
                        <div>
                            <h6 class="material-title">${material.title}</h6>
                            <p class="material-subject">${material.subject_name || '-'}</p>
                        </div>
                        <div class="btn-group">${actionButtons}</div>
                    </div>
                    <div class="material-body">
                        ${renderMaterialAttachments(material)}
                    </div>
                </div>
            </div>`;
            container.append(html);
        });
    }

    function renderMaterialAttachments(material) {
        let html = '';
        if (material.file_path) {
            html += `<a href="/storage/${material.file_path}" target="_blank" class="material-attachment"><i class="fas fa-file me-2"></i> ${material.file_name}</a>`;
        }
        if (material.link_url) {
            html += `<br><a href="${material.link_url}" target="_blank" class="material-attachment"><i class="fas fa-link me-2"></i> Link Materi</a>`;
        }
        return html || '<small class="text-muted">Tidak ada lampiran</small>';
    }

    function setupMaterialModalSelects() {
        const seen = new Set();
        const data = [];
        fullScheduleData.forEach(e => {
            if (e.subject_id && !seen.has(e.subject_id)) {
                seen.add(e.subject_id);
                data.push({ id: e.subject_id, text: e.subject_name });
            }
        });
        $('#material_subject_id').select2({
            theme: "bootstrap-5",
            dropdownParent: $('#materialModal'),
            data: data
        });
    }

    function resetMaterialModal() {
        $('#materialModalForm')[0].reset();
        $('#material_id').val('');
        $('#material_subject_id').val(null).trigger('change');
        $('#current-file-info').text('');
        $('#remove-file-group').hide();
    }

    $('#materialModalForm').on('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        $.ajax({
            url: API.STORE_MATERIAL(CLASS_ID), method: 'POST', data: formData, processData: false, contentType: false,
            success: (res) => { materialModal.hide(); Swal.fire('Sukses', res.message, 'success'); loadLearningMaterials(); },
            error: (err) => Swal.fire('Error', 'Gagal menyimpan', 'error')
        });
    });

    $('#materials-container').on('click', '.btn-delete-material', function () {
        confirmDeleteMaterial($(this).data('id'), $(this).data('title'));
    });

    function handleEditMaterialClick(materialId) {
        const entry = fullMaterialsData.find(item => item.id == materialId);
        if (!entry) return;
        resetMaterialModal();
        $('#materialModalLabel').text('Edit Bahan Ajar');
        $('#material_id').val(entry.id);
        $('#title').val(entry.title);
        $('#description').val(entry.description);
        if (entry.subject_id) $('#material_subject_id').val(entry.subject_id).trigger('change');
        $('#link_url').val(entry.link_url);
        if (entry.file_path) {
            $('#current-file-info').text(`File: ${entry.file_name}`);
            $('#remove-file-group').show();
        }
        materialModal.show();
    }

    function confirmDeleteMaterial(materialId, title) {
        Swal.fire({
            title: 'Hapus Materi?', html: `Hapus <strong>${title}</strong>?`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya'
        }).then((r) => {
            if (r.isConfirmed) $.ajax({ url: API.DESTROY_MATERIAL(CLASS_ID, materialId), method: 'DELETE', success: (res) => { Swal.fire('Sukses', res.message, 'success'); loadLearningMaterials(); } });
        });
    }
    $('#materials-container').on('click', '.btn-edit-material', function () {
        handleEditMaterialClick($(this).data('id'));
    });

    // ========================================================================
    // 6. LOGIC: JADWAL PELAJARAN (PERMISSIONS OK)
    // ========================================================================
    function loadClassSchedule() {
        $('#schedule-loading').show();
        $.get(API.GET_CLASS_SCHEDULE(CLASS_ID))
            .done(response => {
                fullScheduleData = response.data || [];
                renderSchedule(fullScheduleData);
            })
            .always(() => $('#schedule-loading').hide());
    }

    function renderSchedule(data) {
        const container = $('#schedule-container').empty();
        const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        const scheduleByDay = {};
        data.forEach(e => {
            if (!scheduleByDay[e.day_name]) scheduleByDay[e.day_name] = [];
            scheduleByDay[e.day_name].push(e);
        });

        days.forEach(day => {
            const entries = scheduleByDay[day] || [];
            let entriesHtml = '';

            if (entries.length === 0) entriesHtml = '<div class="schedule-empty">Kosong</div>';
            else {
                entries.sort((a, b) => a.start_time_formatted.localeCompare(b.start_time_formatted));
                entriesHtml = entries.map(e => {
                    // Check Permission sesuai Seeder: edit_schedule & delete_schedule
                    let btns = '';
                    // 1. Permission Edit: edit_schedule
                    if (can('edit_schedule')) {
                        btns += `<button class="btn btn-sm btn-outline-primary btn-edit-schedule me-1" data-id="${e.id}">
                            <i class="fas fa-pencil-alt"></i>
                         </button>`;
                    }

                    // 2. Permission Delete: delete_schedule
                    if (can('delete_schedule')) {
                        btns += `<button class="btn btn-sm btn-outline-danger btn-delete-schedule" data-id="${e.id}">
                            <i class="fas fa-trash"></i>
                         </button>`;
                    }

                    return `
                    <div class="schedule-entry">
                        <div class="schedule-time">${e.start_time_formatted} - ${e.end_time_formatted}</div>
                        <div class="schedule-details">
                            <strong>${e.subject_name}</strong><br>
                            <small>${e.teacher_name}</small>
                        </div>
                        <div class="btn-group">${btns}</div>
                    </div>`;
                }).join('');
            }

            container.append(`<div class="col-lg-4 mb-3"><div class="card"><div class="card-header fw-bold">${day}</div><div class="card-body p-2">${entriesHtml}</div></div></div>`);
        });
    }

    function setupScheduleModalSelect2() {
        $('#subject_id').select2({ theme: "bootstrap-5", dropdownParent: $('#scheduleModal'), placeholder: 'Cari mapel...', ajax: { url: API.GET_ASSIGNED_SUBJECTS(), dataType: 'json', delay: 250, data: (p) => ({ term: p.term }), processResults: (r) => ({ results: r.data.map(s => ({ id: s.id, text: s.name })) }) } });
        $('#user_id').select2({ theme: "bootstrap-5", dropdownParent: $('#scheduleModal'), placeholder: 'Pilih mapel dulu', disabled: true });

        $('#subject_id').on('change', function () {
            const sid = $(this).val();
            const teacherSelect = $('#user_id');
            teacherSelect.val(null).trigger('change');
            if (sid) {
                teacherSelect.prop('disabled', false);
                teacherSelect.select2({ theme: "bootstrap-5", dropdownParent: $('#scheduleModal'), ajax: { url: API.GET_TEACHERS_FOR_SUBJECT(sid), dataType: 'json', delay: 250, data: (p) => ({ term: p.term }), processResults: (r) => ({ results: r.data.map(u => ({ id: u.id, text: `${u.name}` })) }) } });
            } else {
                teacherSelect.prop('disabled', true);
            }
        });
    }

    $('#scheduleModalForm').on('submit', function (e) {
        e.preventDefault();
        $.post(API.STORE_SCHEDULE_ENTRY(CLASS_ID), $(this).serialize())
            .done(res => { scheduleModal.hide(); Swal.fire('Sukses', res.message, 'success'); loadClassSchedule(); })
            .fail(err => Swal.fire('Error', 'Gagal simpan jadwal', 'error'));
    });

    // ... Handler Edit/Delete Schedule ...
    function handleEditScheduleClick(scheduleId) {
        const entry = fullScheduleData.find(item => item.id == scheduleId);
        if (!entry) return;
        $('#scheduleModalForm')[0].reset();
        $('#scheduleModalLabel').text('Edit Schedule Entry');
        $('#schedule_id').val(entry.id);
        $('#day_name').val(entry.day_name);
        $('#start_time').val(entry.start_time_formatted);
        $('#end_time').val(entry.end_time_formatted);

        if (entry.subject_id) {
            const subjectOption = new Option(entry.subject_name, entry.subject_id, true, true);
            $('#subject_id').append(subjectOption).trigger('change');
            // Trigger manual untuk load teacher select2
            $('#user_id').prop('disabled', false).select2({ theme: "bootstrap-5", dropdownParent: $('#scheduleModal'), ajax: { url: API.GET_TEACHERS_FOR_SUBJECT(entry.subject_id), dataType: 'json', delay: 250, data: (p) => ({ term: p.term }), processResults: (r) => ({ results: r.data.map(u => ({ id: u.id, text: `${u.name}` })) }) } });
            if (entry.user_id) {
                const teacherOption = new Option(entry.teacher_name, entry.user_id, true, true);
                $('#user_id').append(teacherOption).trigger('change');
            }
        }
        scheduleModal.show();
    }
    $('#schedule-container').on('click', '.btn-edit-schedule', function () { handleEditScheduleClick($(this).data('id')); });
    $('#schedule-container').on('click', '.btn-delete-schedule', function () {
        const id = $(this).data('id');
        Swal.fire({ title: 'Hapus Jadwal?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya' }).then(r => {
            if (r.isConfirmed) $.ajax({ url: API.DESTROY_SCHEDULE_ENTRY(CLASS_ID, id), method: 'DELETE', success: () => loadClassSchedule() });
        });
    });

    // Event Listener Task Buttons
    $('#create-task-btn').on('click', function () {
        // CLASS_ID adalah variabel global di file ini
        window.location.href = `/classes/${CLASS_ID}/tasks/create`;
    });
    $('#tasks-container').on('click', '.btn-delete-task', function () {
        confirmDeleteTask($(this).data('id'), $(this).data('title'));
    });


    // ========================================================================
    // 7. LOGIC: SISWA (DATATABLE) - (PERMISSIONS OK)
    // ========================================================================
    function loadClassStudents() {
        // 1. Tampilkan indikator loading di dalam tabel agar user tahu proses sedang berjalan
        if (studentTable) {
            // Kosongkan tabel terlebih dahulu
            studentTable.clear().draw();
            // Masukkan HTML loading manual ke body tabel
            $('#students-table tbody').html(`
                <tr>
                    <td colspan="5" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin me-2 text-primary"></i>
                        Sedang memuat data siswa...
                    </td>
                </tr>
            `);
        }

        // 2. Lakukan Request ke API menggunakan $.ajax (lebih lengkap daripada $.get)
        $.ajax({
            url: API.GET_CLASS_STUDENTS(CLASS_ID),
            method: 'GET',
            dataType: 'json',

            // A. Jika Request BERHASIL
            success: function (response) {
                console.log('✅ Data siswa berhasil diterima:', response);

                // Validasi apakah datanya benar-benar array
                if (response.success && Array.isArray(response.data)) {
                    // Jika data ada, masukkan ke tabel
                    populateTableData(response.data);
                } else {
                    // Jika format response aneh atau data kosong
                    console.warn('⚠️ Data siswa kosong atau format tidak valid.');
                    populateTableData([]);
                }
            },

            // B. Jika Request GAGAL (Error Server / Jaringan)
            error: function (xhr, status, error) {
                console.error('❌ Gagal memuat data siswa:', error);

                // Ambil pesan error dari server jika ada
                let errorMessage = 'Gagal memuat data. Silakan coba lagi.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                // Tampilkan pesan error di dalam tabel
                $('#students-table tbody').html(`
                    <tr>
                        <td colspan="5" class="text-center text-danger py-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${errorMessage}
                        </td>
                    </tr>
                `);

                // Reset jumlah siswa jadi 0
                $('#student-count').text('0');
            }
        });
    }

    function setupDataTable() {
        studentTable = $('#students-table').DataTable({
            data: [],
            columns: [
                { data: null, render: (d, t, r, m) => m.row + 1 },
                { data: "identity_number" },
                { data: "name" },
                { data: "gender" },
                {
                    data: "id", render: (data, type, row) => {
                        // Permission Seeder: kick_student
                        if (can('kick_student')) {
                            return `<button class="btn btn-sm btn-danger btn-delete-student" data-id="${data}" data-name="${row.name}"><i class="fas fa-trash"></i></button>`;
                        }
                        return 'No Action';
                    }
                }
            ]
        });
    }

    function populateTableData(data) {
        studentTable.clear().rows.add(data).draw();
        $('#student-count').text(data.length);
    }

    function setupStudentSearch() {
        $('#student_user_id').select2({ theme: "bootstrap-5", dropdownParent: $('#studentModal'), placeholder: 'Cari siswa...', multiple: true, ajax: { url: API.SEARCH_USERS_FOR_ASSIGNMENT(CLASS_ID), dataType: 'json', delay: 250, data: (p) => ({ term: p.term }), processResults: (r) => ({ results: r.data.map(u => ({ id: u.id, text: `${u.name} (${u.identity_number})` })) }) } });
    }

    $('#studentModalForm').on('submit', function (e) {
        e.preventDefault();
        const userIds = $('#student_user_id').val();
        if (!userIds.length) return Swal.fire('Error', 'Pilih siswa', 'error');
        $.post(API.ASSIGN_STUDENT_TO_CLASS(CLASS_ID), { user_ids: userIds })
            .done(res => { studentModal.hide(); Swal.fire('Sukses', res.message, 'success'); loadClassStudents(); });
    });

    $('#students-table tbody').on('click', '.btn-delete-student', function () {
        const uid = $(this).data('id');
        Swal.fire({ title: 'Keluarkan Siswa?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya' }).then(r => {
            if (r.isConfirmed) $.ajax({ url: API.REMOVE_STUDENT_FROM_CLASS(CLASS_ID, uid), method: 'DELETE', success: () => loadClassStudents() });
        });
    });

    // ========================================================================
    // 8. EVENT LISTENERS TAB & GLOBAL
    // ========================================================================
    function attachEventListeners() {
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            let targetTab = $(e.target).attr('id');
            if (targetTab === 'schedule-tab-btn') loadClassSchedule();
            else if (targetTab === 'materials-tab-btn') loadLearningMaterials();
            else if (targetTab === 'assignments-tab-btn') loadClassTasks();
        });

        $('#add-schedule-btn').on('click', () => { $('#scheduleModalForm')[0].reset(); scheduleModal.show(); });
        $('#add-material-btn').on('click', () => { resetMaterialModal(); materialModal.show(); });
        $('#add-student-btn').on('click', () => { $('#studentModalForm')[0].reset(); $('#student_user_id').val(null).trigger('change'); studentModal.show(); });
    }

    initializeApp();
});
