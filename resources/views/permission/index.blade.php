@extends('layouts.app')

@section('title', 'Permissions Management')

@section('content')
    {{-- Breadcrumb sudah di-include dari template Anda --}}
    @include('layouts.components.breadcrumb')

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Permissions List</h5>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#permissionModal">
                            <i class="fas fa-plus me-1"></i> Add New Permission
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Display Name</th>
                                        <th>Name (Slug)</th>
                                        <th>Description</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="permissions-table-body">
                                    {{-- Data akan dimuat di sini oleh JavaScript --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Add/Edit Permission -->
    <div class="modal fade" id="permissionModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Permission</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="permissionForm">
                    <div class="modal-body">
                        <input type="hidden" id="permission_id" name="id">
                        <div class="mb-3">
                            <label for="display_name" class="form-label">Display Name</label>
                            <input type="text" class="form-control" id="display_name" name="display_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="name" class="form-label">Name (Slug)</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="form-text">Gunakan format: `verb-noun` (contoh: `create-post`, `edit-user`).</div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="saveBtn">Save Permission</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    /* Style untuk vertical align di tabel agar lebih rapi */
    .table td, .table th {
        vertical-align: middle;
    }
    .action-buttons a, .action-buttons button {
        margin-right: 5px;
    }
</style>
@endpush

@push('scripts')
{{-- SweetAlert2 untuk notifikasi yang lebih baik --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
{{-- jQuery diperlukan untuk script di bawah ini --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function () {
    // Setup CSRF token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // API Endpoint URLs
    const API = {
        INDEX: '/api/permissions',
        STORE: '/api/permissions',
        SHOW: id => `/api/permissions/${id}`,
        UPDATE: id => `/api/permissions/${id}`,
        DESTROY: id => `/api/permissions/${id}`,
    };

    const permissionModal = new bootstrap.Modal($('#permissionModal')[0]);
    const $permissionsTableBody = $('#permissions-table-body');

    /**
     * Fungsi untuk mengambil dan menampilkan semua permissions
     */
    function fetchPermissions() {
        $permissionsTableBody.html('<tr><td colspan="4" class="text-center">Loading...</td></tr>');
        $.get(API.INDEX)
            .done(res => {
                if (res.success) {
                    renderTable(res.data);
                } else {
                    $permissionsTableBody.html('<tr><td colspan="4" class="text-center">Failed to load data.</td></tr>');
                }
            })
            .fail(xhr => {
                console.error('Error fetching permissions:', xhr);
                $permissionsTableBody.html('<tr><td colspan="4" class="text-center">Error loading data. Please try again.</td></tr>');
            });
    }

    /**
     * Fungsi untuk merender data permissions ke dalam tabel
     * @param {Array} permissions
     */
    function renderTable(permissions) {
        $permissionsTableBody.empty();
        if (!permissions || permissions.length === 0) {
            $permissionsTableBody.html('<tr><td colspan="4" class="text-center">No permissions found.</td></tr>');
            return;
        }

        permissions.forEach(permission => {
            const row = `
                <tr data-permission-id="${permission.id}">
                    <td>${permission.display_name}</td>
                    <td><code>${permission.name}</code></td>
                    <td>${permission.description || '-'}</td>
                    <td class="text-center action-buttons">
                        <button class="btn btn-sm btn-info edit-btn" data-id="${permission.id}" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="${permission.id}" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            $permissionsTableBody.append(row);
        });
    }

    // Tampilkan modal untuk "Add New Permission"
    $('button[data-bs-target="#permissionModal"]').on('click', () => {
        $('#permissionForm')[0].reset();
        $('#permission_id').val('');
        $('#modalTitle').text('Add New Permission');
        permissionModal.show();
    });

    // Handle form submission (Create & Update)
    $('#permissionForm').on('submit', function (e) {
        e.preventDefault();
        const permissionId = $('#permission_id').val();
        const url = permissionId ? API.UPDATE(permissionId) : API.STORE;
        const method = permissionId ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            method: method,
            data: $(this).serialize(),
            success: function(res) {
                permissionModal.hide();
                Swal.fire('Success!', res.message, 'success');
                fetchPermissions(); // Refresh table
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let errorMessages = '';
                    $.each(errors, function(key, value) {
                        errorMessages += `${value.join(', ')}\n`;
                    });
                    Swal.fire('Validation Error', errorMessages, 'error');
                } else {
                    const msg = xhr.responseJSON?.message || 'An unexpected error occurred.';
                    Swal.fire('Error', msg, 'error');
                }
            }
        });
    });

    // Event delegation for Edit button
    $permissionsTableBody.on('click', '.edit-btn', function () {
        const permissionId = $(this).data('id');
        $.get(API.SHOW(permissionId))
            .done(res => {
                if(res.success){
                    const permission = res.data;
                    $('#permission_id').val(permission.id);
                    $('#display_name').val(permission.display_name);
                    $('#name').val(permission.name);
                    $('#description').val(permission.description);
                    $('#modalTitle').text('Edit Permission');
                    permissionModal.show();
                }
            })
            .fail(xhr => Swal.fire('Error', 'Failed to fetch permission details.', 'error'));
    });

    // Event delegation for Delete button
    $permissionsTableBody.on('click', '.delete-btn', function () {
        const permissionId = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: API.DESTROY(permissionId),
                    method: 'DELETE',
                    success: function(res) {
                        Swal.fire('Deleted!', res.message, 'success');
                        fetchPermissions(); // Refresh table
                    },
                    error: function(xhr) {
                         const msg = xhr.responseJSON?.message || 'Could not delete permission.';
                         Swal.fire('Error!', msg, 'error');
                    }
                });
            }
        });
    });

    // Initial data load
    fetchPermissions();
});
</script>
@endpush
