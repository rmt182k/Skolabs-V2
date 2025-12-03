@extends('layouts.app')

@section('title', 'Penilaian Tugas Siswa')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/task/formGrade.css') }}">
    <style>
        /* [BARU] Style Tambahan untuk AI enhancement */
        .ai-result-section {
            margin-top: 15px;
            font-size: 0.9rem;
        }

        .ai-feedback-box {
            border-left: 4px solid #198754;
            /* Hijau sukses */
            background-color: #f8fffb;
            padding: 12px;
            border-radius: 4px;
        }

        .ai-failed-box {
            border-left: 4px solid #dc3545;
            /* Merah gagal */
            background-color: #fff8f8;
            padding: 12px;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .ai-pending-box {
            border-left: 4px solid #ffc107;
            /* Kuning pending */
            background-color: #fffbf0;
            padding: 12px;
            text-align: center;
            color: #856404;
        }

        .btn-retry-single {
            font-size: 0.8rem;
            padding: 4px 8px;
        }

        .question-card {
            transition: all 0.3s ease;
        }

        .question-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1) !important;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid py-4">
        @include('layouts.components.breadcrumb')

        <div id="loading-state" class="text-center py-5">
            <div class="spinner-border text-primary mb-3" role="status"></div>
            <h5 class="text-muted">Memuat data penilaian...</h5>
        </div>

        <div id="error-state" class="alert alert-danger" style="display:none;">
            <h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Terjadi Kesalahan</h4>
            <p id="error-message" class="mb-0"></p>
        </div>

        <div id="grading-form" style="display:none;">

            <div class="card shadow-sm mb-4 border-0">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-lg-7">
                            <h3 class="mb-3 fw-bold text-dark" id="task-title"></h3>
                            <div class="d-flex flex-wrap gap-4 text-muted">
                                <div><i class="fas fa-user-graduate me-2"></i><strong id="student-name"
                                        class="text-dark"></strong></div>
                                <div><i class="fas fa-id-card me-2"></i><span id="student-nis"></span></div>
                                <div><i class="fas fa-clock me-2"></i><span id="submitted-at"></span></div>
                            </div>
                        </div>
                        <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                            <div class="mb-2">
                                <span class="text-muted small me-2">Status Submisi:</span>
                                <span id="submission-status" class="badge"></span>
                            </div>

                            <div class="btn-group">
                                <a href="/classes/{{ $class_id }}/tasks/{{ $task_id }}/submissions"
                                    class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-arrow-left me-1"></i> Kembali
                                </a>
                                <button type="button" class="btn btn-primary btn-sm" id="btn-run-all-ai">
                                    <i class="fas fa-robot me-1"></i> Generate Ulang Semua AI
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <form id="grade-form">
                <input type="hidden" id="submission_id" value="{{ $submission_id }}">
                <input type="hidden" id="class_id" value="{{ $class_id }}">
                <input type="hidden" id="task_id" value="{{ $task_id }}">

                <div id="questions-container"></div>

                <div class="card shadow border-0 mb-5 sticky-bottom-card">
                    <div class="card-body bg-dark text-white rounded">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1"><i class="fas fa-calculator me-2"></i>Total Nilai Akhir</h5>
                                <small class="opacity-75">Nilai ini yang akan disimpan ke rapor siswa</small>
                            </div>
                            <div class="text-end">
                                <div class="display-6 fw-bold text-warning" id="total-score-display">0</div>
                            </div>
                        </div>
                        <hr class="border-secondary my-3">
                        <div class="row">
                            <div class="col-md-9">
                                <label class="form-label text-white small">Feedback Umum (Opsional)</label>
                                <input type="text" class="form-control form-control-sm bg-secondary text-white border-0"
                                    id="teacher-feedback" placeholder="Pesan untuk siswa...">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" class="btn btn-success w-100 fw-bold" id="save-grade-btn">
                                    <i class="fas fa-save me-2"></i>Simpan Penilaian
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <template id="question-template">
        <div class="card mb-4 question-card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-primary">
                    <span class="question-number badge bg-primary me-2 rounded-pill"></span>
                    <span class="question-type-text text-uppercase small text-muted"></span>
                </h6>
                <div class="text-muted small">
                    Max Score: <strong class="max-score-val"></strong>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="question-text fw-semibold text-dark"></div>
                </div>

                <div class="bg-light p-3 rounded mb-3 border">
                    <label class="small text-muted fw-bold text-uppercase mb-1">Jawaban Siswa</label>
                    <div class="student-answer-content text-dark" style="white-space: pre-wrap;"></div>
                </div>

                <div class="ai-result-section mb-3">
                </div>

                <div class="row g-2 align-items-center border-top pt-3 mt-3">
                    <div class="col-md-2">
                        <label class="form-label small fw-bold mb-1">Nilai Final</label>
                        <input type="number" class="form-control score-input fw-bold" min="0" step="0.01"
                            placeholder="0">
                    </div>
                    <div class="col-md-10">
                        <label class="form-label small fw-bold mb-1">Komentar Guru (Override)</label>
                        <input type="text" class="form-control comment-input"
                            placeholder="Tulis komentar manual jika perlu...">
                    </div>
                </div>
            </div>
        </div>
    </template>

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // KONFIGURASI
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            const submissionId = $('#submission_id').val();
            const API_URL = `/api/submissions/${submissionId}/details`;

            // LOAD DATA
            loadData();

            function loadData() {
                $('#loading-state').show();
                $('#grading-form').hide();
                $('#error-state').hide();

                $.ajax({
                    url: API_URL,
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            renderForm(response.data);
                        } else {
                            showError(response.message);
                        }
                    },
                    error: function(xhr) {
                        showError('Gagal memuat data: ' + (xhr.responseJSON?.message || xhr
                        .statusText));
                    }
                });
            }

            function showError(msg) {
                $('#loading-state').hide();
                $('#error-message').text(msg);
                $('#error-state').show();
            }

            // RENDER FORM
            function renderForm(data) {
                $('#loading-state').hide();
                $('#grading-form').fadeIn();

                // 1. Isi Header
                $('#task-title').text(data.task_title);
                $('#student-name').text(data.student_name);
                $('#student-nis').text(data.student_nis);
                $('#submitted-at').text(data.submitted_at || '-'); // Format di backend/JS
                $('#teacher-feedback').val(data.teacher_feedback || '');

                // Badge Status
                let badgeClass = 'bg-secondary';
                if (data.status === 'graded') badgeClass = 'bg-success';
                else if (data.status === 'pending_review') badgeClass = 'bg-warning text-dark';
                else if (data.status === 'ai_processing') badgeClass = 'bg-info text-dark';
                $('#submission-status').text(data.status_text).removeClass().addClass('badge ' + badgeClass);

                // 2. Render Soal
                const $container = $('#questions-container');
                $container.empty();
                const template = document.getElementById('question-template').content;
                let totalScore = 0;

                data.answers.forEach((ans, index) => {
                    const clone = document.importNode(template, true);
                    const $card = $(clone).find('.question-card');

                    // Info Dasar
                    $card.find('.question-number').text(index + 1);
                    $card.find('.question-type-text').text(ans.question_type);
                    $card.find('.max-score-val').text(ans.question_score);
                    $card.find('.question-text').html(ans.question_text);
                    $card.find('.student-answer-content').html(ans
                    .student_answer); // Pastikan sudah disanitasi/format di controller

                    // Input Nilai Guru (Prioritas: Nilai Guru > Nilai AI > 0)
                    const currentScore = ans.score_awarded !== null ? ans.score_awarded : (ans
                        .ai_suggested_score || 0);
                    const $scoreInput = $card.find('.score-input');
                    $scoreInput.val(currentScore).data('answer-id', ans.answer_id);
                    $card.find('.comment-input').val(ans.teacher_comment || ans.ai_feedback || '');

                    totalScore += parseFloat(currentScore);

                    // Update Total saat input berubah
                    $scoreInput.on('input', calculateTotal);

                    // --- LOGIKA TAMPILAN AI ---
                    const $aiSection = $card.find('.ai-result-section');

                    if (ans.ai_processing_status === 'completed') {
                        // SUKSES
                        $aiSection.html(`
                    <div class="ai-feedback-box">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <strong class="text-success"><i class="fas fa-robot me-1"></i> Analisis AI</strong>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-success">Skor: ${ans.ai_suggested_score}</span>
                                <button type="button" class="btn btn-outline-secondary btn-sm btn-retry-single" data-id="${ans.answer_id}" title="Refresh AI">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                        <div class="text-muted small fst-italic">"${ans.ai_feedback}"</div>
                    </div>
                `);
                    } else if (ans.ai_processing_status === 'failed') {
                        // GAGAL
                        $aiSection.html(`
                    <div class="ai-failed-box">
                        <div class="text-danger small">
                            <i class="fas fa-exclamation-circle me-1"></i>
                            <strong>Gagal:</strong> ${ans.ai_feedback || 'Koneksi error'}
                        </div>
                        <button type="button" class="btn btn-danger btn-sm btn-retry-single" data-id="${ans.answer_id}">
                            <i class="fas fa-redo me-1"></i> Coba Lagi
                        </button>
                    </div>
                `);
                    } else if (ans.ai_processing_status === 'pending') {
                        // PENDING
                        $aiSection.html(`
                    <div class="ai-pending-box">
                        <i class="fas fa-spinner fa-spin me-2"></i> Sedang dalam antrian analisis AI...
                        <button type="button" class="btn btn-link btn-sm p-0 ms-2 btn-retry-single" data-id="${ans.answer_id}">Paksa Jalan</button>
                    </div>
                `);
                    } else {
                        // Belum ada status (manual submission)
                        $aiSection.html(
                            `<div class="text-muted small text-center border p-2 rounded bg-light">Belum diproses AI</div>`
                            );
                    }

                    $container.append($card);
                });

                calculateTotal();
            }

            function calculateTotal() {
                let total = 0;
                $('.score-input').each(function() {
                    total += parseFloat($(this).val()) || 0;
                });
                $('#total-score-display').text(total.toFixed(2));
            }

            // --- BUTTON ACTIONS ---

            // 1. Tombol "Generate Ulang Semua"
            $('#btn-run-all-ai').click(function() {
                Swal.fire({
                    title: 'Generate Ulang Semua?',
                    text: "Data AI lama akan dihapus dan diganti baru. Nilai manual Anda aman.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Proses!',
                    confirmButtonColor: '#0d6efd'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Memproses...',
                            didOpen: () => Swal.showLoading()
                        });

                        $.ajax({
                            url: `/api/submissions/${submissionId}/run-ai`,
                            method: 'POST',
                            data: {
                                force: true
                            }, // Kirim flag force
                            success: function() {
                                Swal.fire('Berhasil', 'Permintaan dikirim.', 'success')
                                    .then(() => loadData());
                            },
                            error: function(xhr) {
                                Swal.fire('Error', xhr.responseJSON?.message || 'Gagal',
                                    'error');
                            }
                        });
                    }
                });
            });

            // 2. Tombol "Coba Lagi" (Per Soal)
            $(document).on('click', '.btn-retry-single', function() {
                const btn = $(this);
                const answerId = btn.data('id');
                const originalHtml = btn.html();

                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                $.ajax({
                    url: `/api/submissions/${submissionId}/answers/${answerId}/retry-ai`,
                    method: 'POST',
                    success: function(response) {
                        if (response.success) {
                            // Update UI parsial agar lebih smooth
                            const data = response.data;
                            const $card = btn.closest('.question-card');

                            // Update bagian AI result
                            $card.find('.ai-result-section').html(`
                        <div class="ai-feedback-box">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <strong class="text-success"><i class="fas fa-robot me-1"></i> Analisis AI (Baru)</strong>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-success">Skor: ${data.ai_suggested_score}</span>
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-retry-single" data-id="${answerId}">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="text-muted small fst-italic">"${data.ai_feedback}"</div>
                        </div>
                    `);

                            // Update nilai input jika kosong atau mau di-override otomatis (opsional)
                            // $card.find('.score-input').val(data.ai_suggested_score).trigger('input');

                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: 'Berhasil di-generate!',
                                showConfirmButton: false,
                                timer: 1500
                            });
                        }
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false).html(originalHtml);
                        Swal.fire('Gagal', xhr.responseJSON?.message || 'Error', 'error');
                    }
                });
            });

            // 3. Tombol Simpan Nilai Akhir
            $('#save-grade-btn').click(function() {
                const grades = [];
                $('.score-input').each(function() {
                    grades.push({
                        answer_id: $(this).data('answer-id'),
                        score_awarded: $(this).val(),
                        teacher_comment: $(this).closest('.card-body').find(
                            '.comment-input').val()
                    });
                });

                const feedback = $('#teacher-feedback').val();

                Swal.fire({
                    title: 'Menyimpan...',
                    didOpen: () => Swal.showLoading()
                });

                $.ajax({
                    url: `/api/submissions/${submissionId}/grade`,
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        grades: grades,
                        teacher_feedback: feedback
                    }),
                    success: function() {
                        Swal.fire('Tersimpan!', 'Penilaian berhasil disimpan.', 'success')
                            .then(() => window.location.href =
                                `/classes/${$('#class_id').val()}/tasks/${$('#task_id').val()}/submissions`
                                );
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Gagal menyimpan',
                            'error');
                    }
                });
            });
        });
    </script>
@endpush
