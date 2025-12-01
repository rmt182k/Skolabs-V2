{{--
    File: resources/views/dashboard/student/index.blade.php
    Description: Dashboard Siswa (Single Class View)
--}}

<div class="row">
    <div class="col-12">

        {{-- ========================================================== --}}
        {{-- SECTION 1: STATS CARDS (DIPERTAHANKAN) --}}
        {{-- ========================================================== --}}
        <div class="row mb-4">
            {{-- Card: Kehadiran (Contoh) --}}
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Kehadiran</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">95%</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-clock fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card: Tugas Pending --}}
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Tugas Belum Dikerjakan</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-pending-tasks">0</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card: Nilai Rata-rata --}}
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Rata-Rata Nilai</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">-</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================== --}}
        {{-- SECTION 2: CLASS CONTENT (LAYOUT KAMU) --}}
        {{-- ========================================================== --}}

        {{-- Loading State Awal --}}
        <div id="dashboard-loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Memuat data kelas kamu...</p>
        </div>

        {{-- Konten Kelas (Hidden by default, shown by JS) --}}
        <div id="class-content-wrapper" style="display: none;">

            {{-- Kartu Informasi Kelas --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-school me-2 text-primary"></i>
                        Kelas: <span class="fw-bold text-dark" id="class-name">Loading...</span>
                    </h5>
                    <div class="mt-2 text-muted small">
                        <span class="me-3"><i class="fas fa-layer-group me-1"></i> Jurusan: <strong
                                id="class-major">-</strong></span>
                        <span class="me-3"><i class="fas fa-calendar-alt me-1"></i> TA: <strong
                                id="class-year">-</strong></span>
                        {{-- <span class="me-3"><i class="fas fa-user-tie me-1"></i> Wali Kelas: <strong id="class-teacher">-</strong></span> --}}
                    </div>
                </div>
            </div>

            {{-- Konten Utama dengan Tabs --}}
            <div class="card shadow-sm">
                {{-- Navigasi Tab --}}
                <div class="card-header card-header-tab">
                    <ul class="nav nav-tabs card-header-tabs" id="manageClassTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="schedule-tab-btn" data-bs-toggle="tab"
                                data-bs-target="#schedule" type="button" role="tab" aria-controls="schedule"
                                aria-selected="true">
                                <i class="fas fa-calendar-alt me-1"></i> Jadwal Pelajaran
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="materials-tab-btn" data-bs-toggle="tab"
                                data-bs-target="#materials" type="button" role="tab" aria-controls="materials"
                                aria-selected="false">
                                <i class="fas fa-book me-1"></i> Bahan Ajar
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="assignments-tab-btn" data-bs-toggle="tab"
                                data-bs-target="#assignments" type="button" role="tab" aria-controls="assignments"
                                aria-selected="false">
                                <i class="fas fa-tasks me-1"></i> Tugas & Ujian
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="students-tab-btn" data-bs-toggle="tab"
                                data-bs-target="#students" type="button" role="tab" aria-controls="students"
                                aria-selected="false">
                                <i class="fas fa-users me-1"></i> Teman Sekelas
                            </button>
                        </li>
                    </ul>
                </div>

                {{-- Isi Tab --}}
                <div class="card-body p-4">
                    <div class="tab-content" id="manageClassTabContent">

                        {{-- TAB 1: JADWAL --}}
                        <div class="tab-pane fade show active" id="schedule" role="tabpanel">
                            <h6 class="mb-3 text-dark font-weight-bold">Jadwal Pelajaran Mingguan</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="schedule-table">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Hari</th>
                                            <th>Jam</th>
                                            <th>Mata Pelajaran</th>
                                            <th>Guru</th>
                                        </tr>
                                    </thead>
                                    <tbody id="schedule-body">
                                        {{-- JS Load Here --}}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- TAB 2: BAHAN AJAR --}}
                        <div class="tab-pane fade" id="materials" role="tabpanel">
                            <h6 class="mb-3 text-dark font-weight-bold">Materi Pembelajaran</h6>
                            <div id="materials-container" class="row">
                                {{-- JS Load Here --}}
                            </div>
                        </div>

                        {{-- TAB 3: TUGAS --}}
                        <div class="tab-pane fade" id="assignments" role="tabpanel">
                            <h6 class="mb-3 text-dark font-weight-bold">Daftar Tugas & Ujian</h6>
                            <div id="tasks-container" class="row">
                                {{-- JS Load Here --}}
                            </div>
                        </div>

                        {{-- TAB 4: TEMAN SEKELAS --}}
                        <div class="tab-pane fade" id="students" role="tabpanel">
                            <h6 class="mb-3 text-dark font-weight-bold">Daftar Siswa</h6>
                            <div class="table-responsive">
                                <table class="table table-hover" width="100%">
                                    <thead>
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th>Nama</th>
                                            <th>NISN</th>
                                        </tr>
                                    </thead>
                                    <tbody id="students-list-body">
                                        {{-- JS Load Here --}}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        {{-- State jika siswa tidak punya kelas --}}
        <div id="no-class-state" class="text-center py-5" style="display: none;">
            <div class="mb-3"><i class="fas fa-exclamation-circle fa-3x text-gray-300"></i></div>
            <h5 class="text-gray-600">Tidak ada kelas</h5>
            <p class="text-muted">Kamu belum terdaftar di kelas manapun.</p>
        </div>

    </div>
