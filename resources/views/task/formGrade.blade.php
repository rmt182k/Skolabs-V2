@extends('layouts.app')

@section('title', 'Penilaian Tugas Siswa')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/task/formGrade.css') }}">
@endpush

@section('content')
<div class="container-fluid py-4">
    @include('layouts.components.breadcrumb')

    <!-- ========== LOADING STATE ========== -->
    <div id="loading-state">
        <div class="loading-spinner mx-auto mb-3"></div>
        <h5 class="text-muted">Memuat data penilaian...</h5>
        <p class="text-muted small">Mohon tunggu sebentar</p>
    </div>

    <!-- ========== ERROR STATE ========== -->
    <div id="error-state" class="alert alert-danger" style="display:none;">
        <h4 class="alert-heading">
            <i class="fas fa-exclamation-triangle me-2"></i>Terjadi Kesalahan
        </h4>
        <hr>
        <p id="error-message" class="mb-0"></p>
    </div>

    <!-- ========== MAIN FORM ========== -->
    <div id="grading-form" style="display:none;">

        <!-- Header Info Card -->
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h3 class="mb-3 fw-bold text-dark" id="task-title"></h3>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <p class="mb-2">
                                    <i class="fas fa-user-graduate text-primary me-2"></i>
                                    <strong>Siswa:</strong>
                                    <span id="student-name" class="text-dark"></span>
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-id-card text-primary me-2"></i>
                                    <strong>NIS:</strong>
                                    <span id="student-nis" class="text-muted"></span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2">
                                    <i class="fas fa-clock text-success me-2"></i>
                                    <strong>Waktu Pengumpulan:</strong>
                                    <span id="submitted-at" class="text-dark"></span>
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-info-circle text-info me-2"></i>
                                    <strong>Status:</strong>
                                    <span id="submission-status" class="badge"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                        <div class="d-inline-block p-3 bg-light rounded-3">
                            <i class="fas fa-clipboard-check fa-3x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form id="grade-form">
            <input type="hidden" id="submission_id" value="{{ $submission_id }}">
            <input type="hidden" id="class_id" value="{{ $class_id }}">
            <input type="hidden" id="task_id" value="{{ $task_id }}">

            <!-- Questions Container -->
            <div id="questions-container"></div>

            <!-- Summary Card -->
            <div class="card shadow border-0 mb-4">
                <div class="card-body summary-card">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>Ringkasan Penilaian
                    </h5>
                    <hr class="border-white opacity-50">

                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <div class="text-center">
                                <p class="mb-2 opacity-75">Total Nilai</p>
                                <div class="total-score-display">
                                    <span id="total-score">0</span>
                                    <small>/ <span id="max-score">0</span></small>
                                </div>
                                <div class="progress" style="height: 8px; background: rgba(255,255,255,0.3);">
                                    <div id="progress-bar" class="progress-bar bg-white" role="progressbar"
                                         style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-7 mt-4 mt-md-0">
                            <label for="teacher-feedback" class="form-label text-white fw-semibold">
                                <i class="fas fa-comment-dots me-2"></i>Feedback Umum untuk Siswa
                            </label>
                            <textarea class="form-control feedback-textarea" id="teacher-feedback" rows="4"
                                placeholder="Tuliskan komentar atau saran untuk siswa (opsional)..."></textarea>
                            <small class="text-white opacity-75 d-block mt-2">
                                <i class="fas fa-info-circle me-1"></i>
                                Feedback ini akan dilihat oleh siswa
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex justify-content-between gap-3 mb-5">
                <a href="/classes/{{ $class_id }}/tasks/{{ $task_id }}/submissions"
                   class="btn btn-lg btn-secondary btn-action">
                    <i class="fas fa-arrow-left"></i>Kembali ke Daftar
                </a>
                <button type="button" class="btn btn-lg btn-success btn-action" id="save-grade-btn">
                    <i class="fas fa-save"></i>Simpan Penilaian
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========== TEMPLATES ========== -->
<template id="question-template">
    <div class="question-card">
        <div class="question-header">
            <h5>
                <i class="fas fa-question-circle me-2"></i>
                <span class="question-number"></span>
            </h5>
            <span class="question-score-badge"></span>
        </div>
        <div class="question-body">
            <p class="question-text"></p>

            <!-- Student Answer -->
            <div class="student-answer-box">
                <h6><i class="fas fa-user-edit me-2"></i>Jawaban Siswa</h6>
                <div class="student-answer-content"></div>
            </div>

            <!-- Auto-graded Info -->
            <div class="auto-grade-info" style="display:none;">
                <span class="auto-graded-badge">
                    <i class="fas fa-check-circle"></i>
                    Dinilai Otomatis oleh Sistem
                </span>
            </div>

            <!-- Manual Grade Section -->
            <div class="manual-grade-section" style="display:none;">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">
                            <i class="fas fa-star me-1 text-warning"></i>
                            Skor untuk Jawaban Ini
                        </label>
                        <div class="score-input-wrapper">
                            <input type="number" class="form-control form-control-lg score-input answer-score-input"
                                   min="0" step="0.01" placeholder="0">
                            <span class="max-score-label">/ <span class="max-score-span"></span></span>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">
                            <i class="fas fa-comment-medical me-1 text-info"></i>
                            Komentar untuk Jawaban Ini
                        </label>
                        <textarea class="form-control teacher-comment-input" rows="2"
                            placeholder="Berikan feedback spesifik untuk jawaban ini..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Competency Allocations -->
            <div class="competency-allocations-container"></div>
        </div>
    </div>
</template>

<template id="competency-allocation-template">
    <div class="competency-section">
        <h6>
            <i class="fas fa-chart-pie"></i>
            Penilaian Per Kompetensi
        </h6>
        <div class="competency-list"></div>
    </div>
</template>

<template id="competency-item-template">
    <div class="competency-item">
        <div class="competency-name mb-3"></div>
        <div class="row g-3 align-items-center">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Skor Kompetensi</label>
                <input type="number" class="form-control competency-score-input"
                       min="0" step="0.01" placeholder="0">
                <small class="text-muted">
                    Maks: <span class="competency-max-score fw-bold"></span>
                </small>
            </div>
            <div class="col-md-9">
                <label class="form-label small text-muted mb-1">Progress</label>
                <div class="progress competency-progress">
                    <div class="progress-bar competency-progress-bar" role="progressbar"
                         style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                        0%
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('assets/js/app/task/formGrade.js') }}"></script>
@endpush
