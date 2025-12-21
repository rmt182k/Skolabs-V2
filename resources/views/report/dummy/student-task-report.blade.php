@extends('layouts.app')

@section('title', 'Laporan Detail Hasil Tugas Siswa')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header dengan Breadcrumb -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Laporan Hasil Tugas</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="#" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="#" class="text-decoration-none">Tugas</a></li>
                    <li class="breadcrumb-item active">Detail Laporan</li>
                </ol>
            </nav>
        </div>
        <div class="btn-group">
            <button class="btn btn-outline-primary" onclick="window.print()">
                <i class="bi bi-printer me-2"></i>Cetak
            </button>
            <button class="btn btn-outline-success">
                <i class="bi bi-file-earmark-excel me-2"></i>Export
            </button>
        </div>
    </div>

    <!-- Alert Informasi -->
    <div class="alert alert-info border-0 shadow-sm mb-4 d-flex align-items-center" role="alert">
        <i class="bi bi-info-circle-fill fs-4 me-3"></i>
        <div class="flex-grow-1">
            <strong>Laporan Komprehensif</strong> - Analisis detail mencakup nilai per soal, evaluasi kompetensi, dan rekomendasi pembelajaran.
        </div>
    </div>

    <div class="row g-4">
        <!-- KOLOM KIRI: Info Siswa & Statistik -->
        <div class="col-lg-4">
            <!-- Card Info Siswa -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <div class="avatar-circle mb-3">
                            <i class="bi bi-person-circle fs-1 text-primary"></i>
                        </div>
                        <h5 class="fw-bold mb-1" id="student-name">Budi Susanto</h5>
                        <p class="text-muted mb-0" id="student-class">Kelas 10A - IPA 1</p>
                        <span class="badge bg-success mt-2" id="student-status">Siswa Aktif</span>
                    </div>

                    <hr class="my-4">

                    <dl class="row mb-0">
                        <dt class="col-5 text-muted small mb-2">NIS</dt>
                        <dd class="col-7 small mb-2 fw-medium" id="student-nis">20240001</dd>

                        <dt class="col-5 text-muted small mb-2">Email</dt>
                        <dd class="col-7 small mb-2" id="student-email">budi.s@sekolah.com</dd>

                        <dt class="col-5 text-muted small mb-2">Tahun Ajaran</dt>
                        <dd class="col-7 small mb-2" id="academic-year">2024/2025</dd>

                        <dt class="col-5 text-muted small">Semester</dt>
                        <dd class="col-7 small fw-medium" id="semester">Ganjil</dd>
                    </dl>
                </div>
            </div>

            <!-- Card Statistik Ringkas -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold">Statistik Pengerjaan</h6>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div>
                            <i class="bi bi-clock-history text-primary fs-4"></i>
                        </div>
                        <div class="text-end">
                            <div class="small text-muted">Waktu Pengerjaan</div>
                            <div class="fw-bold" id="duration">45 menit 32 detik</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div>
                            <i class="bi bi-calendar-check text-success fs-4"></i>
                        </div>
                        <div class="text-end">
                            <div class="small text-muted">Waktu Kumpul</div>
                            <div class="fw-bold" id="submission-time">05 Nov 2025, 09:30</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div>
                            <i class="bi bi-check2-circle text-info fs-4"></i>
                        </div>
                        <div class="text-end">
                            <div class="small text-muted">Soal Terjawab</div>
                            <div class="fw-bold" id="answered-questions">15 / 15</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-person-badge text-warning fs-4"></i>
                        </div>
                        <div class="text-end">
                            <div class="small text-muted">Diperiksa Oleh</div>
                            <div class="fw-bold" id="teacher-name">Andini, S.Pd.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Status Keterlambatan (jika ada) -->
            <div class="card border-0 shadow-sm bg-warning bg-opacity-10 d-none" id="late-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill text-warning fs-4 me-3"></i>
                        <div>
                            <small class="text-muted d-block">Status Pengumpulan</small>
                            <strong class="text-warning">Terlambat 2 hari</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KOLOM KANAN: Detail Tugas & Analisis -->
        <div class="col-lg-8">
            <!-- Card Header Tugas -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-gradient-primary text-white py-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-white bg-opacity-25 me-2" id="task-type">UJIAN</span>
                                <span class="badge bg-white bg-opacity-25" id="subject-name">Biologi</span>
                            </div>
                            <h4 class="fw-bold mb-2" id="task-title">Ujian Bab 1: Struktur Sel dan Fotosintesis</h4>
                            <p class="mb-0 opacity-90 small" id="task-description">
                                Evaluasi pemahaman siswa tentang struktur sel, fungsi organel, dan proses fotosintesis
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Card Nilai Utama -->
                <div class="card-body p-0">
                    <div class="row g-0">
                        <!-- Nilai Akhir -->
                        <div class="col-md-6 p-4 text-center border-end">
                            <div class="small text-muted text-uppercase mb-2 fw-medium">Nilai Akhir</div>
                            <div class="display-1 fw-bold mb-2" id="final-grade" style="color: #28a745;">82.5</div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" id="grade-progress" style="width: 82.5%"></div>
                            </div>
                            <div class="mt-2 small text-muted">dari 100.0 poin</div>
                        </div>

                        <!-- Performa Kategori -->
                        <div class="col-md-6 p-4">
                            <div class="small text-muted text-uppercase mb-3 fw-medium text-center">Kategori Performa</div>
                            <div class="text-center mb-3">
                                <span class="badge bg-success fs-6 px-4 py-2" id="performance-category">Baik Sekali</span>
                            </div>
                            <div class="small text-center">
                                <div class="mb-2">
                                    <i class="bi bi-trophy-fill text-warning me-1"></i>
                                    <span class="fw-medium" id="rank-info">Peringkat 3 dari 30 siswa</span>
                                </div>
                                <div class="text-muted" id="class-average">Rata-rata kelas: 75.2</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Analisis Kompetensi (CHART) -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">
                            <i class="bi bi-graph-up-arrow text-primary me-2"></i>
                            Analisis Pencapaian Kompetensi
                        </h5>
                        <button class="btn btn-sm btn-outline-secondary" id="toggle-chart-view">
                            <i class="bi bi-bar-chart-fill"></i> Ubah Tampilan
                        </button>
                    </div>
                    <p class="text-muted small mb-0 mt-2">Evaluasi kemampuan siswa berdasarkan indikator kompetensi yang diuji</p>
                </div>
                <div class="card-body p-4">
                    <!-- Radar Chart Canvas -->
                    <div class="mb-4">
                        <canvas id="competencyRadarChart" height="280"></canvas>
                    </div>

                    <!-- Detail Kompetensi dengan Progress Bar -->
                    <div id="competency-details" class="mt-4">
                        <!-- Akan diisi dengan JavaScript -->
                    </div>

                    <!-- Summary Kompetensi -->
                    <div class="alert alert-light border-0 mt-4">
                        <h6 class="alert-heading fw-bold small">
                            <i class="bi bi-lightbulb-fill text-warning me-2"></i>Insight Kompetensi
                        </h6>
                        <ul class="mb-0 small" id="competency-insights">
                            <li><strong>Kekuatan:</strong> Pemahaman Struktur Sel (93.3%)</li>
                            <li><strong>Perlu Ditingkatkan:</strong> Keterampilan Menulis Ilmiah (70.0%)</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Card Distribusi Nilai per Tipe Soal -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-pie-chart-fill text-success me-2"></i>
                        Distribusi Nilai per Tipe Soal
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <canvas id="questionTypeChart" height="200"></canvas>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex flex-column justify-content-center h-100">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="small fw-medium">Pilihan Ganda</span>
                                        <span class="small fw-bold text-success">92.0%</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-success" style="width: 92%"></div>
                                    </div>
                                    <small class="text-muted">23/25 poin</small>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="small fw-medium">Esai</span>
                                        <span class="small fw-bold text-warning">78.0%</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-warning" style="width: 78%"></div>
                                    </div>
                                    <small class="text-muted">39/50 poin</small>
                                </div>
                                <div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="small fw-medium">Isian Singkat</span>
                                        <span class="small fw-bold text-info">80.0%</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-info" style="width: 80%"></div>
                                    </div>
                                    <small class="text-muted">20/25 poin</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Feedback Umum Guru -->
            <div class="card border-0 shadow-sm mb-4" id="teacher-feedback-card">
                <div class="card-header bg-light border-bottom py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-chat-left-quote-fill text-info me-2"></i>
                        Umpan Balik Guru
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="bi bi-person-circle fs-1 text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="fw-medium mb-2">Andini, S.Pd. <span class="text-muted small">- 06 Nov 2025, 14:20</span></div>
                            <div class="bg-light p-3 rounded-3">
                                <p class="mb-0" id="teacher-feedback">
                                    "Kerja bagus, Budi! Pemahamanmu tentang struktur sel sudah sangat baik dengan jawaban yang detail dan sistematis.
                                    Namun, perlu sedikit lebih detail saat menjelaskan proses fotosintesis, terutama pada reaksi gelap (siklus Calvin).
                                    Cobalah untuk lebih memperhatikan penggunaan istilah ilmiah yang tepat dan penjelasan mekanisme molekuler.
                                    Tingkatkan juga keterampilan menulis ilmiah dengan struktur paragraf yang lebih terorganisir."
                                </p>
                            </div>

                            <!-- Rekomendasi Pembelajaran -->
                            <div class="mt-3">
                                <h6 class="small fw-bold text-primary mb-2">📚 Rekomendasi Pembelajaran:</h6>
                                <div class="d-flex flex-wrap gap-2" id="learning-recommendations">
                                    <span class="badge bg-primary bg-opacity-10 text-primary">Bab 2: Siklus Calvin</span>
                                    <span class="badge bg-primary bg-opacity-10 text-primary">Video: Reaksi Gelap Fotosintesis</span>
                                    <span class="badge bg-primary bg-opacity-10 text-primary">Latihan: Penulisan Ilmiah</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Rincian Jawaban per Soal (Accordion) -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">
                            <i class="bi bi-list-check text-primary me-2"></i>
                            Rincian Jawaban per Soal
                        </h5>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-secondary" id="expand-all">
                                <i class="bi bi-arrows-expand"></i> Buka Semua
                            </button>
                            <button class="btn btn-outline-secondary" id="collapse-all">
                                <i class="bi bi-arrows-collapse"></i> Tutup Semua
                            </button>
                        </div>
                    </div>
                </div>
                <div class="accordion accordion-flush" id="answerAccordion">
                    <!-- Akan diisi dengan JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .avatar-circle {
        width: 80px;
        height: 80px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        color: white;
    }

    .card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1) !important;
    }

    .accordion-button:not(.collapsed) {
        background-color: #f8f9fa;
        box-shadow: none;
    }

    .accordion-button:focus {
        box-shadow: none;
        border-color: rgba(0, 0, 0, .125);
    }

    .competency-item {
        transition: all 0.3s ease;
    }

    .competency-item:hover {
        background-color: #f8f9fa;
        border-radius: 8px;
    }

    .question-status-badge {
        width: 35px;
        height: 35px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-weight: bold;
        font-size: 0.85rem;
    }

    @media print {

        .btn-group,
        .no-print {
            display: none !important;
        }
    }
