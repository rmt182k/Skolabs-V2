@extends('layouts.app')

@section('title', 'Manajemen Kompetensi')

@push('styles')
    {{-- BARU: CSS untuk Select2 --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <style>
        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            margin: 0 3px;
        }

        #competenciesTable th:last-child,
        #competenciesTable td:last-child {
            width: 120px;
            text-align: center;
        }

        /* BARU: Style error untuk Select2 */
        .select2-container--bootstrap-5.is-invalid .select2-selection {
            border-color: #dc3545 !important;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        @include('layouts.components.breadcrumb')

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="fas fa-brain me-2"></i>Daftar Kompetensi</h5>
                        <button class="btn btn-primary" id="btn-add-competency">
                            <i class="fas fa-plus me-2"></i>Tambah Kompetensi
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="competenciesTable" class="table table-striped table-bordered" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Mata Pelajaran</th> {{-- BARU --}}
                                        <th>Nama Kompetensi</th>
                                        <th>Deskripsi</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Data akan diisi oleh DataTables --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="competencyModal" tabindex="-1" aria-labelledby="competencyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="competencyModalLabel">Modal Title</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="competencyForm" onsubmit="return false;">
                    <div class="modal-body">
                        <input type="hidden" id="competency_id" name="id">

                        {{-- BARU: Field Subject (Select2) --}}
                        <div class="mb-3">
                            <label for="subject_id" class="form-label">Mata Pelajaran <span
                                    class="text-danger">*</span></label>
                            <select class="form-control" id="subject_id" name="subject_id" style="width: 100%;" required>
                                {{-- Options akan diload oleh Select2 --}}
                            </select>
                            <div class="invalid-feedback" id="error-subject_id"></div>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Kompetensi <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback" id="error-name"></div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            <div class="invalid-feedback" id="error-description"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-primary" id="btn-save-competency">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- BARU: JS untuk Select2 --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(function() {
            // ======================================================================
            // KONFIGURASI & VARIABEL GLOBAL
            // ======================================================================
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            const API = {
                GET_LIST: `{{ url('api/competencies') }}`,
                STORE: `{{ url('api/competencies') }}`,
                SHOW: (id) => `{{ url('api/competencies') }}/${id}`,
                UPDATE: (id) => `{{ url('api/competencies') }}/${id}`, // (Method POST, ditangani di controller)
                DESTROY: (id) => `{{ url('api/competencies') }}/${id}`,
                SEARCH_SUBJECTS: `{{ url('api/subjects/search') }}` // BARU
            };

            const $modal = $('#competencyModal');
            const $form = $('#competencyForm');
            const $selectSubject = $('#subject_id'); // BARU

            // ======================================================================
            // INISIALISASI SELECT2
            // ======================================================================
            $selectSubject.select2({
                theme: 'bootstrap-5',
                dropdownParent: $modal, // Penting agar Select2 tampil di atas modal
                ajax: {
                    url: API.SEARCH_SUBJECTS,
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            term: params.term // Sesuai 'term' di SubjectController@search
                        };
                    },
                    processResults: function(response) {
                        // API Anda mengembalikan { success: true, data: [...] }
                        // 'data' berisi [{id: 1, name: 'Matematika'}]
                        // Select2 butuh format [{id: 1, text: 'Matematika'}]
                        return {
                            results: $.map(response.data, function(item) {
                                return {
                                    id: item.id,
                                    text: item.name // Map 'name' ke 'text'
                                }
                            })
                        };
                    },
                    cache: true
                },
                placeholder: 'Cari Mata Pelajaran...',
                minimumInputLength: 0 // Izinkan pencarian kosong untuk menampilkan semua
            });


            // ======================================================================
            // INISIALISASI DATATABLE
            // ======================================================================
            const table = $('#competenciesTable').DataTable({
                processing: true,
                serverSide: false, // Set false karena data diambil semua di getCompetencies
                ajax: {
                    url: API.GET_LIST,
                    dataSrc: 'data'
                },
                columns: [{ // DIPERBARUI
                        data: 'id'
                    },
                    {
                        data: 'subject_name' // Kolom baru dari JOIN
                    },
                    {
                        data: 'name'
                    },
                    {
                        data: 'description',
                        render: function(data) {
                            if (!data) return '<i class="text-muted">Tidak ada deskripsi</i>';
                            return data.length > 50 ? data.substr(0, 50) + '...' : data;
                        }
                    },
                    {
                        data: 'id',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            return `
                                <button class="btn btn-sm btn-warning btn-action btn-edit" data-id="${data}" title="Edit">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <button class="btn btn-sm btn-danger btn-action btn-delete" data-id="${data}" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            `;
                        }
                    }
                ]
            });

            // ======================================================================
            // FUNGSI BANTUAN (CLEAR FORM, SHOW ERRORS)
            // ======================================================================
            function clearForm() {
                $form[0].reset();
                $('#competency_id').val('');
                $selectSubject.val(null).trigger('change'); // BARU: Reset Select2

                // Hapus semua class error
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('');
                $selectSubject.next('.select2-container').removeClass('is-invalid'); // BARU
            }

            function showErrors(errors) {
                // Hapus error lama
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('');
                $selectSubject.next('.select2-container').removeClass('is-invalid'); // BARU

                $.each(errors, function(key, value) {
                    const $element = $(`#${key}`);
                    $element.addClass('is-invalid');
                    $(`#error-${key}`).text(value[0]);

                    // BARU: Penanganan khusus untuk style error Select2
                    if (key === 'subject_id') {
                        $element.next('.select2-container').addClass('is-invalid');
                    }
                });
            }

            // ======================================================================
            // EVENT LISTENER
            // ======================================================================

            // 1. Tombol "Tambah Kompetensi"
            $('#btn-add-competency').on('click', function() {
                clearForm();
                $('#competencyModalLabel').text('Tambah Kompetensi Baru');
                $modal.modal('show');
            });

            // 2. Tombol "Edit" di dalam tabel
            $('#competenciesTable').on('click', '.btn-edit', function() {
                const id = $(this).data('id');
                clearForm();

                $.ajax({
                    url: API.SHOW(id),
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            const data = response.data;
                            $('#competency_id').val(data.id);
                            $('#name').val(data.name);
                            $('#description').val(data.description);

                            // === BARU: Pre-select Subject di Select2 ===
                            if (data.subject_id && data.subject_name) {
                                // Buat <option> baru untuk nilai yang dipilih
                                var $option = new Option(data.subject_name, data.subject_id,
                                    true, true);
                                // Tambahkan ke select2 dan 'trigger change'
                                $selectSubject.append($option).trigger('change');
                            }
                            // === END BARU ===

                            $('#competencyModalLabel').text('Edit Kompetensi');
                            $modal.modal('show');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON.message || 'Gagal mengambil data.',
                            'error');
                    }
                });
            });

            // 3. Tombol "Simpan" di Modal (Create atau Update)
            $('#btn-save-competency').on('click', function() {
                const id = $('#competency_id').val();
                const isUpdate = !!id;


                const url = isUpdate ? API.UPDATE(id) : API.STORE;
                const method = 'POST';

                const originalBtnText = $(this).html();
                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

                $.ajax({
                    url: url,
                    method: method,
                    data: $form.serialize(),
                    success: function(response) {
                        if (response.success) {
                            $modal.modal('hide');
                            Swal.fire('Sukses!', response.message, 'success');
                            table.ajax.reload(null, false);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422 && xhr.responseJSON.errors) {
                            showErrors(xhr.responseJSON.errors);
                        } else {
                            Swal.fire('Error!', xhr.responseJSON.message ||
                                'Terjadi kesalahan.', 'error');
                        }
                    },
                    complete: function() {
                        $('#btn-save-competency').prop('disabled', false).html(originalBtnText);
                    }
                });
            });

            // 4. Tombol "Delete" di dalam tabel
            $('#competenciesTable').on('click', '.btn-delete', function() {
                // ... (Logika delete Anda sudah benar, tidak perlu diubah)
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Kompetensi yang dihapus tidak dapat dikembalikan.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: API.DESTROY(id),
                            method: 'DELETE',
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire('Terhapus!', response.message, 'success');
                                    table.ajax.reload(null, false);
                                }
                            },
                            error: function(xhr) {
                                Swal.fire('Gagal!', xhr.responseJSON.message ||
                                    'Terjadi kesalahan.', 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
