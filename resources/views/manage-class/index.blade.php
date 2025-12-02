@extends('layouts.app')

@section('title', 'Kelola Kelas: ' . ($class->name ?? 'Nama Kelas'))

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="{{ asset('assets/css/manage-class/manage-class.css') }}">
@endpush
@section('content')
    <div class="container-fluid">
        {{-- Breadcrumb --}}
        @include('layouts.components.breadcrumb')

        {{-- Kartu Informasi Kelas --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0">
                    <i class="fas fa-school me-2 text-primary"></i>
                    Kelola Kelas: <span class="fw-bold text-dark">{{ $class->name ?? 'Nama Kelas' }}</span>
                </h5>
                <div class="mt-2 text-muted small">
                    <span class="me-3"><i class="fas fa-user-tie me-1"></i> Wali Kelas:
                        <strong>{{ $class->homeroomTeacher->name ?? 'Belum Ditentukan' }}</strong></span>
                    <span><i class="fas fa-user-friends me-1"></i> Jumlah Siswa: <strong
                            id="student-count">...</strong></span>
                </div>
            </div>
        </div>

        {{-- Konten Utama dengan Tabs --}}
        @include('manage-class.partials.content')
    </div>


    {{-- =================================================================== --}}
    {{-- ========================== MODAL SECTION ========================== --}}
    {{-- =================================================================== --}}

    {{-- Modal Tambah Siswa --}}
    @include('manage-class.partials.studentModal')

    {{-- Modal Jadwal --}}
    @include('manage-class.partials.scheduleModal')

    {{-- Modal Bahan Ajar --}}
    @include('manage-class.partials.materialModal')

@endsection

@push('scripts')
    {{-- Load jQuery, Bootstrap, DataTables, SweetAlert, Select2 --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    {{-- Load file JS utama --}}
    <script src="{{ asset('assets/js/app/manage-class/manage-class.js') }}"></script>
@endpush
