<div class="leftside-menu">

    <!-- Brand Logo Light -->
    @include('layouts.components.sidebar-brand_logo')

    <!-- Sidebar Hover Menu Toggle Button -->
    <div class="button-sm-hover" data-bs-toggle="tooltip" data-bs-placement="right" title="Show Full Sidebar">
        <i class="ri-checkbox-blank-circle-line align-middle"></i>
    </div>

    <!-- Full Sidebar Menu Close Button -->
    <div class="button-close-fullsidebar">
        <i class="ri-close-fill align-middle"></i>
    </div>

    <!-- Sidebar -->
    <div class="h-100" id="leftside-menu-container" data-simplebar>
        <!-- Leftbar User -->
        <div class="leftbar-user">
            <a href="pages-profile.html">
                <img src="assets/images/users/avatar-1.jpg" alt="user-image" height="42"
                    class="rounded-circle shadow-sm">
                <span class="leftbar-user-name mt-2">{{ Auth::user()->name }}</span>
            </a>
        </div>

        <!--- Sidemenu -->

        <div class="leftside-menu">

            <!-- Brand Logo -->
            <a href="#" class="logo logo-light">
                <span class="logo-lg">
                    <img src="{{ asset('skolabs.png') }}" style="width: 30%; height: 1%;" alt="logo">
                </span>
                <span class="logo-sm">
                    <img src="{{ asset('skolabs.png') }}" style="width: 30%; height: 1%;" alt="small logo">
                </span>
            </a>

            <a href="#" class="logo logo-dark">
                <span class="logo-lg">
                    <img src="{{ asset('skolabs.png') }}" style="width: 30%; height: 1%;" alt="dark logo">
                </span>
                <span class="logo-sm">
                    <img src="{{ asset('skolabs.png') }}" style="width: 30%; height: 1%;" alt="small logo">
                </span>
            </a>

            <div class="button-sm-hover" data-bs-toggle="tooltip" data-bs-placement="right" title="Show Full Sidebar">
                <i class="ri-checkbox-blank-circle-line align-middle"></i>
            </div>

            <div class="button-close-fullsidebar">
                <i class="ri-close-fill align-middle"></i>
            </div>

            <!-- Sidebar -->
            <div class="h-100" id="leftside-menu-container" data-simplebar>
                <!-- Leftbar User -->
                <div class="leftbar-user">
                    <a href="#">
                        <img src="assets/images/users/avatar-1.jpg" alt="user-image" height="42"
                            class="rounded-circle shadow-sm">
                        <span class="leftbar-user-name mt-2">{{ Auth::user()->name }}</span>
                    </a>
                </div>
                <br>
                <!--- Sidemenu -->
                <ul class="side-nav">
                </ul>
                <!--- End Sidemenu -->
                <div class="clearfix"></div>
            </div>
        </div>

        <!--- End Sidemenu -->

        <div class="clearfix"></div>
    </div>
</div>