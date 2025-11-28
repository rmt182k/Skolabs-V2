@extends('layouts.guest')

@section('title', 'Verify Email | Skolabs')

@section('content')
    <div class="account-pages pt-2 pt-sm-5 pb-4 pb-sm-5 position-relative">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xxl-4 col-lg-5">
                    <div class="card">

                        <div class="card-header py-4 text-center bg-primary">
                            <a href="/">
                                <span><img src="assets/images/logo.png" alt="logo" height="22"></span>
                            </a>
                        </div>

                        <div class="card-body p-4">

                            <div class="text-center w-75 m-auto">
                                <h4 class="text-dark-50 text-center pb-0 fw-bold">Verifikasi Email Anda</h4>
                                <p class="text-muted mb-4">
                                    Sebelum melanjutkan, mohon periksa email Anda untuk tautan verifikasi.
                                    Jika Anda tidak menerima email, kami akan dengan senang hati mengirimkan yang lain.
                                </p>
                            </div>

                            {{-- Menampilkan pesan status jika ada --}}
                            @if (session('status') == 'verification-link-sent')
                                <div class="alert alert-success mt-4">
                                    Tautan verifikasi baru telah dikirim ke alamat email yang Anda berikan saat pendaftaran.
                                </div>
                            @endif

                            {{-- Tampilan fungsional dari kode kedua --}}
                            <div class="alert alert-info d-none" id="emailInfo">
                                <i class="fas fa-info-circle me-2"></i>
                                <small>Email verifikasi telah dikirim ke: <strong
                                        id="userEmail">{{ auth()->user()->email ?? 'your email' }}</strong></small>
                            </div>

                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    @foreach ($errors->all() as $error)
                                        <div>{{ $error }}</div>
                                    @endforeach
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('verification.send') }}" id="resend-form">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100 mt-4" id="resendBtn">
                                    <span class="spinner-border spinner-border-sm me-2 d-none" id="spinner"></span>
                                    Kirim Ulang Email Verifikasi
                                    <i class="fas fa-paper-plane ms-1"></i>
                                </button>
                            </form>

                            <div class="mt-4">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger w-100">
                                                <i class="fas fa-sign-out-alt me-1"></i>
                                                Keluar
                                            </button>
                                        </form>
                                    </div>
                                    <div class="col-6">
                                        <button type="button" class="btn btn-outline-secondary w-100" id="checkEmailBtn">
                                            <i class="fas fa-external-link-alt me-1"></i>
                                            Cek Email
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Form submission handler with loading state
        document.getElementById('resendBtn').addEventListener('click', function(e) {
            const spinner = document.getElementById('spinner');
            const button = this;

            spinner.classList.remove('d-none');
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sedang Mengirim...';

            setTimeout(() => {
                spinner.classList.add('d-none');
                button.disabled = false;
                button.innerHTML = 'Kirim Ulang Email Verifikasi <i class="fas fa-paper-plane ms-1"></i>';
            }, 10000);
        });

        // Check email button handler
        document.getElementById('checkEmailBtn').addEventListener('click', function() {
            Swal.fire({
                icon: 'info',
                title: 'Cara Cek Email Anda',
                html: `
            <div class="text-start">
                <p class="mb-3">Ikuti langkah-langkah ini untuk menemukan email verifikasi Anda:</p>
                <ol class="text-start">
                    <li class="mb-2">
                        <strong>Periksa kotak masuk Anda</strong><br>
                        <small class="text-muted">Cari email dari {{ config('app.name') }}</small>
                    </li>
                    <li class="mb-2">
                        <strong>Periksa folder spam/junk</strong><br>
                        <small class="text-muted">Terkadang email masuk ke sana</small>
                    </li>
                    <li class="mb-2">
                        <strong>Cari "verifikasi"</strong><br>
                        <small class="text-muted">Gunakan fungsi pencarian email Anda</small>
                    </li>
                    <li class="mb-2">
                        <strong>Klik tautan verifikasi</strong><br>
                        <small class="text-muted">Ini akan mengaktifkan akun Anda</small>
                    </li>
                </ol>
                <div class="alert alert-info mt-3">
                    <small>
                        <i class="fas fa-envelope me-1"></i>
                        Email dikirim ke: <strong>{{ auth()->user()->email ?? 'your email' }}</strong>
                    </small>
                </div>
            </div>
            `,
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Saya Paham!',
                showCancelButton: true,
                cancelButtonText: 'Buka Aplikasi Email',
                cancelButtonColor: '#6c757d',
                width: '500px',
                customClass: {
                    popup: 'animated fadeIn'
                }
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    window.location.href = 'mailto:';
                }
            });
        });

        // Handle verification link sent status
        @if (session('status') === 'verification-link-sent')
            window.addEventListener('load', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Email Verifikasi Terkirim!',
                    text: 'Tautan verifikasi baru telah dikirim ke alamat email Anda.',
                    confirmButtonColor: '#3085d6',
                    timer: 5000,
                    timerProgressBar: true,
                    showConfirmButton: true,
                    confirmButtonText: 'Cek Email',
                    allowOutsideClick: false,
                    customClass: {
                        popup: 'animated bounceIn'
                    }
                }).then((result) => {
                    Swal.fire({
                        icon: 'info',
                        title: 'Cek Email Anda Sekarang',
                        html: `
                    <div class="text-start">
                        <p class="mb-3">Kami telah mengirimkan tautan verifikasi ke:</p>
                        <div class="alert alert-primary">
                            <i class="fas fa-envelope me-2"></i>
                            <strong>{{ auth()->user()->email ?? 'your email' }}</strong>
                        </div>
                        <p class="mb-2">Langkah selanjutnya:</p>
                        <ol class="text-start">
                            <li>Buka aplikasi email Anda</li>
                            <li>Temukan email dari {{ config('app.name') }}</li>
                            <li>Klik tombol "Verifikasi Email"</li>
                            <li>Anda akan dialihkan kembali ke situs kami</li>
                        </ol>
                        <div class="alert alert-warning mt-3">
                            <small>
                                <i class="fas fa-clock me-1"></i>
                                Tidak melihat email? Periksa folder spam Anda atau tunggu beberapa menit.
                            </small>
                        </div>
                    </div>
                    `,
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'Saya Paham',
                        showCancelButton: true,
                        cancelButtonText: 'Kirim Ulang',
                        cancelButtonColor: '#6c757d',
                        allowOutsideClick: false,
                        width: '500px',
                        customClass: {
                            popup: 'animated fadeIn'
                        }
                    }).then((result) => {
                        if (result.dismiss === Swal.DismissReason.cancel) {
                            document.getElementById('resend-form').submit();
                        }
                    });
                });
            });
        @endif

        // Handle other session statuses
        @if (session('status') && session('status') !== 'verification-link-sent')
            Swal.fire({
                icon: 'info',
                title: 'Informasi',
                text: {!! json_encode(session('status')) !!},
                confirmButtonColor: '#3085d6'
            });
        @endif

        // Handle errors
        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Kesalahan',
                html: '@foreach ($errors->all() as $error)<p class="mb-1">{{ $error }}</p>@endforeach',
                confirmButtonColor: '#d33'
            });
        @endif

        // Auto-refresh page every 30 seconds to check if email is verified
        let refreshInterval = setInterval(function() {
            fetch('{{ route('verification.notice') }}', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }).then(response => {
                if (response.redirected) {
                    clearInterval(refreshInterval);
                    window.location.href = response.url;
                }
            }).catch(error => {
                console.log('Verification check failed:', error);
            });
        }, 30000);

        // Show email info after 3 seconds
        setTimeout(() => {
            document.getElementById('emailInfo').classList.remove('d-none');
        }, 3000);

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
@endpush
