$(document).ready(function () {
    // API URLs
    const API = {
        MENUS: '/api/menu',
        MENU_PERMISSIONS: (id) => `/api/menu/${id}/permissions`,
        ROLE_ACCESS: (id) => `/api/menu/${id}/role-access`,
        USER_OVERRIDE: (id) => `/api/menu/${id}/user-override`,
        DELETE_USER_OVERRIDE: (menuId, userId) => `/api/menu/${menuId}/user-override/${userId}`,
        ROLES: '/api/roles',
        SEARCH_USERS: '/api/users/search'
    };

    let selectedMenuId = null;
    let allRoles = [];

    // ==========================================
    // TAB SWITCH HANDLER
    // ==========================================

    $('#permissions-tab').on('shown.bs.tab', function () {
        if ($('#permission-menu-list').children('.list-group-item').length === 0) {
            loadMenusForPermissions();
        }
        loadAllRoles();
    });

    // ==========================================
    // LOAD MENUS FOR PERMISSIONS
    // ==========================================

    function loadMenusForPermissions() {
        const container = $('#permission-menu-list');
        container.html(`
            <div class="text-center p-4">
                <div class="spinner-border text-primary spinner-border-sm" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted small">Loading menus...</p>
            </div>
        `);

        $.ajax({
            url: API.MENUS,
            method: 'GET',
            success: function (response) {
                container.empty();
                if (response.success && response.data && response.data.length > 0) {
                    renderMenuListForPermissions(response.data, container);
                } else {
                    container.html('<p class="text-center text-muted p-4">Belum ada menu.</p>');
                }
            },
            error: function (xhr) {
                const error = xhr.responseJSON ? xhr.responseJSON.message : "Gagal memuat data menu.";
                container.html(`<p class="text-danger text-center p-4">${error}</p>`);
                console.error("Error fetching menus:", xhr);
            }
        });
    }

    function renderMenuListForPermissions(items, container, level = 0) {
        items.forEach(item => {
            const indent = level > 0 ? `style="padding-left: ${level * 20 + 15}px"` : '';
            const statusBadge = item.is_active
                ? '<span class="badge bg-success badge-sm ms-2">Active</span>'
                : '<span class="badge bg-secondary badge-sm ms-2">Inactive</span>';

            const menuItemHTML = `
                <a href="#" class="list-group-item list-group-item-action permission-menu-item"
                   data-menu-id="${item.id}" ${indent}>
                    <div class="d-flex align-items-center">
                        <i class="${item.icon || 'fas fa-circle'} me-2"></i>
                        <span class="flex-grow-1">${item.title}</span>
                        ${statusBadge}
                    </div>
                </a>
            `;

            container.append(menuItemHTML);

            if (item.children && item.children.length > 0) {
                renderMenuListForPermissions(item.children, container, level + 1);
            }
        });
    }

    // Handle menu item click
    $(document).on('click', '.permission-menu-item', function (e) {
        e.preventDefault();
        $('.permission-menu-item').removeClass('active');
        $(this).addClass('active');

        selectedMenuId = $(this).data('menu-id');
        loadMenuPermissions(selectedMenuId);
    });

    // ==========================================
    // LOAD ROLES
    // ==========================================

    function loadAllRoles() {
        if (allRoles.length > 0) return; // Already loaded

        $.ajax({
            url: API.ROLES,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    allRoles = response.data;
                }
            },
            error: function (xhr) {
                console.error("Error fetching roles:", xhr);
            }
        });
    }

    // ==========================================
    // LOAD MENU PERMISSIONS
    // ==========================================

    function loadMenuPermissions(menuId) {
        const roleContent = $('#role-access-content');
        const userContent = $('#user-override-content');

        roleContent.html('<div class="text-center p-4"><div class="spinner-border spinner-border-sm"></div></div>');
        userContent.html('<div class="text-center p-4"><div class="spinner-border spinner-border-sm"></div></div>');

        $.ajax({
            url: API.MENU_PERMISSIONS(menuId),
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    renderRoleAccess(response.data.menu, response.data.roles);
                    renderUserOverrides(response.data.menu, response.data.user_overrides);
                }
            },
            error: function (xhr) {
                const error = xhr.responseJSON ? xhr.responseJSON.message : "Failed to load permissions.";
                roleContent.html(`<p class="text-danger text-center">${error}</p>`);
                userContent.html(`<p class="text-danger text-center">${error}</p>`);
                console.error("Error fetching menu permissions:", xhr);
            }
        });
    }

    // ==========================================
    // RENDER ROLE ACCESS
    // ==========================================

    function renderRoleAccess(menu, assignedRoles) {
        const roleContent = $('#role-access-content');
        const assignedRoleIds = assignedRoles.map(r => r.id);

        let html = `
            <div class="mb-3">
                <h6 class="fw-bold mb-3">
                    <i class="${menu.icon} me-2"></i>${menu.title}
                </h6>
            </div>
            <form id="role-access-form">
        `;

        allRoles.forEach(role => {
            const checked = assignedRoleIds.includes(role.id) ? 'checked' : '';
            html += `
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" value="${role.id}"
                           id="role-${role.id}" ${checked}>
                    <label class="form-check-label" for="role-${role.id}">
                        ${role.name}
                    </label>
                </div>
            `;
        });

        html += `
            </form>
            <div class="mt-4">
                <button type="button" class="btn btn-primary" id="save-role-access">
                    <i class="fas fa-save me-1"></i> Simpan Role Access
                </button>
            </div>
        `;

        roleContent.html(html);
    }

    // Handle save role access
    $(document).on('click', '#save-role-access', function () {
        const btn = $(this);
        const originalHTML = btn.html();
        const roleIds = [];

        $('#role-access-form input[type="checkbox"]:checked').each(function () {
            roleIds.push(parseInt($(this).val()));
        });

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...');

        $.ajax({
            url: API.ROLE_ACCESS(selectedMenuId),
            method: 'POST',
            data: { role_ids: roleIds },
            success: function (response) {
                showSuccess(response.message);
                btn.removeClass('btn-primary').addClass('btn-success')
                   .html('<i class="fas fa-check-circle me-1"></i> Tersimpan!');
            },
            error: function (xhr) {
                handleError(xhr);
                btn.removeClass('btn-primary').addClass('btn-danger')
                   .html('<i class="fas fa-times-circle me-1"></i> Gagal!');
            },
            complete: function () {
                setTimeout(() => {
                    btn.prop('disabled', false)
                       .removeClass('btn-success btn-danger')
                       .addClass('btn-primary')
                       .html(originalHTML);
                }, 2000);
            }
        });
    });

    // ==========================================
    // RENDER USER OVERRIDES
    // ==========================================

    function renderUserOverrides(menu, overrides) {
        const userContent = $('#user-override-content');

        let html = `
            <div class="mb-3">
                <h6 class="fw-bold mb-3">
                    <i class="${menu.icon} me-2"></i>${menu.title}
                </h6>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold small">Tambah User Override</label>
                <div class="input-group">
                    <select class="form-select" id="user-override-select">
                        <option value="">Pilih user...</option>
                    </select>
                    <select class="form-select" id="user-override-type" style="max-width: 150px;">
                        <option value="grant">Grant Access</option>
                        <option value="revoke">Revoke Access</option>
                    </select>
                    <button class="btn btn-primary" type="button" id="add-user-override">
                        <i class="fas fa-plus"></i> Tambah
                    </button>
                </div>
            </div>
        `;

        if (overrides.length > 0) {
            html += `
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Access Type</th>
                                <th width="100">Action</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            overrides.forEach(override => {
                const accessBadge = override.access_type === 'grant'
                    ? '<span class="badge bg-success">Grant</span>'
                    : '<span class="badge bg-danger">Revoke</span>';

                html += `
                    <tr>
                        <td>${override.name}</td>
                        <td>${override.email}</td>
                        <td>${accessBadge}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-danger delete-user-override"
                                    data-user-id="${override.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
            `;
        } else {
            html += '<p class="text-muted small"><i class="fas fa-info-circle me-1"></i>Belum ada user override untuk menu ini.</p>';
        }

        userContent.html(html);

        // Initialize Select2 for user search
        initializeUserSelect();
    }

    // ==========================================
    // USER SEARCH SELECT2
    // ==========================================

    function initializeUserSelect() {
        $('#user-override-select').select2({
            placeholder: 'Cari user...',
            allowClear: true,
            ajax: {
                url: API.SEARCH_USERS,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (response) {
                    if (response.success) {
                        return {
                            results: response.data.map(user => ({
                                id: user.id,
                                text: `${user.name} (${user.email})`
                            }))
                        };
                    }
                    return { results: [] };
                },
                cache: true
            },
            minimumInputLength: 2
        });
    }

    // Handle add user override
    $(document).on('click', '#add-user-override', function () {
        const userId = $('#user-override-select').val();
        const accessType = $('#user-override-type').val();

        if (!userId) {
            showError('Pilih user terlebih dahulu.');
            return;
        }

        const btn = $(this);
        const originalHTML = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: API.USER_OVERRIDE(selectedMenuId),
            method: 'POST',
            data: {
                user_id: userId,
                access_type: accessType
            },
            success: function (response) {
                showSuccess(response.message);
                loadMenuPermissions(selectedMenuId); // Reload
            },
            error: function (xhr) {
                handleError(xhr);
            },
            complete: function () {
                btn.prop('disabled', false).html(originalHTML);
            }
        });
    });

    // Handle delete user override
    $(document).on('click', '.delete-user-override', function () {
        if (!confirm('Hapus user override ini?')) return;

        const userId = $(this).data('user-id');
        const btn = $(this);
        const originalHTML = btn.html();

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: API.DELETE_USER_OVERRIDE(selectedMenuId, userId),
            method: 'DELETE',
            success: function (response) {
                showSuccess(response.message);
                loadMenuPermissions(selectedMenuId); // Reload
            },
            error: function (xhr) {
                handleError(xhr);
                btn.prop('disabled', false).html(originalHTML);
            }
        });
    });

    // ==========================================
    // HELPER FUNCTIONS
    // ==========================================

    function showSuccess(message) {
        alert(message); // Replace dengan notifikasi library (SweetAlert, Toastr, dll)
    }

    function showError(message) {
        alert('Error: ' + message);
    }

    function handleError(xhr) {
        const errors = xhr.responseJSON ? xhr.responseJSON.errors : null;
        let errorMsg = xhr.responseJSON ? xhr.responseJSON.message : 'An error occurred';
        if (errors) {
            errorMsg += "\n" + Object.values(errors).map(e => `- ${e[0]}`).join("\n");
        }
        showError(errorMsg);
    }
});
