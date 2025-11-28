@extends('layouts.app')
@section('title', 'Laporan Detail Hasil Tugas Siswa')

@push('styles')
    {{-- Memuat Chart.js di head --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="{{ asset('assets/css/report/student-report.css') }}">
@endpush

@section('content')
    <div class="container-fluid px-4 py-4">

        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <div>
                <h2 class="fw-bold mb-1">Laporan Hasil Tugas</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="/dashboard" class="text-decoration-none">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="#" class="text-decoration-none">Tugas</a></li>
                        <li class="breadcrumb-item active">Detail Laporan</li>
                    </ol>
                </nav>
            </div>
            <div class="btn-group">
                <button class="btn btn-outline-primary" id="print-btn">
                    <i class="fas fa-print me-2"></i>Cetak
                </button>
                <a href="/api/export/submission/1/pdf" class="btn btn-outline-success">
                    <i class="fas fa-file-pdf me-2"></i>Export PDF
                </a>
            </div>
        </div>

        <div id="loading-spinner" class="text-center py-5">
            <div class="spinner-border text-primary" style="width: 3.5rem; height: 3.5rem;" role="status">
                <span class="visually-hidden">Memuat...</span>
            </div>
            <p class="mt-3 text-muted fs-5">Memuat Laporan...</p>
        </div>

        <div id="report-content" style="display: none;">
            <div class="alert alert-info border-0 shadow-sm mb-4 d-flex align-items-center" role="alert">
                <i class="fas fa-info-circle fs-4 me-3"></i>
                <div class="flex-grow-1">
                    <strong>Laporan Komprehensif</strong> - Analisis detail mencakup nilai per soal, evaluasi kompetensi,
                    dan rekomendasi pembelajaran.
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <div class="text-center mb-4">
                                <div class="avatar-circle mb-3">
                                    <i class="fas fa-user-circle fs-1"></i>
                                </div>
                                <h5 class="fw-bold mb-1" id="student-name">...</h5>
                                <p class="text-muted mb-0" id="student-class">...</p>
                                <span class="badge bg-success mt-2" id="student-status">Siswa Aktif</span>
                            </div>
                            <hr class="my-4">
                            <dl class="row mb-0">
                                <dt class="col-5 text-muted small mb-2">NIS</dt>
                                <dd class="col-7 small mb-2 fw-medium" id="student-nis">...</dd>
                                <dt class="col-5 text-muted small mb-2">Email</dt>
                                <dd class="col-7 small mb-2" id="student-email">...</dd>
                                <dt class="col-5 text-muted small mb-2">Tahun Ajaran</dt>
                                <dd class="col-7 small mb-2" id="academic-year">...</dd>
                                <dt class="col-5 text-muted small">Semester</dt>
                                <dd class="col-7 small fw-medium" id="semester">...</dd>
                            </dl>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="mb-0 fw-bold">Statistik Pengerjaan</h6>
                        </div>
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div><i class="fas fa-history text-primary fs-4"></i></div>
                                <div class="text-end">
                                    <div class="small text-muted">Waktu Pengerjaan</div>
                                    <div class="fw-bold" id="duration">...</div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div><i class="fas fa-calendar-check text-success fs-4"></i></div>
                                <div class="text-end">
                                    <div class="small text-muted">Waktu Kumpul</div>
                                    <div class="fw-bold" id="submission-time">...</div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div><i class="fas fa-check-circle text-info fs-4"></i></div>
                                <div class="text-end">
                                    <div class="small text-muted">Soal Terjawab</div>
                                    <div class="fw-bold" id="answered-questions">...</div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div><i class="fas fa-id-badge text-warning fs-4"></i></div>
                                <div class="text-end">
                                    <div class="small text-muted">Diperiksa Oleh</div>
                                    <div class="fw-bold" id="teacher-name">...</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm bg-warning bg-opacity-10 d-none" id="late-card">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle text-warning fs-4 me-3"></i>
                                <div>
                                    <small class="text-muted d-block">Status Pengumpulan</small>
                                    <strong class="text-warning" id="late-status">...</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-gradient-primary text-white py-4">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge bg-white bg-opacity-25 me-2" id="task-type">...</span>
                                        <span class="badge bg-white bg-opacity-25" id="subject-name">...</span>
                                    </div>
                                    <h4 class="fw-bold mb-2" id="task-title">...</h4>
                                    <p class="mb-0 opacity-90 small" id="task-description">...</p>
                                </div>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <div class="row g-0">
                                <div class="col-md-6 p-4 text-center border-end">
                                    <div class="small text-muted text-uppercase mb-2 fw-medium">Nilai Akhir</div>
                                    <div class="display-1 fw-bold mb-2" id="final-grade" style="color: #dc3545;">...
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar" id="grade-progress" style="width: 0%"></div>
                                    </div>
                                    <div class="mt-2 small text-muted">dari 100.0 poin</div>
                                </div>
                                <div class="col-md-6 p-4">
                                    <div class="small text-muted text-uppercase mb-3 fw-medium text-center">Kategori
                                        Performa</div>
                                    <div class="text-center mb-3">
                                        <span class="badge fs-6 px-4 py-2" id="performance-category">...</span>
                                    </div>
                                    <div class="small text-center">
                                        <div class="mb-2">
                                            <i class="fas fa-trophy text-warning me-1"></i>
                                            <span class="fw-medium" id="rank-info">...</span>
                                        </div>
                                        <div class="text-muted" id="class-average">...</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold">
                                    <i class="fas fa-chart-line text-primary me-2"></i>
                                    Analisis Pencapaian Kompetensi
                                </h5>
                            </div>
                            <p class="text-muted small mb-0 mt-2">Evaluasi kemampuan siswa berdasarkan indikator kompetensi
                                yang diuji</p>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-4">
                                <canvas id="competencyRadarChart" height="280"></canvas>
                            </div>
                            <div id="competency-details" class="mt-4">
                            </div>
                            <div class="alert alert-light border-0 mt-4">
                                <h6 class="alert-heading fw-bold small">
                                    <i class="fas fa-lightbulb text-warning me-2"></i>Insight Kompetensi
                                </h6>
                                <ul class="mb-0 small" id="competency-insights">
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom py-3">
                            <h5 class="mb-0 fw-bold">
                                <i class="fas fa-chart-pie text-success me-2"></i>
                                Distribusi Nilai per Tipe Soal
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <canvas id="questionTypeChart" height="200"></canvas>
                                </div>
                                <div class="col-md-6">
                                    <div id="question-type-details"
                                        class="d-flex flex-column justify-content-center h-100">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-4" id="teacher-feedback-card" style="display: none;">
                        <div class="card-header bg-light border-bottom py-3">
                            <h5 class="mb-0 fw-bold">
                                <i class="fas fa-quote-left text-info me-2"></i>
                                Umpan Balik Guru
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-user-circle fs-1 text-primary"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="fw-medium mb-2" id="teacher-name-feedback">...</div>
                                    <div class="bg-light p-3 rounded-3">
                                        <p class="mb-0" id="teacher-feedback">...</p>
                                    </div>
                                    <div class="mt-3" id="learning-recommendations-container" style="display: none;">
                                        <h6 class="small fw-bold text-primary mb-2">📚 Rekomendasi Pembelajaran:</h6>
                                        <div class="d-flex flex-wrap gap-2" id="learning-recommendations">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold">
                                    <i class="fas fa-list-check text-primary me-2"></i>
                                    Rincian Jawaban per Soal (<span id="answers-count">0</span>)
                                </h5>
                                <div class="btn-group btn-group-sm no-print">
                                    <button class="btn btn-outline-secondary" id="expand-all">
                                        <i class="fas fa-expand-arrows-alt"></i> Buka Semua
                                    </button>
                                    <button class="btn btn-outline-secondary" id="collapse-all">
                                        <i class="fas fa-compress-arrows-alt"></i> Tutup Semua
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="accordion accordion-flush" id="answerAccordion">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')

    <script>
        $(document).ready(function() {

            // ============================================
            // KONFIGURASI & VARIABEL GLOBAL
            // ============================================
            const submissionId = 1; // Anda bisa ganti ini dengan
            const API_URL = `/api/submissions/${submissionId}/report`;

            let competencyRadarChart = null;
            let questionTypeDoughnutChart = null;

            // ============================================
            // DATA DUMMY (HARDCODED) UNTUK CHARTS
            // ============================================
            const hardcodedReportData = {
                competencies: [{
                        name: 'Memahami Struktur Sel',
                        description: 'Kemampuan mengidentifikasi dan menjelaskan fungsi organel sel',
                        score_awarded: 28.0,
                        max_score: 30.0,
                        percentage: 93.3,
                        level: 'Sangat Baik'
                    },
                    {
                        name: 'Analisis Proses Fotosintesis',
                        description: 'Kemampuan menganalisis tahapan dan faktor fotosintesis',
                        score_awarded: 40.5,
                        max_score: 50.0,
                        percentage: 81.0,
                        level: 'Baik'
                    },
                    {
                        name: 'Keterampilan Menulis Ilmiah',
                        description: 'Kemampuan menyusun jawaban dengan tata bahasa yang runut',
                        score_awarded: 14.0,
                        max_score: 20.0,
                        percentage: 70.0,
                        level: 'Cukup'
                    }
                ],
                questionTypes: {
                    'multiple_choice': {
                        scored: 23,
                        max: 25,
                        percentage: 92.0,
                        label: 'Pilihan Ganda'
                    },
                    'essay': {
                        scored: 39,
                        max: 50,
                        percentage: 78.0,
                        label: 'Esai'
                    },
                    'short_answer': {
                        scored: 20,
                        max: 25,
                        percentage: 80.0,
                        label: 'Isian Singkat'
                    }
                }
            };

            // ============================================
            // FUNGSI HELPER
            // ============================================
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

            const getGradeStyle = (grade) => {
                if (grade >= 90) return {
                    color: '#764ba2',
                    class: 'bg-success',
                    cat: 'Istimewa'
                };
                if (grade >= 85) return {
                    color: '#6C757D',
                    class: 'bg-secondary',
                    cat: 'Baik Sekali'
                };
                if (grade >= 70) return {
                    color: '#17a2b8',
                    class: 'bg-info',
                    cat: 'Baik'
                };
                if (grade >= 60) return {
                    color: '#ffc107',
                    class: 'bg-warning',
                    cat: 'Cukup'
                };
                return {
                    color: '#dc3545',
                    class: 'bg-danger',
                    cat: 'Perlu Perbaikan'
                };
            };

            const getQuestionTypeVisuals = (type) => {
                if (type === 'task') return {
                    label: 'Task',
                    icon: 'fas fa-tasks'
                };
                if (type === 'quiz') return {
                    label: 'Quiz',
                    icon: 'fas fa-file-alt'
                };
                if (type === 'exam') return {
                    label: 'Exam',
                    icon: 'fas fa-file-alt'
                };
                // Fallback untuk 'short_answer' atau tipe lain
                return {
                    label: 'Task',
                    icon: 'fas fa-keyboard'
                };
            };

            // ============================================
            // FUNGSI RENDER (DINAMIS DARI API)
            // ============================================

            /**
             * Mengisi Info Dasar, Statistik, dan Nilai dari API
             */
            function renderBasicInfo(submission, answers, recommendations) {
                const s = submission;
                // Pastikan final_grade adalah angka
                const g = parseFloat(s.final_grade) || 0;
                const style = getGradeStyle(g);

                // Info Siswa
                $('#student-name').text(s.student_name);
                $('#student-nis').text(s.student_nis);
                $('#student-class').text(s.class_name);
                $('#student-email').text(s.email);
                $('#academic-year').text(s.academic_year || '2024/2025'); // Fallback
                $('#semester').text(s.semester || 'Ganjil'); // Fallback

                // Info Tugas
                $('#task-title').text(s.task_title);
                $('#task-type').text(getQuestionTypeVisuals(s.task_type).label);
                $('#subject-name').text(s.subject_name);
                $('#task-description').text(s.task_description || 'Analisis laporan tugas siswa.');

                // Statistik Pengerjaan
                $('#duration').text(formatDuration(s.duration_seconds));
                $('#submission-time').text(formatDate(s.submitted_at));
                $('#answered-questions').text(`${answers.length} Soal`);
                $('#teacher-name').text(s.teacher_name || 'AI Analyzer');

                // Status Keterlambatan
                if (s.is_late) {
                    $('#late-status').text(s.late_info || 'Terlambat');
                    $('#late-card').removeClass('d-none');
                }

                // Nilai Utama
                $('#final-grade').text(g.toFixed(1)).css('color', style.color);
                $('#grade-progress').removeClass('bg-success bg-info bg-warning bg-danger bg-purple').addClass(style
                    .class).css('width', g + '%');

                // Kategori Performa
                $('#performance-category').text(style.cat).removeClass(
                    'bg-success bg-info bg-warning bg-danger bg-purple').addClass(style.class);
                $('#rank-info').text(`Peringkat ${s.rank || '-'} dari ${s.total_students || '-'} siswa`);
                $('#class-average').text(`Rata-rata kelas: ${s.class_average || '-'}`);

                // Umpan Balik Guru (jika ada)
                if (s.teacher_comment) {
                    $('#teacher-name-feedback').text(`${s.teacher_name || 'Guru'} - ${formatDate(s.graded_at)}`);
                    $('#teacher-feedback').text(s.teacher_comment);
                    $('#teacher-feedback-card').show();
                }

                // Rekomendasi Pembelajaran (jika ada)
                if (recommendations && recommendations.length > 0) {
                    const $recContainer = $('#learning-recommendations').empty();
                    recommendations.forEach(rec => {
                        $recContainer.append(
                            `<span class="badge bg-primary bg-opacity-10 text-primary">${rec}</span>`);
                    });
                    $('#learning-recommendations-container').show();
                    $('#teacher-feedback-card').show(); // Tampilkan card feedback jika ada rekomendasi
                }
            }

            /**
             * Render Rincian Jawaban (Accordion) dari API
             */
            function renderAnswerDetails(answers) {
                const $container = $('#answerAccordion').empty();
                $('#answers-count').text(answers.length);

                if (!answers || !answers.length) {
                    $container.html('<div class="p-4 text-center text-muted">Tidak ada rincian jawaban.</div>');
                    return;
                }

                answers.forEach((q, index) => {
                    // **PERBAIKAN UTAMA ADA DI SINI**

                    const scoreFinal = parseFloat(q.score_final) || 0;
                    const maxScore = parseFloat(q.max_score) || 0;
                    // Gunakan 'percentage' dari API jika ada, jika tidak, hitung manual
                    const percentage = q.percentage !== undefined ? parseFloat(q.percentage) : (maxScore >
                        0 ? (scoreFinal / maxScore) * 100 : 0);

                    const style = getGradeStyle(percentage);
                    const visuals = getQuestionTypeVisuals(q.type);

                    let statusIcon = 'fas fa-times-circle';
                    if (percentage >= 85) statusIcon = 'fas fa-check-circle';
                    else if (percentage >= 60) statusIcon = 'fas fa-exclamation-circle';

                    // --- HTML untuk Jawaban (Pilihan Ganda vs Esai) ---
                    let answerHtml = '';

                    if (q.type === 'multiple_choice') {
                        // LOGIKA BARU: Jika API tidak mengirim array 'options'
                        // kita tampilkan saja jawaban yang dipilih dari 'selected_option'
                        const isCorrect = q.is_correct == 1; // Cek status benar/salah
                        const borderColor = isCorrect ? 'border-success' : 'border-danger';
                        const iconClass = isCorrect ? 'fa-check-circle text-success' :
                            'fa-times-circle text-danger';

                        answerHtml = `
                    <h6 class="small fw-bold text-muted mb-2">Jawaban Siswa:</h6>
                    <div class="bg-light p-3 rounded border-start border-4 ${borderColor} mb-3">
                        <p class="mb-0">
                            <i class="fas ${iconClass} me-2"></i>
                            ${q.selected_option || '(Tidak dijawab)'}
                        </p>
                    </div>`;

                    } else {
                        // Ini untuk 'short_answer' atau 'essay'
                        answerHtml = `
                    <h6 class="small fw-bold text-muted mb-2">Jawaban Siswa:</h6>
                    <div class="bg-light p-3 rounded border-start border-4 border-primary mb-3">
                        <p class="mb-0" style="white-space: pre-wrap;">${q.answer_text || '(Tidak dijawab)'}</p>
                    </div>`;
                    }

                    // --- HTML untuk Umpan Balik ---
                    // (Data ini ada di JSON Anda dan akan tampil)
                    const commentHtml = (q.teacher_comment || q.ai_feedback) ? `
                <div class="alert alert-info border-0 border-start border-4 border-info mb-3">
                    <h6 class="alert-heading small fw-bold mb-2">
                        <i class="fas fa-comment-dots me-2"></i>Komentar Guru/AI:
                    </h6>
                    <p class="mb-0 small"><em>"${q.teacher_comment || q.ai_feedback}"</em></p>
                </div>` : '';

                    // --- Gabungkan Semua ---
                    $container.append(`
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#answer${index}">
                            <div class="d-flex align-items-center w-100 pe-3">
                                <span class="question-status-badge ${style.class} text-white me-3">
                                    ${index + 1} </span>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="${visuals.icon} text-primary me-2"></i>
                                        <span class="badge bg-primary bg-opacity-10 text-primary small">${visuals.label}</span>
                                        <span class="badge ${style.class} ms-2 small">
                                            ${scoreFinal.toFixed(1)} / ${maxScore} poin
                                        </span>
                                    </div>
                                    <div class="fw-medium text-dark small">${q.question_text}</div>
                                </div>
                                <i class="${statusIcon} text-${style.color} fs-4 ms-3"></i>
                            </div>
                        </button>
                    </h2>
                    <div id="answer${index}" class="accordion-collapse collapse" data-bs-parent="#answerAccordion">
                        <div class="accordion-body">
                            ${answerHtml}
                            ${commentHtml}
                            </div>
                    </div>
                </div>
            `);
                });
            }

            // ============================================
            // FUNGSI RENDER (HARDCODED UNTUK CHARTS)
            // ============================================

            function renderCompetencyChart() {
                const ctx = document.getElementById('competencyRadarChart').getContext('2d');
                const competencies = hardcodedReportData.competencies;
                if (competencyRadarChart) competencyRadarChart.destroy();
                competencyRadarChart = new Chart(ctx, {
                    type: 'radar',
                    data: {
                        labels: competencies.map(c => c.name),
                        datasets: [{
                            label: 'Pencapaian Siswa (%)',
                            data: competencies.map(c => c.percentage),
                            backgroundColor: 'rgba(102, 126, 234, 0.2)',
                            borderColor: 'rgba(102, 126, 234, 1)',
                            borderWidth: 2,
                            pointRadius: 5
                        }, {
                            label: 'Target Minimal (75%)',
                            data: competencies.map(() => 75),
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            borderColor: 'rgba(40, 167, 69, 0.5)',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            pointRadius: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            r: {
                                min: 0,
                                max: 100,
                                ticks: {
                                    stepSize: 20,
                                    callback: (v) => v + '%'
                                },
                                pointLabels: {
                                    font: {
                                        size: 11,
                                        weight: 'bold'
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 15,
                                    usePointStyle: true
                                }
                            }
                        }
                    }
                });
            }

            function renderCompetencyDetails() {
                const container = document.getElementById('competency-details');
                const insightsContainer = document.getElementById('competency-insights');
                container.innerHTML = '';
                insightsContainer.innerHTML = '';

                hardcodedReportData.competencies.forEach(comp => {
                    const percentage = comp.percentage;
                    let style = getGradeStyle(percentage);
                    let icon = 'far fa-frown';
                    if (percentage >= 85) icon = 'far fa-smile';
                    else if (percentage >= 70) icon = 'far fa-meh';

                    container.innerHTML += `
                <div class="competency-item p-3 mb-3 border rounded">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center mb-1">
                                <i class="${icon} text-${style.class.replace('bg-', '')} me-2 fs-5"></i>
                                <h6 class="mb-0 fw-bold">${comp.name}</h6>
                            </div>
                            <p class="text-muted small mb-2">${comp.description}</p>
                        </div>
                        <div class="text-end ms-3">
                            <div class="fw-bold fs-5" style="color:${style.color}">${percentage.toFixed(1)}%</div>
                            <small class="text-muted">${comp.score_awarded.toFixed(1)}/${comp.max_score.toFixed(1)}</small>
                        </div>
                    </div>
                    <div class="progress" style="height: 12px;"><div class="progress-bar ${style.class}" style="width: ${percentage}%"></div></div>
                    <div class="mt-2"><span class="badge ${style.class} bg-opacity-10 text-${style.class.replace('bg-', '')} small">${comp.level}</span></div>
                </div>`;

                    if (percentage >= 90) insightsContainer.innerHTML +=
                        `<li><strong>Kekuatan:</strong> ${comp.name} (${percentage.toFixed(1)}%)</li>`;
                    if (percentage < 70) insightsContainer.innerHTML +=
                        `<li><strong>Perlu Ditingkatkan:</strong> ${comp.name} (${percentage.toFixed(1)}%)</li>`;
                });
            }

            function renderQuestionTypeChart() {
                const ctx = document.getElementById('questionTypeChart').getContext('2d');
                const types = hardcodedReportData.questionTypes;
                if (questionTypeDoughnutChart) questionTypeDoughnutChart.destroy();
                questionTypeDoughnutChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: Object.values(types).map(t => t.label),
                        datasets: [{
                            data: Object.values(types).map(t => t.scored),
                            backgroundColor: ['rgba(40, 167, 69, 0.8)', 'rgba(255, 193, 7, 0.8)',
                                'rgba(23, 162, 184, 0.8)'
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 10,
                                    usePointStyle: true,
                                    font: {
                                        size: 11
                                    }
                                }
                            }
                        }
                    }
                });
            }

            function renderQuestionTypeDetails() {
                const container = document.getElementById('question-type-details');
                container.innerHTML = '';
                const types = hardcodedReportData.questionTypes;

                const colors = {
                    'multiple_choice': 'success',
                    'essay': 'warning',
                    'short_answer': 'info'
                };

                for (const key in types) {
                    const type = types[key];
                    const color = colors[key] || 'secondary';
                    container.innerHTML += `
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small fw-medium">${type.label}</span>
                        <span class="small fw-bold text-${color}">${type.percentage.toFixed(1)}%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-${color}" style="width: ${type.percentage}%"></div>
                    </div>
                    <small class="text-muted">${type.scored.toFixed(1)}/${type.max.toFixed(1)} poin</small>
                </div>
            `;
                }
            }

            // ============================================
            // FUNGSI LOAD DATA (AJAX)
            // ============================================
            const loadReport = () => {
                $.ajax({
                    url: API_URL,
                    method: 'GET',
                    beforeSend: () => {
                        $('#loading-spinner').show();
                        $('#report-content').hide();
                    },
                    success: (res) => {
                        if (res.data) {
                            // Panggil fungsi render dinamis
                            renderBasicInfo(res.data.submission, res.data.answers, res.data
                                .recommendations);
                            renderAnswerDetails(res.data.answers);
                            // Panggil fungsi render hardcoded (karena data competencies & question_types API berbeda/kosong)
                            renderCompetencyChart();
                            renderCompetencyDetails();
                            renderQuestionTypeChart();
                            renderQuestionTypeDetails();
                        } else {
                            $('#loading-spinner').html(
                                `<div class="alert alert-warning">Data laporan tidak ditemukan.</div>`
                                );
                        }
                    },
                    error: (xhr, status, error) => {
                        // Tampilkan error yang lebih spesifik di console
                        console.error("AJAX Error:", status, error);
                        console.error("Response Text:", xhr.responseText);
                        $('#loading-spinner').html(
                            `<div class="alert alert-danger">Gagal memuat: ${error}. Cek console (F12) untuk detail.</div>`
                            );
                    },
                    complete: () => {
                        // Sembunyikan spinner HANYA jika tidak ada alert error
                        if (!$('#loading-spinner .alert').length) {
                            $('#loading-spinner').fadeOut();
                            $('#report-content').fadeIn();
                        }
                    }
                });
            };

            // ============================================
            // INISIALISASI HALAMAN
            // ============================================

            // 1. Panggil fungsi AJAX untuk memuat data dinamis
            //    Fungsi success() akan memanggil semua fungsi render (baik dinamis maupun hardcoded)
            loadReport();

            // 2. Pasang Event Listeners
            $('#print-btn').on('click', printReport);
            $('#expand-all').on('click', expandAll);
            $('#collapse-all').on('click', collapseAll);
        });
    </script>
@endpush
