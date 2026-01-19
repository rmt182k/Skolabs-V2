@extends('layouts.app')
@section('title', 'Laporan Detail Hasil Tugas Siswa')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<link rel="stylesheet" href="{{ asset('assets/css/report/student-report.css') }}">
@endpush

@section('content')
<div class="container-fluid px-4 py-4">

    {{-- Header & Tombol Aksi --}}
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h2 class="fw-bold mb-1">Laporan Hasil Tugas</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/dashboard" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active">Detail Laporan</li>
                </ol>
            </nav>
        </div>
        <div class="btn-group">
            <button class="btn btn-outline-primary" id="print-btn">
                <i class="fas fa-print me-2"></i>Cetak
            </button>
            {{-- ID submission dinamis --}}
            <a href="/api/export/submission/{{ $submission_id }}/pdf" class="btn btn-outline-success">
                <i class="fas fa-file-pdf me-2"></i>Export PDF
            </a>
        </div>
    </div>

    {{-- Loading Spinner --}}
    <div id="loading-spinner" class="text-center py-5">
        <div class="spinner-border text-primary" style="width: 3.5rem; height: 3.5rem;" role="status">
            <span class="visually-hidden">Memuat...</span>
        </div>
        <p class="mt-3 text-muted fs-5">Memuat Laporan...</p>
    </div>

    {{-- Konten Laporan --}}
    <div id="report-content" style="display: none;">

        {{-- Info Alert --}}
        <div class="alert alert-info border-0 shadow-sm mb-4 d-flex align-items-center" role="alert">
            <i class="fas fa-info-circle fs-4 me-3"></i>
            <div class="flex-grow-1">
                <strong>Laporan Komprehensif</strong> - Analisis detail mencakup nilai per soal, evaluasi kompetensi, dan rekomendasi pembelajaran.
            </div>
        </div>

        <div class="row g-4">
            {{-- Kolom Kiri: Profil & Statistik --}}
            <div class="col-lg-4">
                {{-- Card Profil Siswa --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <div class="avatar-circle mb-3">
                                <i class="fas fa-user-circle fs-1 text-secondary"></i>
                            </div>
                            <h5 class="fw-bold mb-1" id="student-name">...</h5>
                            <p class="text-muted mb-0" id="student-class">...</p>
                            <span class="badge bg-success mt-2">Siswa Aktif</span>
                        </div>
                        <hr class="my-4">
                        <dl class="row mb-0">
                            <dt class="col-5 text-muted small mb-2">NIS</dt>
                            <dd class="col-7 small mb-2 fw-medium" id="student-nis">...</dd>
                            <dt class="col-5 text-muted small mb-2">Email</dt>
                            <dd class="col-7 small mb-2 text-truncate" id="student-email">...</dd>
                            <dt class="col-5 text-muted small mb-2">Tahun Ajaran</dt>
                            <dd class="col-7 small mb-2" id="academic-year">...</dd>
                            <dt class="col-5 text-muted small">Semester</dt>
                            <dd class="col-7 small fw-medium" id="semester">...</dd>
                        </dl>
                    </div>
                </div>

                {{-- Card Statistik --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold">Statistik Pengerjaan</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div><i class="fas fa-history text-primary fs-4"></i></div>
                            <div class="text-end">
                                <div class="small text-muted">Durasi</div>
                                <div class="fw-bold" id="duration">...</div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div><i class="fas fa-calendar-check text-success fs-4"></i></div>
                            <div class="text-end">
                                <div class="small text-muted">Dikumpulkan</div>
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
                            <div><i class="fas fa-user-tie text-warning fs-4"></i></div>
                            <div class="text-end">
                                <div class="small text-muted">Diperiksa Oleh</div>
                                <div class="fw-bold" id="teacher-name">...</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card Status Terlambat (Hidden by default) --}}
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

            {{-- Kolom Kanan: Nilai & Analisis --}}
            <div class="col-lg-8">
                {{-- Header Nilai --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-gradient-primary text-white py-4">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-white bg-opacity-25 me-2" id="task-type">...</span>
                            <span class="badge bg-white bg-opacity-25" id="subject-name">...</span>
                        </div>
                        <h4 class="fw-bold mb-2" id="task-title">...</h4>
                        <p class="mb-0 opacity-90 small" id="task-description">...</p>
                    </div>

                    <div class="card-body p-0">
                        <div class="row g-0">
                            <div class="col-md-6 p-4 text-center border-end">
                                <div class="small text-muted text-uppercase mb-2 fw-medium">Nilai Akhir</div>
                                <div class="display-1 fw-bold mb-2" id="final-grade">0</div>
                                <div class="progress mx-auto" style="height: 8px; width: 80%;">
                                    <div class="progress-bar" id="grade-progress" style="width: 0%"></div>
                                </div>
                                <div class="mt-2 small text-muted">dari 100.0 poin</div>
                            </div>
                            <div class="col-md-6 p-4">
                                <div class="small text-muted text-uppercase mb-3 fw-medium text-center">Kategori Performa</div>
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

                {{-- Analisis Kompetensi (Chart) --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-chart-line text-primary me-2"></i>Analisis Pencapaian Kompetensi</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-4" style="height: 300px;">
                            <canvas id="competencyRadarChart"></canvas>
                        </div>
                        <div id="competency-details" class="mt-4"></div>
                        <div class="alert alert-light border-0 mt-4">
                            <h6 class="alert-heading fw-bold small"><i class="fas fa-lightbulb text-warning me-2"></i>Insight Kompetensi</h6>
                            <ul class="mb-0 small" id="competency-insights"></ul>
                        </div>
                    </div>
                </div>

                {{-- Distribusi Tipe Soal --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-chart-pie text-success me-2"></i>Distribusi Nilai per Tipe Soal</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div style="height: 200px;">
                                    <canvas id="questionTypeChart"></canvas>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div id="question-type-details" class="d-flex flex-column justify-content-center h-100 mt-3 mt-md-0"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Umpan Balik Guru --}}
                <div class="card border-0 shadow-sm mb-4" id="teacher-feedback-card" style="display: none;">
                    <div class="card-header bg-light border-bottom py-3">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-quote-left text-info me-2"></i>Umpan Balik Guru</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-user-circle fs-1 text-primary"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="fw-medium mb-2" id="teacher-name-feedback">...</div>
                                <div class="bg-light p-3 rounded-3 border">
                                    <p class="mb-0" id="teacher-feedback">...</p>
                                </div>
                                <div class="mt-3" id="learning-recommendations-container" style="display: none;">
                                    <h6 class="small fw-bold text-primary mb-2">📚 Rekomendasi Pembelajaran:</h6>
                                    <div class="d-flex flex-wrap gap-2" id="learning-recommendations"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Rincian Jawaban --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-list-check text-primary me-2"></i>Rincian Jawaban (<span id="answers-count">0</span>)
                        </h5>
                        <div class="btn-group btn-group-sm no-print">
                            <button class="btn btn-outline-secondary" id="expand-all"><i class="fas fa-expand-arrows-alt"></i> Buka</button>
                            <button class="btn btn-outline-secondary" id="collapse-all"><i class="fas fa-compress-arrows-alt"></i> Tutup</button>
                        </div>
                    </div>
                    <div class="accordion accordion-flush" id="answerAccordion"></div>
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
        // 1. CONFIG & VARIABEL
        // ============================================
        const submissionId = "{{ $submission_id }}";
        const API_URL = `/api/submissions/${submissionId}/report`;

        let competencyRadarChart = null;
        let questionTypeDoughnutChart = null;

        // ============================================
        // 2. HELPER FUNCTIONS
        // ============================================

        // Fungsi format tanggal aman
        const formatDate = (d) => {
            if (!d) return '-';
            try {
                return new Date(d).toLocaleString('id-ID', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch (e) {
                return d;
            }
        };

        // Fungsi format durasi
        const formatDuration = (s) => {
            if (!s && s !== 0) return '-';
            const min = Math.floor(s / 60);
            const sec = s % 60;
            return `${min}m ${sec}s`;
        };

        // Fungsi warna nilai
        const getGradeStyle = (grade) => {
            const g = parseFloat(grade) || 0;
            if (g >= 90) return {
                color: '#28a745',
                class: 'bg-success',
                cat: 'Sangat Baik'
            }; // Hijau
            if (g >= 75) return {
                color: '#17a2b8',
                class: 'bg-info',
                cat: 'Baik'
            }; // Biru
            if (g >= 60) return {
                color: '#ffc107',
                class: 'bg-warning',
                cat: 'Cukup'
            }; // Kuning
            return {
                color: '#dc3545',
                class: 'bg-danger',
                cat: 'Perlu Perbaikan'
            }; // Merah
        };

        // Label Tipe Tugas
        const getTaskTypeLabel = (type) => {
            const types = {
                'quiz': 'Kuis',
                'exam': 'Ujian',
                'task': 'Tugas'
            };
            return types[type] || 'Tugas';
        };

        // Ikon Tipe Soal
        const getQuestionTypeVisuals = (type) => {
            const visuals = {
                'multiple_choice': {
                    label: 'Pilihan Ganda',
                    icon: 'fas fa-tasks'
                },
                'essay': {
                    label: 'Esai',
                    icon: 'fas fa-file-alt'
                },
                'short_answer': {
                    label: 'Isian Singkat',
                    icon: 'fas fa-keyboard'
                },
                'true_false': {
                    label: 'Benar/Salah',
                    icon: 'fas fa-toggle-on'
                }
            };
            return visuals[type] || {
                label: type || 'Soal',
                icon: 'fas fa-question-circle'
            };
        };

        // ============================================
        // 3. FUNGSI RENDER (UI)
        // ============================================

        function renderBasicInfo(s, answers, recommendations) {
            if (!s) return;

            const g = parseFloat(s.final_grade) || 0;
            const style = getGradeStyle(g);

            // --- Header & Profil ---
            $('#student-name').text(s.student_name || '-');
            $('#student-nis').text(s.student_nis || '-');
            $('#student-class').text(s.class_name || '-');
            $('#student-email').text(s.email || '-');
            $('#subject-name').text(s.subject_name || '-');
            $('#task-title').text(s.task_title || '-');
            $('#task-type').text(getTaskTypeLabel(s.task_type));
            $('#task-description').text(s.task_description || 'Laporan hasil pengerjaan.');

            // --- Statistik ---
            $('#duration').text(formatDuration(s.duration_seconds));
            $('#submission-time').text(formatDate(s.submitted_at));
            $('#answered-questions').text(Array.isArray(answers) ? answers.length : 0);
            $('#teacher-name').text(s.teacher_name || '-');
            $('#academic-year').text(s.created_at ? new Date(s.created_at).getFullYear() : '-');
            $('#semester').text('-');

            // --- Status Terlambat ---
            if (s.is_late) {
                $('#late-status').text(s.late_info || 'Terlambat');
                $('#late-card').removeClass('d-none');
            } else {
                $('#late-card').addClass('d-none');
            }

            // --- Nilai Utama ---
            $('#final-grade').text(g.toFixed(0)).css('color', style.color);
            $('#grade-progress').attr('class', 'progress-bar ' + style.class).css('width', g + '%');
            $('#performance-category').text(style.cat).attr('class', 'badge fs-6 px-4 py-2 ' + style.class);
            $('#rank-info').text(s.rank ? `Peringkat ${s.rank}` : 'Peringkat -');
            $('#class-average').text(s.class_average ? `Rata-rata: ${s.class_average}` : '');

            // ============================================================
            // PERBAIKAN DI SINI: LOGIKA UMPAN BALIK (FEEDBACK)
            // ============================================================

            // 1. Coba ambil dari submission (General)
            let finalFeedback = s.teacher_feedback;

            // 2. Jika kosong, cari komentar dari array 'answers'
            if (!finalFeedback && Array.isArray(answers) && answers.length > 0) {
                // Strategi: Kita gabungkan semua komentar guru yang ada di setiap soal
                // atau ambil yang pertama ditemukan.

                // Opsi A: Gabungkan semua komentar (jika ada banyak soal)
                const comments = answers
                    .filter(a => a.teacher_comment) // Filter yang punya komentar
                    .map(a => a.teacher_comment); // Ambil teksnya

                if (comments.length > 0) {
                    // Gabungkan dengan baris baru agar rapi
                    finalFeedback = comments.join('<br><br>');
                }
            }

            const hasFeedback = finalFeedback != null && finalFeedback !== '';
            const hasRecs = recommendations && recommendations.length > 0;

            if (hasFeedback || hasRecs) {
                $('#teacher-feedback-card').show();
                $('#teacher-name-feedback').text(s.teacher_name || 'Guru');

                // Render Komentar Utama
                if (hasFeedback) {
                    $('#teacher-feedback').parent().show();
                    // Gunakan .html() karena kita mungkin menggabungkan komentar dengan <br>
                    $('#teacher-feedback').html(finalFeedback);
                } else {
                    $('#teacher-feedback').parent().hide();
                }

                // Render Rekomendasi
                if (hasRecs) {
                    $('#learning-recommendations-container').show();
                    const $recParams = $('#learning-recommendations').empty();
                    recommendations.forEach(r => {
                        $recParams.append(`<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 py-2 px-3">${r}</span>`);
                    });
                } else {
                    $('#learning-recommendations-container').hide();
                }
            } else {
                $('#teacher-feedback-card').hide();
            }
        }

        function renderCompetencyChart(competencies) {
            if (!competencies || !Array.isArray(competencies)) return;

            const ctx = document.getElementById('competencyRadarChart');
            if (!ctx) return;

            if (competencyRadarChart) competencyRadarChart.destroy();

            // Data dari JSON (Meskipun isinya 0, tetap kita render)
            const labels = competencies.map(c => c.name);
            const dataValues = competencies.map(c => c.percentage);

            competencyRadarChart = new Chart(ctx.getContext('2d'), {
                type: 'radar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Pencapaian (%)',
                        data: dataValues,
                        backgroundColor: 'rgba(78, 115, 223, 0.2)',
                        borderColor: 'rgba(78, 115, 223, 1)',
                        pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                        borderWidth: 2
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            min: 0,
                            max: 100,
                            ticks: {
                                stepSize: 20,
                                backdropColor: 'transparent'
                            },
                            pointLabels: {
                                font: {
                                    size: 10
                                }
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
        }

        function renderCompetencyDetails(competencies) {
            const container = $('#competency-details').empty();
            const insights = $('#competency-insights').empty();

            if (!competencies || competencies.length === 0) {
                container.html('<div class="text-muted text-center small">Tidak ada data kompetensi.</div>');
                return;
            }

            competencies.forEach(c => {
                const pct = parseFloat(c.percentage);
                const style = getGradeStyle(pct);
                // Ikon berdasarkan persentase
                const icon = pct >= 75 ? 'far fa-smile' : (pct >= 60 ? 'far fa-meh' : 'far fa-frown');

                container.append(`
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="d-flex align-items-center" style="max-width: 80%;">
                            <i class="${icon} text-${style.class.replace('bg-', '')} me-2"></i>
                            <span class="fw-bold small text-truncate">${c.name}</span>
                        </div>
                        <span class="fw-bold small" style="color:${style.color}">${pct.toFixed(0)}%</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar ${style.class}" style="width: ${pct}%"></div>
                    </div>
                    <small class="text-muted d-block mt-1 fst-italic" style="font-size: 0.75rem;">
                        ${c.description || ''} (Level: ${c.level})
                    </small>
                </div>
            `);

                // Insight Sederhana
                if (pct >= 85) insights.append(`<li><span class="text-success fw-bold">Kuat:</span> ${c.name}</li>`);
                else if (pct <= 50) insights.append(`<li><span class="text-danger fw-bold">Perlu Review:</span> ${c.name}</li>`);
            });

            if (insights.children().length === 0) {
                insights.append('<li>Siswa perlu meningkatkan pemahaman di seluruh kompetensi dasar.</li>');
            }
        }

        function renderQuestionTypeChart(types) {
            const ctx = document.getElementById('questionTypeChart');
            if (!ctx || !types) return;

            const labels = Object.values(types).map(t => t.label);
            const data = Object.values(types).map(t => t.scored); // Gunakan scored

            if (questionTypeDoughnutChart) questionTypeDoughnutChart.destroy();

            questionTypeDoughnutChart = new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                font: {
                                    size: 10
                                }
                            }
                        }
                    }
                }
            });
        }

        function renderQuestionTypeDetails(types) {
            const container = $('#question-type-details').empty();
            const colors = ['primary', 'success', 'info'];
            let i = 0;

            if (!types) {
                container.html('-');
                return;
            }

            Object.values(types).forEach(t => {
                const color = colors[i % colors.length];
                container.append(`
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small fw-medium">${t.label}</span>
                        <span class="small fw-bold text-${color}">${parseFloat(t.percentage).toFixed(0)}%</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-${color}" style="width: ${t.percentage}%"></div>
                    </div>
                    <div class="mt-1">
                        <small class="text-muted" style="font-size: 10px;">Poin: ${t.scored}/${t.max}</small>
                    </div>
                </div>
            `);
                i++;
            });
        }

        function renderAnswerDetails(answers) {
            const container = $('#answerAccordion').empty();
            $('#answers-count').text(answers ? answers.length : 0);

            if (!answers || answers.length === 0) return;

            answers.forEach((q, idx) => {
                const style = getGradeStyle(q.percentage);
                const visuals = getQuestionTypeVisuals(q.type);
                const isEssay = (q.type === 'essay' || q.type === 'short_answer');

                // Logika Tampilan Jawaban
                let answerContent = '';

                if (isEssay) {
                    // Untuk Essay: Tampilkan teks jawaban
                    answerContent = `
                    <p class="mb-0 text-dark p-2 bg-white border rounded" style="white-space: pre-wrap;">${q.answer_text || '-'}</p>
                `;
                } else {
                    // Untuk PG: Tampilkan badge Benar/Salah jika is_correct tidak null
                    let badge = '';
                    if (q.is_correct === true || q.is_correct === 1) {
                        badge = '<span class="badge bg-success mb-2"><i class="fas fa-check me-1"></i> Benar</span>';
                    } else if (q.is_correct === false || q.is_correct === 0) {
                        badge = '<span class="badge bg-danger mb-2"><i class="fas fa-times me-1"></i> Salah</span>';
                    }

                    answerContent = `
                    ${badge}
                    <div class="p-2 bg-white border rounded fw-bold text-dark">
                        ${q.selected_option || '<em class="text-muted">Tidak dijawab</em>'}
                    </div>
                `;
                }

                // Logika Tampilan Komentar Per Soal (PENTING: Ambil dari answers.teacher_comment)
                const comment = q.teacher_comment || q.ai_feedback;
                const feedbackHtml = comment ? `
                <div class="mt-3 p-3 bg-info bg-opacity-10 border border-info border-opacity-25 rounded position-relative">
                    <span class="position-absolute top-0 start-0 translate-middle badge rounded-pill bg-info">
                        Guru
                    </span>
                    <p class="small mb-0 text-dark mt-1">
                        <i class="fas fa-comment-dots me-1 text-info"></i> ${comment}
                    </p>
                </div>
            ` : '';

                container.append(`
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ans-${idx}">
                            <div class="d-flex align-items-center w-100 pe-3">
                                <span class="badge ${style.class} me-3 shadow-sm" style="min-width: 30px;">${idx+1}</span>
                                <div class="flex-grow-1 text-truncate">
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="${visuals.icon} text-secondary me-2 small"></i>
                                        <small class="text-muted me-2">${visuals.label}</small>
                                        <span class="fw-bold small text-${style.class.replace('bg-', '')}">
                                            ${parseFloat(q.score_final)} / ${parseFloat(q.max_score)} Poin
                                        </span>
                                    </div>
                                    <div class="fw-medium text-dark small text-truncate" style="max-width: 90%;">
                                        ${q.question_text}
                                    </div>
                                </div>
                            </div>
                        </button>
                    </h2>
                    <div id="ans-${idx}" class="accordion-collapse collapse" data-bs-parent="#answerAccordion">
                        <div class="accordion-body bg-light">
                            <div class="mb-3">
                                <label class="small text-muted fw-bold mb-1">Pertanyaan:</label>
                                <p class="mb-0 text-dark bg-white p-2 rounded border">${q.question_text}</p>
                            </div>
                            
                            <div class="mb-0">
                                <label class="small text-muted fw-bold mb-1">Jawaban Siswa:</label>
                                ${answerContent}
                            </div>
                            
                            ${feedbackHtml}
                        </div>
                    </div>
                </div>
            `);
            });
        }

        // ============================================
        // 4. MAIN LOAD LOGIC
        // ============================================

        // Tampilkan pesan error jika AJAX gagal
        const showError = (msg) => {
            $('#loading-spinner').hide();
            $('#report-content').html(`
            <div class="alert alert-danger m-4" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> ${msg}
            </div>
        `).show();
        };

        $.ajax({
            url: API_URL,
            method: 'GET',
            dataType: 'json',
            success: (res) => {
                // Debugging: Cek data di console
                console.log("API Response:", res);

                try {
                    if (res.success && res.data) {
                        const d = res.data;

                        renderBasicInfo(d.submission, d.answers, d.recommendations);
                        renderCompetencyChart(d.competencies);
                        renderCompetencyDetails(d.competencies);
                        renderQuestionTypeChart(d.questionTypes);
                        renderQuestionTypeDetails(d.questionTypes);
                        renderAnswerDetails(d.answers);

                        $('#loading-spinner').fadeOut(300, () => {
                            $('#report-content').fadeIn();
                        });
                    } else {
                        showError("Format data API tidak valid.");
                    }
                } catch (err) {
                    console.error(err);
                    showError("Terjadi kesalahan script saat menampilkan data.");
                }
            },
            error: (xhr) => {
                console.error(xhr);
                showError(`Gagal memuat data (Status: ${xhr.status})`);
            }
        });

        // Event Listeners
        $('#print-btn').on('click', () => window.print());
        $('#expand-all').on('click', () => $('.accordion-collapse').collapse('show'));
        $('#collapse-all').on('click', () => $('.accordion-collapse').collapse('hide'));
    });
</script>
@endpush