// Dynamic Sidebar Menu - jQuery/AJAX Version (Corrected Final)
$(function () {
    // Fungsi ini akan berjalan setelah seluruh DOM siap
    loadMenuFromAPI();
});

function loadMenuFromAPI() {
    const $sideNav = $('.side-nav');

    if (!$sideNav.length) {
        console.error('Element .side-nav tidak ditemukan!');
        return;
    }

    console.log('Memuat menu dari API menggunakan jQuery...');

    $.ajax({
        url: '/api/menu-users', // URL sudah sesuai permintaan
        type: 'GET',
        dataType: 'json',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            console.log('Response dari API:', response);
            // Langsung gunakan response.data karena sudah dalam format tree
            const menus = response.data || response.menus || response;
            console.log('Data menu yang akan dirender:', menus);
            renderMenu(menus, $sideNav);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.error('AJAX Error:', textStatus, errorThrown);
            showError($sideNav);
        }
    });
}

function renderMenu(menus, $sideNav) {
    $sideNav.empty();

    if (!Array.isArray(menus) || menus.length === 0) {
        $sideNav.html('<li class="side-nav-item"><span class="text-muted p-3">Menu tidak tersedia</span></li>');
        return;
    }

    // Urutkan menu level atas
    menus.sort((a, b) => (a.order || 0) - (b.order || 0));

    // Langsung render setiap item dari data tree, tanpa buildMenuTree
    $.each(menus, function (index, menu) {
        const $menuItem = createMenuItem(menu);
        $sideNav.append($menuItem);
    });

    console.log('Menu berhasil dirender!');
}

// Fungsi buildMenuTree dihapus karena tidak lagi diperlukan.
// API sudah menyediakan data dalam format tree yang benar.

function createMenuItem(menu) {
    const $li = $('<li class="side-nav-item"></li>');
    const currentPath = window.location.pathname;

    // Cek jika menu memiliki anak (submenu) dari properti 'children'
    if (Array.isArray(menu.children) && menu.children.length > 0) {
        // --- INI ADALAH MENU DENGAN SUBMENU ---
        const collapseId = 'sidebar' + menu.title.replace(/[^a-zA-Z0-9]/g, '');
        const hasActiveChild = menu.children.some(child => child.url === currentPath);

        if (hasActiveChild) {
            $li.addClass('menuitem-active');
        }

        const collapseShowClass = hasActiveChild ? 'show' : '';
        const ariaExpanded = "false";

        menu.children.sort((a, b) => (a.order || 0) - (b.order || 0));

        const childrenHtml = menu.children.map(child => {
            const isChildActive = (currentPath === child.url);
            const childLiClass = isChildActive ? 'menuitem-active' : '';
            const childAClass = isChildActive ? 'active' : '';

            return `<li class="${childLiClass}"><a href="${child.url}" class="${childAClass}">${child.title}</a></li>`;
        }).join('');

        const menuHtml = `
            <a data-bs-toggle="collapse" href="#${collapseId}" aria-expanded="${ariaExpanded}" aria-controls="${collapseId}" class="side-nav-link">
                <i class="${menu.icon || 'uil-bars'}"></i>
                <span>${menu.title}</span>
                <span class="menu-arrow"></span>
            </a>
            <div class="collapse ${collapseShowClass}" id="${collapseId}">
                <ul class="side-nav-second-level">${childrenHtml}</ul>
            </div>
        `;
        $li.html(menuHtml);

    } else {
        // --- INI ADALAH MENU TUNGGAL ---
        const isMenuActive = (currentPath === menu.url);
        if (isMenuActive) {
            $li.addClass('menuitem-active');
        }
        const activeClass = isMenuActive ? 'active' : '';
        const menuHtml = `
            <a href="${menu.url}" class="side-nav-link ${activeClass}">
                <i class="${menu.icon || 'uil-bars'}"></i>
                <span>${menu.title}</span>
            </a>
        `;
        $li.html(menuHtml);
    }

    return $li;
}

function showError($sideNav) {
    const errorHtml = `
        <li class="side-nav-item">
            <div class="text-center p-3">
                <i class="uil-exclamation-triangle text-warning" style="font-size: 2rem;"></i>
                <p class="text-muted mt-2 mb-0">Gagal memuat menu</p>
            </div>
        </li>
    `;
    $sideNav.html(errorHtml);
}
