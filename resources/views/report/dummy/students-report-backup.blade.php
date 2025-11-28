@extends('layouts.app')
@section('title', 'Laporan Hasil Tugas: ' . ($submission->task_title ?? ''))

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
    <div class="container-fluid px-4 py-5">
        <div id="app" class="report-container">

            <div id="loading-spinner" class="text-center py-5">
                <div class="spinner-border text-primary" style="width: 3.5rem; height: 3.5rem;" role="status">
                    <span class="visually-hidden">Memuat...</span>
                </div>
                <p class="mt-3 text-muted fs-5">Memuat Laporan AI...</p>
            </div>

            <div id="report-content">
                <div
                    class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
                    <div>
                        <h2 class="fw-bold text-dark mb-1">Laporan Hasil Tugas</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="/dashboard" class="text-decoration-none">Dashboard</a>
                                </li>
                                <li class="breadcrumb-item"><a href="/classes/1/tasks/1"
                                        class="text-decoration-none">Tugas</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Laporan</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="btn-group no-print">
                        <button class="btn btn-outline-primary rounded-pill px-4" id="print-btn">
                            <i class="fas fa-print me-2"></i>Cetak
                        </button>
                        <a href="/api/export/submission/1/pdf" class="btn btn-outline-danger rounded-pill px-4">
                            <i class="fas fa-file-pdf me-2"></i>PDF
                        </a>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="card mb-4">
                            <div class="card-body p-4 text-center">
                                <div class="avatar-circle mb-3">
                                    <i class="fas fa-user"></i>
                                </div>
                                <h5 class="fw-bold mb-1" id="student-name">...</h5>
                                <p class="text-muted mb-2" id="class-name">...</p>
                                <span class="badge bg-success rounded-pill px-3 py-2">Siswa Aktif</span>

                                <hr class="my-4">

                                <div class="text-start small">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">NIS</span>
                                        <span class="fw-semibold" id="student-nis">...</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Email</span>
                                        <span class="fw-semibold text-truncate d-block" id="student-email">...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header bg-white border-0 py-3">
                                <h6 class="mb-0 fw-bold text-dark">
                                    <i class="fas fa-chart-bar text-primary me-2"></i>
                                    Statistik Pengerjaan
                                </h6>
                            </div>
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between py-2 border-bottom">
                                    <span><i class="fas fa-history text-primary me-2"></i>Waktu</span>
                                    <span class="fw-bold" id="duration">...</span>
                                </div>
                                <div class="d-flex justify-content-between py-2 border-bottom">
                                    <span><i class="fas fa-calendar-check text-success me-2"></i>Dikumpul</span>
                                    <span class="fw-bold" id="submitted-at">...</span>
                                </div>
                                <div class="d-flex justify-content-between py-2">
                                    <span><i class="fas fa-check-circle text-info me-2"></i>Jumlah Soal</span>
                                    <span class="fw-bold" id="total-questions">...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-header text-white py-4"
                                style="background: linear-gradient(135deg, var(--primary), #3b5bdb);">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <span class="badge bg-white bg-opacity-25 px-3 py-2 fs-6" id="task-type">...</span>
                                    </div>
                                    <div>
                                        <h4 class="mb-1 fw-bold" id="task-title">...</h4>
                                        <p class="mb-0 opacity-90" id="subject-name">...</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <div class="row text-center">
                                    <div class="col-md-6 py-3">
                                        <div class="h1 fw-bold mb-2" id="final-grade">...</div>
                                        <div class="progress mb-2">
                                            <div class="progress-bar" id="grade-progress" style="width: 0%"></div>
                                        </div>
                                        <small class="text-muted">dari 100 poin</small>
                                    </div>
                                    <div class="col-md-6 py-3">
                                        <div class="mb-3">
                                            <span class="badge badge-performance px-4 py-2"
                                                id="performance-label">...</span>
                                        </div>
                                        <div class="small text-muted">
                                            <div><i class="fas fa-trophy text-warning me-1"></i>
                                                Rank <span id="rank">...</span> dari <span
                                                    id="total-students">...</span>
                                            </div>
                                            <div>Rata-rata kelas: <span id="class-average">...</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4" id="competency-card">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                                <h6 class="mb-0 fw-bold">
                                    <i class="fas fa-chart-line text-primary me-2"></i>
                                    Pencapaian Kompetensi
                                </h6>
                            </div>
                            <div class="card-body p-4">
                                <canvas id="competencyChart" height="280"></canvas>
                            </div>
                        </div>

                        <div class="card">
                            <div
                                class="card-header bg-white d-flex justify-content-between align-items-center py-3 no-print">
                                <h6 class="mb-0 fw-bold">
                                    <i class="fas fa-list-check text-primary me-2"></i>
                                    Rincian Jawaban (<span id="answers-count">0</span> Soal)
                                </h6>
                                <div>
                                    <button class="btn btn-sm btn-outline-secondary rounded-pill" id="expand-all-btn">
                                        <i class="fas fa-expand me-1"></i>Buka Semua
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary rounded-pill" id="collapse-all-btn">
                                        <i class="fas fa-compress me-1"></i>Tutup Semua
                                    </button>
                                </div>
                            </div>
                            <div class="accordion accordion-flush" id="answersAccordion">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4" id="recommendations-card" style="display: none;">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-lightbulb text-warning me-2"></i>
                            Rekomendasi Pembelajaran
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2" id="recommendations-list"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            const submissionId = 1;
            const API_URL = `/api/submissions/1/report`;
            let competencyChart = null;

            // Helper Functions
            const getGradeColor = (g) => g >= 85 ? '#10b981' : g >= 70 ? '#0ea5e9' : g >= 60 ? '#f59e0b' :
            '#ef4444';
            const getGradeProgressClass = (g) => g >= 85 ? 'bg-success' : g >= 70 ? 'bg-info' : g >= 60 ?
                'bg-warning' : 'bg-danger';
            const getPerformanceLabel = (g) => g >= 90 ? 'Istimewa' : g >= 85 ? 'Baik Sekali' : g >= 70 ? 'Baik' :
                g >= 60 ? 'Cukup' : 'Perlu Perbaikan';
            const getPerformanceClass = (g) => g >= 90 ? 'bg-purple' : g >= 85 ? 'bg-success' : g >= 70 ?
                'bg-info' : g >= 60 ? 'bg-warning' : 'bg-danger';
            const getAnswerBadgeClass = (p) => p >= 85 ? 'bg-success' : p >= 70 ? 'bg-info' : p >= 60 ?
                'bg-warning' : 'bg-danger';
            const getQuestionType = (t) => t === 'multiple_choice' ? 'Pilihan Ganda' : t === 'essay' ? 'Esai' :
                'Isian Singkat';

            const formatDate = (d) => !d ? 'N/A' : new Date(d).toLocaleString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            const formatDuration = (s) => !s && s !== 0 ? 'N/A' : `${Math.floor(s/60)}m ${s%60}s`;

            const printReport = () => window.print();
            const expandAll = () => $('.accordion-collapse').collapse('show');
            const collapseAll = () => $('.accordion-collapse').collapse('hide');

            // Render Functions
            const renderReport = (data) => {
                const s = data.submission;
                const g = s.final_grade || 0;

                $('#student-name').text(s.student_name);
                $('#class-name').text(s.class_name);
                $('#student-nis').text(s.student_nis);
                $('#student-email').text(s.email);
                $('#duration').text(formatDuration(s.duration_seconds));
                $('#submitted-at').text(formatDate(s.submitted_at));
                $('#total-questions').text(`${data.answers.length}`);

                $('#task-type').text(getQuestionType(s.task_type));
                $('#task-title').text(s.task_title);
                $('#subject-name').text(s.subject_name);

                $('#final-grade').text(g).css('color', getGradeColor(g));
                $('#grade-progress').css('width', g + '%').removeClass(
                    'bg-success bg-info bg-warning bg-danger').addClass(getGradeProgressClass(g));
                $('#performance-label').text(getPerformanceLabel(g)).removeClass(
                    'bg-purple bg-success bg-info bg-warning bg-danger').addClass(getPerformanceClass(g));
                $('#rank').text(s.rank || 1);
                $('#total-students').text(s.total_students);
                $('#class-average').text(s.class_average);

                renderAnswers(data.answers);
                renderRecommendations(data.recommendations);
                initCharts(data.competencies);
            };

            const renderAnswers = (answers) => {
                const $acc = $('#answersAccordion').empty();
                $('#answers-count').text(answers.length);

                if (!answers.length) {
                    $acc.html('<div class="p-4 text-center text-muted">Tidak ada jawaban.</div>');
                    return;
                }

                answers.forEach((a, i) => {
                    const id = `answer-${i}`;
                    const isMC = a.type === 'multiple_choice';
                    const optionsHtml = isMC && a.options ? a.options.map(o => {
                        const selected = a.answer_option_id == o.id;
                        const correct = o.is_correct;
                        let iconClass = 'far fa-circle'; // Default
                        let cls = '';

                        if (correct) {
                            cls = 'correct-answer';
                            iconClass = 'fas fa-check-circle text-success';
                        } else if (selected && !correct) {
                            cls = 'wrong-answer';
                            iconClass = 'fas fa-times-circle text-danger';
                        } else if (selected) {
                            iconClass = 'fas fa-dot-circle';
                        }

                        return `<div class="list-group-item d-flex align-items-center ${cls}">
                            <i class="${iconClass} me-2"></i> ${o.text || ''}
                        </div>`;
                    }).join('') : '';

                    const answerHtml = isMC ?
                        `<div class="list-group list-group-flush">${optionsHtml}</div>` :
                        `<div class="bg-light p-3 rounded"><pre class="mb-0" style="white-space: pre-wrap;">${a.answer_text || '(kosong)'}</pre></div>`;

                    const feedback = (a.ai_feedback || a.teacher_comment) ? `
                        <div class="mt-3">
                            <h6 class="fw-bold text-muted mb-2"><i class="fas fa-comment-dots me-1"></i> Umpan Balik</h6>
                            <div class="alert alert-info small p-3">${a.teacher_comment || a.ai_feedback}</div>
                        </div>` : '';

                    const comps = a.competencies && a.competencies.length ? `
                        <div class="mt-3">
                            <h6 class="fw-bold text-muted mb-2"><i class="fas fa-award me-1"></i> Kompetensi</h6>
                            ${a.competencies.map(c => `
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span>${c.name}</span>
                                        <span class="fw-bold text-success">${c.score}/${c.max}</span>
                                    </div>`).join('')}
                        </div>` : '';

                    $acc.append(`
                        <div class="accordion-item border-bottom">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#${id}">
                                    <div class="d-flex w-100 align-items-center">
                                        <span class="badge badge-question me-3 ${getAnswerBadgeClass(a.percentage)}">${a.question_number}</span>
                                        <div class="flex-grow-1">
                                            <div class="d-flex gap-2 align-items-center mb-1">
                                                <span class="badge bg-primary bg-opacity-10 text-primary small">${getQuestionType(a.type)}</span>
                                                <span class="badge ${getAnswerBadgeClass(a.percentage)} small">${a.score_final}/${a.max_score}</span>
                                            </div>
                                            <div class="fw-medium text-dark">${a.question_text}</div>
                                        </div>
                                    </div>
                                </button>
                            </h2>
                            <div id="${id}" class="accordion-collapse collapse">
                                <div class="accordion-body pt-2">
                                    <div class="mb-3">
                                        <h6 class="fw-bold text-muted mb-2"><i class="fas fa-comment me-1"></i> Jawaban Anda</h6>
                                        ${answerHtml}
                                    </div>
                                    ${feedback}
                                    ${comps}
                                </div>
                            </div>
                        </div>
                    `);
                });
            };

            const renderRecommendations = (recs) => {
                const $list = $('#recommendations-list').empty();
                const $card = $('#recommendations-card');
                if (recs && recs.length) {
                    recs.forEach(r => $list.append(
                        `<span class="badge bg-warning bg-opacity-15 text-warning px-3 py-2 rounded-pill">${r}</span>`
                        ));
                    $card.slideDown();
                } else {
                    $card.slideUp();
                }
            };

            const initCharts = (comps) => {
                const $card = $('#competency-card');
                if (!comps || !comps.length) {
                    $card.hide();
                    return;
                }
                $card.show();

                const ctx = $('#competencyChart')[0].getContext('2d');
                if (competencyChart) competencyChart.destroy();

                competencyChart = new Chart(ctx, {
                    type: 'radar',
                    data: {
                        labels: comps.map(c => c.name),
                        datasets: [{
                            label: 'Pencapaian (%)',
                            data: comps.map(c => c.percentage),
                            backgroundColor: 'rgba(67, 97, 238, 0.15)',
                            borderColor: '#4361ee',
                            borderWidth: 2,
                            pointBackgroundColor: '#4361ee'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            r: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    stepSize: 20
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            };

            // Load Report
            const loadReport = () => {
                $.ajax({
                    url: API_URL,
                    method: 'GET',
                    beforeSend: () => {
                        $('#loading-spinner').show();
                        $('#report-content').hide();
                    },
                    success: (res) => {
                        if (res.data) renderReport(res.data);
                    },
                    error: (xhr) => {
                        $('#loading-spinner').html(
                            `<div class="alert alert-danger">Gagal memuat: ${xhr.statusText}</div>`
                            );
                    },
                    complete: () => {
                        if (!$('#loading-spinner .alert').length) {
                            $('#loading-spinner').fadeOut();
                            $('#report-content').fadeIn();
                        }
                    }
                });
            };

            // Events
            $('#print-btn').on('click', printReport);
            $('#expand-all-btn').on('click', expandAll);
            $('#collapse-all-btn').on('click', collapseAll);

            loadReport();
        });
    </script>
@endpush
