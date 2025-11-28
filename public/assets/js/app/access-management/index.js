$(document).ready(function () {
    // =================================================================
    // SETUP GLOBAL & INISIALISASI
    // =================================================================

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    let allPermissions = []; // Cache untuk menyimpan semua permission

    // =================================================================
    // FUNGSI LOAD DATA
    // =================================================================

    function loadRolesForDropdown() {
        $.get('/api/roles', function (data) {
            const roleSelect = $('#role-select-for-menus');
            roleSelect.empty().append('<option value="">Pilih Role untuk dikonfigurasi...</option>');
            data.roles.forEach(role => {
                roleSelect.append(`<option value="${role.id}">${role.name}</option>`);
            });
        }).fail(err => console.error('Gagal memuat roles:', err));
    }

    function cacheAllPermissions() {
        $.get('/api/permissions', function (data) {
            if (data.permissions) {
                allPermissions = data.permissions;
            }
        });
    }

    // =================================================================
    // LOGIKA UTAMA AKSES MENU
    // =================================================================

    function buildMenuItemRow(item, level) {
        let permissionOptions = '<option value="">-- Tidak Perlu Permission --</option>';
        allPermissions.forEach(p => {
            const isSelected = p.id == item.required_permission_id ? 'selected' : '';
            permissionOptions += `<option value="${p.id}" ${isSelected}>${p.name}</option>`;
        });

        const levelClass = level === 1 ? 'is-child' : (level > 1 ? 'is-grandchild' : '');

        return `
            <div class="menu-item-row ${levelClass}" data-menu-id="${item.id}">
                <div class="menu-item-name">
                    <i class="${item.icon || 'bi bi-dash'} me-2"></i>
                    <span>${item.name}</span>
                </div>
                <div class="menu-item-permission">
                    <select class="form-select form-select-sm" style="width: 250px;">
                        ${permissionOptions}
                    </select>
                </div>
            </div>
        `;
    }

    function buildMenuTree(menus, parentId = null, level = 0) {
        let html = '';
        const children = menus.filter(item => item.parent_id == parentId);
        children.forEach(item => {
            html += buildMenuItemRow(item, level);
            html += buildMenuTree(menus, item.id, level + 1);
        });
        return html;
    }

    $('#role-select-for-menus').on('change', function () {
        const roleId = $(this).val();
        const container = $('#menu-access-container');
        const saveButton = $('#save-menu-access-btn');

        if (!roleId) {
            container.html('<p class="text-muted">Silakan pilih role terlebih dahulu.</p>');
            saveButton.hide();
            return;
        }

        container.html('<p class="text-muted">Memuat struktur menu...</p>');

        $.get('/api/menus', function (data) {
            if (data.menus && data.menus.length > 0) {
                const menuHtml = buildMenuTree(data.menus);
                container.html(menuHtml);
                saveButton.show();
            } else {
                container.html('<p class="text-muted">Belum ada menu yang dibuat.</p>');
                saveButton.hide();
            }
        }).fail(err => {
            console.error('Gagal memuat menu:', err);
            container.html('<p class="text-danger">Gagal memuat struktur menu.</p>');
            saveButton.hide();
        });
    });

    $('#save-menu-access-btn').on('click', function () {
        const payload = [];
        $('.menu-item-row').each(function () {
            const menuId = $(this).data('menu-id');
            const permissionId = $(this).find('select').val();
            payload.push({
                menu_id: menuId,
                permission_id: permissionId ? parseInt(permissionId) : null
            });
        });

        $.ajax({
            url: '/api/menu-access',
            method: 'POST',
            data: JSON.stringify({ menu_access: payload }),
            contentType: 'application/json',
            success: function (response) {
                alert('Akses menu berhasil diperbarui!');
            },
            error: function (err) {
                console.error('Gagal menyimpan akses menu:', err);
                alert('Terjadi kesalahan saat menyimpan perubahan.');
            }
        });
    });

    // =================================================================
    // PEMANGGILAN FUNGSI AWAL
    // =================================================================
    loadRolesForDropdown();
    cacheAllPermissions();
});