</div>

@push('styles')
    <style>
        .card-header-tab {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }

        .card-header-tabs .nav-link {
            color: #858796;
            border: none;
            border-bottom: 2px solid transparent;
            font-weight: 500;
            padding: 1rem 1.5rem;
        }

        .card-header-tabs .nav-link:hover {
            color: #4e73df;
            border-color: transparent;
        }

        .card-header-tabs .nav-link.active {
            color: #4e73df;
            background-color: transparent;
            border-bottom: 2px solid #4e73df;
            font-weight: 700;
        }

        .material-icon {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #eaecf4;
            border-radius: 10px;
            font-size: 24px;
            color: #4e73df;
        }

        .task-card {
            border-left: 4px solid #4e73df;
        }

        .task-card.quiz {
            border-left-color: #f6c23e;
        }

        .task-card.exam {
            border-left-color: #e74a3b;
        }
    </style>
@endpush

@push('scripts')
    {{-- Note: Kita pakai script inline khusus Dashboard ini agar ringan & langsung jalan --}}
    <script>
        $(document).ready(function() {
            // Setup CSRF Token
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            let myClassId = null;

            // ==========================================================
            // 1. FUNGSI UTAMA: LOAD DASHBOARD & CLASS INFO
            // ==========================================================
            function initDashboard() {
                $('#dashboard-loading').show();
                $('#class-content-wrapper').hide();

                $.get('/api/classes')
                    .done(function(response) {
                        // API kamu return object { success: true, data: [...] }
                        const classes = response.data;

                        if (classes && classes.length > 0) {
                            const cls = classes[0]; // Ambil kelas pertama
                            myClassId = cls.id;

                            // Render Info Kelas (Sesuai JSON API kamu)
                            let classNameFull = cls.name + ' - ' + cls.educational_level_name;
                            if (cls.major_name) {
                                classNameFull += ' - ' + cls.major_name;
                            }
                            // if (cls.suffix) classNameFull += ' ' + cls.suffix;

                            $('#class-name').text(classNameFull);
                            $('#class-major').text(cls.major_name ? cls.major_name : 'N.A');
                            $('#class-year').text(cls.academic_year_name ? cls.academic_year_name : '-');

                            // Tampilkan Konten
                            $('#dashboard-loading').hide();
                            $('#class-content-wrapper').fadeIn();

                            // Load Data Tab Lainnya
                            loadSchedule(myClassId);
                            loadMaterials(myClassId);
                            loadTasks(myClassId);
                            loadClassmates(myClassId);
                        } else {
                            $('#dashboard-loading').hide();
                            $('#no-class-state').fadeIn();
                        }
                    })
                    .fail(function(xhr) {
                        console.error("Error loading classes:", xhr);
                        $('#dashboard-loading').hide();
                        $('#no-class-state').html(
                            '<div class="text-danger mt-3">Gagal memuat data kelas.</div>').show();
                    });
            }

            // ==========================================================
            // 2. LOAD JADWAL (Sesuai Struktur API Baru)
            // ==========================================================
            function loadSchedule(classId) {
                const $tbody = $('#schedule-body');
                $tbody.html('<tr><td colspan="4" class="text-center">Memuat jadwal...</td></tr>');

                $.get(`/api/classes/${classId}/schedule`)
                    .done(function(response) {
                        const schedules = response.data; // Akses properti .data
                        $tbody.empty();

                        if (!schedules || schedules.length === 0) {
                            $tbody.html(
                                '<tr><td colspan="4" class="text-center text-muted">Belum ada jadwal.</td></tr>'
                            );
                            return;
                        }

                        schedules.forEach(item => {
                            // JSON kamu pakai: start_time_formatted, subject_name, teacher_name
                            const timeRange =
                                `${item.start_time_formatted} - ${item.end_time_formatted}`;

                            $tbody.append(`
                        <tr>
                            <td class="font-weight-bold">${item.day_name}</td>
                            <td><span class="badge bg-light text-dark border">${timeRange}</span></td>
                            <td>${item.subject_name || '-'}</td>
                            <td><small>${item.teacher_name || '-'}</small></td>
                        </tr>
                    `);
                        });
                    })
                    .fail(function() {
                        $tbody.html(
                            '<tr><td colspan="4" class="text-center text-danger">Gagal mengambil jadwal.</td></tr>'
                        );
                    });
            }

            // ==========================================================
            // 3. LOAD MATERI (Defensive Code untuk array kosong)
            // ==========================================================
            function loadMaterials(classId) {
                const $container = $('#materials-container');
                $container.html('<div class="col-12 text-center">Memuat materi...</div>');

                $.get(`/api/classes/${classId}/materials`)
                    .done(function(response) {
                        const materials = response.data;
                        $container.empty();

                        if (!materials || materials.length === 0) {
                            $container.html(
                                '<div class="col-12 text-center text-muted py-3">Belum ada materi dibagikan.</div>'
                            );
                            return;
                        }

                        materials.forEach(mat => {
                            // Logic Icon
                            let icon = 'fa-file-alt';
                            // Cek jika field file_type ada (antisipasi jika API materi strukturnya lain)
                            if (mat.file_type && mat.file_type.includes('pdf')) icon = 'fa-file-pdf';

                            // Logic Button
                            let actionBtn = '';
                            if (mat.file_path) {
                                actionBtn =
                                    `<a href="/storage/${mat.file_path}" target="_blank" class="btn btn-sm btn-outline-primary">Download</a>`;
                            } else if (mat.link_url) {
                                actionBtn =
                                    `<a href="${mat.link_url}" target="_blank" class="btn btn-sm btn-outline-info">Buka Link</a>`;
                            }

                            // Gunakan field subject_name jika ada, atau fallback
                            const subjectDisplay = mat.subject_name ? mat.subject_name : (mat.subject ?
                                mat.subject.name : 'Umum');

                            $container.append(`
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-start border-4 border-info shadow-sm py-2">
                                <div class="card-body d-flex align-items-start">
                                    <div class="material-icon me-3 p-2 bg-light rounded text-primary">
                                        <i class="fas ${icon} fa-2x"></i>
                                    </div>
                                    <div class="w-100">
                                        <h6 class="font-weight-bold mb-1">${mat.title}</h6>
                                        <p class="small text-muted mb-2">${subjectDisplay}</p>
                                        ${mat.description ? `<p class="small mb-2 text-secondary">${mat.description}</p>` : ''}
                                        ${actionBtn}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `);
                        });
                    });
            }

            function getPermissionsFromApi(targetMenuTitle) {
                let resultPermissions = [];

                $.ajax({
                    url: '/api/menu-users',
                    method: 'GET',
                    async: false,
                    success: function(response) {
                        if (response.success && Array.isArray(response.data)) {
                            const allMenus = response.data.flatMap(item => {
                                return [item, ...(item.children || [])];
                            });
                            const foundMenu = allMenus.find(menu => menu.title === targetMenuTitle);
                            if (foundMenu && foundMenu.permissions) {
                                resultPermissions = foundMenu.permissions;
                            }
                        }
                    },
                    error: function(xhr) {
                        console.error("Gagal mengambil permission untuk:", targetMenuTitle);
                    }
                });

                return resultPermissions;
            }

            function can(permissionName) {
                return permissions.includes(permissionName);
            }

            // ==========================================================
            // 4. LOAD TUGAS (Placeholder Logic)
            // ==========================================================
            function loadTasks(classId) {
                const $container = $('#tasks-container');
                const permissions = getPermissionsFromApi('Class');

                // Gunakan endpoint khusus student view
                $.get(`/api/classes/${classId}/tasks/`)
                    .done(function(response) {
                        const tasks = response.data || response; // Handle wrapper
                        $container.empty();

                        if (!tasks || tasks.length === 0) {
                            $container.html(
                                '<div class="col-12 text-center text-muted py-3">Tidak ada tugas aktif saat ini.</div>'
                            );
                            $('#stat-pending-tasks').text('0');
                            return;
                        }

                        // Update Stats Pending (yang belum submit)
                        let pendingCount = tasks.filter(t => t.submission_status !== 'submitted').length;
                        $('#stat-pending-tasks').text(pendingCount);

                        // --- LOOPING TUGAS DIMULAI ---
                        tasks.forEach(task => {

                            // 1. Tentukan Warna Badge
                            let badgeColor = 'primary';
                            if (task.type === 'quiz') badgeColor = 'warning';
                            if (task.type === 'exam') badgeColor = 'danger';

                            // 2. Sesuaikan Nama Mapel
                            const subjectName = task.subject_name ? task.subject_name : (task.subject ?
                                task.subject.name : 'General');

                            // 3. LOGIKA TOMBOL (HARUS DI DALAM LOOP)
                            // Agar task.id terbaca sesuai tugas yang sedang di-loop
                            let buttonsHtml = '';

                            // Cek permission 'answer' (sesuai seeder kita)
                            if (can('answer')) {
                                // Tombol aktif mengarah ke halaman pengerjaan
                                buttonsHtml = `
                                <a href="/classes/${classId}/tasks/${task.id}/answer" class="btn btn-sm btn-primary">
                                    <i class="fas fa-pen-square me-1"></i> Kerjakan
                                </a>`;
                            } else {
                                // Jika tidak punya izin, tampilkan tombol terkunci atau kosong
                                buttonsHtml = `
                                <button class="btn btn-sm btn-secondary" disabled>
                                    <i class="fas fa-lock"></i>
                                </button>`;
                            }

                            // 4. Render HTML
                            $container.append(`
                            <div class="col-md-6 mb-3">
                                <div class="card task-card shadow-sm h-100 border-start border-4 border-${badgeColor}">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="font-weight-bold text-dark mb-0">${task.title}</h6>
                                            <span class="badge bg-${badgeColor}">${task.type ? task.type.toUpperCase() : 'TASK'}</span>
                                        </div>
                                        <p class="small text-muted mb-2">
                                            <i class="fas fa-book me-1"></i> ${subjectName}
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <span class="small text-danger">Deadline: ${task.end_time ? task.end_time.substring(0,10) : '-'}</span>
                                            ${buttonsHtml}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `);
                        });
                    });
            }

            // ==========================================================
            // 5. LOAD TEMAN SEKELAS
            // ==========================================================
            function loadClassmates(classId) {
                $.get(`/api/classes/${classId}/students`)
                    .done(function(response) {
                        const students = response.data || response;
                        const $tbody = $('#students-list-body');
                        $tbody.empty();

                        if (!students || students.length === 0) return;

                        students.forEach((s, index) => {
                            // Akses user details dengan aman
                            let nisn = '-';
                            if (s.details && s.details.identity_number) nisn = s.details
                                .identity_number;

                            $tbody.append(`
                        <tr>
                            <td>${index + 1}</td>
                            <td>${s.name}</td>
                            <td>${nisn}</td>
                        </tr>
                    `);
                        });
                    });
            }

            // Jalankan Fungsi Utama
            initDashboard();
        });
    </script>
@endpush
