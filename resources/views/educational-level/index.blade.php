@extends('layouts.app')

@section('title', 'Jenjang Pendidikan')

@section('content')
<div class="container-fluid">
    @include('layouts.components.breadcrumb', ['title' => 'Jenjang Pendidikan'])

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Data Jenjang Pendidikan</h5>
            {{-- This button will trigger the modal for adding new data --}}
            <button type="button" class="btn btn-primary" id="add-level-btn">
                <i class="fas fa-plus"></i> Tambah Baru
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                {{-- The table will be populated by DataTables --}}
                <table class="table table-bordered table-striped" id="educational-levels-table" style="width:100%">
                    <thead>
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th>Nama</th>
                            <th>Deskripsi</th>
                            <th>Durasi (Tahun)</th>
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
<div class="modal fade" id="levelModal" tabindex="-1" aria-labelledby="levelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="levelModalLabel">Form Jenjang Pendidikan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="levelForm">
                <div class="modal-body">
                    {{-- Hidden input for storing ID during edit --}}
                    <input type="hidden" id="level_id" name="id">

                    <div class="form-group mb-3">
                        <label for="name" class="form-label">Nama <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback" id="name-error"></div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="description" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>

                    <div class="form-group mb-3">
                        <label for="duration" class="form-label">Durasi (dalam tahun) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="duration" name="duration" required min="1">
                        <div class="invalid-feedback" id="duration-error"></div>
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

@push('styles')
{{-- Make sure DataTables and SweetAlert2 CSS are included in your main layout --}}
@endpush

@push('scripts')
{{-- Make sure jQuery, DataTables, and SweetAlert2 JS are included in your main layout --}}
<script>
$(document).ready(function () {
    // 1. SETUP
    // =================================================================================
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    const API = {
        LEVELS: '/api/educational-levels',
        SHOW_LEVEL: levelId => `/api/educational-levels/${levelId}`,
    };

    const levelModal = new bootstrap.Modal($('#levelModal')[0]);
    let dataTable;

    // 2. INITIALIZATION
    // =================================================================================
    function init() {
        initializeDataTable();
    }

    function initializeDataTable() {
        dataTable = $('#educational-levels-table').DataTable({
            processing: true,
            serverSide: false, // Diubah menjadi client-side
            ajax: {
                url: API.LEVELS,
                dataSrc: 'data', // Menentukan sumber data dari response JSON
                // PERBAIKAN: Tambahkan error handler untuk menangani kegagalan AJAX
                error: function(xhr, error, thrown) {
                    console.error("DataTables Ajax error:", xhr, error, thrown);
                    Swal.fire({
                        title: 'Gagal Memuat Data',
                        text: 'Terjadi kesalahan saat mengambil data dari server. Silakan coba lagi nanti.',
                        icon: 'error'
                    });
                    // Sembunyikan indikator "Processing..."
                    $('#educational-levels-table_processing').hide();
                }
            },
            columns: [
                { data: null, name: 'no', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'description', name: 'description' },
                { data: 'duration', name: 'duration' },
                { data: 'id', name: 'action', orderable: false, searchable: false }
            ],
            columnDefs: [
                {
                    targets: 0, // Kolom 'No'
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                {
                    targets: 4, // Kolom 'Aksi'
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

    // 3. EVENT HANDLERS
    // =================================================================================

    // Show modal for adding new level
    $('#add-level-btn').on('click', function () {
        $('#levelForm')[0].reset();
        $('#level_id').val('');
        $('.form-control').removeClass('is-invalid');
        $('#levelModalLabel').text('Tambah Jenjang Pendidikan');
        levelModal.show();
    });

    // Handle Edit button click
    $('#educational-levels-table tbody').on('click', '.edit-btn', function () {
        const levelId = $(this).data('id');
        $.get(API.SHOW_LEVEL(levelId)).done(res => {
            if (res.success) {
                const level = res.data;
                $('#levelForm')[0].reset();
                $('.form-control').removeClass('is-invalid');

                $('#level_id').val(level.id);
                $('#name').val(level.name);
                $('#description').val(level.description);
                $('#duration').val(level.duration);

                $('#levelModalLabel').text(`Edit Jenjang Pendidikan: ${level.name}`);
                levelModal.show();
            }
        }).fail(err => {
            Swal.fire('Error', 'Gagal mengambil data.', 'error');
        });
    });

    // Handle Delete button click
    $('#educational-levels-table tbody').on('click', '.delete-btn', function () {
        const levelId = $(this).data('id');
        const levelName = $(this).data('name');

        Swal.fire({
            title: 'Apakah Anda yakin?',
            html: `Anda akan menghapus <strong>${levelName}</strong>. Aksi ini tidak dapat dibatalkan.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: API.SHOW_LEVEL(levelId),
                    method: 'DELETE',
                    success: (res) => {
                        Swal.fire('Berhasil!', res.message, 'success');
                        dataTable.ajax.reload(null, false);
                    },
                    error: (xhr) => Swal.fire('Error!', 'Gagal menghapus data.', 'error')
                });
            }
        });
    });

    // Handle Form Submission (Create & Update)
    $('#levelForm').on('submit', function (e) {
        e.preventDefault();
        $('.form-control').removeClass('is-invalid'); // Clear previous errors

        const levelId = $('#level_id').val();
        let url = levelId ? API.SHOW_LEVEL(levelId) : API.LEVELS;
        let method = levelId ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            method: method,
            data: $(this).serialize(),
            success: (res) => {
                levelModal.hide();
                Swal.fire('Berhasil!', res.message, 'success');
                dataTable.ajax.reload(null, false);
            },
            error: (xhr) => {
                // PERBAIKAN: Penanganan error validasi yang lebih aman
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(key => {
                        const inputField = $(`#${key}`);
                        const errorField = $(`#${key}-error`);
                        if (inputField.length) {
                            inputField.addClass('is-invalid');
                        }
                        if (errorField.length) {
                            errorField.text(errors[key][0]);
                        }
                    });
                } else {
                    Swal.fire('Error!', 'Terjadi kesalahan. Silakan coba lagi.', 'error');
                }
            }
        });
    });

    // Clear validation on modal close
    $('#levelModal').on('hidden.bs.modal', function () {
        $('.form-control').removeClass('is-invalid');
    });

    // 4. RUN
    // =================================================================================
    init();
});
</script>
@endpush

