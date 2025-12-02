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
                                <th>Mata Pelajaran</th> {{-- KOLOM BARU --}}
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
    <script>
        $(document).ready(function() {
            // Setup CSRF
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // API Endpoints
            const API = {
                CLASSES: `/api/classes/fetchUserClasses/${window.globalAuthUser.id}`,
                MY_SUBJECTS: `/api/subjects-assignments/${window.globalAuthUser.id}` // Pastikan route ini aktif
            };


            /**
             * 1. Load Data Kelas (Tabel)
             */
            function loadTeacherClasses() {
                const $tableBody = $('#teacher-classes-body');
                $tableBody.html('<tr><td colspan="6" class="text-center">Loading classes...</td></tr>');

                $.get(API.CLASSES)
                    .done(res => {
                        const classes = res.data || res;
                        $tableBody.empty();

                        $('#stat-my-classes').text(classes.length);

                        if (classes.length === 0) {
                            $tableBody.html(
                                '<tr><td colspan="6" class="text-center">Belum ada kelas yang terdaftar.</td></tr>'
                            );
                            return;
                        }

                        classes.forEach(cls => {
                            const majorName = cls.major ? cls.major.name : '-';
                            const academicYear = cls.academic_year_name ? cls.academic_year_name : '-';
                            const eduLevel = cls.educational_level ? cls.educational_level.name : (cls
                                .grade_level || '-');

                            // Logic Badge Subject
                            let subjectBadges = '';
                            if (cls.subjects && cls.subjects.length > 0) {
                                cls.subjects.forEach(sub => {
                                    subjectBadges +=
                                        `<span class="badge badge-info mr-1 mb-1">${sub.name}</span>`;
                                });
                            } else {
                                subjectBadges =
                                    '<span class="text-muted text-xs font-italic">Tidak ada mapel</span>';
                            }

                            const row = `
                        <tr>
                            <td>
                                <strong>${cls.name}</strong>
                                <span class="badge badge-secondary ml-1">${cls.suffix || ''}</span>
                            </td>
                            <td>${subjectBadges}</td>
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
                        console.error("Error loading classes:", xhr);
                        $tableBody.html(
                            '<tr><td colspan="6" class="text-center text-danger">Gagal memuat data kelas.</td></tr>'
                        );
                        $('#stat-my-classes').text('0');
                    });
            }

            /**
             * 2. Load Data Mata Pelajaran (Card & List Bawah)
             * INI YANG HILANG DI KODE SEBELUMNYA
             */
            function loadTeacherSubjects() {
                const $subjectList = $('#teacher-subjects-list');
                const $statCounter = $('#stat-my-subjects');

                // Set Loading State
                $subjectList.html('<div class="col-12 text-center">Loading subjects...</div>');

                $.get(API.MY_SUBJECTS)
                    .done(res => {
                        // Handle struktur data: kadang API return {data: [...]}, kadang langsung [...]
                        // Sesuaikan dengan response "fetchTeacherAssignments" kamu yang ada di turn 1
                        const subjects = res.data || res;

                        $subjectList.empty();

                        // Update angka di Card Hijau
                        $statCounter.text(subjects.length);

                        if (subjects.length === 0) {
                            $statCounter.text(0);
                            $subjectList.html(
                                '<div class="col-12 text-muted text-center">Anda belum di-assign ke mata pelajaran apapun.</div>'
                            );
                            return;
                        }

                        // Loop untuk membuat card kecil di bagian bawah (List Mapel)
                        subjects.forEach(item => {
                            // Cek struktur JSON dari fetchTeacherAssignments (Turn 1)
                            // Di turn 1, formatnya: item.subject.name dan item.subject.code
                            const subjName = item.subject ? item.subject.name : item.subject_name;
                            const subjCode = item.subject ? item.subject.code : item.subject_code;

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
                        console.error("Error loading subjects:", xhr);
                        $subjectList.html(
                            '<div class="col-12 text-danger text-center">Gagal memuat mata pelajaran.</div>'
                        );
                        $statCounter.text('Error');
                    });
            }

            // Panggil kedua fungsi
            loadTeacherClasses();
            loadTeacherSubjects();
        });
    </script>
@endpush
