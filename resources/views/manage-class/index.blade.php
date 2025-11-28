@extends('layouts.app')

@section('title', 'Kelola Kelas: ' . ($class->name ?? 'Nama Kelas'))

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="{{ asset('assets/css/manage-class/manage-class.css') }}">

@endpush

@section('content')
    <div class="container-fluid">
        {{-- Breadcrumb --}}
        @include('layouts.components.breadcrumb')

        {{-- Kartu Informasi Kelas --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0">
                    <i class="fas fa-school me-2 text-primary"></i>
                    Kelola Kelas: <span class="fw-bold text-dark">{{ $class->name ?? 'Nama Kelas' }}</span>
                </h5>
                <div class="mt-2 text-muted small">
                    <span class="me-3"><i class="fas fa-user-tie me-1"></i> Wali Kelas: <strong>{{ $class->homeroomTeacher->name ?? 'Belum Ditentukan' }}</strong></span>
                    <span><i class="fas fa-user-friends me-1"></i> Jumlah Siswa: <strong id="student-count">...</strong></span>
                </div>
            </div>
        </div>

        {{-- Konten Utama dengan Tabs --}}
        <div class="card shadow-sm">
            {{-- Navigasi Tab --}}
            <div class="card-header card-header-tab">
                <ul class="nav nav-tabs card-header-tabs" id="manageClassTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="schedule-tab-btn" data-bs-toggle="tab" data-bs-target="#schedule"
                            type="button" role="tab" aria-controls="schedule" aria-selected="true">
                            <i class="fas fa-calendar-alt me-1"></i> Class Schedule
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="materials-tab-btn" data-bs-toggle="tab" data-bs-target="#materials"
                            type="button" role="tab" aria-controls="materials" aria-selected="false">
                            <i class="fas fa-book me-1"></i> Bahan Ajar
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="assignments-tab-btn" data-bs-toggle="tab" data-bs-target="#assignments"
                            type="button" role="tab" aria-controls="assignments" aria-selected="false">
                            <i class="fas fa-tasks me-1"></i> Tugas & Ujian
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="discussion-tab" data-bs-toggle="tab" data-bs-target="#discussion"
                            type="button" role="tab" aria-controls="discussion" aria-selected="false">
                            <i class="fas fa-comments me-1"></i> Diskusi
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="students-tab-btn" data-bs-toggle="tab" data-bs-target="#students"
                            type="button" role="tab" aria-controls="students" aria-selected="false">
                            <i class="fas fa-users me-1"></i> Daftar Siswa
                        </button>
                    </li>
                </ul>
            </div>

            {{-- Isi Tab --}}
            <div class="card-body p-4">
                <div class="tab-content" id="manageClassTabContent">

                    {{-- ================= TAB JADWAL PELAJARAN ================= --}}
                    <div class="tab-pane fade show active" id="schedule" role="tabpanel" aria-labelledby="schedule-tab-btn">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 text-dark">Weekly Class Schedule</h6>
                            <button class="btn btn-primary btn-sm" id="add-schedule-btn">
                                <i class="fas fa-plus me-1"></i> Add Schedule Entry
                            </button>
                        </div>
                        <div id="schedule-loading">
                            <i class="fas fa-spinner fa-spin me-2"></i>
                            Loading schedule...
                        </div>
                        <div id="schedule-error" class="alert alert-warning" style="display: none;"></div>
                        <div id="schedule-container" class="row">
                            {{-- Konten jadwal dimuat oleh JS --}}
                        </div>
                    </div>

                    {{-- ================= TAB BAHAN AJAR ================= --}}
                    <div class="tab-pane fade" id="materials" role="tabpanel" aria-labelledby="materials-tab-btn">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 text-dark">Daftar Bahan Ajar</h6>
                            <button class="btn btn-primary btn-sm" id="add-material-btn" data-bs-toggle="modal" data-bs-target="#materialModal">
                                <i class="fas fa-plus me-1"></i> Tambah Bahan Ajar
                            </button>
                        </div>
                        <div id="materials-loading">
                            <i class="fas fa-spinner fa-spin me-2"></i>
                            Loading materials...
                        </div>
                        <div id="materials-error" class="alert alert-warning" style="display: none;"></div>
                        <div id="materials-container" class="row">
                            {{-- Konten bahan ajar dimuat oleh JS --}}
                        </div>
                    </div>

                    {{-- ================= TAB TUGAS & UJIAN (BARU) ================= --}}
                    <div class="tab-pane fade" id="assignments" role="tabpanel" aria-labelledby="assignments-tab-btn">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 text-dark">Daftar Tugas & Ujian</h6>
                            {{-- ⭐ INI DIA PERUBAHANNYA: <a> diubah jadi <button> --}}
                            <button id="create-task-btn" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i> Buat Tugas Baru
                            </button>
                        </div>

                        <div id="tasks-loading">
                            <i class="fas fa-spinner fa-spin me-2"></i>
                            Loading tasks...
                        </div>
                        <div id="tasks-error" class="alert alert-warning" style="display: none;"></div>

                        {{-- Kontainer untuk daftar tugas (layout 2 kolom) --}}
                        <div id="tasks-container" class="row">
                            {{-- Konten tugas dimuat oleh JS --}}
                        </div>
                    </div>

                    {{-- ================= TAB DISKUSI (DUMMY) ================= --}}
                    <div class="tab-pane fade" id="discussion" role="tabpanel" aria-labelledby="discussion-tab">
                         <div class="alert alert-info text-center">
                             <i class="fas fa-info-circle me-1"></i> Fitur diskusi sedang dalam pengembangan.
                         </div>
                    </div>

                    {{-- ================= TAB DAFTAR SISWA ================= --}}
                    <div class="tab-pane fade" id="students" role="tabpanel" aria-labelledby="students-tab-btn">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 text-dark">Daftar Siswa Terdaftar (<span id="student-count-table">...</span> Siswa)</h6>
                            <button class="btn btn-primary btn-sm" id="add-student-btn" data-bs-toggle="modal" data-bs-target="#studentModal">
                                <i class="fas fa-user-plus me-1"></i> Tambah Siswa
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered" id="students-table" style="width:100%">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%;">No</th>
                                        <th>NISN/Induk</th>
                                        <th>Nama Siswa</th>
                                        <th>Jenis Kelamin</th>
                                        <th style="width: 10%;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>


    {{-- =================================================================== --}}
    {{-- ========================== MODAL SECTION ========================== --}}
    {{-- =================================================================== --}}

    {{-- Modal Tambah Siswa --}}
    <div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="studentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="studentModalLabel">Tambah Siswa ke Kelas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="studentModalForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="student_user_id" class="form-label">Cari Siswa (bisa pilih lebih dari satu)</label>
                            <select class="form-select" id="student_user_id" name="user_ids[]" required style="width: 100%;" multiple>
                            </select>
                            <div class="invalid-feedback" id="user_id-error"></div>
                            <div class="form-text">Anda bisa memilih lebih dari satu siswa dari daftar pencarian.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Tambahkan ke Kelas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Jadwal --}}
    <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="scheduleModalLabel">Add Schedule Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="scheduleModalForm">
                    <input type="hidden" id="schedule_id" name="schedule_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="day_name" class="form-label">Day</label>
                            <select class="form-select" id="day_name" name="day_name" required>
                                <option value="">Select Day</option>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                            </select>
                            <div class="invalid-feedback" id="day_name-error"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_time" class="form-label">Start Time</label>
                                <input type="time" class="form-control" id="start_time" name="start_time" required>
                                <div class="invalid-feedback" id="start_time-error"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_time" class="form-label">End Time</label>
                                <input type="time" class="form-control" id="end_time" name="end_time" required>
                                <div class="invalid-feedback" id="end_time-error"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="subject_id" class="form-label">Subject</label>
                            <select class="form-select" id="subject_id" name="subject_id" required style="width: 100%;">
                                </select>
                            <div class="invalid-feedback" id="subject_id-error"></div>
                        </div>
                        <div class="mb-3">
                            <label for="user_id" class="form-label">Teacher</label>
                            <select class="form-select" id="user_id" name="user_id" required style="width: 100%;">
                                </select>
                            <div class="invalid-feedback" id="user_id-error"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="scheduleSubmitBtn">Save Entry</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Bahan Ajar --}}
    <div class="modal fade" id="materialModal" tabindex="-1" aria-labelledby="materialModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="materialModalLabel">Tambah Bahan Ajar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="materialModalForm" enctype="multipart/form-data">
                    <input type="hidden" id="material_id" name="material_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="material_subject_id" class="form-label">Mata Pelajaran</label>
                            <select class="form-select" id="material_subject_id" name="subject_id" required style="width: 100%;">
                                {{-- Diisi oleh Select2 --}}
                            </select>
                            <div class="invalid-feedback" id="subject_id-error"></div>
                        </div>
                        <div class="mb-3">
                            <label for="title" class="form-label">Judul Materi</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                            <div class="invalid-feedback" id="title-error"></div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi (Opsional)</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            <div class="invalid-feedback" id="description-error"></div>
                        </div>
                        <hr>
                        <p class="mb-2 fw-bold small text-muted">Lampiran (Opsional)</p>
                        <div class="mb-3">
                            <label for="file_input" class="form-label">Upload File</label>
                            <input class="form-control" type="file" id="file_input" name="file_input">
                            <div class="form-text" id="current-file-info"></div>
                            <div class="invalid-feedback" id="file_input-error"></div>
                        </div>
                        <div class="mb-3 form-check" id="remove-file-group" style="display: none;">
                            <input type="checkbox" class="form-check-input" id="remove_current_file" name="remove_current_file" value="1">
                            <label class="form-check-label" for="remove_current_file">Hapus file saat ini</label>
                            <div class="form-text">Centang untuk menghapus file yang sudah ter-upload.</div>
                        </div>
                        <div class="mb-3">
                            <label for="link_url" class="form-label">URL / Link Materi</label>
                            <input type="url" class="form-control" id="link_url" name="link_url" placeholder="https://www.example.com/materi.pdf">
                            <div class="invalid-feedback" id="link_url-error"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="materialSubmitBtn">Simpan Materi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    {{-- Load jQuery, Bootstrap, DataTables, SweetAlert, Select2 --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    {{-- Load file JS utama --}}
    <script src="{{ asset('assets/js/app/manage-class/manage-class.js') }}"></script>
@endpush

