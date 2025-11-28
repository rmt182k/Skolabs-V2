@extends('layouts.app')

@section('title', 'Roles Management')

@section('content')
    {{-- Breadcrumb sudah di-include dari template Anda --}}
    @include('layouts.components.breadcrumb')

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Roles List</h5>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#roleModal">
                            <i class="fas fa-plus me-1"></i> Add New Role
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
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="roles-table-body">
                                    {{-- Data akan dimuat di sini oleh JavaScript --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="roleModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="roleForm">
                    <div class="modal-body">
                        <input type="hidden" id="role_id" name="id">
                        {{-- CSRF Token tidak perlu di form, sudah dihandle oleh ajaxSetup --}}
                        <div class="mb-3">
                            <label for="display_name" class="form-label">Display Name</label>
                            <input type="text" class="form-control" id="display_name" name="display_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="name" class="form-label">Name (Slug)</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="form-text">Gunakan huruf kecil tanpa spasi (contoh: admin-area, blog-editor).</div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="badge_color" class="form-label">Badge Color</label>
                            <select class="form-select" id="badge_color" name="badge_color" required>
                                <option value="primary">Primary (Blue)</option>
                                <option value="secondary">Secondary (Gray)</option>
                                <option value="success">Success (Green)</option>
                                <option value="danger">Danger (Red)</option>
                                <option value="warning">Warning (Yellow)</option>
                                <option value="info">Info (Cyan)</option>
                                <option value="dark">Dark</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="saveBtn">Save Role</button>
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
    /* Style untuk switch status */
    .form-check-input:checked {
        background-color: #198754;
        border-color: #198754;
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
        INDEX: '/api/roles',
        STORE: '/api/roles',
        SHOW: id => `/api/roles/${id}`,
        UPDATE: id => `/api/roles/${id}`,
        DESTROY: id => `/api/roles/${id}`,
    };

    const roleModal = new bootstrap.Modal($('#roleModal')[0]);
    const $rolesTableBody = $('#roles-table-body');

    /**
     * Fungsi untuk mengambil dan menampilkan semua roles
     */
    function fetchRoles() {
        $rolesTableBody.html('<tr><td colspan="5" class="text-center">Loading...</td></tr>');
        $.get(API.INDEX)
            .done(res => {
                if (res.success) {
                    renderTable(res.data);
                } else {
                    $rolesTableBody.html('<tr><td colspan="5" class="text-center">Failed to load data.</td></tr>');
                }
            })
            .fail(xhr => {
                console.error('Error fetching roles:', xhr);
                $rolesTableBody.html('<tr><td colspan="5" class="text-center">Error loading data. Please try again.</td></tr>');
            });
    }

    /**
     * Fungsi untuk merender data roles ke dalam tabel
     * @param {Array} roles
     */
    function renderTable(roles) {
        $rolesTableBody.empty();
        if (!roles || roles.length === 0) {
            $rolesTableBody.html('<tr><td colspan="5" class="text-center">No roles found.</td></tr>');
            return;
        }

        roles.forEach(role => {
            const row = `
                <tr data-role-id="${role.id}">
                    <td>
                        <span class="badge bg-${role.badge_color}">${role.display_name}</span>
                    </td>
                    <td><code>${role.name}</code></td>
                    <td>${role.description || '-'}</td>
                    <td class="text-center">
                        <div class="form-check form-switch d-inline-block">
                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                   id="statusSwitch-${role.id}" ${role.is_active ? 'checked' : ''} data-id="${role.id}">
                        </div>
                    </td>
                    <td class="text-center action-buttons">
                        <button class="btn btn-sm btn-info edit-btn" data-id="${role.id}" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="${role.id}" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            $rolesTableBody.append(row);
        });
    }

    // Tampilkan modal untuk "Add New Role"
    $('button[data-bs-target="#roleModal"]').on('click', () => {
        $('#roleForm')[0].reset();
        $('#role_id').val('');
        $('#modalTitle').text('Add New Role');
        roleModal.show();
    });

    // Handle form submission (Create & Update)
    $('#roleForm').on('submit', function (e) {
        e.preventDefault();
        const roleId = $('#role_id').val();
        const url = roleId ? API.UPDATE(roleId) : API.STORE;
        const method = roleId ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            method: method,
            data: $(this).serialize(),
            success: function(res) {
                roleModal.hide();
                Swal.fire('Success!', res.message, 'success');
                fetchRoles(); // Refresh table
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
    $rolesTableBody.on('click', '.edit-btn', function () {
        const roleId = $(this).data('id');
        $.get(API.SHOW(roleId))
            .done(res => {
                if(res.success){
                    const role = res.data;
                    $('#role_id').val(role.id);
                    $('#display_name').val(role.display_name);
                    $('#name').val(role.name);
                    $('#description').val(role.description);
                    $('#badge_color').val(role.badge_color);
                    $('#modalTitle').text('Edit Role');
                    roleModal.show();
                }
            })
            .fail(xhr => Swal.fire('Error', 'Failed to fetch role details.', 'error'));
    });

    // Event delegation for Delete button
    $rolesTableBody.on('click', '.delete-btn', function () {
        const roleId = $(this).data('id');
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
                    url: API.DESTROY(roleId),
                    method: 'DELETE',
                    success: function(res) {
                        Swal.fire('Deleted!', res.message, 'success');
                        fetchRoles(); // Refresh table
                    },
                    error: function(xhr) {
                         const msg = xhr.responseJSON?.message || 'Could not delete role.';
                         Swal.fire('Error!', msg, 'error');
                    }
                });
            }
        });
    });

    // =======================================================
    // KODE YANG DIPERBAIKI ADA DI BLOK INI
    // =======================================================
    // Event delegation for Status Toggle
    $rolesTableBody.on('change', '.status-toggle', function () {
        const roleId = $(this).data('id');
        // Mengubah nilai boolean (true/false) menjadi integer (1/0)
        const is_active = $(this).is(':checked') ? 1 : 0;
        const self = this;

        $.ajax({
            url: API.UPDATE(roleId),
            method: 'PUT',
            // Data yang dikirim sekarang adalah { is_active: 1 } atau { is_active: 0 }
            data: { is_active: is_active },
            success: function(res) {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                Toast.fire({ icon: 'success', title: res.message || 'Status updated successfully' });
            },
            error: function(xhr) {
                Swal.fire('Error', 'Failed to update status.', 'error');
                // Kembalikan posisi toggle jika update gagal
                $(self).prop('checked', !$(self).is(':checked'));
            }
        });
    });

    // Initial data load
    fetchRoles();
});
</script>
@endpush
