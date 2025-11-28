@extends('layouts.app')

@section('title', 'Jawab Tugas')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/task/formAnswer.css') }}">
@endpush

@section('content')
    <div class="container-fluid">
        @include('layouts.components.breadcrumb')

        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-8">

                {{-- AREA LOADING --}}
                <div id="loading-state">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5 class_="mt-3">Memuat soal...</h5>
                </div>

                {{-- AREA ERROR --}}
                <div class="alert alert-danger" id="error-state">
                    <h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Gagal Memuat Tugas</h4>
                    <p id="error-message">Terjadi kesalahan saat mengambil data tugas. Silakan coba lagi nanti.</p>
                </div>

                {{-- AREA FORM JAWABAN (UTAMA) --}}
                <form id="answer-form">
                    {{-- Input Hidden --}}
                    <input type="hidden" id="task_id" value="{{ $task_id }}">
                    <input type="hidden" id="class_id" value="{{ $class_id }}">

                    {{-- 1. Kartu Header Detail Tugas --}}
                    <div class="card shadow-sm mb-4 task-header-card">
                        <div class="card-body">
                            <h2 class="mb-1" id="task-title">...</h2>
                            <p class="mb-2 text-primary fw-semibold" id="task-subject">...</p>
                            <hr>
                            <p class="mb-1 small"><strong><i class="fas fa-play me-2"></i>Mulai:</strong> <span
                                    id="task-start-time">...</span></p>
                            <p class="mb-1 small"><strong><i class="fas fa-stop-circle me-2"></i>Selesai:</strong> <span
                                    id="task-end-time">...</span></p>
                            <hr>
                            <div class="mt-3" id="task-description">
                                {{-- Deskripsi akan dimuat di sini --}}
                            </div>
                        </div>
                    </div>

                    {{-- 2. Kontainer untuk Pertanyaan --}}
                    <div id="question-container">
                        {{-- Pertanyaan akan dirender oleh JS di sini --}}
                    </div>

                    {{-- 3. Tombol Kumpulkan --}}
                    <div class="d-grid gap-2 mt-4 mb-5">
                        <button type="button" class="btn btn-success btn-lg" id="submit-btn">
                            <i class="fas fa-check-circle me-2"></i>Kumpulkan Jawaban
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Kita butuh SweetAlert2 untuk konfirmasi --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Buat file JS baru untuk halaman ini --}}
    <script src="{{ asset('assets/js/app/task/formAnswer.js') }}"></script>
@endpush
