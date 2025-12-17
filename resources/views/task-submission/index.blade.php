@extends('layouts.app')

@section('title', 'Hasil Pengerjaan: ' . $task->title)

@push('styles')
{{-- CSS Anda yang sudah ada --}}
<style>
    .stats-card {
        /* ... style Anda ... */
    }

    .stats-item {
        /* ... style Anda ... */
    }

    /* [BARU] Style untuk tombol aksi agar lebarnya konsisten */
    #submissions-table .btn-aksi {
        width: 130px;
        text-align: left;
        padding-left: 10px;
    }

    #submissions-table .btn-aksi .fa-spin {
        margin-right: 8px;
    }

    #submissions-table .btn-aksi i {
        width: 20px;
        text-align: center;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    {{-- Breadcrumb --}}
    @include('layouts.components.breadcrumb')

    {{-- Kartu Informasi Tugas --}}
    <div class="card shadow-sm mb-4">
        {{-- ... Konten header tugas Anda (sudah OK) ... --}}
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0">
                <i class="fas fa-clipboard-check me-2 text-primary"></i>
                Hasil Pengerjaan: <span class="fw-bold text-dark">{{ $task->title }}</span>
            </h5>
            <div class="mt-2 text-muted small">
                <span class="me-3"><i class="fas fa-school me-1"></i> Kelas:
                    <strong>{{ $class->name }}</strong></span>
                <span class="me-3"><i class="fas fa-book me-1"></i> Mapel:
                    <strong>{{ $task->subject_name ?? 'N/A' }}</strong></span>
                <span class="me-3"><i class="fas fa-hourglass-end me-1"></i> Batas Waktu:

                    <strong>{{ \Carbon\Carbon::parse(time: $task->end_time)->format('d M Y, H:i') }}</strong></span>
            </div>
        </div>
    </div>

    {{-- Kartu Statistik --}}
    <div class="card shadow-sm mb-4">
        {{-- ... Konten kartu statistik Anda (sudah OK) ... --}}
        <div class="card-body">
            <div class="row stats-card">
                <div class="col-md-4 stats-item border-end">
                    <div class="value stats-loading" id="stats-submissions-value">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                    </div>
                    <div class="label">Siswa Mengerjakan</div>
                </div>
                <div class="col-md-4 stats-item border-end">
                    <div class="value stats-loading" id="stats-not-submitted-value">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                    </div>
                    <div class="label">Siswa Belum Mengerjakan</div>
                </div>
                <div class="col-md-4 stats-item">
                    <div class="value stats-loading" id="stats-average-score-value">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                    </div>
                    <div class="label">Rata-rata Nilai</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel Hasil Pengerjaan --}}
    <div class="card shadow-sm">
        {{-- ... Konten header tabel dan filter (sudah OK) ... --}}
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0 text-dark">
                <i class="fas fa-table me-2"></i>Daftar Submisi Siswa
            </h6>
            <div class="filter-buttons btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary status-filter active" data-status="">
                    <i class="fas fa-th me-1"></i>Semua
                </button>
                <button type="button" class="btn btn-outline-success status-filter" data-status="graded">
                    <i class="fas fa-check-circle me-1"></i>Sudah Dinilai
                </button>
                {{-- [BARU] Filter untuk status baru --}}
                <button type="button" class="btn btn-outline-primary status-filter" data-status="pending_review">
                    <i class="fas fa-user-check me-1"></i>Perlu Review
                </button>
                <button type="button" class="btn btn-outline-info status-filter" data-status="submitted">
                    <i class="fas fa-robot me-1"></i>Siap Proses AI
                </button>
                <button type="button" class="btn btn-outline-danger status-filter" data-status="not_submitted"><i
                        class="fas fa-times-circle me-1"></i>Belum Mengerjakan
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle" id="submissions-table" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 5%;" class="text-center">No</th>
                            <th style="width: 12%;">NISN/Induk</th>
                            <th>Nama Siswa</th>
                            <th style="width: 18%;">Status Pengerjaan</th>
                            <th style="width: 15%;">Waktu Submisi</th>
                            <th style="width: 10%;" class="text-center">Nilai</th>
                            <th style="width: 15%;" class="text-center">Aksi</th> {{-- Lebar disesuaikan --}}
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Data akan di-load oleh AJAX DataTables --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- ⭐ CRITICAL: Pastikan jQuery dan DataTables sudah di-load di layouts.app --}}
{{-- ⭐ [BARU] SweetAlert untuk konfirmasi --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {

        // ================================================================
        // KONFIGURASI
        // ================================================================

        // [BARU] Setup CSRF Token untuk semua request AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        const pathParts = window.location.pathname.split('/');
        const CLASS_ID = pathParts[2];
        const TASK_ID = pathParts[4];

        const API_URL = `/api/classes/${CLASS_ID}/tasks/${TASK_ID}/submissions-data`;

        // [BARU] URL untuk memicu AI
        const AI_RUN_URL_TEMPLATE = `/api/submissions/SUBMISSION_ID/run-ai`;


        // ================================================================
        // INISIALISASI DATATABLE
        // ================================================================

        const table = $('#submissions-table').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: API_URL,
                dataSrc: function(json) {
                    if (json.error) {
                        console.error('❌ API Error:', json.error);
                        alert('Gagal memuat data: ' + json.error);
                        return [];
                    }
                    updateStatsCards(json.stats);
                    return json.data || [];
                },
                error: function(xhr, error, code) {
                    console.error('❌ AJAX Error:', error, code);
                    alert('Terjadi kesalahan saat memuat data. Silakan refresh halaman.');
                }
            },
            columns: [{
                    data: null,
                    defaultContent: '',
                    searchable: false,
                    orderable: false,
                    className: 'text-center'
                }, // No
                {
                    data: 'identity_number'
                },
                {
                    data: 'name',
                },
                {
                    data: 'status_pengerjaan'
                }, // Status (Badge)
                {
                    data: 'submitted_at_formatted'
                }, // Waktu Submisi
                {
                    data: 'score'
                }, // Nilai
                {
                    data: null,
                    searchable: false,
                    orderable: false,
                    className: 'text-center'
                } // Aksi
            ],
            columnDefs: [{
                    // ⭐ Kolom Status Pengerjaan (index 3)
                    // [UPDATE] Menambahkan badge untuk status baru
                    targets: 3,
                    render: function(data, type, row) {
                        // Teks dan Badge dari Controller sudah OK, tapi kita bisa override di sini
                        // jika perlu. Kita gunakan data dari controller saja.
                        // `status_badge` dan `status_pengerjaan` sudah diset di controller.
                        return `<span class="badge ${row.status_badge}">${row.status_pengerjaan}</span>`;
                    }
                },
                {
                    // ⭐ Kolom Nilai (index 5)
                    targets: 5,
                    className: 'text-center fw-bold',
                    render: function(data, type, row) {
                        if (row.status_raw === 'not_submitted') {
                            return '<span class="text-muted">-</span>';
                        }
                        if (data !== null && data !== undefined) {
                            let grade = parseFloat(data).toFixed(2);
                            let colorClass = 'bg-success';
                            if (grade < 60) colorClass = 'bg-danger';
                            else if (grade < 75) colorClass = 'bg-warning text-dark';
                            else if (grade < 85) colorClass = 'bg-info text-dark';

                            return `<span class="badge ${colorClass}">${grade}</span>`;
                        } else {
                            // Jika status 'pending_review' atau 'ai_processing'
                            if (row.status_raw === 'pending_review' || row.status_raw ===
                                'ai_processing') {
                                return `<span class="badge bg-light text-dark">Menunggu...</span>`;
                            }
                            return '<span class="badge bg-secondary">Belum Dinilai</span>';
                        }
                    }
                },
                {
                    // ⭐ [PERUBAIKAN BESAR] Kolom Aksi (index 6)
                    targets: 6,
                    render: function(data, type, row) {

                        // Jika belum mengerjakan
                        if (!row.submission_id) {
                            return `<button class="btn btn-sm btn-secondary btn-aksi" disabled title="Belum ada jawaban">
                                            <i class="fas fa-eye-slash"></i> Belum
                                        </button>`;
                        }

                        const gradeUrl =
                            `/classes/${CLASS_ID}/tasks/${TASK_ID}/submissions/${row.submission_id}/grade`;
                        let actionButton = '';

                        // Logika tombol berdasarkan status dari database
                        switch (row.status_raw) {
                            case 'submitted':
                            case 'late':
                                // Status 'late' juga harus bisa di-proses AI
                                actionButton = `
                                        <button class="btn btn-sm btn-info btn-aksi run-ai-btn" data-id="${row.submission_id}" title="Mulai analisis AI">
                                            <i class="fas fa-robot"></i> Jalankan AI
                                        </button>`;
                                break;
                            case 'ai_processing':
                                actionButton = `
                                        <button class="btn btn-sm btn-secondary btn-aksi" disabled>
                                            <i class="fas fa-spinner fa-spin"></i> Memproses...
                                        </button>`;
                                break;
                            case 'pending_review':
                                actionButton = `
                                        <a href="${gradeUrl}" class="btn btn-sm btn-primary btn-aksi" title="Review hasil AI & beri nilai final">
                                            <i class="fas fa-edit"></i> Review
                                        </a>`;
                                break;
                            case 'graded':
                                actionButton = `
                                        <a href="${gradeUrl}" class="btn btn-sm btn-success btn-aksi" title="Lihat hasil akhir">
                                            <i class="fas fa-eye"></i> Lihat Hasil
                                        </a>`;
                                break;
                            default:
                                // Fallback (seharusnya tidak terjadi jika ada submission_id)
                                actionButton = `<span class="text-muted small">N/A</span>`;
                        }
                        return actionButton;
                    }
                }
            ],
            order: [
                [2, 'asc']
            ], // Sort by Nama Siswa (kolom index 2)
            pageLength: 25,
            language: {
                processing: '<i class="fas fa-spinner fa-spin fa-3x text-primary"></i><br><span class="mt-2">Memuat data...</span>',
                lengthMenu: "Tampil _MENU_ entri",
                zeroRecords: "Tidak ditemukan data yang cocok",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                infoEmpty: "Menampilkan 0 sampai 0 dari 0 entri",
                infoFiltered: "(disaring dari _MAX_ total entri)",
                search: "Cari:",
                paginate: {
                    first: "Awal",
                    last: "Akhir",
                    next: "Berikutnya",
                    previous: "Sebelumnya"
                }
            }
        });

        // ================================================================
        // NOMOR URUT OTOMATIS
        // ================================================================
        table.on('draw.dt order.dt search.dt', function() {
            table.column(0, {
                    search: 'applied',
                    order: 'applied'
                })
                .nodes()
                .each(function(cell, i) {
                    cell.innerHTML = i + 1;
                });
        });

        // ================================================================
        // FILTER STATUS KUSTOM
        // ================================================================
        $('.status-filter').on('click', function() {
            $('.status-filter').removeClass('active');
            $(this).addClass('active');
            const status = $(this).data('status');

            $.fn.dataTable.ext.search.pop();

            if (status) {
                $.fn.dataTable.ext.search.push(
                    function(settings, data, dataIndex) {
                        const rowData = table.row(dataIndex).data();
                        return rowData.status_raw === status;
                    }
                );
            }
            table.draw();
        });

        // ================================================================
        // FUNGSI HELPER: UPDATE STATS CARDS
        // ================================================================
        function updateStatsCards(stats) {
            $('.stats-loading').removeClass('stats-loading');
            $('#stats-submissions-value').html(
                `<span class="text-success">${stats.total_submissions}</span> / ${stats.total_students}`
            );
            const notSubmitted = stats.total_students - stats.total_submissions;
            $('#stats-not-submitted-value').html(
                `<span class="text-danger">${notSubmitted}</span>`
            );
            $('#stats-average-score-value').html(
                `<span class="text-primary">${stats.average_score}</span>`
            );
        }

        // ================================================================
        // [BARU] EVENT HANDLER UNTUK TOMBOL "JALANKAN AI"
        // ================================================================
        // ================================================================
        // [BARU] EVENT HANDLER UNTUK TOMBOL "JALANKAN AI" (DENGAN POLLING)
        // ================================================================
        $('#submissions-table tbody').on('click', '.run-ai-btn', function() {
            const $button = $(this);
            const submissionId = $button.data('id');
            const $row = $button.closest('tr');
            const rowData = table.row($row).data();

            Swal.fire({
                title: 'Mulai Analisis AI?',
                html: `Anda akan menjalankan analisis AI untuk siswa: <br><b>${rowData.name}</b>. <br><br>Proses ini akan berjalan di latar belakang.`,
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Jalankan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {

                    // 1. Ubah tombol jadi loading
                    $button.prop('disabled', true).html(
                        '<i class="fas fa-spinner fa-spin"></i> Memulai...');

                    // 2. Panggil API Trigger
                    $.ajax({
                        url: AI_RUN_URL_TEMPLATE.replace('SUBMISSION_ID', submissionId),
                        method: 'POST',
                        timeout: 10000, // Timeout request awal 10 detik (kita lanjut polling kalau timeout)
                        success: function(response) {
                            // Meskipun sukses, kita tetap polling sebentar untuk memastikan status DB terupdate final
                            // atau langsung reload jika response sudah final.
                            pollForCompletion(submissionId, $button, $row);
                        },
                        error: function(xhr, status, error) {
                            // Jika timeout atau error 504 (Gateway Timeout), kita asumsikan proses jalan di background
                            if (status === 'timeout' || xhr.status === 504 || xhr.status === 524) {
                                console.warn('Request timeout, beralih ke polling...');
                                pollForCompletion(submissionId, $button, $row);
                            } else {
                                // Error beneran
                                const errorMsg = xhr.responseJSON?.message || 'Terjadi kesalahan server.';
                                Swal.fire('Gagal!', errorMsg, 'error');
                                table.row($row).draw(false); // Reset tombol
                            }
                        }
                    });
                }
            });
        });

        // ================================================================
        // FUNGSI POLLING STATUS
        // ================================================================
        function pollForCompletion(submissionId, $button, $row) {
            let attempts = 0;
            const maxAttempts = 60; // 60 x 3 detik = 3 menit maks
            const intervalTime = 3000; // 3 detik

            $button.html('<i class="fas fa-sync fa-spin"></i> Memproses... (Mohon tunggu)');

            const poller = setInterval(() => {
                attempts++;

                $.ajax({
                    url: `/api/submissions/${submissionId}/details`,
                    method: 'GET',
                    success: function(response) {
                        if (response.success && response.data) {
                            const status = response.data.status;
                            console.log(`Polling #${attempts}: Status = ${status}`);

                            // Cek apakah selesai
                            if (status === 'pending_review' || status === 'graded') {
                                clearInterval(poller);

                                Swal.fire({
                                    title: 'Selesai!',
                                    text: 'Analisis AI telah selesai.',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });

                                // Reload tabel / halaman
                                window.location.reload();
                            } else if (status === 'failed') { // Jika ada status failed (opsional)
                                clearInterval(poller);
                                Swal.fire('Gagal', 'Proses AI gagal.', 'error');
                                table.row($row).draw(false);
                            }
                            // Jika masih 'ai_processing' atau 'submitted', lanjut polling
                        }
                    },
                    error: function() {
                        // Silent error saat polling, lanjut aja
                        console.error('Polling error, retrying...');
                    }
                });

                // Hentikan jika terlalu lama
                if (attempts >= maxAttempts) {
                    clearInterval(poller);
                    Swal.fire('Waktu Habis', 'Proses memakan waktu terlalu lama. Silakan refresh halaman nanti.', 'warning');
                    table.row($row).draw(false);
                }

            }, intervalTime);
        }

    });
</script>
@endpush