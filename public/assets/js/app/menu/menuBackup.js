$(document).ready(function() {
    // Setup CSRF token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // API Endpoint URLs
    const API = {
        MENUS: '/api/menus',
        UPDATE_ORDER: '/api/menus/update-order',
        ACCESS_DETAILS: id => `/api/menus/${id}/access-details`,
        ROLE_PERMISSIONS: id => `/api/menus/${id}/role-permissions`,
        USER_MENU_OVERRIDE: id => `/api/menus/${id}/user-menu-overrides`,
        DELETE_USER_MENU_OVERRIDE: (menuId, userId) => `/api/menus/${menuId}/user-menu-overrides/${userId}`,
        USER_PERMISSION_OVERRIDE: id => `/api/menus/${id}/user-permission-overrides`,
        DELETE_USER_PERMISSION_OVERRIDE: (menuId, overrideId) => `/api/menus/${menuId}/user-permission-overrides/${overrideId}`,
        ROLES: '/api/roles',
        PERMISSIONS: '/api/permissions',
        SEARCH_USERS: '/api/users/search'
    };

    // Global state variables
    let allMenusData = [];
    let allRoles = [];
    let selectedMenuId = null;
    let availablePermissions = [];

    // Initialize Select2 components
    $('#menu-icon').select2({
        data: fontAwesomeIcons,
        placeholder: 'Select an icon',
        allowClear: true,
        templateResult: formatIcon,
        templateSelection: formatIconSelection
    });
    $('#menu-parent').select2({
        placeholder: '-- Root Menu (No parent) --',
        allowClear: true
    });

    // Load initial data
    loadAllMenus();

    $('#permissions-tab').on('shown.bs.tab', () => {
        if ($('#permission-menu-list .list-group-item').length === 0) {
            loadMenusForPermissions();
        }
        if (allRoles.length === 0) {
            loadAllRoles();
        }
        if (availablePermissions.length === 0) {
            loadAllPermissions();
        }
    });

    // ==========================================
    // MENU STRUCTURE (No Changes)
    // ==========================================
    function loadAllMenus() {
        const container = $('#menu-list');
        showLoading(container, 'Loading Menu Structure...');
        $.get(API.MENUS)
            .done(res => {
                container.empty();
                if (res.success && res.data?.length) {
                    allMenusData = res.data;
                    renderMenuItems(res.data, container);
                    updateParentDropdown();
                    initializeSortables();
                } else {
                    container.html('<p class="text-center text-muted py-5">No menu items found. Please add one.</p>');
                }
            })
            .fail(xhr => handleError(xhr, container));
    }

    function renderMenuItems(items, container) {
        items.forEach(item => {
            const statusBadge = item.is_active ?
                '<span class="badge bg-success badge-sm ms-2">Active</span>' :
                '<span class="badge bg-secondary badge-sm ms-2">Inactive</span>';

            const itemHtml = `
                <div class="menu-item ${item.is_active ? '' : 'menu-inactive'}" data-id="${item.id}">
                    <div class="menu-item-content">
                        <i class="fas fa-grip-vertical handle me-2"></i>
                        <i class="${item.icon || 'fas fa-circle'} menu-icon me-2"></i>
                        <span class="menu-title flex-grow-1">${item.title}${statusBadge}</span>
                        <span class="menu-url text-muted small">${item.url || '#'}</span>
                        <div class="menu-actions ms-2">
                            <button class="btn btn-sm btn-outline-primary edit-btn" title="Edit"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-outline-danger delete-btn" title="Delete"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>`;

            const menuItem = $(itemHtml);
            container.append(menuItem);
            attachActionHandlers(menuItem, item);

            if (item.children?.length) {
                const childrenContainer = $('<div class="menu-children sortable-list"></div>');
                menuItem.append(childrenContainer);
                renderMenuItems(item.children, childrenContainer);
            }
        });
    }

    function initializeSortables() {
        document.querySelectorAll('.sortable-list').forEach(list => {
            new Sortable(list, {
                group: 'nested-menu',
                animation: 200,
                handle: '.handle',
                fallbackOnBody: true,
                swapThreshold: 0.65
            });
        });
    }

    function updateParentDropdown() {
        const parentDropdown = $('#menu-parent');
        const currentMenuId = $('#menu-id').val();
        parentDropdown.empty().append('<option value="">-- Root Menu --</option>');

        function appendOptions(items, level = 0) {
            items.forEach(item => {
                if (item.id == currentMenuId) return;
                const prefix = level > 0 ? `${'—'.repeat(level)} ` : '';
                const title = `${prefix}${item.title}${!item.is_active ? ' (Inactive)' : ''}`;
                parentDropdown.append(`<option value="${item.id}">${title}</option>`);
                if (item.children?.length) {
                    appendOptions(item.children, level + 1);
                }
            });
        }
        appendOptions(allMenusData);
    }

    // ==========================================
    // PERMISSIONS & ACCESS CONTROL
    // ==========================================
    function loadMenusForPermissions() {
        const container = $('#permission-menu-list');
        showLoading(container, 'Loading menus...', true);
        $.get(API.MENUS)
            .done(res => {
                container.empty();
                if (res.success && res.data?.length) {
                    renderPermissionMenuList(res.data, container);
                } else {
                    container.html('<p class="text-center text-muted p-4">No menus available.</p>');
                }
            })
            .fail(xhr => handleError(xhr, container));
    }

    function renderPermissionMenuList(items, container, level = 0) {
        items.forEach(item => {
            const statusBadge = item.is_active ?
                '<span class="badge bg-success badge-sm ms-2">Active</span>' :
                '<span class="badge bg-secondary badge-sm ms-2">Inactive</span>';

            const itemHtml = `
                <a href="#" class="list-group-item list-group-item-action permission-menu-item" data-menu-id="${item.id}" style="padding-left: ${level * 20 + 15}px">
                    <div class="d-flex align-items-center">
                        <i class="${item.icon || 'fas fa-circle'} me-2"></i>
                        <span class="flex-grow-1">${item.title}</span>
                        ${statusBadge}
                    </div>
                </a>`;
            container.append(itemHtml);

            if (item.children?.length) {
                renderPermissionMenuList(item.children, container, level + 1);
            }
        });
    }

    function loadAllRoles() {
        if (allRoles.length) return;
        $.get(API.ROLES)
            .done(res => {
                if (res.success) allRoles = res.data;
            })
            .fail(xhr => console.error('Error fetching roles:', xhr));
    }

    function loadAllPermissions() {
        if (availablePermissions.length) return;
        $.get(API.PERMISSIONS)
            .done(res => {
                if (res.success) availablePermissions = res.data;
            })
            .fail(xhr => console.error('Error fetching permissions:', xhr));
    }

    function loadAccessDetails(menuId) {
        const roleContent = $('#role-permissions-content');
        const userContent = $('#user-override-content');
        showLoading(roleContent, '', true);
        showLoading(userContent, '', true);

        $.get(API.ACCESS_DETAILS(menuId))
            .done(res => {
                if (res.success) {
                    renderRolePermissions(res.data.menu, res.data.roles_with_access, res.data.role_permissions);
                    renderUserOverrides(res.data.menu, res.data.user_menu_overrides, res.data.user_permission_overrides);
                }
            })
            .fail(xhr => {
                handleError(xhr, roleContent);
                handleError(xhr, userContent);
            });
    }

    function renderRolePermissions(menu, rolesWithAccess, assignedPermissions) {
        const container = $('#role-permissions-content');
        let permissionHeaders = '';
        availablePermissions.forEach(p => {
            permissionHeaders += `<th class="text-center text-capitalize">${p.name}</th>`;
        });

        let roleRows = '';
        allRoles.forEach(role => {
            const hasAccess = rolesWithAccess.includes(role.id);
            const permissions = assignedPermissions.find(x => x.role_id === role.id)?.permissions || [];
            const isDisabled = !hasAccess ? 'disabled' : '';

            let permissionCells = '';
            availablePermissions.forEach(perm => {
                const isChecked = hasAccess && permissions.includes(perm.name) ? 'checked' : '';
                permissionCells += `
                    <td class="text-center">
                        <div class="form-check d-inline-block">
                            <input class="form-check-input permission-checkbox" type="checkbox" value="${perm.name}" ${isChecked} ${isDisabled}>
                        </div>
                    </td>`;
            });

            roleRows += `
                <tr class="role-permission-row ${!hasAccess ? 'row-disabled' : ''}" data-role-id="${role.id}">
                    <td class="text-center">
                        <div class="form-check d-inline-block">
                            <input class="form-check-input role-access-checkbox" type="checkbox" ${hasAccess ? 'checked' : ''}>
                        </div>
                    </td>
                    <td>${role.name}</td>
                    ${permissionCells}
                </tr>`;
        });

        const tableHtml = `
            <div class="mb-3">
                <h6 class="fw-bold"><i class="${menu.icon || 'fas fa-circle'} me-2"></i>${menu.title}</h6>
                <p class="text-muted small mb-0">Check "Access" to enable a role for this menu, then define its permissions.</p>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:80px">Access</th>
                            <th>Role</th>
                            ${permissionHeaders}
                        </tr>
                    </thead>
                    <tbody>${roleRows}</tbody>
                </table>
            </div>
            <div class="mt-4">
                <button type="button" class="btn btn-primary" id="save-role-permissions-btn">
                    <i class="fas fa-save me-2"></i> Save Role Permissions
                </button>
            </div>`;
        container.html(tableHtml);
    }

    function renderUserOverrides(menu, menuOverrides, permissionOverrides) {
        const container = $('#user-override-content');
        let menuOverrideRows = '';
        menuOverrides.forEach(o => {
            menuOverrideRows += `
                <tr>
                    <td>${o.name}</td>
                    <td>${o.email}</td>
                    <td>${o.access_type === 'grant' ? '<span class="badge bg-success">Grant</span>' : '<span class="badge bg-danger">Revoke</span>'}</td>
                    <td><button class="btn btn-sm btn-outline-danger delete-user-menu-override-btn" data-user-id="${o.id}"><i class="fas fa-trash"></i></button></td>
                </tr>`;
        });

        let permissionOverrideRows = '';
        permissionOverrides.forEach(o => {
            permissionOverrideRows += `
                <tr>
                    <td>${o.user_name}</td>
                    <td>${o.permission_name}</td>
                    <td>${o.type === 'grant' ? '<span class="badge bg-success">Grant</span>' : '<span class="badge bg-danger">Revoke</span>'}</td>
                    <td><button class="btn btn-sm btn-outline-danger delete-user-permission-override-btn" data-override-id="${o.id}"><i class="fas fa-trash"></i></button></td>
                </tr>`;
        });

        let permissionOptions = '';
        availablePermissions.forEach(p => {
            permissionOptions += `<option value="${p.id}">${p.name}</option>`;
        });

        const contentHtml = `
            <div class="mb-3">
                <h6 class="fw-bold"><i class="${menu.icon || 'fas fa-circle'} me-2"></i>${menu.title}</h6>
            </div>
            <div class="mb-4">
                <h5>Menu Access Overrides</h5>
                <div class="input-group">
                    <select class="form-select user-search-select"></select>
                    <select class="form-select" id="user-menu-override-type" style="max-width:150px">
                        <option value="grant">Grant Access</option>
                        <option value="revoke">Revoke Access</option>
                    </select>
                    <button class="btn btn-primary" id="add-user-menu-override-btn"><i class="fas fa-plus"></i> Add</button>
                </div>
            </div>
            ${menuOverrides.length ? `
                <div class="table-responsive mb-5">
                    <table class="table table-hover table-sm">
                        <thead><tr><th>User</th><th>Email</th><th>Access Type</th><th width="80">Action</th></tr></thead>
                        <tbody>${menuOverrideRows}</tbody>
                    </table>
                </div>` : '<p class="text-muted small mb-5">No menu access overrides.</p>'}

            <h5>Permission Overrides</h5>
            <div class="card bg-light border-0">
                <div class="card-body">
                    <form id="add-permission-override-form" class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small">User</label>
                            <select class="form-select form-select-sm user-search-select" name="user_id" required></select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Permission</label>
                            <select class="form-select form-select-sm" name="permission_id" required>${permissionOptions}</select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Type</label>
                            <select class="form-select form-select-sm" name="type" required>
                                <option value="grant">Grant</option>
                                <option value="revoke">Revoke</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-secondary btn-sm w-100">Add</button>
                        </div>
                    </form>
                </div>
            </div>
            ${permissionOverrides.length ? `
                <div class="table-responsive mt-3">
                    <table class="table table-hover table-sm">
                        <thead><tr><th>User</th><th>Permission</th><th>Type</th><th width="80">Action</th></tr></thead>
                        <tbody>${permissionOverrideRows}</tbody>
                    </table>
                </div>` : '<p class="text-muted small mt-3">No permission overrides.</p>'}`;

        container.html(contentHtml);
        initializeUserSelects();
    }

    function initializeUserSelects() {
        $('.user-search-select').select2({
            placeholder: 'Search user...',
            allowClear: true,
            ajax: {
                url: API.SEARCH_USERS,
                dataType: 'json',
                delay: 250,
                data: params => ({
                    q: params.term
                }),
                processResults: response => ({
                    results: response.success ? response.data.map(user => ({
                        id: user.id,
                        text: `${user.name} (${user.email})`
                    })) : []
                }),
                cache: true
            },
            minimumInputLength: 2
        });
    }

    // ==========================================
    // EVENT HANDLERS
    // ==========================================
    $('#menu-form').on('submit', e => {
        e.preventDefault();
        const mode = $('#form-mode').val();
        const id = $('#menu-id').val();
        mode === 'edit' ? handleUpdateMenu(id) : handleAddMenu();
    });

    $('#cancel-btn').on('click', () => {
        if ($('#form-mode').val() === 'edit') {
            if (confirm('Cancel editing?')) {
                resetMenuForm();
            }
        } else {
            resetMenuForm();
        }
    });

    $('#save-order-btn').on('click', handleSaveOrder);

    $(document).on('click', '.permission-menu-item', function(e) {
        e.preventDefault();
        $('.permission-menu-item').removeClass('active');
        $(this).addClass('active');
        selectedMenuId = $(this).data('menu-id');
        loadAccessDetails(selectedMenuId);
    });

    $(document).on('change', '.role-access-checkbox', function() {
        const row = $(this).closest('tr');
        const permissionCheckboxes = row.find('.permission-checkbox');
        if (this.checked) {
            row.removeClass('row-disabled');
            permissionCheckboxes.prop('disabled', false);
        } else {
            row.addClass('row-disabled');
            permissionCheckboxes.prop('disabled', true).prop('checked', false);
        }
    });

    $(document).on('click', '#save-role-permissions-btn', handleSaveAccessAndPermissions);
    $(document).on('click', '#add-user-menu-override-btn', handleAddUserMenuOverride);
    $(document).on('click', '.delete-user-menu-override-btn', function() {
        handleDeleteUserMenuOverride($(this).data('user-id'));
    });
    $(document).on('submit', '#add-permission-override-form', handleAddUserPermissionOverride);
    $(document).on('click', '.delete-user-permission-override-btn', function() {
        handleDeleteUserPermissionOverride($(this).data('override-id'));
    });

    function attachActionHandlers(element, data) {
        element.find('.edit-btn').on('click', () => populateFormForEdit(data));
        element.find('.delete-btn').on('click', () => handleDeleteMenu(data.id));
    }

    // ==========================================
    // HANDLER FUNCTIONS & AJAX CALLS
    // ==========================================
    function handleAddMenu() {
        const button = $('#submit-btn');
        const originalHtml = button.html();
        setButtonLoading(button, 'Adding...');
        $.post(API.MENUS, $('#menu-form').serialize())
            .done(res => {
                alert(res.message);
                resetMenuForm();
                loadAllMenus();
            })
            .fail(handleError)
            .always(() => setButtonNormal(button, originalHtml));
    }

    function handleUpdateMenu(id) {
        const button = $('#submit-btn');
        const originalHtml = button.html();
        setButtonLoading(button, 'Saving...');
        $.ajax({
                url: `${API.MENUS}/${id}`,
                method: 'PUT',
                data: $('#menu-form').serialize()
            })
            .done(res => {
                alert(res.message);
                resetMenuForm();
                loadAllMenus();
            })
            .fail(handleError)
            .always(() => setButtonNormal(button, originalHtml));
    }

    function handleDeleteMenu(id) {
        if (!confirm('Delete this menu item?')) return;
        $.ajax({
                url: `${API.MENUS}/${id}`,
                method: 'DELETE'
            })
            .done(res => {
                alert(res.message);
                loadAllMenus();
                resetMenuForm();
            })
            .fail(handleError);
    }

    function handleSaveOrder() {
        const button = $('#save-order-btn');
        const originalHtml = button.html();
        const menuOrder = serializeMenuOrder($('#menu-list')[0]);
        setButtonLoading(button, 'Saving...');

        $.post(API.UPDATE_ORDER, {
                menu: menuOrder
            })
            .done(res => {
                alert(res.message);
                setButtonState(button, 'Saved!', 'btn-success', 'fa-check-circle');
            })
            .fail(xhr => {
                handleError(xhr);
                setButtonState(button, 'Failed!', 'btn-danger', 'fa-times-circle');
            })
            .always(() => {
                setTimeout(() => setButtonNormal(button, originalHtml, 'btn-primary'), 2000);
            });
    }

    function handleSaveAccessAndPermissions() {
        const button = $('#save-role-permissions-btn');
        const originalHtml = button.html();
        const rolesWithAccess = [];
        const permissionsByRole = [];

        $('.role-permission-row').each(function() {
            const roleId = $(this).data('role-id');
            if ($(this).find('.role-access-checkbox').is(':checked')) {
                rolesWithAccess.push(roleId);
                const permissions = $(this).find('.permission-checkbox:checked').map((_, el) => $(el).val()).get();
                permissionsByRole.push({
                    role_id: roleId,
                    permissions: permissions
                });
            }
        });

        setButtonLoading(button, 'Saving...');
        $.ajax({
                url: API.ROLE_PERMISSIONS(selectedMenuId),
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    roles_with_access: rolesWithAccess,
                    permissions_by_role: permissionsByRole
                })
            })
            .done(res => {
                alert(res.message);
                setButtonState(button, 'Saved!', 'btn-success', 'fa-check-circle');
            })
            .fail(xhr => {
                handleError(xhr);
                setButtonState(button, 'Failed!', 'btn-danger', 'fa-times-circle');
            })
            .always(() => {
                setTimeout(() => setButtonNormal(button, originalHtml), 2000);
            });
    }

    function handleAddUserMenuOverride() {
        const userId = $('#user-override-content .input-group .user-search-select').val();
        const type = $('#user-menu-override-type').val();
        if (!userId) {
            return alert('Please select a user.');
        }

        const button = $('#add-user-menu-override-btn');
        const originalHtml = button.html();
        setButtonLoading(button, '');

        $.post(API.USER_MENU_OVERRIDE(selectedMenuId), {
                user_id: userId,
                access_type: type
            })
            .done(res => {
                alert(res.message);
                loadAccessDetails(selectedMenuId);
            })
            .fail(handleError)
            .always(() => setButtonNormal(button, originalHtml));
    }

    function handleDeleteUserMenuOverride(userId) {
        if (!confirm('Remove this menu override?')) return;
        $.ajax({
                url: API.DELETE_USER_MENU_OVERRIDE(selectedMenuId, userId),
                method: 'DELETE'
            })
            .done(res => {
                alert(res.message);
                loadAccessDetails(selectedMenuId);
            })
            .fail(handleError);
    }

    function handleAddUserPermissionOverride(e) {
        e.preventDefault();
        const form = $(this);
        const button = form.find('button');
        const originalHtml = button.html();
        setButtonLoading(button, '');

        $.post(API.USER_PERMISSION_OVERRIDE(selectedMenuId), form.serialize())
            .done(res => {
                alert(res.message);
                loadAccessDetails(selectedMenuId);
            })
            .fail(handleError)
            .always(() => setButtonNormal(button, originalHtml));
    }

    function handleDeleteUserPermissionOverride(overrideId) {
        if (!confirm('Remove this permission override?')) return;
        $.ajax({
                url: API.DELETE_USER_PERMISSION_OVERRIDE(selectedMenuId, overrideId),
                method: 'DELETE'
            })
            .done(res => {
                alert(res.message);
                loadAccessDetails(selectedMenuId);
            })
            .fail(handleError);
    }

    // ==========================================
    // UI & HELPER FUNCTIONS
    // ==========================================
    function populateFormForEdit(data) {
        resetMenuForm();
        $('#form-mode').val('edit');
        $('#menu-id').val(data.id);
        $('#form-title').html('<i class="fas fa-edit me-2"></i>Edit Menu');
        $('#form-subtitle').text('Update menu item details.');
        $('#menu-title').val(data.title);
        $('#menu-url').val(data.url || '');
        $('#menu-status').val(data.is_active ? '1' : '0');
        if (data.icon) {
            $('#menu-icon').val(data.icon).trigger('change');
            $('#icon-preview').attr('class', data.icon);
        }
        updateParentDropdown();
        $('#menu-parent').val(data.parent_id || '').trigger('change');
        $('#submit-text').text('Update Menu');
        $('#submit-btn i').attr('class', 'fas fa-save me-2');
        $('#cancel-text').text('Cancel');
        $('html, body').animate({
            scrollTop: $('#menu-form').offset().top - 100
        }, 300);
    }

    function resetMenuForm() {
        $('#menu-form')[0].reset();
        $('#form-mode').val('add');
        $('#menu-id').val('');
        $('#menu-icon').val(null).trigger('change');
        $('#menu-parent').val('').trigger('change');
        $('#icon-preview').attr('class', 'fas fa-home');
        $('#menu-status').val('1');
        $('#form-title').html('<i class="fas fa-plus-circle me-2"></i>Add New Menu');
        $('#form-subtitle').text('New items added at the bottom.');
        $('#submit-text').text('Add Menu');
        $('#submit-btn i').attr('class', 'fas fa-plus-circle me-2');
        $('#cancel-text').text('Clear');
        updateParentDropdown();
    }

    function serializeMenuOrder(container) {
        const items = [];
        $(container).children('.menu-item').each(function() {
            const menuItem = $(this);
            const childrenContainer = menuItem.children('.menu-children');
            items.push({
                id: menuItem.data('id'),
                children: childrenContainer.length ? serializeMenuOrder(childrenContainer[0]) : []
            });
        });
        return items;
    }

    function formatIcon(icon) {
        if (!icon.id) return icon.text;
        return $(`<span><i class="${icon.id} me-2"></i> ${icon.text}</span>`);
    }

    function formatIconSelection(icon) {
        if (!icon.id) return icon.text;
        return $(`<span><i class="${icon.id}"></i> ${icon.text}</span>`);
    }

    $('#menu-icon').on('select2:select', e => {
        $('#icon-preview').attr('class', e.params.data.id || 'fas fa-home');
    });

    function showLoading(container, text, small = false) {
        const spinnerClass = small ? 'spinner-border-sm' : '';
        const textHtml = text ? `<p class="mt-2 text-muted small">${text}</p>` : '';
        const loaderHtml = `
            <div class="text-center p-4">
                <div class="spinner-border text-primary ${spinnerClass}" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                ${textHtml}
            </div>`;
        container.html(loaderHtml);
    }

    function setButtonLoading(button, text) {
        const iconHtml = '<i class="fas fa-spinner fa-spin me-2"></i>';
        const buttonText = text ? `${iconHtml} ${text}` : iconHtml.replace(' me-2', '');
        button.prop('disabled', true).html(buttonText);
    }

    function setButtonNormal(button, originalHtml, newClass = 'btn-primary') {
        button.prop('disabled', false)
            .html(originalHtml)
            .removeClass('btn-success btn-danger')
            .addClass(newClass);
    }

    function setButtonState(button, text, btnClass, iconClass) {
        button.removeClass('btn-primary btn-success btn-danger')
            .addClass(btnClass)
            .html(`<i class="fas ${iconClass} me-2"></i> ${text}`);
    }

    function handleError(xhr, container = null) {
        let errorMessage = 'An unexpected error occurred.';
        if (xhr.responseJSON) {
            errorMessage = xhr.responseJSON.message || errorMessage;
            if (xhr.responseJSON.errors) {
                const errors = Object.values(xhr.responseJSON.errors).map(err => `- ${err[0]}`).join('\n');
                errorMessage += `\n${errors}`;
            }
        } else if (xhr.status === 404) {
            errorMessage = 'Error: API endpoint not found (404). Check your routes.';
        }

        if (container) {
            const errorHtml = `<div class="text-center p-4 text-danger"><strong>Error:</strong><br>${errorMessage.replace(/\n/g, '<br>')}</div>`;
            container.html(errorHtml);
        } else {
            alert(errorMessage);
        }
        console.error('AJAX Error:', xhr);
    }
});
