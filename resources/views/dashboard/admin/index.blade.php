{{--
    File: resources/views/dashboard/admin/index.blade.php
    Note: File ini di-include oleh index.blade.php utama, jadi tidak perlu @extends.
--}}

<div class="row">
    <div class="col-12">

        {{-- Section: Stats Cards --}}
        <div class="row mb-4">
            {{-- Card: Total Users --}}
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Users</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-users-count">
                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card: Total Classes --}}
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Total Classes</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-classes-count">
                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chalkboard fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card: Total Subjects --}}
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Subjects</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-subjects-count">
                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-book fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card: Total Competencies --}}
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Competencies</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-competencies-count">
                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-star fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section: Recent Users Table --}}
        <div class="card shadow mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Newest Users</h6>
                <a href="/users" class="btn btn-sm btn-primary">View All Users</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Registered At</th>
                            </tr>
                        </thead>
                        <tbody id="recent-users-table-body">
                            {{-- Data will be loaded via JS --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

@push('styles')
    <style>
        .border-left-primary {
            border-left: 0.25rem solid #4e73df !important;
        }

        .border-left-success {
            border-left: 0.25rem solid #1cc88a !important;
        }

        .border-left-info {
            border-left: 0.25rem solid #36b9cc !important;
        }

        .border-left-warning {
            border-left: 0.25rem solid #f6c23e !important;
        }

        .table td,
        .table th {
            vertical-align: middle;
        }
    </style>
@endpush

@push('scripts')
    {{-- SweetAlert2 & jQuery --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    {{-- Moment JS for date formatting (Optional, but good for dates) --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>

    <script>
        $(document).ready(function() {
            // Setup CSRF token
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // API Endpoints (Sesuai route list Anda)
            const API = {
                USERS: '/api/users',
                CLASSES: '/api/classes',
                SUBJECTS: '/api/subjects',
                COMPETENCIES: '/api/competencies' // Menggunakan endpoint list kompetensi
            };

            /**
             * 1. Load Dashboard Statistics
             * Karena tidak ada endpoint khusus statistik di daftar route Anda,
             * kita akan mengambil count dari endpoint list masing-masing.
             */
            function loadDashboardStats() {
                // Fetch Users Count
                $.get(API.USERS)
                    .done(res => {
                        // Asumsi response format: { success: true, data: [...] } atau langsung array
                        let count = 0;
                        if (res.data) count = res.data.length;
                        else if (Array.isArray(res)) count = res.length;

                        $('#stat-users-count').text(count);

                        // Render Recent Users Table juga dari data ini
                        renderRecentUsers(res.data || res);
                    })
                    .fail(() => $('#stat-users-count').text('Err'));

                // Fetch Classes Count
                $.get(API.CLASSES)
                    .done(res => {
                        let count = res.data ? res.data.length : (Array.isArray(res) ? res.length : 0);
                        $('#stat-classes-count').text(count);
                    })
                    .fail(() => $('#stat-classes-count').text('Err'));

                // Fetch Subjects Count
                $.get(API.SUBJECTS)
                    .done(res => {
                        let count = res.data ? res.data.length : (Array.isArray(res) ? res.length : 0);
                        $('#stat-subjects-count').text(count);
                    })
                    .fail(() => $('#stat-subjects-count').text('Err'));

                // Fetch Competencies Count
                $.get(API.COMPETENCIES)
                    .done(res => {
                        let count = res.data ? res.data.length : (Array.isArray(res) ? res.length : 0);
                        $('#stat-competencies-count').text(count);
                    })
                    .fail(() => $('#stat-competencies-count').text('Err'));
            }

            /**
             * 2. Render Recent Users Table
             */
            function renderRecentUsers(users) {
                const $tableBody = $('#recent-users-table-body');
                $tableBody.empty();

                if (!users || users.length === 0) {
                    $tableBody.html('<tr><td colspan="5" class="text-center">No users found.</td></tr>');
                    return;
                }

                // Ambil 5 user terakhir (asumsi data belum di-sort dari backend, kita slice manual)
                // Jika backend sudah sort desc by created_at, cukup slice(0, 5)
                // Kita gunakan slice(0, 5) untuk keamanan
                const recentUsers = users.slice(0, 5);

                recentUsers.forEach(user => {
                    // Format tanggal menggunakan moment.js atau fallback JS date
                    const dateStr = user.created_at ?
                        moment(user.created_at).format('DD MMM YYYY, HH:mm') :
                        '-';

                    const statusBadge = user.is_active ?
                        '<span class="badge badge-success">Active</span>' :
                        '<span class="badge badge-secondary">Inactive</span>';

                    const row = `
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="mr-2">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                    ${user.name.charAt(0).toUpperCase()}
                                </div>
                            </div>
                            <div>
                                <span class="font-weight-bold">${user.name}</span>
                            </div>
                        </div>
                    </td>
                    <td>${user.email}</td>
                    <td>${statusBadge}</td>
                    <td>${dateStr}</td>
                </tr>
            `;
                    $tableBody.append(row);
                });
            }

            // Initialize
            loadDashboardStats();
        });
    </script>
@endpush
