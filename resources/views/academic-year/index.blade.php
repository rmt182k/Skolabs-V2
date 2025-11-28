@extends('layouts.app')

@section('title', 'Academic Years')

@section('content')
    <div class="container-fluid">
        @include('layouts.components.breadcrumb', ['title' => 'Academic Years'])

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Academic Year Data</h5>
                <button type="button" class="btn btn-primary" id="add-academic-year-btn">
                    <i class="fas fa-plus"></i> Add New
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="academic-years-table" style="width:100%">
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th>Year</th>
                                <th>Semester</th>
                                <th>Name</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th style="width: 15%;">Action</th>
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
    <div class="modal fade" id="academicYearModal" tabindex="-1" aria-labelledby="academicYearModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="academicYearModalLabel">Academic Year Form</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="academicYearForm">
                    <div class="modal-body">
                        <input type="hidden" id="academic_year_id" name="id">

                        <div class="form-group mb-3">
                            <label for="year" class="form-label">Academic Year <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="year" name="year"
                                placeholder="e.g., 2023/2024" required>
                            <div class="invalid-feedback" id="year-error"></div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="semester" class="form-label">Semester <span class="text-danger">*</span></label>
                            <select class="form-select" id="semester" name="semester" required>
                                <option value="">Select Semester...</option>
                                <option value="odd">Odd</option>
                                <option value="even">Even</option>
                            </select>
                            <div class="invalid-feedback" id="semester-error"></div>
                        </div>

                        <!-- BARU: Form group untuk preview Nama -->
                        <div class="form-group mb-3">
                            <label for="name_preview" class="form-label">Generated Name (Preview)</label>
                            <input type="text" class="form-control" id="name_preview" readonly
                                style="background-color: #e9ecef; cursor: not-allowed;"
                                placeholder="e.g., 2023/2024 - Odd">
                            <small class="form-text text-muted">This name is generated automatically based on Year and
                                Semester.</small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                            <div class="invalid-feedback" id="start_date-error"></div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                            <div class="invalid-feedback" id="end_date-error"></div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="is_active" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="is_active" name="is_active" required>
                                <option value="0">Not Active</option>
                                <option value="1">Active</option>
                            </select>
                            <small class="form-text text-muted">Setting this to 'Active' will deactivate other
                                periods.</small>
                            <div class="invalid-feedback" id="is_active-error"></div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
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
                ACADEMIC_YEARS: '/api/academic-years',
                SHOW_ACADEMIC_YEAR: yearId => `/api/academic-years/${yearId}`,
            };

            const academicYearModal = new bootstrap.Modal($('#academicYearModal')[0]);
            let dataTable;

            // BARU: Fungsi untuk update preview nama
            function updateNamePreview() {
                const year = $('#year').val().trim();
                const semester = $('#semester').val();

                if (year && semester) {
                    // Capitalize first letter (e.g., "odd" -> "Odd")
                    const formattedSemester = semester.charAt(0).toUpperCase() + semester.slice(1);
                    const generatedName = `${year} - ${formattedSemester}`;
                    $('#name_preview').val(generatedName);
                } else {
                    $('#name_preview').val(''); // Kosongkan jika salah satu field belum diisi
                }
            }

            // 2. INITIALIZATION
            function init() {
                initializeDataTable();
            }

            function initializeDataTable() {
                dataTable = $('#academic-years-table').DataTable({
                    // ... (existing datatable config) ...
                    processing: true,
                    serverSide: false, // Set to false as fetchAll() returns all data
                    ajax: {
                        url: API.ACADEMIC_YEARS,
                        dataSrc: 'data',
                        error: function(xhr, error, thrown) {
                            Swal.fire('Failed to Load Data',
                                'An error occurred while fetching data.', 'error');
                            $('#academic-years-table_processing').hide();
                        }
                    },
                    columns: [{
                            data: null,
                            name: 'no',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'year',
                            name: 'year'
                        },
                        {
                            data: 'semester',
                            name: 'semester'
                        },
                        {
                            data: 'name', // <-- TAMBAHKAN INI
                            name: 'name'
                        },
                        {
                            data: 'start_date',
                            name: 'start_date'
                        },
                        {
                            data: 'end_date',
                            name: 'end_date'
                        },
                        {
                            data: 'is_active',
                            name: 'is_active'
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
                            render: (data, type, row, meta) => meta.row + meta.settings
                                ._iDisplayStart + 1
                        },
                        {
                            targets: 6, // <-- UBAH INI (sebelumnya 5)
                            className: 'text-center',
                            render: (data, type, row) => data == 1 ?
                                '<span class="badge bg-success">Active</span>' :
                                '<span class="badge bg-secondary">Not Active</span>'
                        },
                        {
                            targets: 7, // <-- UBAH INI (sebelumnya 6)
                            className: 'text-center',
                            render: (data, type, row) => `
                            <button class="btn btn-sm btn-warning edit-btn" data-id="${row.id}" title="Edit">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}" data-name="${row.name}" title="Delete">
                                <i class="fas fa-trash"></i> Delete
                            </button>`
                        }
                    ]
                });
            }

            // 3. EVENT HANDLERS

            // BARU: Listener untuk field Year dan Semester
            $('#year').on('input', updateNamePreview);
            $('#semester').on('change', updateNamePreview);

            $('#add-academic-year-btn').on('click', function() {
                $('#academicYearForm')[0].reset();
                $('#academic_year_id').val('');
                $('.form-control, .form-select').removeClass('is-invalid');
                $('#name_preview').val(''); // BARU: Pastikan preview dikosongkan
                $('#academicYearModalLabel').text('Add New Academic Year');
                $('#is_active').val('0'); // Default to 'Not Active'
                academicYearModal.show();
            });

            $('#academic-years-table tbody').on('click', '.edit-btn', function() {
                const yearId = $(this).data('id');

                $.get(API.SHOW_ACADEMIC_YEAR(yearId))
                    .done(function(res) {
                        if (res.success) {
                            const yearData = res.data;

                            $('#academicYearForm')[0].reset();
                            $('.form-control, .form-select').removeClass('is-invalid');

                            $('#academic_year_id').val(yearData.id);
                            $('#year').val(yearData.year);
                            $('#semester').val(yearData.semester);
                            $('#start_date').val(yearData.start_date);
                            $('#end_date').val(yearData.end_date);
                            $('#is_active').val(yearData.is_active);

                            updateNamePreview(); // BARU: Panggil fungsi preview setelah data dimuat

                            $('#academicYearModalLabel').text(
                                `Edit Academic Year: ${yearData.name}`); // <-- UBAH INI
                            academicYearModal.show();
                        } else {
                            Swal.fire('Error', res.message || 'Failed to load data for editing.',
                                'error');
                        }
                    })
                    .fail(function() {
                        Swal.fire('Error', 'Failed to load data for editing.', 'error');
                    });
            });

            // ... (Sisa kode JS sama, tidak perlu diubah) ...
            // ... (Delete handler) ...
            $('#academic-years-table tbody').on('click', '.delete-btn', function() {
                const yearId = $(this).data('id');
                const yearName = $(this).data('name'); // <-- Ini sekarang akan mengambil 'name' yang baru

                Swal.fire({
                    title: 'Are you sure?',
                    html: `You are about to delete <strong>${yearName}</strong>.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: API.SHOW_ACADEMIC_YEAR(yearId),
                            method: 'DELETE',
                            success: (res) => {
                                if (res.success) {
                                    Swal.fire('Deleted!', res.message, 'success');
                                    dataTable.ajax.reload(null, false);
                                } else {
                                    Swal.fire('Error!', res.message, 'error');
                                }
                            },
                            error: (xhr) => {
                                const res = xhr.responseJSON;
                                Swal.fire('Error!', res.message ||
                                    'Failed to delete data.', 'error');
                            }
                        });
                    }
                });
            });


            $('#academicYearForm').on('submit', function(e) {
                e.preventDefault();
                $('.form-control, .form-select').removeClass('is-invalid');

                const yearId = $('#academic_year_id').val();
                let url = yearId ? API.SHOW_ACADEMIC_YEAR(yearId) : API.ACADEMIC_YEARS;
                let method = yearId ? 'PUT' : 'POST';

                $.ajax({
                    url: url,
                    method: method,
                    data: $(this).serialize(),
                    success: (res) => {
                        academicYearModal.hide();
                        Swal.fire('Success!', res.message, 'success');
                        dataTable.ajax.reload(null, false); // false = don't reset pagination
                    },
                    error: (xhr) => {
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            Object.keys(errors).forEach(key => {
                                $(`#${key}`).addClass('is-invalid');
                                $(`#${key}-error`).text(errors[key][0]);
                            });
                        } else {
                            const res = xhr.responseJSON;
                            Swal.fire('Error!', res.message ||
                                'An error occurred. Please try again.', 'error');
                        }
                    }
                });
            });

            $('#academicYearModal').on('hidden.bs.modal', function() {
                $('.form-control, .form-select').removeClass('is-invalid');
                $('#academicYearForm')[0].reset();
                $('#name_preview').val(''); // BARU: Pastikan preview dikosongkan
            });

            // 4. RUN
            init();
        });
    </script>
@endpush

