// Dynamic Sidebar Loader - jQuery/AJAX Version
$(function() {
    // Fungsi ini akan berjalan setelah seluruh DOM siap
    loadModulesFromAPI();
});

function loadModulesFromAPI() {
    const $sideNav = $('.side-nav');

    if (!$sideNav.length) {
        console.error('Element .side-nav tidak ditemukan!');
        return;
    }

    console.log('Memuat modul dari API menggunakan jQuery...');

    $.ajax({
        url: '/api/module', // Diubah dari /api/menu menjadi /api/module
        type: 'GET',
        dataType: 'json',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            console.log('Response dari API:', response);
            // Langsung gunakan response.data karena diharapkan sudah dalam format tree
            const modules = response.data || response.modules || response;
            console.log('Data modul yang akan dirender:', modules);
            renderModules(modules, $sideNav);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('AJAX Error:', textStatus, errorThrown);
            showError($sideNav);
        }
    });
}

function renderModules(modules, $sideNav) {
    $sideNav.empty();

    if (!Array.isArray(modules) || modules.length === 0) {
        $sideNav.html('<li class="side-nav-item"><span class="text-muted p-3">Modul tidak tersedia</span></li>');
        return;
    }

    // Urutkan modul level atas
    modules.sort((a, b) => (a.order || 0) - (b.order || 0));

    // Render setiap item dari data tree
    $.each(modules, function(index, module) {
        // Logika render sekarang akan menangani modul sebagai kategori (side-nav-title)
        // atau sebagai item dropdown biasa.

        // Jika modul tidak punya URL dan punya menu, anggap sebagai Kategori Judul
        if ((!module.url || module.url === '#') && module.menus && module.menus.length > 0) {
            $sideNav.append(`<li class="side-nav-title side-nav-item">${module.display_name}</li>`);
            // Render menu di dalam modul ini
            $.each(module.menus, function(idx, menu) {
                 const $menuItem = createModuleItem(menu, 'menu'); // Tandai sebagai menu
                 $sideNav.append($menuItem);
            });
        } else {
             // Jika modul punya URL atau punya children (sub-modul), anggap sebagai item biasa
             const $moduleItem = createModuleItem(module, 'module'); // Tandai sebagai modul
             $sideNav.append($moduleItem);
        }
    });

    console.log('Navigasi berhasil dirender!');
}


function createModuleItem(item, type) {
    const $li = $('<li class="side-nav-item"></li>');
    const currentPath = window.location.pathname;

    // Sesuaikan nama field berdasarkan tipe (modul atau menu)
    const title = type === 'module' ? item.display_name : item.title;
    const children = item.children || []; // children bisa sub-modul atau sub-menu

    // Cek jika item memiliki anak (sub-modul atau sub-menu)
    if (Array.isArray(children) && children.length > 0) {
        // --- INI ADALAH ITEM DENGAN ANAK (SUB-MODUL / SUB-MENU) ---
        const collapseId = 'sidebar' + title.replace(/[^a-zA-Z0-9]/g, '') + item.id;
        const hasActiveChild = children.some(child => child.url === currentPath);

        if (hasActiveChild) {
            $li.addClass('menuitem-active');
        }

        const collapseShowClass = hasActiveChild ? 'show' : '';
        const ariaExpanded = hasActiveChild ? "true" : "false";

        children.sort((a, b) => (a.order || 0) - (b.order || 0));

        // Anak dari item bisa jadi sub-modul atau sub-menu, perlakukan sama
        const childrenHtml = children.map(child => {
            const childTitle = child.display_name || child.title; // Ambil display_name atau title
            const isChildActive = (currentPath === child.url);
            const childLiClass = isChildActive ? 'menuitem-active' : '';
            const childAClass = isChildActive ? 'active' : '';
            return `<li class="${childLiClass}"><a href="${child.url}" class="${childAClass}">${childTitle}</a></li>`;
        }).join('');

        const itemHtml = `
            <a data-bs-toggle="collapse" href="#${collapseId}" aria-expanded="${ariaExpanded}" aria-controls="${collapseId}" class="side-nav-link">
                <i class="${item.icon || 'uil-bars'}"></i>
                <span>${title}</span>
                <span class="menu-arrow"></span>
            </a>
            <div class="collapse ${collapseShowClass}" id="${collapseId}">
                <ul class="side-nav-second-level">${childrenHtml}</ul>
            </div>
        `;
        $li.html(itemHtml);

    } else {
        // --- INI ADALAH ITEM TUNGGAL (MODUL / MENU) ---
        const isItemActive = (currentPath === item.url && item.url !== '#');
        if (isItemActive) {
            $li.addClass('menuitem-active');
        }
        const activeClass = isItemActive ? 'active' : '';
        const itemHtml = `
            <a href="${item.url || '#'}" class="side-nav-link ${activeClass}">
                <i class="${item.icon || 'uil-bars'}"></i>
                <span>${title}</span>
            </a>
        `;
        $li.html(itemHtml);
    }

    return $li;
}

function showError($sideNav) {
    const errorHtml = `
        <li class="side-nav-item">
            <div class="text-center p-3">
                <i class="uil-exclamation-triangle text-warning" style="font-size: 2rem;"></i>
                <p class="text-muted mt-2 mb-0">Gagal memuat navigasi</p>
            </div>
        </li>
    `;
    $sideNav.html(errorHtml);
}

