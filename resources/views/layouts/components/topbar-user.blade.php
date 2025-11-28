<li class="dropdown">
    <a class="nav-link dropdown-toggle arrow-none nav-user px-2" data-bs-toggle="dropdown" href="#" role="button"
        aria-haspopup="false" aria-expanded="false">
        <span class="account-user-avatar">
            <img src="assets/images/users/avatar-1.jpg" alt="user-image" width="32" class="rounded-circle">
        </span>
        <span class="d-lg-flex flex-column gap-1 d-none">
            <h5 class="my-0">{{ Auth::user()->name }}</h5>
            <h6 class="my-0 fw-normal">
                {{ implode(', ', \App\Models\Role::getRoleNamesByUserId(Auth::user()->id)) }}
            </h6>
        </span>
    </a>
    <div class="dropdown-menu dropdown-menu-end dropdown-menu-animated profile-dropdown">
        <!-- item-->
        <div class=" dropdown-header noti-title">
            <h6 class="text-overflow m-0">Welcome !</h6>
        </div>

        <!-- item-->
        <a href="javascript:void(0);" class="dropdown-item">
            <i class="ri-user-smile-line font-16 me-1"></i>
            <span>My Account</span>
        </a>

        <!-- item-->
        <a href="javascript:void(0);" class="dropdown-item">
            <i class="ri-user-settings-line font-16 me-1"></i>
            <span>Settings</span>
        </a>

        <!-- item-->
        <a href="javascript:void(0);" class="dropdown-item">
            <i class="ri-lifebuoy-line font-16 me-1"></i>
            <span>Support</span>
        </a>

        <!-- item-->
        <a href="javascript:void(0);" class="dropdown-item">
            <i class="ri-lock-line font-16 me-1"></i>
            <span>Lock Screen</span>
        </a>

        <!-- item-->
        <a href="#" id="logout-button" class="dropdown-item">
            <i class="ri-login-circle-line font-16 me-1"></i>
            <span>Logout</span>
        </a>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
    </div>
</li>
@push('scripts')
    <script>
        $(document).ready(function() {
            // Event listener untuk tombol logout
            $('#logout-button').on('click', function(event) {
                event.preventDefault(); // Mencegah link melakukan apapun
                $('#logout-form').submit(); // Submit form tersembunyi
            });
        });
    </script>
@endpush
