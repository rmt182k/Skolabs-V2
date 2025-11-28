{{--
    File: resources/views/dashboard/teacher/index.blade.php
    Description: Dashboard utama untuk role Teacher
--}}

<div class="row">
    <div class="col-12">

        {{-- Section: Welcome & Stats Cards --}}
        <div class="row mb-4">

            {{-- Card: My Classes (Kelas yang diajar) --}}
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    My Classes</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-my-classes">
                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card: Assigned Subjects (Mapel yang diampu) --}}
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Assigned Subjects</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-my-subjects">
                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-book-reader fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card: Pending Tasks (Contoh dummy karena API specific filter task guru belum explicit) --}}
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Active Tasks/Exams</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-active-tasks">0</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-tasks fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section: Main Content - Daftar Kelas Guru --}}
        <div class="card shadow mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Kelas Saya & Jadwal</h6>
                <button class="btn btn-sm btn-primary" onclick="window.location.href='/classes'">
                    <i class="fas fa-plus me-1"></i> Buat Materi / Tugas
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered" width="100%" cellspacing="0">
                        <thead class="thead-light">
                            <tr>
                                <th>Nama Kelas</th>
                                <th>Tingkat</th>
                                <th>Jurusan</th>
                                <th>Tahun Ajaran</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="teacher-classes-body">
                            {{-- Data dimuat via AJAX --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Section: Assigned Subjects List (Optional / Info Tambahan) --}}
        <div class="row">
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-success">Mata Pelajaran yang Diampu</h6>
                    </div>
                    <div class="card-body">
                        <div class="row" id="teacher-subjects-list">
                            {{-- Subject badges loaded via AJAX --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@push('styles')
    <style>
        .border-left-primary {
            border-left: 0.25rem solid #4e73df !important;
        }

        .border-left-success {
            border-left: 0.25rem solid #1cc88a !important;
        }

        .border-left-warning {
            border-left: 0.25rem solid #f6c23e !important;
        }

        .action-btn-group .btn {
            margin-right: 5px;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function() {
            // Setup CSRF
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // API Endpoints berdasarkan route list kamu
            const API = {
                CLASSES: '/api/classes/fetchUserClasses', // Asumsi Controller memfilter kelas user jika role teacher, atau return all
                MY_SUBJECTS: '/api/subjects-assignments' // Endpoint khusus assignment mapel user login
            };

            /**
             * 1. Load Data Kelas
             * Menggunakan /api/classes
             */
            function loadTeacherClasses() {
                const $tableBody = $('#teacher-classes-body');
                $tableBody.html('<tr><td colspan="5" class="text-center">Loading classes...</td></tr>');

                $.get(API.CLASSES)
                    .done(res => {
                        const classes = res.data || res; // Handle wrapper data
                        $tableBody.empty();

                        // Update Statistik Card
                        $('#stat-my-classes').text(classes.length);

                        if (classes.length === 0) {
                            $tableBody.html(
                                '<tr><td colspan="5" class="text-center">Belum ada kelas yang terdaftar.</td></tr>'
                                );
                            return;
                        }

                        classes.forEach(cls => {
                            // Cek kelengkapan data (antisipasi null)
                            const majorName = cls.major ? cls.major.name : '-';
                            const academicYear = cls.academic_year_name ? cls.academic_year_name : '-';
                            const eduLevel = cls.educational_level ? cls.educational_level.name : cls
                                .grade_level;

                            const row = `
                        <tr>
                            <td><strong>${cls.name}</strong> <span class="badge badge-secondary ml-1">${cls.suffix || ''}</span></td>
                            <td>${eduLevel}</td>
                            <td>${majorName}</td>
                            <td>${academicYear}</td>
                            <td class="text-center action-btn-group">
                                <a href="/manage-classes/${cls.id}" class="btn btn-sm btn-info" title="Manage Siswa & Materi">
                                    <i class="fas fa-cog"></i> Manage
                                </a>
                                <a href="/classes/${cls.id}/tasks/create" class="btn btn-sm btn-warning" title="Buat Tugas">
                                    <i class="fas fa-clipboard-list"></i> Tugas
                                </a>
                            </td>
                        </tr>
                    `;
                            $tableBody.append(row);
                        });
                    })
                    .fail(xhr => {
                        console.error(xhr);
                        $tableBody.html(
                            '<tr><td colspan="5" class="text-center text-danger">Gagal memuat data kelas.</td></tr>'
                            );
                        $('#stat-my-classes').text('0');
                    });
            }

            /**
             * 2. Load Data Mata Pelajaran Guru
             * Menggunakan /api/subjects-assignments
             */
            function loadTeacherSubjects() {
                const $subjectList = $('#teacher-subjects-list');
                $subjectList.html('<div class="col-12">Loading subjects...</div>');

                $.get(API.MY_SUBJECTS)
                    .done(res => {
                        // Asumsi response mengembalikan list subject yang di-assign ke user ini
                        const subjects = res.data || res;
                        $subjectList.empty();

                        // Update Statistik Card
                        $('#stat-my-subjects').text(subjects.length);

                        if (subjects.length === 0) {
                            $subjectList.html(
                                '<div class="col-12 text-muted">Anda belum di-assign ke mata pelajaran apapun.</div>'
                                );
                            return;
                        }

                        subjects.forEach(item => {
                            // Struktur data tergantung return API subjects-assignments.
                            // Biasanya item.subject atau item langsung. Sesuaikan disini.
                            // Kita asumsi item memiliki relasi 'subject' atau field name langsung.
                            const subjName = item.subject ? item.subject.name : (item.name ||
                                'Unknown Subject');
                            const subjCode = item.subject ? item.subject.code : (item.code || '');

                            const card = `
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card border-bottom-success shadow-sm h-100">
                                <div class="card-body p-3">
                                    <div class="font-weight-bold text-gray-800 text-truncate" title="${subjName}">
                                        ${subjName}
                                    </div>
                                    <div class="text-xs text-muted mt-1">Code: ${subjCode}</div>
                                </div>
                            </div>
                        </div>
                    `;
                            $subjectList.append(card);
                        });
                    })
                    .fail(xhr => {
                        console.error(xhr);
                        $subjectList.html('<div class="col-12 text-danger">Gagal memuat mata pelajaran.</div>');
                        $('#stat-my-subjects').text('0');
                    });
            }

            // Initialize Data Loading
            loadTeacherClasses();
            loadTeacherSubjects();
        });
    </script>
@endpush
