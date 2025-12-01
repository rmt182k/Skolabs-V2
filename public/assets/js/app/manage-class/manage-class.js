$(document).ready(function () {
    // ========================================================================
    // KONFIGURASI & VARIABEL GLOBAL
    // ========================================================================
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    function getClassIdFromUrl() {
        // 1. Ambil ID dari URL sebagai default
        // .filter(Boolean) berguna membuang string kosong jika URL berakhiran '/' (misal: /classes/12/)
        const pathParts = window.location.pathname.split('/').filter(Boolean);
        let urlId = pathParts.pop();

        // 2. Cek apakah global variable userData sudah ada
        // Jika belum ada (null), kembalikan urlId saja agar tidak error
        if (!window.userData) {
            console.warn("userData belum siap, menggunakan ID dari URL.");
            return urlId;
        }

        // 3. Akses data menggunakan struktur BARU
        // userData.role adalah object { id, name }
        // userData.class adalah object { id, name } atau null
        const roleName = window.userData.role ? window.userData.role.name : '';
        const studentClassId = window.userData.class ? window.userData.class.id : null;

        // 4. Logika Override
        if (roleName === 'student') {
            return studentClassId;
        }
        return urlId;
    }

    const CLASS_ID = getClassIdFromUrl();

    const STUDENT_ROLE_ID = 3;
    const TEACHER_ROLE_ID = 2;

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

        // Data untuk Modal Jadwal
        GET_ASSIGNED_SUBJECTS: () => `/api/subjects/assigned/search`,
        GET_TEACHERS_FOR_SUBJECT: (subjectId) => `/api/subjects/${subjectId}/teachers/search`,

        // API Bahan Ajar
        GET_CLASS_MATERIALS: (classId) => `/api/classes/${classId}/materials`,
        STORE_MATERIAL: (classId) => `/api/classes/${classId}/materials/store`,
        DESTROY_MATERIAL: (classId, materialId) => `/api/classes/${classId}/materials/${materialId}/destroy`,

        // API Tugas & Ujian
        GET_CLASS_TASKS: (classId) => `/api/classes/${classId}/tasks`,
        DESTROY_TASK: (classId, taskId) => `/api/classes/${classId}/tasks/${taskId}/destroy`
    };

    let studentModal;
    let scheduleModal;
    let materialModal;
    let studentTable;
    let fullScheduleData = [];
    let fullMaterialsData = [];

    let hasAssignedSubjects = false;
    const subjectsAdminUrl = '/subjects';


    function checkAssignedSubjects() {
        return $.get(API.GET_ASSIGNED_SUBJECTS(), { term: '', page: 1 })
            .done(response => {
                // API select2 mengembalikan { data: [...] }
                if (response.data && Array.isArray(response.data) && response.data.length > 0) {
                    hasAssignedSubjects = true;
                } else {
                    hasAssignedSubjects = false;
                }
            })
            .fail(error => {
                console.error('❌ Error checking assigned subjects:', error);
                hasAssignedSubjects = false;
            });
    }

    // ========================================================================
    // INISIALISASI APLIKASI
    // ========================================================================
    function initializeApp() {
        if (!CLASS_ID || isNaN(CLASS_ID)) {
            console.error('❌ FATAL: CLASS_ID tidak ditemukan.');
            Swal.fire('Error Kritis', 'ID Kelas tidak ditemukan dari URL.', 'error');
            return;
        }

        $.get(API.GET_CLASS_SCHEDULE(CLASS_ID))
            .done(response => {
                if (response.success && Array.isArray(response.data)) {
                    fullScheduleData = response.data;
                }
            })
            .fail(error => {
                console.error('❌ Error pre-loading schedule:', error);
            })
            .always(() => {
                studentModal = new bootstrap.Modal($('#studentModal')[0]);
                scheduleModal = new bootstrap.Modal($('#scheduleModal')[0]);
                materialModal = new bootstrap.Modal($('#materialModal')[0]);

                // Init Tab Siswa
                setupDataTable();
                loadClassStudents();
                setupStudentSearch();

                // Init Tab Jadwal
                setupScheduleModalSelect2();

                // Init Tab Bahan Ajar
                setupMaterialModalSelects();

                attachEventListeners();

                // Muat data untuk tab yang aktif saat halaman dibuka
                const activeTab = $('button[data-bs-toggle="tab"].active').attr('id');

                if (activeTab === 'schedule-tab-btn') {
                    renderSchedule(fullScheduleData);
                    $('#schedule-loading').hide();
                } else if (activeTab === 'materials-tab-btn') {
                    loadLearningMaterials();
                } else if (activeTab === 'assignments-tab-btn') {
                    loadClassTasks();
                }

                console.log('✅ Aplikasi Kelola Kelas (Siswa, Jadwal, Bahan Ajar) berhasil diinisialisasi untuk Kelas ID:', CLASS_ID);
            });
        checkAssignedSubjects();
    }

    // ========================================================================
    // BAGIAN: TUGAS & UJIAN (BARU)
    // ========================================================================

    function loadClassTasks() {
        $('#tasks-loading').show();
        $('#tasks-container').empty();
        $('#tasks-error').hide();

        $.get(API.GET_CLASS_TASKS(CLASS_ID))
            .done(response => {
                if (response.success && Array.isArray(response.data)) {
                    renderClassTasks(response.data);
                } else {
                    renderClassTasks([]);
                }
            })
            .fail(error => {
                console.error('❌ Error fetch tugas:', error);
                const errorMsg = error.responseJSON ? error.responseJSON.message : 'Gagal memuat data tugas';
                $('#tasks-error').text(errorMsg).show();
                renderClassTasks([]);
            })
            .always(() => {
                $('#tasks-loading').hide();
            });
    }

    function getPermissionsFromApi() {
        let resultPermissions = [];

        $.ajax({
            url: `/api/permissions/${window.userData.id}`,
            method: 'GET',
            async: false,
            dataType: 'json',
            success: function (response) {
                if (response.success && Array.isArray(response.data)) {
                    resultPermissions = response.data;
                }
            },
            error: function (xhr) {
                console.error("Gagal mengambil permission user:", xhr.responseText);
            }
        });

        return resultPermissions;
    }



    function renderClassTasks(data) {
        const container = $('#tasks-container');

        // 1. Ambil data mentah (Array of Objects) dari API
        // Contoh isi: [{id: 1, name: 'task.create'}, {id: 2, name: 'task.edit'}]
        const rawPermissions = getPermissionsFromApi();

        // 2. Mapping: Kita ubah menjadi Array of Strings agar mudah dicek
        // Hasilnya jadi: ['task.create', 'task.edit']
        const myPermissionNames = rawPermissions.map(function (permissionItem) {
            return permissionItem.name;
        });

        function can(permissionName) {
            // Cek apakah array myPermissionNames memiliki string permissionName
            if (myPermissionNames.includes(permissionName)) {
                return true;
            } else {
                return false;
            }
        }

        container.empty();

        if (data.length === 0) {
            container.html('<div class="col-12"><div class="tasks-empty"><i class="fas fa-file-signature me-2"></i>Belum ada tugas atau ujian</div></div>');
            return;
        }

        // Sort tugas berdasarkan waktu berakhir (terbaru di atas)
        data.sort((a, b) => new Date(b.end_time) - new Date(a.end_time));

        data.forEach(task => {
            const { status, badgeClass } = getTaskStatus(task.start_time, task.end_time);

            const now = new Date();
            const endTime = new Date(task.end_time);
            const isExpired = now > endTime;

            let iconClass = 'fa-file-alt';
            if (task.type === 'quiz') iconClass = 'fa-question-circle';
            if (task.type === 'exam') iconClass = 'fa-graduation-cap';

            const editUrl = `/classes/${CLASS_ID}/tasks/${task.id}/edit`;
            const submissionsUrl = `/classes/${CLASS_ID}/tasks/${task.id}/submissions`;
            const answerUrl = `/classes/${CLASS_ID}/tasks/${task.id}/answer`;

            // ============================================================
            // MULAI LOGIKA PEMBUATAN TOMBOL DINAMIS
            // ============================================================
            let buttonsHtml = '';

            // 1. Permission: answer (Untuk Siswa)
            if (can('answer')) {
                // Tombol ditutup jika status teks 'Ditutup' ATAU waktu sekarang sudah melewati end_time
                const isClosed = status === 'Ditutup' || isExpired;

                const btnClass = isClosed ? 'btn-secondary disabled' : 'btn-success';
                const btnText = isClosed ? 'Ditutup' : 'Jawab Soal';

                // Jika ditutup, kita ubah link jadi javascript:void(0) agar tidak bisa diklik (double protection)
                const finalUrl = isClosed ? 'javascript:void(0)' : answerUrl;

                buttonsHtml += `
            <a href="${finalUrl}" class="btn btn-sm ${btnClass} btn-answer-task me-1" ${isClosed ? 'aria-disabled="true"' : ''}>
                <i class="fas fa-pen-square me-1"></i> ${btnText}
            </a>
        `;
            }

            // 2. Permission: grade (Untuk Guru melihat nilai)
            if (can('grade')) {
                buttonsHtml += `
                    <a href="${submissionsUrl}" class="btn btn-sm btn-outline-secondary me-1" title="Lihat Hasil Siswa">
                        <i class="fas fa-clipboard-check me-1"></i> Hasil
                    </a>
                `;
            }

            // 3. Permission: edit (Untuk Guru/Admin edit soal)
            if (can('edit')) {
                buttonsHtml += `
                <a href="${editUrl}" class="btn btn-sm btn-outline-primary me-1" title="Edit Tugas">
                    <i class="fas fa-pencil-alt"></i> Edit
                </a>
            `;
            }

            // 4. Permission: delete (Untuk Guru/Admin hapus soal)
            if (can('delete')) {
                buttonsHtml += `
                <button class="btn btn-sm btn-outline-danger btn-delete-task" data-id="${task.id}" data-title="${task.title}" title="Hapus Tugas">
                    <i class="fas fa-trash"></i> Delete
                </button>
            `;
            }
            // ============================================================

            const taskHtml = `
            <div class="col-xl-6">
                <div class="task-card" data-id="${task.id}">
                    <div class="task-header">
                        <div class="task-icon type-${task.type}">
                            <i class="fas ${iconClass}"></i>
                        </div>
                        <div>
                            <h6 class="task-title">${task.title}</h6>
                            <p class="task-subject">${task.subject_name || '<i>Mapel Dihapus</i>'}</p>
                        </div>
                    </div>
                    <div class="task-body">
                        ${task.description ? `<p class="task-description">${task.description.substring(0, 150)}...</p>` : '<p class="task-description fst-italic text-muted">Tidak ada deskripsi.</p>'}

                        <div class="task-meta">
                            <div><i class="fas fa-tag me-2 text-muted"></i> <strong>${task.type.charAt(0).toUpperCase() + task.type.slice(1)}</strong></div>
                            <div><i class="fas fa-info-circle me-2 text-muted"></i> <span class="badge ${badgeClass}">${status}</span></div>
                            <div><i class="fas fa-hourglass-start me-2 text-muted"></i> ${new Date(task.start_time).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })}</div>
                            <div><i class="fas fa-hourglass-end me-2 text-muted"></i> ${new Date(task.end_time).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })}</div>
                        </div>
                    </div>
                    <div class="task-footer">
                        ${buttonsHtml}
                    </div>
                </div>
            </div>
        `;
            container.append(taskHtml);
        });
    }

    function getTaskStatus(start, end) {
        const now = new Date();
        const startTime = new Date(start);
        const endTime = new Date(end);

        if (now < startTime) {
            return { status: 'Akan Datang', badgeClass: 'bg-info text-dark' };
        } else if (now >= startTime && now <= endTime) {
            return { status: 'Sedang Berlangsung', badgeClass: 'bg-success' };
        } else {
            return { status: 'Ditutup', badgeClass: 'bg-danger' };
        }
    }

    function confirmDeleteTask(taskId, title) {
        Swal.fire({
            title: 'Anda yakin?',
            html: `Anda akan menghapus <strong>${title}</strong> secara permanen. <br><br><strong class="text-danger">PERINGATAN:</strong> Ini akan menghapus semua pertanyaan, opsi, dan SEMUA HASIL PENGUMPULAN siswa terkait tugas ini!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus Semuanya!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: API.DESTROY_TASK(CLASS_ID, taskId),
                    method: 'DELETE',
                    success: function (response) {
                        Swal.fire({
                            icon: 'success', title: 'Berhasil Dihapus!',
                            text: response.message,
                            timer: 2000, showConfirmButton: false
                        });
                        loadClassTasks(); // Muat ulang daftar
                    },
                    error: (xhr) => Swal.fire('Error', xhr.responseJSON.message || 'Gagal menghapus tugas', 'error')
                });
            }
        });
    }

    // ========================================================================
    // BAGIAN: BAHAN AJAR
    // ========================================================================

    function loadLearningMaterials() {
        $('#materials-loading').show();
        $('#materials-container').empty();
        $('#materials-error').hide();

        $.get(API.GET_CLASS_MATERIALS(CLASS_ID))
            .done(response => {
                if (response.success && Array.isArray(response.data)) {
                    fullMaterialsData = response.data;
                    renderLearningMaterials(response.data);
                } else {
                    renderLearningMaterials([]);
                }
            })
            .fail(error => {
                console.error('❌ Error fetch bahan ajar:', error);
                const errorMsg = error.responseJSON ? error.responseJSON.message : 'Gagal memuat data bahan ajar';
                $('#materials-error').text(errorMsg).show();
                renderLearningMaterials([]);
            })
            .always(() => {
                $('#materials-loading').hide();
            });
    }

    function renderLearningMaterials(data) {
        const container = $('#materials-container');
        container.empty();

        if (data.length === 0) {
            container.html('<div class="col-12"><div class="materials-empty"><i class="fas fa-box-open me-2"></i>Belum ada bahan ajar</div></div>');
            return;
        }

        data.forEach(material => {
            const materialHtml = `
                <div class="col-lg-6">
                    <div class="material-card" data-id="${material.id}">
                        <div class="material-header">
                            <div>
                                <h6 class="material-title">${material.title}</h6>
                                <p class="material-subject">${material.subject_name || '<i>Mapel Dihapus</i>'}</p>
                            </div>
                            <div class="material-actions btn-group">
                                <button class="btn btn-sm btn-outline-primary btn-edit-material" data-id="${material.id}" title="Edit Materi">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger btn-delete-material" data-id="${material.id}" data-title="${material.title}" title="Hapus Materi">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="material-body">
                            ${material.description ? `<p class="material-description">${material.description.replace(/\n/g, '<br>')}</p>` : ''}
                            ${renderMaterialAttachments(material)}
                        </div>
                    </div>
                </div>
            `;
            container.append(materialHtml);
        });
    }

    function renderMaterialAttachments(material) {
        let html = '';

        if (material.file_path) {
            const fileUrl = `/storage/${material.file_path}`;
            const iconClass = getFileIconClass(material.file_type);
            html += `
                <a href="${fileUrl}" target="_blank" class="material-attachment text-decoration-none mb-2">
                    <div class="material-icon"><i class="fas ${iconClass}"></i></div>
                    <div class="material-file-details">
                        <div class="material-file-name">${material.file_name || 'Download File'}</div>
                        <div class="material-file-meta">
                            ${material.file_type || 'File'}
                            ${material.file_size ? `(${formatBytes(material.file_size)})` : ''}
                        </div>
                    </div>
                </a>
            `;
        }

        if (material.link_url) {
            let domain = 'Link Eksternal';
            try { domain = new URL(material.link_url).hostname; } catch (e) { }

            html += `
                <a href="${material.link_url}" target="_blank" rel="noopener noreferrer" class="material-attachment text-decoration-none">
                    <div class="material-icon"><i class="fas fa-link text-primary"></i></div>
                    <div class="material-file-details">
                        <div class="material-file-name">${material.link_url}</div>
                        <div class="material-file-meta">${domain}</div>
                    </div>
                </a>
            `;
        }

        if (html === '') {
            return '<p class="text-muted small">Tidak ada lampiran.</p>';
        }

        return html;
    }

    function getFileIconClass(mimeType) {
        if (!mimeType) return 'fa-file';
        if (mimeType.includes('pdf')) return 'fa-file-pdf text-danger';
        if (mimeType.includes('word') || mimeType.includes('doc')) return 'fa-file-word text-primary';
        if (mimeType.includes('excel') || mimeType.includes('xls')) return 'fa-file-excel text-success';
        if (mimeType.includes('presentation') || mimeType.includes('ppt')) return 'fa-file-powerpoint text-warning';
        if (mimeType.includes('image')) return 'fa-file-image text-info';
        if (mimeType.includes('zip') || mimeType.includes('archive')) return 'fa-file-archive text-muted';
        return 'fa-file';
    }

    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    function setupMaterialModalSelects() {
        const scheduledSubjects = [];
        const subjectIds = new Set();

        fullScheduleData.forEach(entry => {
            if (entry.subject_id && !subjectIds.has(entry.subject_id)) {
                subjectIds.add(entry.subject_id);
                scheduledSubjects.push({
                    id: entry.subject_id,
                    text: entry.subject_name || 'Mapel (Tanpa Nama)'
                });
            }
        });

        scheduledSubjects.sort((a, b) => a.text.localeCompare(b.text));

        $('#material_subject_id').select2({
            theme: "bootstrap-5",
            dropdownParent: $('#materialModal'),
            placeholder: 'Pilih mata pelajaran dari jadwal...',
            allowClear: true,
            data: scheduledSubjects
        });
    }

    function resetMaterialModal() {
        $('#materialModalForm')[0].reset();
        $('#material_id').val('');
        $('#materialModalLabel').text('Tambah Bahan Ajar');

        $('#material_subject_id').val(null).trigger('change');
        $('#current-file-info').text('');

        $('#link_url').val('');
        $('#file_input').val('');

        $('#remove-file-group').hide();
        $('#remove_current_file').prop('checked', false);

        $('.form-control, .form-select').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    }

    function handleMaterialFormSubmit(e) {
        e.preventDefault();
        const form = $(this)[0];
        const formData = new FormData(form);

        $('.form-control, .form-select').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        $('#materialSubmitBtn').prop('disabled', true).text('Menyimpan...');

        $.ajax({
            url: API.STORE_MATERIAL(CLASS_ID),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    materialModal.hide();
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    loadLearningMaterials();
                }
            },
            error: function (error) {
                if (error.status === 422 && error.responseJSON.errors) {
                    const errors = error.responseJSON.errors;
                    Object.keys(errors).forEach(key => {
                        $(`#${key}`).addClass('is-invalid');
                        $(`#${key}-error`).text(errors[key][0]);
                    });
                } else {
                    Swal.fire('Error', error.responseJSON.message || 'Terjadi kesalahan', 'error');
                }
            },
            complete: function () {
                $('#materialSubmitBtn').prop('disabled', false).text('Simpan Materi');
            }
        });
    }

    function handleEditMaterialClick(materialId) {
        const entry = fullMaterialsData.find(item => item.id == materialId);
        if (!entry) {
            Swal.fire('Error', 'Data materi tidak ditemukan. Muat ulang halaman.', 'error');
            return;
        }

        resetMaterialModal();

        $('#materialModalLabel').text('Edit Bahan Ajar');
        $('#material_id').val(entry.id);
        $('#title').val(entry.title);
        $('#description').val(entry.description);

        if (entry.subject_id) {
            $('#material_subject_id').val(entry.subject_id).trigger('change');
        }

        $('#link_url').val(entry.link_url);

        if (entry.file_path && entry.file_name) {
            $('#current-file-info').text(`File saat ini: ${entry.file_name}. Upload file baru untuk menggantinya.`);
            $('#remove-file-group').show();
        } else {
            $('#current-file-info').text('');
            $('#remove-file-group').hide();
        }

        materialModal.show();
    }

    function confirmDeleteMaterial(materialId, title) {
        Swal.fire({
            title: 'Anda yakin?',
            html: `Anda akan menghapus materi <strong>${title}</strong> secara permanen. File terkait juga akan dihapus.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: API.DESTROY_MATERIAL(CLASS_ID, materialId),
                    method: 'DELETE',
                    success: function (response) {
                        Swal.fire({
                            icon: 'success', title: 'Berhasil Dihapus!',
                            text: response.message,
                            timer: 2000, showConfirmButton: false
                        });
                        loadLearningMaterials();
                    },
                    error: (xhr) => Swal.fire('Error', xhr.responseJSON.message || 'Gagal menghapus materi', 'error')
                });
            }
        });
    }


    // ========================================================================
    // BAGIAN: JADWAL PELAJARAN
    // ========================================================================

    function loadClassSchedule() {
        $('#schedule-loading').show();
        $('#schedule-container').empty();
        $('#schedule-error').hide();

        $.get(API.GET_CLASS_SCHEDULE(CLASS_ID))
            .done(response => {
                if (response.success && Array.isArray(response.data)) {
                    fullScheduleData = response.data;
                    renderSchedule(response.data);
                } else {
                    renderSchedule([]);
                }
            })
            .fail(error => {
                console.error('❌ Error fetch jadwal:', error);
                const errorMsg = error.responseJSON ? error.responseJSON.message : 'Gagal memuat data jadwal';
                $('#schedule-error').text(errorMsg).show();
                renderSchedule([]);
            })
            .always(() => {
                $('#schedule-loading').hide();
            });
    }

    function renderSchedule(data) {
        const container = $('#schedule-container');
        container.empty();

        const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        const scheduleByDay = {};
        data.forEach(entry => {
            if (!scheduleByDay[entry.day_name]) {
                scheduleByDay[entry.day_name] = [];
            }
            scheduleByDay[entry.day_name].push(entry);
        });

        days.forEach(day => {
            const dayEntries = scheduleByDay[day] || [];

            const dayCardHtml = `
                <div class="col-lg-6 col-xl-4">
                    <div class="schedule-day-card">
                        <div class="schedule-day-header">${day}</div>
                        <div id="schedule-day-${day}">
                            ${renderDayEntries(dayEntries)}
                        </div>
                    </div>
                </div>
            `;
            container.append(dayCardHtml);
        });
    }

    function renderDayEntries(entries) {
        if (entries.length === 0) {
            return '<div class="schedule-empty"><i class="fas fa-moon me-2"></i>No schedule</div>';
        }

        entries.sort((a, b) => a.start_time_formatted.localeCompare(b.start_time_formatted));

        return entries.map(entry => `
            <div class="schedule-entry" data-id="${entry.id}">
                <div class="schedule-time">
                    ${entry.start_time_formatted} - ${entry.end_time_formatted}
                </div>
                <div class="schedule-details">
                    <p class="schedule-subject">${entry.subject_name || '<i>Subject Removed</i>'}</p>
                    <p class="schedule-teacher">${entry.teacher_name || '<i>Teacher Removed</i>'}</p>
                </div>
                <div class="schedule-actions btn-group">
                    <button class="btn btn-sm btn-outline-primary btn-edit-schedule" data-id="${entry.id}" title="Edit Entry">
                        <i class="fas fa-pencil-alt"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger btn-delete-schedule" data-id="${entry.id}" title="Delete Entry">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
    }

    function resetScheduleModal() {
        $('#scheduleModalForm')[0].reset();
        $('#schedule_id').val('');
        $('#scheduleModalLabel').text('Add Schedule Entry');

        $('#subject_id').val(null).trigger('change');
        $('#user_id').val(null).trigger('change');

        $('.form-control, .form-select').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    }

    function setupScheduleModalSelect2() {
        $('#subject_id').select2({
            theme: "bootstrap-5",
            dropdownParent: $('#scheduleModal'),
            placeholder: 'Cari mata pelajaran...',
            allowClear: true,
            ajax: {
                url: API.GET_ASSIGNED_SUBJECTS(),
                dataType: 'json',
                delay: 250,
                data: (params) => ({ term: params.term }),
                processResults: (response) => ({
                    results: response.data.map(subject => ({
                        id: subject.id,
                        text: subject.name
                    }))
                }),
                cache: true
            }
        });

        $('#user_id').select2({
            theme: "bootstrap-5",
            dropdownParent: $('#scheduleModal'),
            placeholder: 'Pilih mata pelajaran terlebih dahulu',
            disabled: true
        });

        $('#subject_id').on('change', function () {
            const subjectId = $(this).val();
            const teacherSelect = $('#user_id');
            teacherSelect.val(null).trigger('change');

            if (subjectId) {
                teacherSelect.prop('disabled', false);
                teacherSelect.select2('destroy');
                teacherSelect.select2({
                    theme: "bootstrap-5",
                    dropdownParent: $('#scheduleModal'),
                    placeholder: 'Cari guru untuk mapel ini...',
                    allowClear: true,
                    ajax: {
                        url: API.GET_TEACHERS_FOR_SUBJECT(subjectId),
                        dataType: 'json',
                        delay: 250,
                        data: (params) => ({ term: params.term }),
                        processResults: (response) => ({
                            results: response.data.map(user => ({
                                id: user.id,
                                text: `${user.name} (${user.identity_number || 'No ID'})`
                            }))
                        }),
                        cache: true
                    }
                });
            } else {
                teacherSelect.prop('disabled', true);
                teacherSelect.select2('destroy');
                teacherSelect.select2({
                    theme: "bootstrap-5",
                    dropdownParent: $('#scheduleModal'),
                    placeholder: 'Pilih mata pelajaran terlebih dahulu',
                    disabled: true
                });
            }
        });
    }

    function handleScheduleFormSubmit(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        $('.form-control, .form-select').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        // ⭐ 1. NONAKTIFKAN TOMBOL (BARU)
        $('#scheduleSubmitBtn').prop('disabled', true).text('Menyimpan...');

        $.post(API.STORE_SCHEDULE_ENTRY(CLASS_ID), formData)
            .done(response => {
                if (response.success) {
                    scheduleModal.hide(); // Modal ditutup HANYA jika sukses
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    loadClassSchedule();

                    // Refresh data mapel untuk modal bahan ajar
                    $.get(API.GET_CLASS_SCHEDULE(CLASS_ID)).done(r => {
                        if (r.success) fullScheduleData = r.data;
                        setupMaterialModalSelects(); // Update select2 bahan ajar
                    });
                }
            })
            .fail(error => {
                if (error.status === 422 && error.responseJSON.errors) {
                    const errors = error.responseJSON.errors;
                    Object.keys(errors).forEach(key => {
                        // Target select2
                        if (key === 'subject_id' || key === 'user_id') {
                            $(`#${key}`).next('.select2-container').addClass('is-invalid');
                        } else {
                            $(`#${key}`).addClass('is-invalid');
                        }
                        $(`#${key}-error`).text(errors[key][0]);
                    });
                } else {
                    Swal.fire('Error', error.responseJSON.message || 'Terjadi kesalahan', 'error');
                }
            })
            .always(() => {
                $('#scheduleSubmitBtn').prop('disabled', false).text('Save Entry');
            });
    }

    function handleEditScheduleClick(scheduleId) {
        const entry = fullScheduleData.find(item => item.id == scheduleId);
        if (!entry) {
            Swal.fire('Error', 'Data jadwal tidak ditemukan. Muat ulang halaman.', 'error');
            return;
        }

        resetScheduleModal();

        $('#scheduleModalLabel').text('Edit Schedule Entry');
        $('#schedule_id').val(entry.id);
        $('#day_name').val(entry.day_name);
        $('#start_time').val(entry.start_time_formatted);
        $('#end_time').val(entry.end_time_formatted);

        if (entry.subject_id && entry.subject_name) {
            const subjectOption = new Option(entry.subject_name, entry.subject_id, true, true);
            $('#subject_id').append(subjectOption).trigger('change');

            const teacherSelect = $('#user_id');
            teacherSelect.prop('disabled', false);
            teacherSelect.select2('destroy');

            teacherSelect.select2({
                theme: "bootstrap-5",
                dropdownParent: $('#scheduleModal'),
                placeholder: 'Cari guru untuk mapel ini...',
                allowClear: true,
                ajax: {
                    url: API.GET_TEACHERS_FOR_SUBJECT(entry.subject_id),
                    dataType: 'json',
                    delay: 250,
                    data: (params) => ({ term: params.term }),
                    processResults: (response) => ({
                        results: response.data.map(user => ({
                            id: user.id,
                            text: `${user.name} (${user.identity_number || 'No ID'})`
                        }))
                    }),
                    cache: true
                }
            });

            if (entry.user_id && entry.teacher_name) {
                const teacherOption = new Option(entry.teacher_name, entry.user_id, true, true);
                teacherSelect.append(teacherOption).trigger('change');
            }
        }
        scheduleModal.show();
    }


    function confirmDeleteSchedule(scheduleId) {
        Swal.fire({
            title: 'Anda yakin?',
            text: "Anda akan menghapus sesi jadwal ini secara permanen.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: API.DESTROY_SCHEDULE_ENTRY(CLASS_ID, scheduleId),
                    method: 'DELETE',
                    success: function (response) {
                        Swal.fire({
                            icon: 'success', title: 'Berhasil Dihapus!',
                            text: response.message,
                            timer: 2000, showConfirmButton: false
                        });
                        loadClassSchedule();

                        $.get(API.GET_CLASS_SCHEDULE(CLASS_ID)).done(r => {
                            if (r.success) fullScheduleData = r.data;
                            setupMaterialModalSelects();
                        });
                    },
                    error: (xhr) => Swal.fire('Error', xhr.responseJSON.message || 'Gagal menghapus sesi', 'error')
                });
            }
        });
    }

    // ========================================================================
    // BAGIAN: DAFTAR SISWA
    // ========================================================================

    function loadClassStudents() {
        if (studentTable) {
            studentTable.clear().draw();
            $('#students-table tbody').html('<tr><td colspan="5" class="text-center"><i class="fas fa-spinner fa-spin"></i> Memuat data...</td></tr>');
        }

        $.get(API.GET_CLASS_STUDENTS(CLASS_ID))
            .done(response => {
                if (response.success && Array.isArray(response.data)) {
                    populateTableData(response.data);
                } else {
                    populateTableData([]);
                }
            })
            .fail(error => {
                console.error('❌ Error fetch siswa:', error);
                const errorMsg = error.responseJSON ? error.responseJSON.message : 'Gagal memuat data siswa';
                if ($('#students-tab-btn').hasClass('active')) {
                    Swal.fire('Error', errorMsg, 'error');
                }
                $('#students-table tbody').html(`<tr><td colspan="5" class="text-center text-danger">${errorMsg}</td></tr>`);
                updateStudentCount(0);
            });
    }

    function setupDataTable() {
        if ($.fn.DataTable.isDataTable('#students-table')) {
            $('#students-table').DataTable().destroy();
        }
        studentTable = $('#students-table').DataTable({
            data: [],
            processing: false,
            serverSide: false,
            columns: [
                { "data": null, "title": "No" },
                { "data": "identity_number", "title": "NISN/Induk", "defaultContent": "<i>N/A</i>" },
                { "data": "name", "title": "Nama Siswa" },
                { "data": "gender", "title": "Jenis Kelamin", "defaultContent": "<i>N/A</i>" },
                { "data": "id", "title": "Aksi" }
            ],
            columnDefs: [
                { "searchable": false, "orderable": false, "targets": 0 },
                {
                    "targets": 4, "orderable": false, "searchable": false,
                    "className": "text-center",
                    "render": (data, type, row) => `<button class="btn btn-sm btn-danger btn-delete-student" data-id="${data}" data-name="${row.name}" title="Keluarkan Siswa"><i class="fas fa-trash"></i></button>`
                }
            ],
            language: {
                lengthMenu: "Tampilkan _MENU_ data",
                zeroRecords: "Tidak ada data siswa di kelas ini",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                infoEmpty: "Tidak ada data",
                infoFiltered: "(difilter dari _MAX_ total data)",
                search: "Cari:",
                paginate: { first: "Pertama", last: "Terakhir", next: "Selanjutnya", previous: "Sebelumnya" },
            },
            order: [[2, 'asc']],
            drawCallback: function (settings) {
                var api = this.api();
                api.column(0, { search: 'applied', order: 'applied' }).nodes().each((cell, i) => {
                    cell.innerHTML = i + 1;
                });
                updateStudentCount(api.page.info().recordsTotal);
            }
        });
    }

    function populateTableData(data) {
        if (studentTable) {
            studentTable.clear().rows.add(data).draw();
        }
    }

    function updateStudentCount(count) {
        if (typeof count === 'undefined' && studentTable) {
            count = studentTable.data().count();
        } else if (typeof count === 'undefined') {
            count = 0;
        }
        $('#student-count').text(count);
        $('#student-count-table').text(count);
    }

    function setupStudentSearch() {
        if ($.fn.select2) {
            $('#student_user_id').select2({
                theme: "bootstrap-5",
                dropdownParent: $('#studentModal'),
                placeholder: 'Ketik NISN atau Nama Siswa...',
                allowClear: true,
                multiple: true,
                ajax: {
                    url: API.SEARCH_USERS_FOR_ASSIGNMENT(CLASS_ID),
                    dataType: 'json',
                    delay: 250,
                    data: (params) => ({ term: params.term }),
                    processResults: (response) => ({
                        results: response.data.map(user => ({
                            id: user.id,
                            text: `${user.name} (${user.identity_number || 'No ID'})`
                        }))
                    }),
                    cache: true
                }
            });
        }
    }

    function resetStudentModalForm() {
        $('#studentModalForm')[0].reset();
        $('#student_user_id').val(null).trigger('change');
        $('.form-control, .form-select').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        $('#student_user_id').next('.select2-container').removeClass('is-invalid');
    }

    function handleStudentFormSubmit(e) {
        e.preventDefault();
        $('.form-control, .form-select').removeClass('is-invalid');
        $('#student_user_id').next('.select2-container').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        const userIds = $('#student_user_id').val();
        if (!userIds || userIds.length === 0) {
            $('#student_user_id').next('.select2-container').addClass('is-invalid');
            $('#user_id-error').text('Anda harus memilih minimal satu siswa.');
            return;
        }
        $.ajax({
            url: API.ASSIGN_STUDENT_TO_CLASS(CLASS_ID),
            method: 'POST',
            data: { user_ids: userIds },
            success: function (response) {
                studentModal.hide();
                Swal.fire({
                    icon: 'success', title: 'Berhasil!',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                loadClassStudents();
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(key => {
                        if (key === 'user_ids' || key.startsWith('user_ids.')) {
                            $('#student_user_id').next('.select2-container').addClass('is-invalid');
                            $('#user_id-error').text(errors[key][0]);
                        } else {
                            $(`#${key}`).addClass('is-invalid');
                            $(`#${key}-error`).text(errors[key][0]);
                        }
                    });
                } else {
                    Swal.fire('Error', xhr.responseJSON.message || 'Terjadi kesalahan', 'error');
                }
            }
        });
    }

    function confirmDeleteStudent(userId, userName) {
        Swal.fire({
            title: 'Anda yakin?',
            html: `Anda akan mengeluarkan siswa <strong>${userName}</strong> dari kelas ini.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Keluarkan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: API.REMOVE_STUDENT_FROM_CLASS(CLASS_ID, userId),
                    method: 'DELETE',
                    success: function (response) {
                        Swal.fire({
                            icon: 'success', title: 'Berhasil Dikeluarkan!',
                            text: response.message,
                            timer: 2000, showConfirmButton: false
                        });
                        loadClassStudents();
                    },
                    error: (xhr) => Swal.fire('Error', xhr.responseJSON.message || 'Gagal mengeluarkan siswa', 'error')
                });
            }
        });
    }

    // ========================================================================
    // EVENT LISTENERS (GABUNGAN)
    // ========================================================================

    function attachEventListeners() {
        // --- Siswa ---
        $('#studentModalForm').on('submit', handleStudentFormSubmit);
        $('#studentModal').on('hidden.bs.modal', resetStudentModalForm);
        $('#students-table tbody').on('click', '.btn-delete-student', function () {
            confirmDeleteStudent($(this).data('id'), $(this).data('name'));
        });
        $('#student_user_id').on('select2:open', function () {
            $(this).next('.select2-container').removeClass('is-invalid');
            $('#user_id-error').text('');
        });

        // --- Jadwal ---
        $('#scheduleModalForm').on('submit', handleScheduleFormSubmit);
        $('#scheduleModal').on('hidden.bs.modal', resetScheduleModal);

        $('#add-schedule-btn').on('click', function () {
            if (hasAssignedSubjects) {
                resetScheduleModal();
                scheduleModal.show();
            } else {
                // Jika TIDAK ADA, tampilkan alert
                Swal.fire({
                    title: 'Mata Pelajaran Belum Siap',
                    html: `Sistem tidak menemukan <strong>Mata Pelajaran</strong> yang telah di-assign ke guru. <br><br>Anda harus meng-assign guru ke mata pelajaran terlebih dahulu untuk membuat jadwal.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#0d6efd',
                    confirmButtonText: 'Assign Guru ke Mapel',
                    cancelButtonText: 'Nanti Saja',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Arahkan ke link yang diminta
                        window.location.href = subjectsAdminUrl;
                    }
                });
            }
        });

        $('#schedule-container').on('click', '.btn-edit-schedule', function () {
            handleEditScheduleClick($(this).data('id'));
        });
        $('#schedule-container').on('click', '.btn-delete-schedule', function () {
            confirmDeleteSchedule($(this).data('id'));
        });

        // --- Event Listener Bahan Ajar ---
        $('#materialModalForm').on('submit', handleMaterialFormSubmit);
        $('#materialModal').on('hidden.bs.modal', resetMaterialModal);
        $('#add-material-btn').on('click', () => resetMaterialModal());
        $('#materials-container').on('click', '.btn-edit-material', function () {
            handleEditMaterialClick($(this).data('id'));
        });
        $('#materials-container').on('click', '.btn-delete-material', function () {
            confirmDeleteMaterial($(this).data('id'), $(this).data('title'));
        });

        // Event Listener Tugas
        $('#create-task-btn').on('click', function () {
            // CLASS_ID adalah variabel global di file ini
            window.location.href = `/classes/${CLASS_ID}/tasks/create`;
        });
        $('#tasks-container').on('click', '.btn-delete-task', function () {
            confirmDeleteTask($(this).data('id'), $(this).data('title'));
        });

        // --- Navigasi Tab ---
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            let targetTab = $(e.target).attr('id');
            if (targetTab === 'schedule-tab-btn') {
                loadClassSchedule();
            } else if (targetTab === 'materials-tab-btn') {
                loadLearningMaterials();
            } else if (targetTab === 'assignments-tab-btn') {
                loadClassTasks();
            }
        });
    }

    // ========================================================================
    // START APPLICATION
    // ========================================================================
    initializeApp();

});
