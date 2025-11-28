@extends('layouts.app')

@section('title', 'Kelas')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .card-view-item {
            transition: transform 0.2s;
        }
        .card-view-item:hover {
            transform: translateY(-5px);
        }
        .view-toggle-btn {
            transition: all 0.3s;
        }
        #filter-controls {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        #classes-table .btn {
            margin-right: 3px;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        {{-- Breadcrumb --}}
        @include('layouts.components.breadcrumb', ['page' => 'Kelas', 'routes' => [['name' => 'Home', 'url' => '/']]])

        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="card-title mb-0">
                    <i class="fas fa-school me-2 text-primary"></i>Data Kelas
                </h5>
                <div class="d-flex align-items-center gap-2">
                    {{-- Toggle View Buttons --}}
                    <div class="btn-group" role="group" aria-label="Toggle view">
                        <button type="button" class="btn btn-sm btn-outline-primary active view-toggle-btn"
                                id="view-table-btn" title="Tampilan Tabel">
                            <i class="fas fa-table"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary view-toggle-btn"
                                id="view-card-btn" title="Tampilan Card">
                            <i class="fas fa-th-large"></i>
                        </button>
                    </div>
                    {{-- Add Button --}}
                    <button type="button" class="btn btn-primary btn-sm" id="add-class-btn">
                        <i class="fas fa-plus me-1"></i> Tambah Kelas
                    </button>
                </div>
            </div>

            <div class="card-body">
                {{-- Filter Controls --}}
                <div id="filter-controls">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control" id="filter-search"
                                       placeholder="Cari nama kelas...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="filter-academic-year">
                                <option value="">Semua Tahun Ajaran</option>
                                {{-- Options loaded by JS --}}
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="filter-level">
                                <option value="">Semua Jenjang</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="filter-major">
                                <option value="">Semua Jurusan</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- TABLE VIEW --}}
                <div id="table-view">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered" id="classes-table" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%;">No</th>
                                    <th>Nama Kelas</th>
                                    <th>Jenjang</th>
                                    <th>Jurusan</th>
                                    <th>Tahun Ajaran</th>
                                    <th>Tingkat</th>
                                    <th style="width: 20%;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Data will be loaded via JavaScript --}}
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- CARD VIEW --}}
                <div id="card-view" style="display: none;">
                    <div class="row" id="class-cards-container">
                        {{-- Cards will be rendered by JavaScript --}}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL FORM --}}
    <div class="modal fade" id="classModal" tabindex="-1" aria-labelledby="classModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="classModalLabel">Form Kelas</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="classForm">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="class_id" name="id">

                        {{-- Jenjang Pendidikan --}}
                        <div class="mb-3">
                            <label for="educational_level_id" class="form-label">
                                Jenjang Pendidikan <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="educational_level_id" name="educational_level_id" required>
                                <option value="">Pilih Jenjang...</option>
                            </select>
                            <div class="invalid-feedback" id="educational_level_id-error"></div>
                        </div>

                        {{-- Tahun Ajaran --}}
                        <div class="mb-3">
                            <label for="academic_year_id" class="form-label">
                                Tahun Ajaran <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="academic_year_id" name="academic_year_id" required>
                                <option value="">Pilih Tahun Ajaran...</option>
                                {{-- Options loaded by JS --}}
                            </select>
                            <div class="invalid-feedback" id="academic_year_id-error"></div>
                        </div>

                        {{-- Jurusan (Conditional) --}}
                        <div class="mb-3" id="major-group" style="display: none;">
                            <label for="major_id" class="form-label">
                                Jurusan <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="major_id" name="major_id">
                                <option value="">Pilih Jurusan...</option>
                            </select>
                            <div class="invalid-feedback" id="major_id-error"></div>
                        </div>

                        {{-- === BARU: PREVIEW NAMA === --}}
                        <div class="mb-3">
                             <label for="name_preview" class="form-label">Generated Name (Preview)</label>
                             <input type="text" class="form-control" id="name_preview" readonly
                                style="background-color: #e9ecef; cursor: not-allowed;"
                                placeholder="e.g., 10 RPL A">
                             <small class="form-text text-muted">Nama ini dibuat otomatis dari Tingkat, Jurusan (jika ada), dan Suffix (jika ada).</small>
                        </div>
                        {{-- === SELESAI PREVIEW NAMA === --}}


                        {{-- Tingkat & Suffix Kelas --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="grade_level" class="form-label">
                                        Tingkat <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="grade_level"
                                           name="grade_level" required placeholder="Contoh: 10" min="1">
                                    <div class="invalid-feedback" id="grade_level-error"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    {{-- === PERUBAHAN DI SINI (SESUAI SKEMA) === --}}
                                    <label for="suffix" class="form-label">
                                        Suffix (Opsional)
                                    </label>
                                    {{-- 1. Ganti id="class_index" -> "suffix" --}}
                                    {{-- 2. Ganti name="class_index" -> "suffix" --}}
                                    {{-- 3. Hapus 'required' dan '*' (karena nullable) --}}
                                    <input type="text" class="form-control" id="suffix"
                                           name="suffix" placeholder="Contoh: A" maxlength="10">
                                    {{-- 4. Ganti id error --}}
                                    <div class="invalid-feedback" id="suffix-error"></div>
                                    {{-- === SELESAI PERUBAHAN === --}}
                                </div>
                            </div>
                        </div>

                        {{-- Alert Info (Dihapus karena sudah ada di preview) --}}
                        {{-- <div class="alert alert-info mb-0" role="alert"> ... </div> --}}

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    {{-- Pastikan path ini benar --}}
    <script src="{{ asset('assets/js/app/class/class.js') }}"></script>
@endpush

