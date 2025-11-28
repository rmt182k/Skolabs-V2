$(document).ready(function () {
    // Setup CSRF token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // API Endpoints
    const API = {
        MODULES: '/api/module',
        UPDATE_ORDER: '/api/module/update-order'
    };

    let allModulesData = [];

    // ==========================================
    // INITIALIZATION
    // ==========================================

    $('#module-icon').select2({
        data: fontAwesomeIcons, // Assuming fontAwesomeIcons is defined in listIcon.js
        placeholder: 'Pilih sebuah ikon',
        allowClear: true,
        templateResult: formatIcon,
        templateSelection: formatIconSelection
    });

    $('#module-parent').select2({
        placeholder: '-- Root Modul (Tidak ada parent) --',
        allowClear: true
    });

    loadModules();

    // ==========================================
    // MODULE LOADING & RENDERING
    // ==========================================

    function loadModules() {
        const listContainer = $('#module-list');
        listContainer.html(`
            <div class="text-center p-5">
                <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                <p class="mt-2 text-muted">Memuat Struktur Modul...</p>
            </div>`);

        $.ajax({
            url: API.MODULES,
            method: 'GET',
            success: function (response) {
                listContainer.empty();
                if (response.success && response.data && response.data.length > 0) {
                    allModulesData = response.data;
                    renderModuleItems(response.data, listContainer);
                    updateParentDropdown();
                } else {
                    listContainer.html('<p class="text-center text-muted">Belum ada modul. Silakan tambahkan.</p>');
                }
                document.querySelectorAll('.sortable-list').forEach(list => initializeSortable(list));
            },
            error: function (xhr) {
                const error = xhr.responseJSON ? xhr.responseJSON.message : "Gagal memuat data modul.";
                listContainer.html(`<p class="text-danger text-center">${error}</p>`);
            }
        });
    }

    function renderModuleItems(items, container) {
        items.forEach(item => {
            const statusBadge = item.is_active ?
                '<span class="badge bg-success badge-sm ms-2">Active</span>' :
                '<span class="badge bg-secondary badge-sm ms-2">Inactive</span>';

            const moduleItemHTML = `
                <div class="menu-item ${!item.is_active ? 'menu-inactive' : ''}" data-id="${item.id}">
                    <div class="menu-item-content">
                        <i class="fas fa-grip-vertical handle"></i>
                        <i class="${item.icon || 'fas fa-folder'} menu-icon"></i>
                        <span class="menu-title">${item.display_name}${statusBadge}</span>
                        <span class="menu-url text-muted">${item.url}</span>
                        <div class="menu-actions">
                            <button class="btn-action edit-btn" title="Edit"><i class="fas fa-edit"></i></button>
                            <button class="btn-action delete-btn" title="Hapus"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>`;

            const moduleItem = $(moduleItemHTML);
            container.append(moduleItem);

            moduleItem.find('.edit-btn').on('click', () => populateFormForEdit(item));
            moduleItem.find('.delete-btn').on('click', () => deleteModule(item.id));

            if (item.children && item.children.length > 0) {
                const childrenContainer = $('<div class="menu-children sortable-list"></div>');
                moduleItem.append(childrenContainer);
                renderModuleItems(item.children, childrenContainer);
            }
        });
    }

    function updateParentDropdown() {
        const parentSelect = $('#module-parent');
        const currentModuleId = $('#module-id').val();
        parentSelect.empty().append('<option value="">-- Root Modul (Tidak ada parent) --</option>');

        function addOptionsRecursive(items, level = 0) {
            items.forEach(item => {
                if (item.id == currentModuleId) return;
                const indent = '—'.repeat(level);
                const optionText = `${indent} ${item.display_name}`;
                parentSelect.append(`<option value="${item.id}">${optionText}</option>`);
                if (item.children && item.children.length > 0) {
                    addOptionsRecursive(item.children, level + 1);
                }
            });
        }
        addOptionsRecursive(allModulesData);
    }

    // ==========================================
    // EVENT HANDLERS
    // ==========================================

    $('#module-form').on('submit', function (e) {
        e.preventDefault();
        const formMode = $('#form-mode').val();
        const moduleId = $('#module-id').val();
        formMode === 'edit' ? updateModule(moduleId) : addModule();
    });

    $('#cancel-btn').on('click', () => resetForm());

    $('#save-order-btn').on('click', function () {
        const moduleData = serializeModules($('#module-list')[0]);
        const btn = $(this);
        const originalHTML = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...');

        $.ajax({
            url: API.UPDATE_ORDER,
            method: 'POST',
            data: { modules: moduleData },
            success: (response) => showSuccess(response.message),
            error: (xhr) => showError(xhr.responseJSON?.message || "Gagal menyimpan urutan."),
            complete: () => {
                setTimeout(() => {
                    btn.prop('disabled', false).html(originalHTML);
                }, 1500);
            }
        });
    });

    // ==========================================
    // CRUD OPERATIONS
    // ==========================================

    function addModule() {
        const btn = $('#submit-btn');
        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menambahkan...');

        $.ajax({
            url: API.MODULES,
            method: 'POST',
            data: $('#module-form').serialize(),
            success: (response) => {
                showSuccess(response.message);
                resetForm();
                loadModules();
            },
            error: handleError,
            complete: () => btn.prop('disabled', false).html(originalHtml)
        });
    }

    function updateModule(moduleId) {
        const btn = $('#submit-btn');
        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: `${API.MODULES}/${moduleId}`,
            method: 'PUT',
            data: $('#module-form').serialize(),
            success: (response) => {
                showSuccess(response.message);
                resetForm();
                loadModules();
            },
            error: handleError,
            complete: () => btn.prop('disabled', false).html(originalHtml)
        });
    }

    function deleteModule(moduleId) {
        // Ganti confirm dengan modal yang lebih baik seperti SweetAlert jika memungkinkan
        if (!confirm('Apakah Anda yakin ingin menghapus modul ini? Aksi ini tidak dapat dibatalkan.')) return;

        $.ajax({
            url: `${API.MODULES}/${moduleId}`,
            method: 'DELETE',
            success: (response) => {
                showSuccess(response.message);
                loadModules();
            },
            error: handleError
        });
    }

    // ==========================================
    // FORM & UI HELPERS
    // ==========================================

    function populateFormForEdit(moduleData) {
        resetForm();
        $('#form-mode').val('edit');
        $('#module-id').val(moduleData.id);
        updateParentDropdown();

        $('#form-title').html('<i class="fas fa-edit me-2"></i>Edit Modul');
        $('#form-subtitle').text('Ubah data modul yang sudah ada.');

        $('#module-display-name').val(moduleData.display_name);
        $('#module-name').val(moduleData.name);
        $('#module-url').val(moduleData.url); // Populate URL field
        $('#module-status').val(moduleData.is_active ? '1' : '0');
        $('#module-parent').val(moduleData.parent_id || '').trigger('change');
        if (moduleData.icon) {
            $('#module-icon').val(moduleData.icon).trigger('change');
        }

        $('#submit-text').text('Update Modul');
        $('#submit-btn i').attr('class', 'fas fa-save me-1');
        $('#cancel-text').text('Batal');
        $('html, body').animate({ scrollTop: $('#module-form').offset().top - 100 }, 500);
    }

    function resetForm() {
        $('#module-form')[0].reset();
        $('#module-url').val('#'); // Reset URL to default '#'
        $('#form-mode').val('add');
        $('#module-id').val('');
        $('#module-icon').val(null).trigger('change');
        $('#module-parent').val('').trigger('change');
        updateParentDropdown();

        $('#form-title').html('<i class="fas fa-plus-circle me-2"></i>Tambah Modul Baru');
        $('#form-subtitle').text('Modul baru akan ditambahkan di bagian paling bawah.');
        $('#submit-text').text('Tambah Modul');
        $('#submit-btn i').attr('class', 'fas fa-plus-circle me-1');
        $('#cancel-text').text('Bersihkan');
    }

    // ==========================================
    // UTILITY & HELPER FUNCTIONS
    // ==========================================

    function initializeSortable(el) {
        new Sortable(el, {
            group: 'nested-module',
            animation: 200,
            handle: '.handle',
            ghostClass: 'sortable-ghost',
        });
    }

    function serializeModules(container) {
        const items = [];
        $(container).children('.menu-item').each(function () {
            const moduleItem = $(this);
            const childrenContainer = moduleItem.children('.menu-children');
            items.push({
                id: moduleItem.data('id'),
                children: childrenContainer.length > 0 ? serializeModules(childrenContainer[0]) : []
            });
        });
        return items;
    }

    function formatIcon(icon) {
        if (!icon.id) return icon.text;
        return $('<span><i class="' + icon.id + ' me-2"></i> ' + icon.text + '</span>');
    }

    function formatIconSelection(icon) {
        if (!icon.id) return icon.text;
        $('#icon-preview').attr('class', icon.id || 'fas fa-folder');
        return formatIcon(icon);
    }

    $('#module-icon').on('change', function() {
        $('#icon-preview').attr('class', $(this).val() || 'fas fa-folder');
    });

    function showSuccess(message) {
        // Ganti alert dengan notifikasi yang lebih baik (Toastr, SweetAlert, dll.)
        alert(message);
    }

    function showError(message) {
        alert('Error: ' + message);
    }

    function handleError(xhr) {
        const response = xhr.responseJSON;
        let errorMsg = response?.message || 'Terjadi kesalahan.';
        if (response?.errors) {
            errorMsg += "\n" + Object.values(response.errors).map(e => `- ${e[0]}`).join("\n");
        }
        showError(errorMsg);
    }
});
