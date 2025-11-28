@extends('layouts.guest')

@section('title', 'Reset Password | Skolabs')

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
                                <h4 class="text-dark-50 text-center pb-0 fw-bold">Atur Ulang Kata Sandi</h4>
                                <p class="text-muted mb-4">
                                    Masukkan email dan kata sandi baru Anda untuk melanjutkan.
                                </p>
                            </div>

                            {{-- Menampilkan pesan status jika ada --}}
                            @if (session('status'))
                                <div class="alert alert-success mt-4">
                                    {{ session('status') }}
                                </div>
                            @endif

                            {{-- Menambahkan validasi error dari Laravel --}}
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('password.update') }}">
                                @csrf

                                {{-- Token untuk reset password --}}
                                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                                <div class="mb-3">
                                    <label for="email" class="form-label">Alamat Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input class="form-control" type="email" id="email" name="email"
                                            value="{{ old('email', $request->email) }}" required autofocus
                                            placeholder="Enter your email">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Kata Sandi Baru</label>
                                    <div class="input-group input-group-merge">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" id="password" class="form-control" name="password" required
                                            autocomplete="new-password" placeholder="Enter new password">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="password_confirmation" class="form-label">Konfirmasi Kata Sandi Baru</label>
                                    <div class="input-group input-group-merge">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" id="password_confirmation" class="form-control"
                                            name="password_confirmation" required autocomplete="new-password"
                                            placeholder="Confirm new password">
                                    </div>
                                </div>

                                <div class="mb-3 mb-0 text-center">
                                    <button class="btn btn-primary" type="submit">Atur Ulang Kata Sandi</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
