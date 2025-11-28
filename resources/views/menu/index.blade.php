@extends('layouts.app')
@section('title', 'Navigation & Access Control')

@section('content')
    <div class="container-fluid py-4">
        @include('layouts.components.breadcrumb', ['title' => 'Navigation & Access Control', 'links' => [['name' => 'Dashboard', 'url' => '/'], ['name' => 'Navigation & Access Control']]])

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4 border-0 shadow-sm rounded overflow-hidden" id="accessControlTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active px-4 py-3" id="menu-structure-tab" data-bs-toggle="tab" data-bs-target="#menu-structure-panel" type="button" role="tab" aria-controls="menu-structure-panel" aria-selected="true">
                    <i class="fas fa-list me-2"></i> Menu Structure
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link px-4 py-3" id="permissions-tab" data-bs-toggle="tab" data-bs-target="#permissions-panel" type="button" role="tab" aria-controls="permissions-panel" aria-selected="false">
                    <i class="fas fa-shield-alt me-2"></i> Role & Menu Permissions
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="accessControlTabsContent">
            <!-- TAB 1: Menu Structure -->
            <div class="tab-pane fade show active" id="menu-structure-panel" role="tabpanel" aria-labelledby="menu-structure-tab">
                <div class="row g-4">
                    <!-- Daftar Struktur Menu -->
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header bg-white border-0 py-3 px-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0 fw-bold"><i class="fas fa-list me-2"></i>Menu Structure</h5>
                                    <button class="btn btn-primary btn-sm px-4" id="save-order-btn">
                                        <i class="fas fa-save me-2"></i>Save Order
                                    </button>
                                </div>
                                <p class="mb-0 text-muted small mt-2">Drag and drop to reorder menus.</p>
                            </div>
                            <div class="card-body p-4">
                                <div id="menu-list" class="sortable-list">
                                    <div class="text-center py-5">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-3 text-muted">Loading Menu Structure...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Tambah/Edit Menu -->
                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm rounded-3 sticky-top" style="top: 20px;">
                            <div class="card-header bg-white border-0 py-3 px-4">
                                <h5 class="mb-0 fw-bold" id="form-title"><i class="fas fa-plus-circle me-2"></i>Add New Menu</h5>
                                <p class="mb-0 text-muted small mt-2" id="form-subtitle">New items will be added at the bottom.</p>
                            </div>
                            <div class="card-body p-4">
                                <form id="menu-form" autocomplete="off">
                                    <input type="hidden" id="menu-id" name="menu_id">
                                    <input type="hidden" id="form-mode" value="add">

                                    <div class="mb-3">
                                        <label for="menu-title" class="form-label fw-semibold small">Title *</label>
                                        <input type="text" class="form-control rounded-3" id="menu-title" name="title" placeholder="e.g., Dashboard" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="menu-url" class="form-label fw-semibold small">URL / Route</label>
                                        <input type="text" class="form-control rounded-3" id="menu-url" name="url" placeholder="/dashboard or #">
                                    </div>

                                    <div class="mb-3">
                                        <label for="menu-icon" class="form-label fw-semibold small">Icon</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i id="icon-preview" class="fas fa-home"></i></span>
                                            <select class="form-select rounded-3" id="menu-icon" name="icon">
                                                <option value="">Select an icon</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="menu-parent" class="form-label fw-semibold small">Parent Menu (Optional)</label>
                                        <select class="form-select rounded-3" id="menu-parent" name="parent_id">
                                            <option value="">-- Root Menu (No parent) --</option>
                                        </select>
                                        <small class="text-muted">Select a parent if this is a submenu.</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="menu-status" class="form-label fw-semibold small">Status</label>
                                        <select class="form-select rounded-3" id="menu-status" name="is_active">
                                            <option value="1" selected>Active</option>
                                            <option value="0">Inactive</option>
                                        </select>
                                    </div>
                                </form>
                            </div>
                            <div class="card-footer bg-white border-0 text-end py-3 px-4">
                                <button type="button" class="btn btn-outline-secondary px-4 me-2" id="cancel-btn">
                                    <span id="cancel-text">Clear</span>
                                </button>
                                <button type="submit" class="btn btn-primary px-4" id="submit-btn" form="menu-form">
                                    <i class="fas fa-plus-circle me-2"></i> <span id="submit-text">Add Menu</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: Roles & Permissions -->
            <div class="tab-pane fade" id="permissions-panel" role="tabpanel" aria-labelledby="permissions-tab">
                <div class="row g-4">
                    <!-- Menu List untuk Permission -->
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header bg-white border-0 py-3 px-4">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-sitemap me-2"></i>Select Menu</h5>
                                <p class="mb-0 text-muted small mt-2">Choose a menu to manage permissions.</p>
                            </div>
                            <div class="card-body p-0">
                                <div id="permission-menu-list" class="list-group list-group-flush" style="max-height: 600px; overflow-y: auto;">
                                    <div class="text-center p-4">
                                        <div class="spinner-border text-primary spinner-border-sm" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-2 text-muted small">Loading menus...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Role Access & User Overrides -->
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm rounded-3 mb-4">
                            <div class="card-header bg-white border-0 py-3 px-4">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-users-cog me-2"></i>Role Permissions</h5>
                                <p class="mb-0 text-muted small mt-2">Manage which roles can access this menu and what they can do.</p>
                            </div>
                            <div class="card-body p-4">
                                <div id="role-permissions-content">
                                    <div class="text-center text-muted py-5">
                                        <i class="fas fa-hand-pointer fa-3x mb-3 opacity-25"></i>
                                        <p>Select a menu from the left panel to set role access and permissions.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header bg-white border-0 py-3 px-4">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-user-shield me-2"></i>User Menu Overrides</h5>
                                <p class="mb-0 text-muted small mt-2">Grant or revoke access for specific users, overriding their role permissions.</p>
                            </div>
                            <div class="card-body p-4">
                                <div id="user-override-content">
                                    <div class="text-center text-muted py-5">
                                        <i class="fas fa-hand-pointer fa-3x mb-3 opacity-25"></i>
                                        <p>Select a menu from the left panel.</p>
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

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/app/menu/menu.css') }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('assets/js/app/menu/listIcon.js') }}"></script>
    <script src="{{ asset('assets/js/app/menu/menu.js') }}"></script>
@endpush