</style>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        // ============================================
        // DATA DUMMY - COMPREHENSIVE
        // ============================================

        const reportData = {
            student: {
                name: 'Budi Susanto',
                nis: '20240001',
                class: 'Kelas 10A - IPA 1',
                email: 'budi.s@sekolah.com',
                status: 'Siswa Aktif'
            },

            task: {
                title: 'Ujian Bab 1: Struktur Sel dan Fotosintesis',
                type: 'UJIAN',
                subject: 'Biologi',
                description: 'Evaluasi pemahaman siswa tentang struktur sel, fungsi organel, dan proses fotosintesis',
                teacher: 'Andini, S.Pd.',
                total_questions: 15,
                total_score: 100
            },

            submission: {
                submitted_at: '2025-11-05 09:30:00',
                duration: '45 menit 32 detik',
                final_grade: 82.5,
                status: 'graded',
                is_late: false,
                late_days: 0,
                answered_count: 15,
                rank: 3,
                total_students: 30,
                class_average: 75.2,
                teacher_feedback: 'Kerja bagus, Budi! Pemahamanmu tentang struktur sel sudah sangat baik dengan jawaban yang detail dan sistematis. Namun, perlu sedikit lebih detail saat menjelaskan proses fotosintesis, terutama pada reaksi gelap (siklus Calvin). Cobalah untuk lebih memperhatikan penggunaan istilah ilmiah yang tepat dan penjelasan mekanisme molekuler. Tingkatkan juga keterampilan menulis ilmiah dengan struktur paragraf yang lebih terorganisir.',
                learning_recommendations: [
                    'Bab 2: Siklus Calvin',
                    'Video: Reaksi Gelap Fotosintesis',
                    'Latihan: Penulisan Ilmiah'
                ]
            },

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
                    percentage: 92.0
                },
                'essay': {
                    scored: 39,
                    max: 50,
                    percentage: 78.0
                },
                'short_answer': {
                    scored: 20,
                    max: 25,
                    percentage: 80.0
                }
            },

            answers: [{
                    question_number: 1,
                    question_text: 'Jelaskan perbedaan utama antara sel hewan dan sel tumbuhan, sertakan minimal 4 perbedaan struktural!',
                    type: 'essay',
                    max_score: 20,
                    score_awarded: 18.0,
                    is_correct: null,
                    answer_text: 'Sel tumbuhan memiliki dinding sel yang terbuat dari selulosa, sedangkan sel hewan tidak memiliki dinding sel. Sel tumbuhan memiliki kloroplas untuk fotosintesis, sel hewan tidak. Vakuola pada sel tumbuhan berukuran besar dan tunggal, sementara sel hewan memiliki vakuola kecil atau tidak ada. Sel tumbuhan memiliki bentuk tetap karena dinding sel, sedangkan sel hewan bentuknya tidak tetap.',
                    teacher_comment: 'Jawaban sangat baik dan lengkap! Semua perbedaan dijelaskan dengan detail. Penjelasan tentang fungsi struktural juga sudah tepat.',
                    competency_evaluations: [{
                            name: 'Memahami Struktur Sel',
                            score: 15.0,
                            max: 15.0
                        },
                        {
                            name: 'Keterampilan Menulis Ilmiah',
                            score: 3.0,
                            max: 5.0
                        }
                    ]
                },
                {
                    question_number: 2,
                    question_text: 'Organel yang berfungsi sebagai "pembangkit energi" dalam sel adalah...',
                    type: 'multiple_choice',
                    max_score: 5,
                    score_awarded: 5.0,
                    is_correct: true,
                    selected_option: 'Mitokondria',
                    options: [{
                            text: 'Ribosom',
                            is_correct: false
                        },
                        {
                            text: 'Mitokondria',
                            is_correct: true
                        },
                        {
                            text: 'Lisosom',
                            is_correct: false
                        },
                        {
                            text: 'Retikulum Endoplasma',
                            is_correct: false
                        }
                    ],
                    teacher_comment: null,
                    competency_evaluations: [{
                        name: 'Memahami Struktur Sel',
                        score: 5.0,
                        max: 5.0
                    }]
                },
                {
                    question_number: 3,
                    question_text: 'Jelaskan secara rinci proses yang terjadi pada reaksi terang fotosintesis, termasuk lokasi dan produk yang dihasilkan.',
                    type: 'essay',
                    max_score: 30,
                    score_awarded: 23.0,
                    is_correct: null,
                    answer_text: 'Reaksi terang terjadi di membran tilakoid dalam grana kloroplas. Proses dimulai dengan penyerapan cahaya oleh klorofil. Energi cahaya digunakan untuk memecah molekul air (fotolisis), menghasilkan elektron, proton, dan oksigen. Elektron berenergi tinggi melewati rantai transpor elektron, menghasilkan ATP. NADP+ menerima elektron dan proton membentuk NADPH. Produk akhir reaksi terang adalah ATP, NADPH, dan O2.',
                    teacher_comment: 'Penjelasan sudah baik dan mencakup poin-poin penting. Namun, bisa lebih detail tentang mekanisme fotosistem I dan II, serta proses kemiosmosis dalam pembentukan ATP. Penjelasan transfer elektron bisa diperjelas.',
                    competency_evaluations: [{
                            name: 'Analisis Proses Fotosintesis',
                            score: 18.0,
                            max: 25.0
                        },
                        {
                            name: 'Keterampilan Menulis Ilmiah',
                            score: 5.0,
                            max: 5.0
                        }
                    ]
                },
                {
                    question_number: 4,
                    question_text: 'Sebutkan 3 faktor yang mempengaruhi laju fotosintesis',
                    type: 'short_answer',
                    max_score: 10,
                    score_awarded: 10.0,
                    is_correct: true,
                    answer_text: 'Intensitas cahaya, konsentrasi CO2, dan suhu',
                    teacher_comment: 'Benar! Jawaban tepat dan ringkas.',
                    competency_evaluations: [{
                        name: 'Analisis Proses Fotosintesis',
                        score: 10.0,
                        max: 10.0
                    }]
                },
                {
                    question_number: 5,
                    question_text: 'Fungsi utama dari Retikulum Endoplasma Kasar (RER) adalah...',
                    type: 'multiple_choice',
                    max_score: 5,
                    score_awarded: 5.0,
                    is_correct: true,
                    selected_option: 'Sintesis protein',
                    options: [{
                            text: 'Sintesis lipid',
                            is_correct: false
                        },
                        {
                            text: 'Sintesis protein',
                            is_correct: true
                        },
                        {
                            text: 'Pencernaan sel',
                            is_correct: false
                        },
                        {
                            text: 'Penyimpanan energi',
                            is_correct: false
                        }
                    ],
                    teacher_comment: null,
                    competency_evaluations: [{
                        name: 'Memahami Struktur Sel',
                        score: 5.0,
                        max: 5.0
                    }]
                }
            ]
        };

        // ============================================
        // RENDER FUNCTIONS
        // ============================================

        function renderBasicInfo() {
            const {
                student,
                task,
                submission
            } = reportData;

            document.getElementById('student-name').textContent = student.name;
            document.getElementById('student-nis').textContent = student.nis;
            document.getElementById('student-class').textContent = student.class;
            document.getElementById('student-email').textContent = student.email;
            document.getElementById('student-status').textContent = student.status;

            document.getElementById('task-title').textContent = task.title;
            document.getElementById('task-type').textContent = task.type;
            document.getElementById('subject-name').textContent = task.subject;
            document.getElementById('task-description').textContent = task.description;
            document.getElementById('teacher-name').textContent = task.teacher;

            document.getElementById('submission-time').textContent = new Date(submission.submitted_at).toLocaleString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            document.getElementById('duration').textContent = submission.duration;
            document.getElementById('answered-questions').textContent = `${submission.answered_count} / ${task.total_questions}`;

            // Nilai dan Progress
            const grade = submission.final_grade;
            document.getElementById('final-grade').textContent = grade.toFixed(1);
            document.getElementById('grade-progress').style.width = grade + '%';

            // Update warna progress bar
            const progressBar = document.getElementById('grade-progress');
            if (grade >= 85) {
                progressBar.className = 'progress-bar bg-success';
                document.getElementById('final-grade').style.color = '#28a745';
            } else if (grade >= 70) {
                progressBar.className = 'progress-bar bg-info';
                document.getElementById('final-grade').style.color = '#17a2b8';
            } else if (grade >= 60) {
                progressBar.className = 'progress-bar bg-warning';
                document.getElementById('final-grade').style.color = '#ffc107';
            } else {
                progressBar.className = 'progress-bar bg-danger';
                document.getElementById('final-grade').style.color = '#dc3545';
            }

            // Kategori Performa
            let category = '';
            let categoryClass = '';
            if (grade >= 90) {
                category = 'Istimewa';
                categoryClass = 'bg-purple';
            } else if (grade >= 85) {
                category = 'Baik Sekali';
                categoryClass = 'bg-success';
            } else if (grade >= 70) {
                category = 'Baik';
                categoryClass = 'bg-info';
            } else if (grade >= 60) {
                category = 'Cukup';
                categoryClass = 'bg-warning';
            } else {
                category = 'Perlu Perbaikan';
                categoryClass = 'bg-danger';
            }

            document.getElementById('performance-category').textContent = category;
            document.getElementById('performance-category').className = `badge ${categoryClass} fs-6 px-4 py-2`;

            document.getElementById('rank-info').textContent = `Peringkat ${submission.rank} dari ${submission.total_students} siswa`;
            document.getElementById('class-average').textContent = `Rata-rata kelas: ${submission.class_average}`;

            // Feedback
            document.getElementById('teacher-feedback').textContent = submission.teacher_feedback;

            // Learning Recommendations
            const recContainer = document.getElementById('learning-recommendations');
            recContainer.innerHTML = submission.learning_recommendations.map(rec =>
                `<span class="badge bg-primary bg-opacity-10 text-primary">${rec}</span>`
            ).join('');

            // Late status
            if (submission.is_late) {
                document.getElementById('late-card').classList.remove('d-none');
            }
        }

        function renderCompetencyChart() {
            const ctx = document.getElementById('competencyRadarChart').getContext('2d');
            const competencies = reportData.competencies;

            new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: competencies.map(c => c.name),
                    datasets: [{
                        label: 'Pencapaian Siswa (%)',
                        data: competencies.map(c => c.percentage),
                        backgroundColor: 'rgba(102, 126, 234, 0.2)',
                        borderColor: 'rgba(102, 126, 234, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(102, 126, 234, 1)',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgba(102, 126, 234, 1)',
                        pointRadius: 5,
                        pointHoverRadius: 7
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
                    maintainAspectRatio: true,
                    scales: {
                        r: {
                            min: 0,
                            max: 100,
                            ticks: {
                                stepSize: 20,
                                callback: function(value) {
                                    return value + '%';
                                }
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
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.r.toFixed(1) + '%';
                                }
                            }
                        }
                    }
                }
            });
        }

        function renderCompetencyDetails() {
            const container = document.getElementById('competency-details');
            const competencies = reportData.competencies;

            container.innerHTML = competencies.map((comp, index) => {
                const percentage = comp.percentage;
                let colorClass = 'danger';
                let bgColorClass = 'bg-danger';
                let icon = 'emoji-frown';

                if (percentage >= 85) {
                    colorClass = 'success';
                    bgColorClass = 'bg-success';
                    icon = 'emoji-smile';
                } else if (percentage >= 70) {
                    colorClass = 'info';
                    bgColorClass = 'bg-info';
                    icon = 'emoji-neutral';
                } else if (percentage >= 60) {
                    colorClass = 'warning';
                    bgColorClass = 'bg-warning';
                    icon = 'emoji-neutral';
                }

                return `
                <div class="competency-item p-3 mb-3 border rounded">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center mb-1">
                                <i class="bi bi-${icon} text-${colorClass} me-2 fs-5"></i>
                                <h6 class="mb-0 fw-bold">${comp.name}</h6>
                            </div>
                            <p class="text-muted small mb-2">${comp.description}</p>
                        </div>
                        <div class="text-end ms-3">
                            <div class="fw-bold fs-5 text-${colorClass}">${percentage.toFixed(1)}%</div>
                            <small class="text-muted">${comp.score_awarded.toFixed(1)}/${comp.max_score.toFixed(1)}</small>
                        </div>
                    </div>
                    <div class="progress" style="height: 12px;">
                        <div class="progress-bar ${bgColorClass}" style="width: ${percentage}%"></div>
                    </div>
                    <div class="mt-2">
                        <span class="badge bg-${colorClass} bg-opacity-10 text-${colorClass} small">${comp.level}</span>
                    </div>
                </div>
            `;
            }).join('');
        }

        function renderQuestionTypeChart() {
            const ctx = document.getElementById('questionTypeChart').getContext('2d');
            const types = reportData.questionTypes;

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Pilihan Ganda', 'Esai', 'Isian Singkat'],
                    datasets: [{
                        data: [types.multiple_choice.scored, types.essay.scored, types.short_answer.scored],
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.8)',
                            'rgba(255, 193, 7, 0.8)',
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
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    return label + ': ' + value.toFixed(1) + ' poin';
                                }
                            }
                        }
                    }
                }
            });
        }

        function renderAnswerDetails() {
            const container = document.getElementById('answerAccordion');
            const answers = reportData.answers;

            container.innerHTML = answers.map((answer, index) => {
                const q = answer;
                const percentage = (q.score_awarded / q.max_score) * 100;

                let scoreBadgeClass = 'bg-danger';
                let statusIcon = 'x-circle-fill';
                let statusColor = 'danger';

                if (percentage >= 85) {
                    scoreBadgeClass = 'bg-success';
                    statusIcon = 'check-circle-fill';
                    statusColor = 'success';
                } else if (percentage >= 70) {
                    scoreBadgeClass = 'bg-info';
                    statusIcon = 'check-circle';
                    statusColor = 'info';
                } else if (percentage >= 60) {
                    scoreBadgeClass = 'bg-warning';
                    statusIcon = 'exclamation-circle';
                    statusColor = 'warning';
                }

                // Tipe soal label
                let typeLabel = '';
                let typeIcon = '';
                if (q.type === 'essay') {
                    typeLabel = 'Esai';
                    typeIcon = 'file-text';
                } else if (q.type === 'multiple_choice') {
                    typeLabel = 'Pilihan Ganda';
                    typeIcon = 'ui-checks';
                } else {
                    typeLabel = 'Isian Singkat';
                    typeIcon = 'input-cursor-text';
                }

                // Render jawaban berdasarkan tipe
                let answerHtml = '';
                if (q.type === 'multiple_choice') {
                    answerHtml = `
                    <h6 class="small fw-bold text-muted mb-2">Pilihan Jawaban:</h6>
                    <ul class="list-group mb-3">
                        ${q.options.map(opt => {
                            const isSelected = opt.text === q.selected_option;
                            const isCorrect = opt.is_correct;

                            let itemClass = 'list-group-item';
                            let icon = 'circle';
                            let iconColor = 'muted';

                            if (isSelected && isCorrect) {
                                itemClass += ' list-group-item-success border-success';
                                icon = 'check-circle-fill';
                                iconColor = 'success';
                            } else if (isSelected && !isCorrect) {
                                itemClass += ' list-group-item-danger border-danger';
                                icon = 'x-circle-fill';
                                iconColor = 'danger';
                            } else if (!isSelected && isCorrect) {
                                itemClass += ' list-group-item-light border-success';
                                icon = 'arrow-right-circle';
                                iconColor = 'success';
                            }

                            return ` <
                        li class = "${itemClass}" >
                        <
                        i class = "bi bi-${icon} text-${iconColor} me-2" > < /i>
                    $ {
                        opt.text
                    }
                    $ {
                        isSelected ? '<span class="badge bg-primary ms-2">Jawaban Anda</span>' : ''
                    }
                    $ {
                        isCorrect ? '<span class="badge bg-success ms-2">Jawaban Benar</span>' : ''
                    } <
                    /li>
                    `;
                        }).join('')}
                    </ul>
                `;
                } else {
                    answerHtml = `
                    <h6 class="small fw-bold text-muted mb-2">Jawaban Siswa:</h6>
                    <div class="bg-light p-3 rounded border-start border-4 border-primary mb-3">
                        <p class="mb-0" style="white-space: pre-wrap;">${q.answer_text}</p>
                    </div>
                `;
                }

                // Teacher comment
                const commentHtml = q.teacher_comment ? `
                <div class="alert alert-info border-0 border-start border-4 border-info mb-3">
                    <h6 class="alert-heading small fw-bold mb-2">
                        <i class="bi bi-chat-square-text-fill me-2"></i>Komentar Guru:
                    </h6>
                    <p class="mb-0 small"><em>"${q.teacher_comment}"</em></p>
                </div>
            ` : '';

                // Competency evaluations
                const compEvalHtml = q.competency_evaluations.map(eval => {
                    const evalPercentage = (eval.score / eval.max) * 100;
                    let evalColor = 'danger';
                    if (evalPercentage >= 85) evalColor = 'success';
                    else if (evalPercentage >= 70) evalColor = 'info';
                    else if (evalPercentage >= 60) evalColor = 'warning';

                    return `
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <span class="small text-muted">
                            <i class="bi bi-bookmark-fill text-${evalColor} me-2"></i>${eval.name}
                        </span>
                        <span class="fw-bold text-${evalColor}">${eval.score.toFixed(1)} / ${eval.max.toFixed(1)}</span>
                    </div>
                `;
                }).join('');

                return `
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#answer${index}">
                            <div class="d-flex align-items-center w-100 pe-3">
                                <span class="question-status-badge ${scoreBadgeClass} text-white me-3">
                                    ${q.question_number}
                                </span>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="bi bi-${typeIcon} text-primary me-2"></i>
                                        <span class="badge bg-primary bg-opacity-10 text-primary small">${typeLabel}</span>
                                        <span class="badge ${scoreBadgeClass} bg-opacity-10 text-white ms-2 small">
                                            ${q.score_awarded.toFixed(1)} / ${q.max_score} poin
                                        </span>
                                    </div>
                                    <div class="fw-medium text-dark small">${q.question_text}</div>
                                </div>
                                <i class="bi bi-${statusIcon} text-${statusColor} fs-4 ms-3"></i>
                            </div>
                        </button>
                    </h2>
                    <div id="answer${index}" class="accordion-collapse collapse" data-bs-parent="#answerAccordion">
                        <div class="accordion-body">
                            ${answerHtml}
                            ${commentHtml}

                            <div class="mt-3">
                                <h6 class="small fw-bold text-muted mb-2">
                                    <i class="bi bi-graph-up me-2"></i>Kontribusi Kompetensi:
                                </h6>
                                ${compEvalHtml}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            }).join('');
        }

        // ============================================
        // EVENT HANDLERS
        // ============================================

        document.getElementById('expand-all')?.addEventListener('click', function() {
            document.querySelectorAll('.accordion-collapse').forEach(el => {
                const bsCollapse = new bootstrap.Collapse(el, {
                    toggle: false
                });
                bsCollapse.show();
            });
        });

        document.getElementById('collapse-all')?.addEventListener('click', function() {
            document.querySelectorAll('.accordion-collapse').forEach(el => {
                const bsCollapse = new bootstrap.Collapse(el, {
                    toggle: false
                });
                bsCollapse.hide();
            });
        });

        // ============================================
        // INITIALIZE
        // ============================================

        renderBasicInfo();
        renderCompetencyChart();
        renderCompetencyDetails();
        renderQuestionTypeChart();
        renderAnswerDetails();

    });
</script>
@endpush