@extends('layouts.app')

@section('title', 'Jurusan')

@section('content')
    <div class="container-fluid">
        @include('layouts.components.breadcrumb')

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Data Jurusan</h5>
                <button type="button" class="btn btn-primary" id="add-major-btn">
                    <i class="fas fa-plus"></i> Tambah Baru
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="majors-table" style="width:100%">
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th>Nama Jurusan</th>
                                <th>Kode</th> <!-- TAMBAHAN -->
                                <th>Jenjang Pendidikan</th>
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

    <!-- Bootstrap Modal for Add/Edit -->
    <div class="modal fade" id="majorModal" tabindex="-1" aria-labelledby="majorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="majorModalLabel">Form Jurusan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="majorForm">
                    <div class="modal-body">
                        <input type="hidden" id="major_id" name="id">

                        <div class="form-group mb-3">
                            <label for="educational_level_id" class="form-label">Jenjang Pendidikan <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="educational_level_id" name="educational_level_id" required>
                                <option value="">Pilih Jenjang...</option>
                            </select>
                            <div class="invalid-feedback" id="educational_level_id-error"></div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="name" class="form-label">Nama Jurusan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback" id="name-error"></div>
                        </div>

                        <!-- TAMBAHAN: Form Input untuk Code -->
                        <div class="form-group mb-3">
                            <label for="code" class="form-label">Kode Jurusan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="code" name="code" required
                                style="text-transform:uppercase">
                            <small class="form-text text-muted">Contoh: IPA, IPS, RPL</small>
                            <div class="invalid-feedback" id="code-error"></div>
                        </div>
                        <!-- END TAMBAHAN -->

                        <div class="form-group mb-3">
                            <label for="description" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                            <div class="invalid-feedback" id="description-error"></div>
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
    <script>
        $(document).ready(function() {
            // 1. SETUP
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            const API = {
                MAJORS: '/api/majors',
                SHOW_MAJOR: majorId => `/api/majors/${majorId}`,
                EDUCATIONAL_LEVEL: '/api/educational-levels',
            };

            const DISABLED_LEVEL_IDS = ['1', '2'];
            const ENABLED_LEVEL_IDS = ['3', '4'];

            const majorModal = new bootstrap.Modal($('#majorModal')[0]);
            let dataTable;

            // 2. INITIALIZATION
            function init() {
                initializeDataTable();
            }

            function initializeDataTable() {
                dataTable = $('#majors-table').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: API.MAJORS,
                        dataSrc: 'data',
                        error: function(xhr, error, thrown) {
                            Swal.fire('Gagal Memuat Data', 'Terjadi kesalahan saat mengambil data.',
                                'error');
                            $('#majors-table_processing').hide();
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
                        // --- TAMBAHAN ---
                        {
                            data: 'code',
                            name: 'code',
                            render: data => data.toUpperCase() // Menampilkan kode sbg huruf besar
                        },
                        // --- END TAMBAHAN ---
                        {
                            data: 'educational_level_name',
                            name: 'educational_level_name'
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
                            // --- MODIFIKASI ---
                            // Target diubah dari 4 menjadi 5 karena ada penambahan kolom
                            targets: 5,
                            // --- END MODIFIKASI ---
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

            // 3. HELPER FUNCTIONS
            function loadEducationalLevel() {
                return $.get(API.EDUCATIONAL_LEVEL)
                    .done(res => {
                        if (res.success) {
                            const educationalLevels = res.data;

                            if (Array.isArray(educationalLevels)) {
                                const $elSelect = $('#educational_level_id');
                                $elSelect.empty().append('<option value="">Pilih Jenjang...</option>');

                                educationalLevels.forEach(level => {
                                    const levelId = String(level
                                        .id); // Konversi ke string untuk perbandingan
                                    let isDisabled = DISABLED_LEVEL_IDS.includes(levelId);
                                    let disabledAttribute = isDisabled ? 'disabled' : '';
                                    let textSuffix = isDisabled ? ' (Dinonaktifkan)' : '';

                                    // Memuat semua opsi jenjang, dengan disabilitas selektif
                                    $elSelect.append(
                                        `<option value="${level.id}" ${disabledAttribute}>${level.name}${textSuffix}</option>`
                                    );
                                });
                            } else {
                                console.error("Kesalahan: Data jenjang pendidikan bukan array.", res);
                            }
                        } else {
                            console.error("Gagal memuat data jenjang pendidikan:", res.message);
                            Swal.fire('Gagal!', "Gagal memuat data jenjang pendidikan: " + res.message,
                                'error');
                        }
                    })
                    .fail((jqXHR, textStatus, errorThrown) => {
                        console.error("Kesalahan API:", textStatus, errorThrown);
                        Swal.fire('Error', "Terjadi kesalahan saat menghubungi server.", 'error');
                    });
            }

            // 4. EVENT HANDLERS
            $('#add-major-btn').on('click', function() {
                $('#majorForm')[0].reset();
                $('#major_id').val('');
                $('.form-control, .form-select').removeClass('is-invalid');
                $('#majorModalLabel').text('Tambah Jurusan Baru');

                loadEducationalLevel().done(() => {
                    majorModal.show();
                }).fail(() => {
                    Swal.fire('Error', 'Gagal memuat data untuk form.', 'error');
                });
            });

            $('#majors-table tbody').on('click', '.edit-btn', function() {
                const majorId = $(this).data('id');

                $.when(loadEducationalLevel(), $.get(API.SHOW_MAJOR(majorId))).done(function(relatedDataRes,
                    majorDataRes) {
                    const majorData = majorDataRes[0].data;

                    $('#majorForm')[0].reset();
                    $('.form-control, .form-select').removeClass('is-invalid');

                    $('#major_id').val(majorData.id);
                    $('#educational_level_id').val(majorData.educational_level_id);
                    $('#name').val(majorData.name);
                    $('#code').val(majorData.code); // --- TAMBAHAN ---
                    $('#description').val(majorData.description);

                    $('#majorModalLabel').text(`Edit Jurusan: ${majorData.name}`);
                    majorModal.show();

                }).fail(function() {
                    Swal.fire('Error', 'Gagal mengambil data untuk diedit.', 'error');
                });
            });

            $('#majors-table tbody').on('click', '.delete-btn', function() {
                const majorId = $(this).data('id');
                const majorName = $(this).data('name');

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    html: `Anda akan menghapus jurusan <strong>${majorName}</strong>.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: API.SHOW_MAJOR(majorId),
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

            $('#majorForm').on('submit', function(e) {
                e.preventDefault();
                $('.form-control, .form-select').removeClass('is-invalid');

                // --- TAMBAHAN ---
                // Otomatis ubah input kode menjadi huruf besar sebelum dikirim
                const $code = $('#code');
                $code.val($code.val().toUpperCase());
                // --- END TAMBAHAN ---

                const majorId = $('#major_id').val();
                let url = majorId ? API.SHOW_MAJOR(majorId) : API.MAJORS;
                let method = majorId ? 'PUT' : 'POST';

                $.ajax({
                    url: url,
                    method: method,
                    data: $(this).serialize(),
                    success: (res) => {
                        majorModal.hide();
                        Swal.fire('Berhasil!', res.message, 'success');
                        dataTable.ajax.reload(null, false);
                    },
                    error: (xhr) => {
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            Object.keys(errors).forEach(key => {
                                $(`#${key}`).addClass('is-invalid');
                                $(`#${key}-error`).text(errors[key][0]);
                            });
                        } else {
                            Swal.fire('Error!', 'Terjadi kesalahan. Silakan coba lagi.',
                                'error');
                        }
                    }
                });
            });

            $('#majorModal').on('hidden.bs.modal', function() {
                $('.form-control, .form-select').removeClass('is-invalid');
            });

            // 5. RUN
            init();
        });
    </script>
@endpush
