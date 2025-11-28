@extends('layouts.app')

@section('title', 'User & Role Management')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="{{ asset('assets/app/user/user.css') }}">
@endpush

@section('content')
    @include('layouts.components.breadcrumb')

    <div class="container-fluid">
        {{-- Bagian Atas (Tabs) --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-0">
                <ul class="nav nav-tabs nav-tabs-custom" id="user-role-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="all-users-tab-btn" data-bs-toggle="tab"
                            data-bs-target="#all-users-tab-pane" type="button" role="tab">
                            <i class="fas fa-users me-2"></i>All Users
                        </button>
                    </li>
                    {{-- Role tabs akan dimuat di sini oleh JavaScript --}}
                </ul>
            </div>
        </div>

        {{-- Konten Tab --}}
        <div class="tab-content" id="user-role-tabs-content">

            {{-- INI ADALAH TAB STATIS "ALL USERS" --}}
            <div class="tab-pane fade show active" id="all-users-tab-pane" role="tabpanel">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 fw-semibold">Registered Users</h5>
                            <small class="text-muted">Manage all registered users and their roles</small>
                        </div>
                        <button id="add-user-btn" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-2"></i>Add New User
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="all-users-table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Roles</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center" style="width: 100px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Konten untuk tab per role akan dimuat di sini oleh JavaScript --}}
        </div>
    </div>

    {{-- MODAL (Tidak ada perubahan dari kode Anda) --}}
    <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="userForm">
                    <div class="modal-body">
                        <input type="hidden" id="user_id" name="user_id">

                        <h6 class="fw-semibold text-primary">User Account</h6>
                        <hr class="mt-0">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password">
                                <small id="passwordHelp" class="form-text text-muted">Leave blank if you don't want to
                                    change it.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="password_confirmation"
                                    name="password_confirmation">
                            </div>
                        </div>
                        <div id="verify-now-container" class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="verify_now" id="verify_now">
                                <label class="form-check-label" for="verify_now">
                                    Verify user immediately
                                </label>
                                <small class="form-text text-muted d-block">Skip email verification and mark user as
                                    verified.</small>
                            </div>
                        </div>

                        {{-- USER DETAILS --}}
                        <h6 class="fw-semibold text-primary mt-4">User Details (Optional)</h6>
                        <hr class="mt-0">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="identity_number" class="form-label">Identity Number (KTP/NIK)</label>
                                <input type="text" class="form-control" id="identity_number" name="identity_number">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone_number" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="phone_number" name="phone_number">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="date_of_birth" class="form-label">Date of Birth</label>
                                <input type="text" class="form-control" id="date_of_birth" name="date_of_birth" placeholder="YYYY-MM-DD">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="gender" class="form-label">Gender</label>
                                <select class="form-select" id="gender" name="gender">
                                    <option value="">-- Select Gender --</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                             <label for="address" class="form-label">Address</label>
                             <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                        </div>

                        {{-- ASSIGN ROLES --}}
                        <h6 class="fw-semibold text-primary mt-4">Assign Roles</h6>
                        <hr class="mt-0">
                        <div class="mb-3">
                            <div id="modal-roles-checkbox-container">
                                {{-- Checkbox roles akan dimuat di sini --}}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="saveUserBtn">Save User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="{{ asset('assets/js/app/user/user.js') }}"></script>
@endpush
