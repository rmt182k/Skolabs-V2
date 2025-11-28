<div class="navbar-custom">
    <div class="topbar container-fluid">
        <div class="d-flex align-items-center gap-lg-2 gap-1">

            <!-- Topbar Brand Logo -->
            @include('layouts.components.topbar-brand_logo')

            <!-- Sidebar Menu Toggle Button -->
            <button class="button-toggle-menu">
                <i class="ri-menu-5-line"></i>
            </button>

            <!-- Horizontal Menu Toggle Button -->
            <button class="navbar-toggle" data-bs-toggle="collapse" data-bs-target="#topnav-menu-content">
                <div class="lines">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </button>

            <!-- Topbar Search Form -->
            {{-- @include('layouts.components.topbar-search') --}}

        </div>

        <ul class="topbar-menu d-flex align-items-center gap-3">
            <!-- Mobile Search Toggle Button -->
            {{-- @include('layouts.components.topbar-search_mobile') --}}

            <!-- Language Switcher -->
            {{-- @include('layouts.components.topbar-language_switcher') --}}

            <!-- Notification -->
            {{-- @include('layouts.components.topbar-notification') --}}

            <!-- Apps -->
            {{-- @include('layouts.components.topbar-apps') --}}

            <li class="d-none d-sm-inline-block">
                <a class="nav-link" data-bs-toggle="offcanvas" href="#theme-settings-offcanvas">
                    <i class="ri-settings-3-line font-22"></i>
                </a>
            </li>

            <li class="d-none d-sm-inline-block">
                <div class="nav-link" id="light-dark-mode">
                    <i class="ri-moon-line font-22"></i>
                </div>
            </li>


            <li class="d-none d-md-inline-block">
                <a class="nav-link" href="" data-toggle="fullscreen">
                    <i class="ri-fullscreen-line font-22"></i>
                </a>
            </li>

            <!-- User-->
            @include('layouts.components.topbar-user')
        </ul>
    </div>
</div>
