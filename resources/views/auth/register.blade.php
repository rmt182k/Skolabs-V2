@extends('layouts.guest')

@section('title', 'Register | Skolabs')

@section('content')
    <div class="account-pages pt-2 pt-sm-5 pb-4 pb-sm-5 position-relative">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xxl-4 col-lg-5">
                    <div class="card">
                        <div class="card-header py-4 text-center bg-primary">
                            <a href="/">
                                {{-- put logo here --}}
                                {{-- <span><img src="assets/images/logo.png" alt="logo" height="22"></span> --}}
                            </a>
                        </div>

                        <div class="card-body p-4">
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form id="registerForm" method="POST" action="{{ route('register') }}">
                                @csrf

                                <div class="mb-3">
                                    <label class="form-label fw-semibold" for="name">Full Name</label>
                                    <input type="text" class="form-control" name="name" id="name"
                                        placeholder="Enter your full name" required>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold" for="email">Email Address</label>
                                    <input type="email" class="form-control" name="email" id="email"
                                        placeholder="Enter your email" required>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold" for="password">Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="password" id="password"
                                            placeholder="Create a password" required minlength="8">
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <small class="form-text text-muted">Password must be at least 8 characters long</small>
                                    <div class="invalid-feedback password-error"></div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold" for="password_confirmation">Confirm Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="password_confirmation"
                                            id="password_confirmation" placeholder="Confirm your password" required minlength="8">
                                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback confirm-password-error"></div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="terms-checkbox" required>
                                        <label class="form-check-label" for="terms-checkbox">I accept <a href="#"
                                                class="text-muted">Terms and Conditions</a></label>
                                    </div>
                                    <div id="terms-error" class="invalid-feedback"></div>
                                </div>

                                <div class="mb-3 text-center">
                                    <button class="btn btn-primary" type="submit" id="submitBtn">
                                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status"></span>
                                        Sign Up
                                    </button>
                                </div>
                            </form>

                            {{-- <div class="row mt-4 mb-3 align-items-center">
                                <div class="col">
                                    <hr class="my-0">
                                </div>
                                <div class="col-auto text-uppercase text-muted small">Or</div>
                                <div class="col">
                                    <hr class="my-0">
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-secondary" type="button" onclick="showComingSoon('Google')">
                                    <i class="fab fa-fw fa-google me-2"></i> Sign up with Google
                                </button>
                                <button class="btn btn-outline-secondary" type="button" onclick="showComingSoon('Facebook')">
                                    <i class="fab fa-fw fa-facebook-f me-2"></i> Sign up with Facebook
                                </button>
                                <button class="btn btn-outline-secondary" type="button" onclick="showComingSoon('Microsoft')">
                                    <i class="fab fa-fw fa-microsoft me-2"></i> Sign up with Microsoft
                                </button>
                            </div> --}}
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12 text-center">
                            <p class="text-muted">Already have account? <a href="{{ route('login') }}"
                                    class="text-muted ms-1"><b>Log In</b></a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            const submitBtn = document.getElementById('submitBtn');
            const passwordInput = document.getElementById('password');
            const passwordConfirmationInput = document.getElementById('password_confirmation');
            const termsCheckbox = document.getElementById('terms-checkbox');
            const termsError = document.getElementById('terms-error');
            const nameInput = document.getElementById('name');
            const emailInput = document.getElementById('email');

            function togglePasswordVisibility(fieldId, buttonId) {
                const passwordField = document.getElementById(fieldId);
                const button = document.getElementById(buttonId);
                const icon = button.querySelector('i');
                const isPassword = passwordField.type === 'password';
                passwordField.type = isPassword ? 'text' : 'password';
                icon.className = `fas fa-eye${isPassword ? '-slash' : ''}`;
            }

            document.getElementById('togglePassword').addEventListener('click', () => {
                togglePasswordVisibility('password', 'togglePassword');
            });

            document.getElementById('toggleConfirmPassword').addEventListener('click', () => {
                togglePasswordVisibility('password_confirmation', 'toggleConfirmPassword');
            });

            // Fungsi untuk membersihkan semua feedback validasi
            function clearValidationFeedback() {
                document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
            }

            // Fungsi validasi real-time untuk nama dan email
            function validateInput(inputElement) {
                const isValid = inputElement.checkValidity();
                if (!isValid) {
                    inputElement.classList.add('is-invalid');
                    inputElement.nextElementSibling.textContent = inputElement.validationMessage;
                } else {
                    inputElement.classList.remove('is-invalid');
                }
                return isValid;
            }

            // Validasi password secara real-time
            const validatePasswords = () => {
                let isValid = true;
                if (passwordInput.value.length < 8 && passwordInput.value !== '') {
                    passwordInput.classList.add('is-invalid');
                    document.querySelector('.password-error').textContent = 'Password must be at least 8 characters long.';
                    isValid = false;
                } else {
                    passwordInput.classList.remove('is-invalid');
                    document.querySelector('.password-error').textContent = '';
                }

                if (passwordConfirmationInput.value !== passwordInput.value && passwordConfirmationInput.value !== '') {
                    passwordConfirmationInput.classList.add('is-invalid');
                    document.querySelector('.confirm-password-error').textContent = 'Passwords do not match.';
                    isValid = false;
                } else {
                    passwordConfirmationInput.classList.remove('is-invalid');
                    document.querySelector('.confirm-password-error').textContent = '';
                }

                return isValid;
            };

            // Validasi real-time untuk syarat dan ketentuan
            const validateTerms = () => {
                if (!termsCheckbox.checked) {
                    termsCheckbox.classList.add('is-invalid');
                    termsError.textContent = 'You must agree to the Terms and Conditions.';
                    return false;
                } else {
                    termsCheckbox.classList.remove('is-invalid');
                    termsError.textContent = '';
                    return true;
                }
            };

            // Event listeners untuk validasi real-time
            nameInput.addEventListener('input', () => validateInput(nameInput));
            emailInput.addEventListener('input', () => validateInput(emailInput));
            passwordInput.addEventListener('input', validatePasswords);
            passwordConfirmationInput.addEventListener('input', validatePasswords);
            termsCheckbox.addEventListener('change', validateTerms);

            // Validasi saat form di-submit
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                clearValidationFeedback();

                const isNameValid = validateInput(nameInput);
                const isEmailValid = validateInput(emailInput);
                const isPasswordValid = validatePasswords();
                const isTermsAccepted = validateTerms();

                if (!isNameValid || !isEmailValid || !isPasswordValid || !isTermsAccepted) {
                    Swal.fire('Validation Error', 'Please correct the highlighted fields and try again.', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Create Account?',
                    text: 'Are you sure you want to create this account?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#dc3545',
                    confirmButtonText: 'Yes, create account!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        processRegistration();
                    }
                });
            });

            // Fungsi untuk mengirim permintaan pendaftaran
            function processRegistration() {
                submitBtn.disabled = true;
                submitBtn.innerHTML =
                    `<span class="spinner-border spinner-border-sm me-2" role="status"></span>Creating Account...`;

                Swal.fire({
                    title: 'Creating Account...',
                    text: 'Please wait while we process your registration.',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json' // Perlu ditambahkan untuk menerima respons JSON
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(errorData => {
                                throw {
                                    status: response.status,
                                    data: errorData
                                };
                            });
                        }
                        return response.json();
                    })
                    .then(response => {
                        if (response.email_verification_required || response.requires_verification) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Registration Successful! 🎉',
                                html: `
                                    <div class="text-center">
                                        <p class="mb-3">Welcome aboard! Your account has been created.</p>
                                        <div class="alert alert-info text-start">
                                            <strong>Email Verification Required:</strong><br>
                                            We've sent a verification link to **${response.email || document.getElementById('email').value}**. Please check your email and click the link to activate your account.
                                        </div>
                                    </div>
                                `,
                                showCancelButton: true,
                                confirmButtonColor: '#198754',
                                cancelButtonColor: '#6c757d',
                                confirmButtonText: '<i class="fas fa-envelope me-2"></i>Go to Email',
                                cancelButtonText: 'I\'ll check later',
                                allowOutsideClick: false
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.open('mailto:', '_blank');
                                }
                                window.location.href = response.redirect_url || '/email/verify';
                            });
                        } else {
                            Swal.fire({
                                icon: 'success',
                                title: 'Registration Successful!',
                                text: 'Welcome aboard! Redirecting to your dashboard...',
                                timer: 3000,
                                showConfirmButton: false,
                                allowOutsideClick: false,
                            }).then(() => {
                                window.location.href = response.redirect_url || '/dashboard';
                            });
                        }
                    })
                    .catch(error => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = `Sign Up`;
                        Swal.close();

                        let title = 'Registration Failed';
                        let message = 'An unexpected error occurred. Please try again.';

                        if (error.status === 422) {
                            title = 'Validation Error';
                            const validationErrors = error.data.errors;
                            Object.keys(validationErrors).forEach(field => {
                                const fieldElement = document.getElementById(field);
                                if (fieldElement) {
                                    fieldElement.classList.add('is-invalid');
                                    const feedback = fieldElement.closest('.mb-3, .mb-4').querySelector('.invalid-feedback');
                                    if (feedback) {
                                        feedback.textContent = validationErrors[field][0];
                                    }
                                }
                            });
                            message = 'Please correct the highlighted fields and try again.';
                        } else if (error.status === 409) {
                            title = 'Account Already Exists';
                            message = 'An account with this email already exists.';
                        } else if (error instanceof TypeError && error.message.includes('fetch')) {
                            title = 'Network Error';
                            message = 'Could not connect to the server. Please check your internet connection.';
                        }

                        Swal.fire({
                            icon: 'error',
                            title: title,
                            html: `<p>${message}</p>`,
                            confirmButtonColor: '#dc3545',
                            confirmButtonText: 'Try Again'
                        });
                    });
            }
        });

        function showComingSoon(provider) {
            Swal.fire({
                icon: 'info',
                title: 'Coming Soon!',
                text: `${provider} registration will be available soon. Please use email registration for now.`,
                confirmButtonColor: '#17a2b8',
                confirmButtonText: 'Got it!'
            });
        }
    </script>
@endpush
