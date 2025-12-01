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
                    <button class="btn btn-primary btn-sm" id="add-material-btn" data-bs-toggle="modal"
                        data-bs-target="#materialModal">
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
                    <h6 class="mb-0 text-dark">Daftar Siswa Terdaftar (<span id="student-count-table">...</span>
                        Siswa)</h6>
                    <button class="btn btn-primary btn-sm" id="add-student-btn" data-bs-toggle="modal"
                        data-bs-target="#studentModal">
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
