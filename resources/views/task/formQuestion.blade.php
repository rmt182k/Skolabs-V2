@extends('layouts.app')

{{-- [GABUNGAN] Judul Halaman dinamis --}}
@section('title', isset($task_id) ? 'Edit Tugas' : 'Buat Tugas Baru')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<link rel="stylesheet" href="{{ asset('assets/css/task/task.css') }}">
@endpush

@section('content')
<div class="container-fluid">
    @include('layouts.components.breadcrumb')

    <div class="row justify-content-center">
        <div class="col-lg-12">

            {{-- [GABUNGAN] Judul Halaman dinamis --}}
            <h1 class="h3 mb-4">{{ isset($task_id) ? 'Edit Tugas' : 'Buat Tugas Baru' }}</h1>

            <div id="messageArea" class="mb-3"></div>

            <form id="taskForm" onsubmit="return false;">

                {{-- [GABUNGAN] Input hidden ini akan berisi ID (mode edit) atau kosong (mode create) --}}
                <input type="hidden" id="task_id" value="{{ $task_id ?? null }}">
                <input type="hidden" id="class_id" value="{{ $class_id }}"> {{-- class_id selalu ada --}}

                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i>Detail Tugas</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold">Judul Tugas
                                <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title"
                                placeholder="Masukkan judul tugas" required>
                        </div>

                        <div class="row">
                            {{-- [BARU] Tambahkan dropdown Mata Pelajaran di sini --}}
                            <div class="col-md-6 mb-3">
                                <label for="subject_id" class="form-label fw-semibold">Mata Pelajaran <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="subject_id" name="subject_id" required>
                                    {{-- Akan diisi oleh Select2 AJAX --}}
                                </select>
                                <small class="form-text text-muted">Hanya mata pelajaran yang terjadwal di kelas
                                    ini.</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="type" class="form-label fw-semibold">Tipe Tugas <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="">Pilih Tipe</option>
                                    <option value="task">Tugas (Task)</option>
                                    <option value="quiz">Kuis (Quiz)</option>
                                    <option value="exam">Ujian (Exam)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="total_possible_score" class="form-label fw-semibold">Total Skor <span
                                        class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="total_possible_score"
                                    name="total_possible_score" min="0" value="0" placeholder="e.g., 100">
                                <small class="form-text text-muted">Akan terisi otomatis
                                    jika 0.</small>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="start_time" class="form-label fw-semibold">Waktu Mulai <span
                                        class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="start_time" name="start_time"
                                    required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="end_time" class="form-label fw-semibold">Waktu Selesai <span
                                        class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="end_time" name="end_time"
                                    required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="duration_minutes" class="form-label fw-semibold">Durasi (Menit)</label>
                                <input type="number" class="form-control" id="duration_minutes" name="duration_minutes"
                                    min="1" placeholder="e.g., 60 (Opsional)">
                                <small class="form-text text-muted">Biarkan kosong jika tidak ada batasan waktu.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label fw-semibold">Status <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="draft">Draft (Belum Diterbitkan)</option>
                                    <option value="published">Diterbitkan (Buka)</option>
                                    <option value="closed">Ditutup (Tidak Bisa Diakses)</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" rows="3"
                                placeholder="Deskripsi tugas (opsional)"></textarea>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="fas fa-question-circle me-2"></i>Pertanyaan</h5>
                        <span class="badge bg-light text-dark" id="question-counter">0
                            Pertanyaan</span>
                    </div>
                    <div class="card-body">
                        <div id="question-builder"></div>

                        <div class="empty-state" id="empty-state">
                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                            <h5>Mulai buat tugas Anda</h5>
                            <p class="text-muted">Klik "Tambah Pertanyaan" untuk memulai</p>
                        </div>

                        <div class="text-center mt-3">
                            <button type="button" class="btn btn-primary" id="add-question-btn">
                                <i class="fas fa-plus me-2"></i>Tambah Pertanyaan
                            </button>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4 mb-5">
                    <button type="button" class="btn btn-secondary" id="cancelBtn">
                        <i class="fas fa-arrow-left me-2"></i>Batal
                    </button>

                    {{-- [GABUNGAN] Tombol dinamis --}}
                    <button type="button" class="btn btn-success btn-lg" id="saveBtn">
                        <i class="fas fa-save me-2"></i>
                        <span id="saveBtnText">{{ isset($task_id) ? 'Update Tugas' : 'Simpan Tugas' }}</span>
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

{{-- Semua Template --}}
<template id="question-template">
    <div class="question-card mb-3">
        <div class="question-header p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="question-number me-3"></div>
                    <span class="fw-semibold">Pertanyaan</span>
                </div>
                <button type="button" class="btn-remove-question" title="Hapus Pertanyaan">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <div class="p-3">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Teks Pertanyaan <span class="text-danger">*</span></label>
                    <textarea class="form-control question-text" rows="2" placeholder="Masukkan pertanyaan Anda"></textarea>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Tipe Pertanyaan</label>
                    <select class="form-select question-type-select">
                        <option value="short_answer">Isian Singkat</option>
                        <option value="multiple_choice">Pilihan Ganda</option>
                        <option value="essay">Esai</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Skor <span class="text-danger">*</span></label>
                    <input type="number" class="form-control question-score" min="1" value="10"
                        placeholder="e.g., 10">
                </div>
            </div>

            <div class="answer-container mb-3">
            </div>

            {{-- [DIHAPUS] Seluruh blok div.competency-container telah dihapus dari sini --}}

        </div>
    </div>
</template>
<template id="answer-short-answer-template">
    <div>
        <label class="form-label fw-semibold text-primary">Kunci Jawaban <span class="text-danger">*</span></label>
        <input type="text" class="form-control correct-answer-input" placeholder="Masukkan kunci jawaban singkat">
        <small class="form-text text-muted">Sensitif huruf besar/kecil. Untuk beberapa jawaban, pisahkan dengan
            koma (e.g., A,B)</small>
    </div>
</template>
<template id="answer-essay-template">
    <div>
        <label class="form-label fw-semibold text-primary">Model Jawaban / Rubrik</label>
        <textarea class="form-control correct-answer-textarea" rows="3"
            placeholder="Masukkan model jawaban atau rubrik penilaian (opsional)"></textarea>
    </div>
</template>
<template id="answer-mc-template">
    <div>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="form-label fw-semibold mb-0">Opsi Jawaban <small class="text-muted">(Centang
                    jawaban yang benar)</small></label>
            <div class="form-check form-switch">
                <input class="form-check-input allow-multiple-answers-cb" type="checkbox" role="switch">
                <label class="form-check-label small">Boleh pilih >1 jawaban</label>
            </div>
        </div>
        <div class="mc-options-list">
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary mt-2 add-option-btn">
            <i class="fas fa-plus me-1"></i>Tambah Opsi
        </button>
    </div>
</template>
<template id="mc-option-template">
    <div class="d-flex align-items-center mb-2 p-2 border rounded mc-option">
        <div class="form-check me-3">
            <input class="form-check-input correct-answer-selector" value="">
        </div>
        <div class="option-label fw-semibold me-2"></div>
        <input type="text" class="form-control option-input" placeholder="Masukkan teks opsi">
        <button type="button" class="btn-remove-option ms-2" title="Hapus Opsi">
            <i class="fas fa-trash"></i>
        </button>
    </div>
</template>

{{-- [DIHAPUS] Template 'competency-row-template' telah dihapus seluruhnya --}}

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset('assets/js/app/task/formQuestion.js') }}"></script>
@endpush