@extends('layouts.app')

@section('title', 'Mata Pelajaran & Penugasan')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush
@section('content')
    <div class="container-fluid">
        @include('layouts.components.breadcrumb')

        <ul class="nav nav-tabs" id="subjectTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="master-mapel-tab" data-bs-toggle="tab"
                    data-bs-target="#master-mapel-tab-pane" type="button" role="tab"
                    aria-controls="master-mapel-tab-pane" aria-selected="true">
                    <i class="fas fa-book"></i> Master Mata Pelajaran
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="assign-mapel-tab" data-bs-toggle="tab" data-bs-target="#assign-mapel-tab-pane"
                    type="button" role="tab" aria-controls="assign-mapel-tab-pane" aria-selected="false">
                    <i class="fas fa-chalkboard-teacher"></i> Penugasan Guru
                </button>
            </li>
        </ul>

        <div class="tab-content" id="subjectTabsContent">

            <div class="tab-pane fade show active" id="master-mapel-tab-pane" role="tabpanel"
                aria-labelledby="master-mapel-tab" tabindex="0">
                <div class="card mt-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Data Mata Pelajaran</h5>
                        <button type="button" class="btn btn-primary" id="add-subject-btn">
                            <i class="fas fa-plus"></i> Tambah Baru
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="subjects-table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th style="width: 5%;">No</th>
                                        <th>Nama</th>
                                        <th>Kode</th>
                                        <th>Deskripsi</th>
                                        <th style="width: 15%;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Data will be loaded via AJAX --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="assign-mapel-tab-pane" role="tabpanel" aria-labelledby="assign-mapel-tab"
                tabindex="0">

                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Form Penugasan Mata Pelajaran</h5>
                    </div>
                    <div class="card-body">
                        <form id="assignmentForm">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-5">
                                    <label for="user_id" class="form-label">Pilih Guru <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="user_id" name="user_id" required>
                                        <option value="" selected disabled>Memuat data guru...</option>
                                        {{-- Opsi guru akan dimuat via AJAX --}}
                                    </select>
                                    <div class="invalid-feedback" id="user_id-error"></div>
                                </div>
                                <div class="col-md-5">
                                    <label for="subject_id_assign" class="form-label">Pilih Mata Pelajaran <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="subject_id_assign" name="subject_id" required>
                                        <option value="" selected disabled>Memuat data mapel...</option>
                                        {{-- Opsi mapel akan dimuat via AJAX --}}
                                    </select>
                                    <div class="invalid-feedback" id="subject_id_assign-error"></div>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-plus-circle"></i> Tugaskan
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Data Penugasan Aktif</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="assignments-table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th style="width: 5%;">No</th>
                                        <th>Nama Guru</th>
                                        <th>Mata Pelajaran</th>
                                        <th>Kode Mapel</th>
                                        <th style="width: 10%;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Data akan dimuat via AJAX --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="subjectModal" tabindex="-1" aria-labelledby="subjectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="subjectModalLabel">Form Mata Pelajaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="subjectForm">
                    <div class="modal-body">
                        <input type="hidden" id="subject_id" name="id">
                        <div class="form-group mb-3">
                            <label for="name" class="form-label">Nama <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback" id="name-error"></div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="code" class="form-label">Kode Mata Pelajaran <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="code" name="code" required>
                            <div class="invalid-feedback" id="code-error"></div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // 1. SETUP
            // =================================================================================
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            const TEACHER_ROLE_ID = 2; // Ganti sesuai konfigurasi Anda
            const API = {
                // API Tab 1
                SUBJECTS: '/api/subjects',
                SHOW_SUBJECT: subjectId => `/api/subjects/${subjectId}`,
                TEACHERS: `/api/roles/${TEACHER_ROLE_ID}/users`, // Endpoint untuk mengambil list guru
                SUBJECTS_LIST: '/api/subjects', // Endpoint untuk list mapel (dropdown)
                ASSIGNMENTS: '/api/subjects-assignments', // Endpoint untuk CRUD penugasan
                SHOW_ASSIGNMENT: assignmentId => `/api/subjects-assignments/${assignmentId}`
            };

            const subjectModal = new bootstrap.Modal($('#subjectModal')[0]);
            let dataTable;

            // --- VARIABEL BARU UNTUK TAB 2 ---
            let assignmentsDataTable;
            let isAssignmentTabLoaded = false; // Flag untuk load data saat tab diklik

            // 2. INITIALIZATION
            // =================================================================================
            function init() {
                // Inisialisasi tabel pertama (Master Mapel)
                initializeDataTable();

                // Fungsi untuk Tab 2 akan dipanggil saat tab-nya diklik
            }

            // --- FUNGSI TAB 1 (EXISTING) ---
            function initializeDataTable() {
                dataTable = $('#subjects-table').DataTable({
                    processing: true,
                    serverSide: false, // Ubah ke true jika data besar & pakai server-side processing
                    ajax: {
                        url: API.SUBJECTS,
                        dataSrc: 'data',
                        error: function(xhr, error, thrown) {
                            console.error("DataTables Ajax error:", xhr, error, thrown);
                            Swal.fire('Gagal Memuat Data',
                                'Terjadi kesalahan saat mengambil data master mapel.', 'error');
                        }
                    },
                    columns: [{
                            data: null,
                            name: 'no',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'name',
                            name: 'name'
                        },
                        {
                            data: 'code',
                            name: 'code'
                        },
                        {
                            data: 'description',
                            name: 'description'
                        },
                        {
                            data: 'id',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        }
                    ],
                    columnDefs: [{
                            targets: 0,
                            render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart +
                                1
                        },
                        {
                            targets: 4,
                            className: 'text-center',
                            render: (data, type, row) => `
                        <button class="btn btn-sm btn-warning edit-btn" data-id="${row.id}" title="Edit">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}" data-name="${row.name}" title="Hapus">
                            <i class="fas fa-trash"></i> Hapus
                        </button>`
                        }
                    ]
                });
            }

            // --- FUNGSI BARU UNTUK TAB 2 ---

            /**
             * Memuat data untuk dropdown Guru dan Mata Pelajaran di Tab 2
             */
            function loadDropdownData() {
                const $teacherSelect = $('#user_id');
                const $subjectSelect = $('#subject_id_assign');

                // 1. Load Teachers
                $.get(API.TEACHERS).done(res => {
                    if (res.data && res.data.length > 0) {
                        $teacherSelect.empty().append(new Option('Pilih Guru', ''));
                        res.data.forEach(teacher => {
                            $teacherSelect.append(new Option(teacher.name, teacher.id));
                        });
                    } else {
                        $teacherSelect.empty().append(new Option('Tidak ada data guru', ''));
                    }
                }).fail(err => {
                    console.error("Gagal load guru:", err);
                    $teacherSelect.empty().append(new Option('Gagal memuat data', ''));
                });

                // 2. Load Subjects
                // Asumsi API.SUBJECTS_LIST mengembalikan format { data: [{id: 1, name: 'Matematika'}, ...] }
                // Jika tidak, Anda bisa gunakan API.SUBJECTS dan parsing 'res.data'
                $.get(API.SUBJECTS_LIST).done(res => {
                    if (res.data && res.data.length > 0) {
                        $subjectSelect.empty().append(new Option('Pilih Mata Pelajaran', ''));
                        res.data.forEach(subject => {
                            $subjectSelect.append(new Option(`${subject.name} (${subject.code})`,
                                subject.id));
                        });
                    } else {
                        $subjectSelect.empty().append(new Option('Tidak ada data mapel', ''));
                    }
                }).fail(err => {
                    console.error("Gagal load mapel:", err);
                    $subjectSelect.empty().append(new Option('Gagal memuat data', ''));
                });

                // =================================================================
                // --- BARU: AKTIFKAN SELECT2 ---
                // Inisialisasi Select2 untuk Guru
                $teacherSelect.select2({
                    placeholder: 'Pilih Guru',
                    width: '100%',
                    theme: 'bootstrap-5' // Terapkan tema Bootstrap 5
                });

                // Inisialisasi Select2 untuk Mata Pelajaran
                $subjectSelect.select2({
                    placeholder: 'Pilih Mata Pelajaran',
                    width: '100%',
                    theme: 'bootstrap-5' // Terapkan tema Bootstrap 5
                });
                // =================================================================
            }

            /**
             * Inisialisasi DataTable untuk data penugasan di Tab 2
             */
            function initializeAssignmentsDataTable() {
                assignmentsDataTable = $('#assignments-table').DataTable({
                    processing: true,
                    serverSide: false, // Sesuaikan jika perlu
                    ajax: {
                        url: API.ASSIGNMENTS,
                        dataSrc: 'data', // Asumsi API mengembalikan { data: [...] }
                        error: function(xhr, error, thrown) {
                            console.error("DataTables Ajax error (Assignments):", xhr, error, thrown);
                            Swal.fire('Gagal Memuat Data',
                                'Terjadi kesalahan saat mengambil data penugasan.', 'error');
                        }
                    },
                    // Asumsi data yang diterima: { id: 1, teacher: { name: 'Nama Guru' }, subject: { name: 'Matematika', code: 'MTK' } }
                    columns: [{
                            data: null,
                            name: 'no',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'teacher.name',
                            name: 'teacher.name',
                            defaultContent: 'N/A'
                        },
                        {
                            data: 'subject.name',
                            name: 'subject.name',
                            defaultContent: 'N/A'
                        },
                        {
                            data: 'subject.code',
                            name: 'subject.code',
                            defaultContent: 'N/A'
                        },
                        {
                            data: 'id',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        }
                    ],
                    columnDefs: [{
                            targets: 0,
                            render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart +
                                1
                        },
                        {
                            targets: 4,
                            className: 'text-center',
                            render: (data, type, row) => `
                        <button class="btn btn-sm btn-danger delete-assignment-btn" data-id="${row.id}" data-teacher="${row.teacher?.name}" data-subject="${row.subject?.name}" title="Hapus Tugas">
                            <i class="fas fa-trash-alt"></i> Hapus
                        </button>`
                        }
                    ]
                });
            }


            // 3. EVENT HANDLERS
            // =================================================================================

            // --- EVENT HANDLER TAB 1 (EXISTING) ---

            $('#add-subject-btn').on('click', function() {
                $('#subjectForm')[0].reset();
                $('#subject_id').val('');
                $('.form-control').removeClass('is-invalid');
                $('#subjectModalLabel').text('Tambah Mata Pelajaran');
                subjectModal.show();
            });

            $('#subjects-table tbody').on('click', '.edit-btn', function() {
                const subjectId = $(this).data('id');
                $.get(API.SHOW_SUBJECT(subjectId)).done(res => {
                    if (res.success) {
                        const subject = res.data;
                        $('#subjectForm')[0].reset();
                        $('.form-control').removeClass('is-invalid');
                        $('#subject_id').val(subject.id);
                        $('#name').val(subject.name);
                        $('#code').val(subject.code);
                        $('#description').val(subject.description);
                        $('#subjectModalLabel').text(`Edit Mata Pelajaran: ${subject.name}`);
                        subjectModal.show();
                    }
                }).fail(err => {
                    Swal.fire('Error', 'Gagal mengambil data.', 'error');
                });
            });

            $('#subjects-table tbody').on('click', '.delete-btn', function() {
                const subjectId = $(this).data('id');
                const subjectName = $(this).data('name');

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    html: `Anda akan menghapus <strong>${subjectName}</strong>. Aksi ini tidak dapat dibatalkan.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: API.SHOW_SUBJECT(subjectId),
                            method: 'DELETE',
                            success: (res) => {
                                Swal.fire('Berhasil!', res.message, 'success');
                                dataTable.ajax.reload(null, false);
                            },
                            error: (xhr) => Swal.fire('Error!', 'Gagal menghapus data.',
                                'error')
                        });
                    }
                });
            });

            $('#subjectForm').on('submit', function(e) {
                e.preventDefault();
                $('.form-control').removeClass('is-invalid');

                const subjectId = $('#subject_id').val();
                let url = subjectId ? API.SHOW_SUBJECT(subjectId) : API.SUBJECTS;
                let method = subjectId ? 'PUT' : 'POST';

                $.ajax({
                    url: url,
                    method: method,
                    data: $(this).serialize(),
                    success: (res) => {
                        subjectModal.hide();
                        Swal.fire('Berhasil!', res.message, 'success');
                        dataTable.ajax.reload(null, false);
                        // --- BARU ---
                        // Reload juga dropdown mapel di tab 2 jika ada perubahan
                        if (isAssignmentTabLoaded) {
                            loadDropdownData();
                        }
                    },
                    error: (xhr) => {
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            Object.keys(errors).forEach(key => {
                                const inputField = $(`#${key}`);
                                const errorField = $(`#${key}-error`);
                                if (inputField.length) inputField.addClass(
                                'is-invalid');
                                if (errorField.length) errorField.text(errors[key][0]);
                            });
                        } else {
                            Swal.fire('Error!', 'Terjadi kesalahan. Silakan coba lagi.',
                                'error');
                        }
                    }
                });
            });

            $('#subjectModal').on('hidden.bs.modal', function() {
                $('.form-control').removeClass('is-invalid');
            });

            // --- EVENT HANDLER BARU UNTUK TAB 2 ---

            /**
             * Load data Tab 2 (Penugasan) hanya ketika tab tersebut pertama kali ditampilkan
             */
            $('button[data-bs-target="#assign-mapel-tab-pane"]').on('shown.bs.tab', function() {
                if (!isAssignmentTabLoaded) {
                    console.log('Tab Penugasan dibuka, memuat data...');
                    initializeAssignmentsDataTable();
                    loadDropdownData();
                    isAssignmentTabLoaded = true;
                } else {
                    // Opsional: Reload data tabel setiap kali tab diklik
                    // assignmentsDataTable.ajax.reload(null, false);
                }
            });

            /**
             * Submit form penugasan baru
             */
            $('#assignmentForm').on('submit', function(e) {
                e.preventDefault();

                // Hapus validasi error sebelumnya
                $('#user_id').removeClass('is-invalid');
                $('#subject_id_assign').removeClass('is-invalid');

                $.ajax({
                    url: API.ASSIGNMENTS,
                    method: 'POST',
                    data: $(this).serialize(),
                    success: (res) => {
                        Swal.fire('Berhasil!', res.message || 'Penugasan berhasil disimpan.',
                            'success');
                        assignmentsDataTable.ajax.reload(null, false);

                        // Reset form
                        $('#assignmentForm')[0].reset();

                        // =================================================================
                        // --- BARU: AKTIFKAN RESET UNTUK SELECT2 ---
                        // Ini penting agar tampilan Select2 ikut ter-reset
                        $('#user_id').val(null).trigger('change');
                        $('#subject_id_assign').val(null).trigger('change');
                        // =================================================================
                    },
                    error: (xhr) => {
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            // Tampilkan error validasi
                            if (errors.user_id) {
                                $('#user_id').addClass('is-invalid');
                                $('#user_id-error').text(errors.user_id[0]);
                            }
                            if (errors.subject_id) {
                                $('#subject_id_assign').addClass('is-invalid');
                                $('#subject_id_assign-error').text(errors.subject_id[0]);
                            }
                        } else {
                            Swal.fire('Error!', xhr.responseJSON?.message ||
                                'Gagal menyimpan penugasan.', 'error');
                        }
                    }
                });
            });

            /**
             * Hapus data penugasan
             */
            $('#assignments-table tbody').on('click', '.delete-assignment-btn', function() {
                const assignmentId = $(this).data('id');
                const teacherName = $(this).data('teacher') || 'Guru';
                const subjectName = $(this).data('subject') || 'Mapel';

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    html: `Anda akan menghapus penugasan <strong>${subjectName}</strong> dari <strong>${teacherName}</strong>.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: API.SHOW_ASSIGNMENT(assignmentId),
                            method: 'DELETE',
                            success: (res) => {
                                Swal.fire('Berhasil!', res.message ||
                                    'Penugasan berhasil dihapus.', 'success');
                                assignmentsDataTable.ajax.reload(null, false);
                            },
                            error: (xhr) => Swal.fire('Error!', xhr.responseJSON?.message ||
                                'Gagal menghapus data.', 'error')
                        });
                    }
                });
            });
            init();
        });
    </script>
@endpush
