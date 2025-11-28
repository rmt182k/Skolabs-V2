$(document).ready(function () {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    const API = {
        USERS: '/api/users',
        SHOW_USER: userId => `/api/users/${userId}`,
        ROLES: '/api/roles',
        USERS_BY_ROLE: roleId => `/api/roles/${roleId}/users`,
        UPDATE_STATUS: userId => `/api/users/${userId}/update-status`,
    };

    const userModal = new bootstrap.Modal($('#userModal')[0]);
    let initializedDataTables = {};

    flatpickr("#date_of_birth", {
        dateFormat: "Y-m-d",
        allowInput: true
    });

    function init() {
        loadAllRolesAndBuildUI();
        initializeDataTable('#all-users-table', API.USERS);
    }

    function loadAllRolesAndBuildUI() {
        $.get(API.ROLES).done(res => {
            if (res.success) {
                renderRoleTabs(res.data);
                renderRoleCheckboxes(res.data, '#modal-roles-checkbox-container');
            }
        });
    }

    function renderRoleTabs(roles) {
        const $tabsContainer = $('#user-role-tabs');
        const $contentContainer = $('#user-role-tabs-content');

        $('.role-generated').remove();

        roles.forEach(role => {
            const tableId = `role-table-${role.id}`;
            $tabsContainer.append(`
                <li class="nav-item role-generated" role="presentation">
                    <button class="nav-link" id="role-tab-${role.id}" data-bs-toggle="tab"
                        data-bs-target="#role-pane-${role.id}" type="button" role="tab"
                        data-role-id="${role.id}" data-table-id="#${tableId}">
                        <span class="badge rounded-pill bg-${role.badge_color} me-2">&nbsp;</span>
                        ${role.display_name}
                    </button>
                </li>
            `);
            // Tambahkan class 'role-generated' ke pane agar mudah diseleksi
            $contentContainer.append(`
                <div class="tab-pane fade role-generated" id="role-pane-${role.id}" role="tabpanel">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3">
                             <h5 class="mb-0 fw-semibold">Users with Role: ${role.display_name}</h5>
                             <small class="text-muted">List of all users assigned to this role</small>
                        </div>
                        <div class="card-body">
                             <div class="table-responsive">
                                 <table class="table table-hover align-middle" id="${tableId}" style="width:100%">
                                     <thead>
                                         <tr>
                                             <th>User</th><th>Email</th><th>Roles</th>
                                             <th class="text-center">Status</th><th class="text-center">Actions</th>
                                         </tr>
                                     </thead>
                                     <tbody></tbody>
                                 </table>
                             </div>
                        </div>
                    </div>
                </div>
            `);
        });
    }

    function renderRoleCheckboxes(roles, containerSelector) {
        const $checkboxContainer = $(containerSelector);
        $checkboxContainer.empty();
        roles.forEach(role => {
            $checkboxContainer.append(`
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="roles[]" value="${role.id}" id="modal-role-check-${role.id}">
                    <label class="form-check-label" for="modal-role-check-${role.id}">${role.display_name}</label>
                </div>
            `);
        });
    }

    function initializeDataTable(tableSelector, ajaxUrl) {
        if ($.fn.DataTable.isDataTable(tableSelector)) {
            $(tableSelector).DataTable().destroy();
        }
        const dataTable = $(tableSelector).DataTable({
            processing: true,
            serverSide: false,
            ajax: { url: ajaxUrl, dataSrc: 'data' },
            columns: [
                { data: 'name', name: 'name' },
                { data: 'email', name: 'email' },
                { data: 'roles', name: 'roles', orderable: false, searchable: false },
                { data: 'is_active', name: 'is_active', orderable: false, searchable: false },
                { data: null, name: 'action', orderable: false, searchable: false }
            ],
            columnDefs: [
                {
                    targets: 0,
                    render: (data, type, row) => `
                        <div class="d-flex align-items-center">
                            <div class="user-avatar me-3">${row.name.charAt(0).toUpperCase()}</div>
                            <div>
                                <div class="fw-bold">${row.name}</div>
                                <div class="text-muted small">${row.email_verified_at ? 'Verified' : 'Not Verified'}</div>
                            </div>
                        </div>`
                },
                {
                    targets: 2,
                    render: (data, type, row) => {
                        if (!row.roles || row.roles.length === 0) return '<span class="text-muted small">No roles</span>';
                        return row.roles.map(role => `<span class="badge bg-${role.badge_color} me-1">${role.display_name}</span>`).join('');
                    }
                },
                {
                    targets: 3,
                    className: 'text-center',
                    render: (data, type, row) => `
                        <div class="form-check form-switch d-inline-block">
                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                   ${row.is_active ? 'checked' : ''} data-id="${row.id}">
                        </div>`
                },
                {
                    targets: 4,
                    className: 'text-center',
                    render: (data, type, row) => `
                        <button class="btn btn-sm btn-outline-info edit-btn" data-id="${row.id}" title="Edit User">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${row.id}" data-name="${row.name}" title="Delete User">
                            <i class="fas fa-trash-alt"></i>
                        </button>`
                }
            ]
        });
        initializedDataTables[tableSelector] = dataTable;
    }

    // --- HELPER FUNCTIONS UNTUK AKSI TOMBOL ---

    function handleEditClick(element) {
        const userId = $(element).data('id');
        $.get(API.SHOW_USER(userId)).done(res => {
            if (res.success) {
                const user = res.data;
                $('#userForm')[0].reset();
                $('#user_id').val(user.id);
                $('#name').val(user.name);
                $('#email').val(user.email);
                $('#password').prop('required', false);
                $('#passwordHelp').show();
                $('#verify-now-container').hide();

                // Isi data user_details
                $('#identity_number').val(user.identity_number);
                $('#date_of_birth').val(user.date_of_birth);
                $('#gender').val(user.gender);
                $('#phone_number').val(user.phone_number);
                $('#address').val(user.address);

                const userRoleIds = user.roles.map(r => r.id);
                $('#modal-roles-checkbox-container .form-check-input').each(function () {
                    $(this).prop('checked', userRoleIds.includes(parseInt($(this).val())));
                });

                $('#userModalLabel').text(`Edit User: ${user.name}`);
                userModal.show();
            }
        });
    }

    function handleDeleteClick(element) {
        const userId = $(element).data('id');
        const userName = $(element).data('name');

        Swal.fire({
            title: 'Are you sure?',
            html: `You are about to delete <strong>${userName}</strong>. This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: API.SHOW_USER(userId),
                    method: 'DELETE',
                    success: (res) => {
                        Swal.fire('Deleted!', res.message, 'success');
                        for (const tableId in initializedDataTables) {
                            initializedDataTables[tableId].ajax.reload(null, false);
                        }
                    },
                    error: (xhr) => Swal.fire('Error!', 'Could not delete the user.', 'error')
                });
            }
        });
    }

    function handleStatusChange(element) {
        const userId = $(element).data('id');
        const is_active = $(element).is(':checked') ? 1 : 0;
        $.ajax({
            url: API.UPDATE_STATUS(userId),
            method: 'PUT',
            data: { is_active: is_active },
            error: (xhr) => {
                Swal.fire('Error', 'Failed to update status.', 'error');
                $(element).prop('checked', !is_active);
            }
        });
    }

    // --- EVENT LISTENERS ---

    // Listener Tombol "Add New User"
    $('#add-user-btn').on('click', function () {
        $('#userForm')[0].reset();
        $('#user_id').val('');
        $('#userModalLabel').text('Add New User');
        $('#password').prop('required', true);
        $('#passwordHelp').hide();
        $('#verify-now-container').show();
        userModal.show();
    });

    // PERBAIKAN: Listener untuk TAB STATIS "ALL USERS"
    // Kita menempel langsung ke #all-users-table
    $('#all-users-table').on('click', '.edit-btn', function () {
        handleEditClick(this);
    });
    $('#all-users-table').on('click', '.delete-btn', function () {
        handleDeleteClick(this);
    });
    $('#all-users-table').on('change', '.status-toggle', function () {
        handleStatusChange(this);
    });

    // PERBAIKAN: Listener untuk TAB DINAMIS "ROLES"
    // Kita mendelegasikan dari parent #user-role-tabs-content
    const $staticTabContent = $('#user-role-tabs-content');
    $staticTabContent.on('click', '.role-generated .edit-btn', function () {
        handleEditClick(this);
    });
    $staticTabContent.on('click', '.role-generated .delete-btn', function () {
        handleDeleteClick(this);
    });
    $staticTabContent.on('change', '.role-generated .status-toggle', function () {
        handleStatusChange(this);
    });


    // Listener Form Submit (Statis, tidak perlu diubah)
    $('#userForm').on('submit', function (e) {
        e.preventDefault();
        const userId = $('#user_id').val();
        let url = userId ? API.SHOW_USER(userId) : API.USERS;
        let method = userId ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            method: method,
            data: $(this).serialize(),
            success: (res) => {
                userModal.hide();
                Swal.fire('Success!', res.message, 'success');
                // Reload semua DataTables yang sudah diinisialisasi
                for (const tableId in initializedDataTables) {
                    initializedDataTables[tableId].ajax.reload(null, false);
                }
            },
            error: (xhr) => {
                // Error handling yang lebih baik
                let errorMsg = 'An error occurred. Please check your input.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMsg = '<ul>';
                    $.each(xhr.responseJSON.errors, function (key, value) {
                        errorMsg += `<li>${value[0]}</li>`;
                    });
                    errorMsg += '</ul>';
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                Swal.fire({
                    title: 'Error!',
                    html: errorMsg,
                    icon: 'error'
                });
            }
        });
    });

    // Listener Pindah Tab (Tidak perlu diubah)
    $('#user-role-tabs').on('shown.bs.tab', 'button[data-bs-toggle="tab"]', function () {
        const tableId = $(this).data('table-id');
        const roleId = $(this).data('role-id');
        if (tableId && !initializedDataTables[tableId]) {
            const apiUrl = API.USERS_BY_ROLE(roleId);
            initializeDataTable(tableId, apiUrl);
        }
    });

    init();
});
