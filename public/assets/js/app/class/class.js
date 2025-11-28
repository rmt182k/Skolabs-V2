$(document).ready(function () {
    // ========================================================================
    // KONFIGURASI & VARIABEL GLOBAL
    // ========================================================================
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    const API = {
        CLASSES: '/api/classes',
        SHOW_CLASS: (id) => `/api/classes/${id}`,
        EDUCATIONAL_LEVEL: '/api/educational-levels',
        MAJORS: '/api/majors',
        ACADEMIC_YEARS: '/api/academic-years'
    };

    // ⭐ ID Jenjang yang dianggap memiliki Jurusan (misal: 3=SMA, 4=SMK)
    // Sesuaikan ini dengan ID di database educational_levels Anda
    const LEVEL_IDS_WITH_MAJORS = ['3', '4'];

    let classModal;
    let dataTable;
    let allClassesData = []; // Data mentah dari API
    let allMajorsData = []; // Data mentah semua jurusan
    let currentView = 'table'; // 'table' atau 'card'

    // ⭐ PERUBAHAN: Variabel untuk melacak status tahun ajaran
    let hasActiveAcademicYear = false;
    // Link yang Anda minta
    const academicYearUrl = '/academic-years';


    // ========================================================================
    // INISIALISASI APLIKASI
    // ========================================================================
    function initializeApp() {
        classModal = new bootstrap.Modal($('#classModal')[0]);

        Promise.all([
            fetchAllClasses(),
            loadEducationalLevelsToFilter(),
            loadMajorsToFilter(), // Ini akan menyimpan 'code' jurusan
            loadAcademicYearsToFilter() // ⭐ Ini akan mengisi var 'hasActiveAcademicYear'
        ]).then(() => {

            // ⭐ PERUBAHAN: Cek status tahun ajaran setelah semua data dimuat
            if (!hasActiveAcademicYear) {
                // Tampilkan SweetAlert seperti yang diminta
                Swal.fire({
                    title: 'Tahun Ajaran Aktif Tidak Ditemukan',
                    html: `Sistem tidak menemukan <strong>Tahun Ajaran</strong> yang sedang aktif. <br><br>Anda perlu membuatnya terlebih dahulu untuk mengelola kelas.`,
                    icon: 'warning',
                    allowOutsideClick: false, // Mencegah user menutup
                    allowEscapeKey: false,    // Mencegah user menutup
                    showCancelButton: true,
                    confirmButtonColor: '#0d6efd',
                    confirmButtonText: 'Buat Tahun Ajaran',
                    cancelButtonText: 'Nanti Saja',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Arahkan ke link yang diminta
                        window.location.href = academicYearUrl;
                    }
                });

                // Nonaktifkan tombol "Tambah Kelas"
                $('#add-class-btn')
                    .prop('disabled', true)
                    .attr('title', 'Anda harus membuat tahun ajaran aktif terlebih dahulu');
            }

            // Lanjutkan sisa inisialisasi
            setupDataTable();
            populateTableData(allClassesData);
            attachEventListeners();
            console.log('✅ Aplikasi berhasil diinisialisasi');

        }).catch(error => {
            console.error('❌ Error inisialisasi:', error);
            Swal.fire('Error', 'Gagal memuat data awal', 'error');
        });
    }

    // ========================================================================
    // FETCH DATA DARI API
    // ========================================================================

    // ... (fungsi fetchAllClasses tidak berubah) ...

    function fetchAllClasses() {
        return $.get(API.CLASSES)
            .done(response => {
                if (response.success && Array.isArray(response.data)) {
                    allClassesData = response.data;
                } else {
                    allClassesData = [];
                }
            })
            .fail(error => {
                console.error('❌ Error fetch classes:', error);
                allClassesData = [];
            });
    }

    // ... (fungsi loadEducationalLevelsToFilter tidak berubah) ...

    function loadEducationalLevelsToFilter() {
        return $.get(API.EDUCATIONAL_LEVEL)
            .done(response => {
                if (response.success && response.data) {
                    const $select = $('#filter-level');
                    $select.empty().append('<option value="">Semua Jenjang</option>');
                    response.data.forEach(level => {
                        $select.append(`<option value="${level.id}">${level.name}</option>`);
                    });
                }
            })
            .fail(error => console.error('❌ Error load levels filter:', error));
    }


    // ⭐ PERUBAHAN: Modifikasi fungsi ini untuk mengecek tahun ajaran
    function loadAcademicYearsToFilter() {
        return $.get(API.ACADEMIC_YEARS)
            .done(response => {
                if (response.success && response.data) {
                    const $select = $('#filter-academic-year');
                    $select.empty().append('<option value="">Semua Tahun Ajaran</option>');

                    if (response.data.length === 0) {
                        hasActiveAcademicYear = false; // Tidak ada data sama sekali
                    } else {
                        // Cek apakah ada 'is_active: true' di dalam data
                        hasActiveAcademicYear = response.data.some(year => year.is_active);
                    }

                    response.data.forEach(year => {
                        const activeLabel = year.is_active ? ' (Aktif)' : '';
                        $select.append(`<option value="${year.id}">${year.name}${activeLabel}</option>`);
                    });
                } else {
                    // Jika API sukses tapi data kosong
                    hasActiveAcademicYear = false;
                }
            })
            .fail(error => {
                console.error('❌ Error load academic years filter:', error);
                hasActiveAcademicYear = false; // Anggap error = tidak ada
            });
    }

    // ... (Sisa kode JavaScript Anda dari loadMajorsToFilter() ke bawah tetap sama) ...
    // ... (Pastikan untuk menyalin sisa kode Anda di sini) ...

    function loadMajorsToFilter() {
        // Asumsi /api/majors mengembalikan { id, name, code, educational_level_id }
        return $.get(API.MAJORS)
            .done(response => {
                if (response.success && response.data) {
                    allMajorsData = response.data; // Simpan data mentah (termasuk .code)
                    populateMajorFilter();
                }
            })
            .fail(error => {
                allMajorsData = [];
                console.error('❌ Error load majors filter:', error)
            });
    }

    // ========================================================================
    // FUNGSI UTILITY (Filter, Render, dsb.)
    // ========================================================================

    function populateMajorFilter(selectedLevelId = '') {
        const $select = $('#filter-major');
        const currentMajorId = $select.val();
        $select.empty().prop('disabled', false);

        const hasMajors = LEVEL_IDS_WITH_MAJORS.includes(String(selectedLevelId));

        if (selectedLevelId === '') {
            $select.append('<option value="">Semua Jurusan</option>');
            allMajorsData.forEach(major => {
                $select.append(
                    `<option value="${major.id}">${major.educational_level_name} - ${major.name}</option>`
                );
            });
        } else if (hasMajors) {
            $select.append('<option value="">Semua Jurusan</option>');
            const relevantMajors = allMajorsData.filter(m => String(m.educational_level_id) === String(selectedLevelId));
            relevantMajors.forEach(major => {
                $select.append(`<option value="${major.id}">${major.name}</option>`);
            });
        } else {
            $select.append('<option value="">Tidak Ada Jurusan</option>');
            $select.prop('disabled', true);
        }

        if (currentMajorId && $select.find(`option[value="${currentMajorId}"]`).length) {
            $select.val(currentMajorId);
        } else {
            $select.val('');
        }
    }

    // === BARU: FUNGSI PREVIEW NAMA ===
    function updateNamePreview() {
        const grade = $('#grade_level').val().trim();
        // === GANTI 'class_index' -> 'suffix' ===
        const suffix = $('#suffix').val().trim().toUpperCase();

        const $majorOption = $('#major_id option:selected');
        let majorCode = '';

        // Cek jika major dropdown visible dan punya value
        if ($('#major-group').is(':visible') && $majorOption.val()) {
            // Ambil data-code (dari loadMajorsToModal)
            majorCode = $majorOption.data('code') || '';
        }

        // Gabungkan bagian-bagian yang ada
        const parts = [grade, majorCode, suffix];
        const generatedName = parts.filter(Boolean).join(' '); // Filter (null, '', undefined)

        $('#name_preview').val(generatedName);
    }

    // ========================================================================
    // SETUP DATATABLE
    // ========================================================================
    function setupDataTable() {
        if ($.fn.DataTable.isDataTable('#classes-table')) {
            $('#classes-table').DataTable().destroy();
        }

        dataTable = $('#classes-table').DataTable({
            data: [],
            // ... (konfigurasi datatable lainnya) ...
            processing: false,
            serverSide: false,
            searching: false,
            ordering: true,
            paging: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            columns: [
                { data: null, orderable: false, width: '5%' },
                { data: 'name' },
                { data: 'educational_level_name' },
                { data: 'major_name', render: (data) => data || '-' },
                { data: 'academic_year_name' },
                { data: 'grade_level' },
                { data: 'id', orderable: false, width: '20%' }
            ],
            columnDefs: [
                {
                    targets: 0,
                    render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
                },
                {
                    targets: -1,
                    className: 'text-center',
                    render: (data, type, row) => `
                        <a href="/manage-classes/${row.id}" class="btn btn-sm btn-info" title="Kelola Kelas">
                            <i class="fas fa-cog"></i>
                        </a>
                        <button class="btn btn-sm btn-warning edit-btn" data-id="${row.id}" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}" data-name="${row.name}" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    `
                }
            ],
            language: {
                lengthMenu: "Tampilkan _MENU_ data",
                zeroRecords: "Tidak ada data yang ditemukan",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                infoEmpty: "Tidak ada data",
                infoFiltered: "(difilter dari _MAX_ total data)",
                paginate: { first: "Pertama", last: "Terakhir", next: "Selanjutnya", previous: "Sebelumnya" },
                processing: "Memproses..."
            }
        });
    }

    // ========================================================================
    // FILTER & RENDER DATA
    // ========================================================================
    function getFilteredData() {
        const searchText = $('#filter-search').val().toLowerCase().trim();
        const levelId = $('#filter-level').val();
        const majorId = $('#filter-major').is(':disabled') ? '' : $('#filter-major').val();
        const academicYearId = $('#filter-academic-year').val();

        return allClassesData.filter(item => {
            const searchMatch = searchText === '' ||
                `${item.name || ''} ${item.educational_level_name || ''} ${item.major_name || ''}`.toLowerCase().includes(searchText);

            const levelMatch = !levelId || String(item.educational_level_id) === String(levelId);
            const majorMatch = !majorId || String(item.major_id) === String(majorId);
            const yearMatch = !academicYearId || String(item.academic_year_id) === String(academicYearId);

            return searchMatch && levelMatch && majorMatch && yearMatch;
        });
    }

    function applyFilters() {
        const filteredData = getFilteredData();
        if (currentView === 'table') {
            populateTableData(filteredData);
        } else {
            renderCardView(filteredData);
        }
    }

    function populateTableData(data) {
        if (dataTable) {
            dataTable.clear().rows.add(data).draw();
        }
    }

    function renderCardView(data) {
        const $container = $('#class-cards-container').empty();
        if (data.length === 0) {
            $container.html(`<div class="col-12"><div class="alert alert-info text-center">Tidak ada data</div></div>`);
            return;
        }
        data.forEach(item => {
            const card = `
                <div class="col-xl-3 col-lg-4 col-md-6 mb-4 card-view-item">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-primary mb-3"><i class="fas fa-school me-2"></i>${item.name}</h5>
                            <div class="mb-3 flex-grow-1">
                                <p class="card-text mb-2"><i class="fas fa-graduation-cap text-secondary me-2"></i><strong>Jenjang:</strong> ${item.educational_level_name}</p>
                                <p class="card-text mb-2"><i class="fas fa-code-branch text-secondary me-2"></i><strong>Jurusan:</strong> ${item.major_name || 'Umum'}</p>
                                <p class="card-text mb-2"><i class="fas fa-calendar-alt text-secondary me-2"></i><strong>T.A:</strong> ${item.academic_year_name || '-'}</p>
                                <p class="card-text mb-2"><i class="fas fa-layer-group text-secondary me-2"></i><strong>Tingkat:</strong> ${item.grade_level}</p>
                            </div>
                            <div class="d-flex gap-2 mt-auto">
                                <a href="/manage-class/${item.id}" class="btn btn-sm btn-info flex-fill"><i class="fas fa-cog"></i> Kelola</a>
                                <button class="btn btn-sm btn-warning flex-fill edit-btn" data-id="${item.id}"><i class="fas fa-edit"></i> Edit</button>
                                <button class="btn btn-sm btn-danger flex-fill delete-btn" data-id="${item.id}" data-name="${item.name}"><i class="fas fa-trash"></i> Hapus</button>
                            </div>
                        </div>
                    </div>
                </div>`;
            $container.append(card);
        });
    }

    // ========================================================================
    // EVENT LISTENERS
    // ========================================================================
    function attachEventListeners() {
        // Toggle View
        $('#view-table-btn').on('click', function () {
            currentView = 'table';
            $('#card-view').hide();
            $('#table-view').show();
            $(this).addClass('active').siblings().removeClass('active');
            applyFilters();
            setTimeout(() => dataTable && dataTable.columns.adjust().draw(false), 100);
        });

        $('#view-card-btn').on('click', function () {
            currentView = 'card';
            $('#table-view').hide();
            $('#card-view').show();
            $(this).addClass('active').siblings().removeClass('active');
            applyFilters();
        });

        // Filter Controls
        let searchTimeout;
        $('#filter-search').on('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(applyFilters, 300);
        });
        $('#filter-level').on('change', function () {
            const selectedLevelId = $(this).val();
            populateMajorFilter(selectedLevelId);
            applyFilters();
        });
        $('#filter-major').on('change', applyFilters);
        $('#filter-academic-year').on('change', applyFilters);

        // CRUD Buttons
        $('#add-class-btn').on('click', openModalForAdd);
        $(document).on('click', '.edit-btn', function () { openModalForEdit($(this).data('id')); });
        $(document).on('click', '.delete-btn', function () { confirmDelete($(this).data('id'), $(this).data('name')); });

        // Modal Form
        $('#classForm').on('submit', handleFormSubmit);
        // Panggil preview saat field berubah
        $('#educational_level_id').on('change', function () {
            toggleMajorField($(this).val());
            updateNamePreview(); // BARU
        });
        $('#major_id').on('change', updateNamePreview); // BARU
        $('#grade_level').on('input', updateNamePreview); // BARU
        // === GANTI 'class_index' -> 'suffix' ===
        $('#suffix').on('input', updateNamePreview); // BARU

        $('#classModal').on('hidden.bs.modal', resetModalForm);
    }

    // ========================================================================
    // MODAL & FORM FUNCTIONS (LOAD DATA)
    // ========================================================================

    function loadEducationalLevelsToModal() {
        return $.get(API.EDUCATIONAL_LEVEL)
            .done(response => {
                if (response.success && response.data) {
                    const $select = $('#educational_level_id');
                    $select.empty().append('<option value="">Pilih Jenjang...</option>');
                    response.data.forEach(level => {
                        $select.append(`<option value="${level.id}">${level.name}</option>`);
                    });
                }
            });
    }

    function loadAcademicYearsToModal() {
        return $.get(API.ACADEMIC_YEARS)
            .done(response => {
                if (response.success && response.data) {
                    const $select = $('#academic_year_id');
                    $select.empty().append('<option value="">Pilih Tahun Ajaran...</option>');
                    let activeYearId = null;
                    response.data.forEach(year => {
                         if (year.is_active) activeYearId = year.id;
                         const activeLabel = year.is_active ? ' (Aktif)' : '';
                         $select.append(`<option value="${year.id}">${year.name}${activeLabel}</option>`);
                    });

                    // ⭐ PERUBAHAN: Hanya set default jika TIDAK sedang edit
                    // DAN ada tahun ajaran aktif
                    if (!$('#class_id').val() && activeYearId) {
                        $select.val(activeYearId);
                    }
                }
            });
    }

    function loadMajorsToModal() {
        // Asumsi /api/majors mengembalikan { id, name, code, educational_level_id }
        return $.get(API.MAJORS)
            .done(response => {
                if (response.success && response.data) {
                    const $select = $('#major_id');
                    $select.empty().append('<option value="">Pilih Jurusan...</option>');
                    response.data.forEach(major => {
                        $select.append(
                            // === TAMBAHKAN data-code="${major.code}" ===
                            `<option value="${major.id}" data-level-id="${major.educational_level_id}" data-code="${major.code || ''}">${major.name}</option>`
                        );
                    });
                }
            });
    }

    // ========================================================================
    // MODAL & FORM FUNCTIONS (CRUD)
    // ========================================================================

    function openModalForAdd() {
        resetModalForm();
        $('#classModalLabel').text('Tambah Kelas Baru');
        $('#class_id').val('');

        Promise.all([
            loadEducationalLevelsToModal(),
            loadMajorsToModal(),
            loadAcademicYearsToModal() // Ini akan auto-select tahun aktif
        ]).then(() => {
            classModal.show();
            updateNamePreview(); // Panggil preview setelah modal siap
        });
    }

    function openModalForEdit(classId) {
        resetModalForm();
        $('#classModalLabel').text('Edit Kelas');

        Promise.all([
            loadEducationalLevelsToModal(),
            loadMajorsToModal(),
            loadAcademicYearsToModal(), // Ini TIDAK akan auto-select
            $.get(API.SHOW_CLASS(classId))
        ]).then(([, , , classRes]) => {
            if (classRes.success && classRes.data) {
                const data = classRes.data;
                $('#class_id').val(data.id);
                $('#grade_level').val(data.grade_level);
                // === GANTI 'class_index' -> 'suffix' ===
                $('#suffix').val(data.suffix); // Ambil dari kolom suffix
                $('#educational_level_id').val(data.educational_level_id);
                $('#academic_year_id').val(data.academic_year_id);

                toggleMajorField(data.educational_level_id, () => {
                    $('#major_id').val(data.major_id || '');
                    updateNamePreview(); // Panggil preview setelah major di-set
                });
                classModal.show();
            }
        }).catch(error => {
            console.error('❌ Error load edit data:', error);
            Swal.fire('Error', 'Gagal memuat data untuk diedit', 'error');
        });
    }

    function toggleMajorField(levelId, callback) {
        const $majorGroup = $('#major-group');
        const $majorSelect = $('#major_id');

        if (LEVEL_IDS_WITH_MAJORS.includes(String(levelId))) {
            $majorGroup.slideDown(200, callback);
            $majorSelect.prop('required', true);
        } else {
            $majorSelect.val('').removeClass('is-invalid');
            $('#major_id-error').text('');
            $majorGroup.slideUp(200, callback);
            $majorSelect.prop('required', false);
        }
        // Panggil preview setiap kali major disembunyikan/ditampilkan
        updateNamePreview();
    }

    function resetModalForm() {
        $('#classForm')[0].reset();
        $('.form-control, .form-select').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        $('#name_preview').val(''); // Kosongkan preview
        toggleMajorField(null);
    }

    function handleFormSubmit(e) {
        e.preventDefault();
        $('.form-control, .form-select').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        const form = $('#classForm');
        const classId = $('#class_id').val();
        const url = classId ? API.SHOW_CLASS(classId) : API.CLASSES;
        const method = classId ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            method: method,
            data: form.serialize(),
            success: function (response) {
                classModal.hide();
                Swal.fire({
                    icon: 'success', title: 'Berhasil!',
                    text: response.message || 'Data berhasil disimpan',
                    timer: 2000, showConfirmButton: false
                });
                fetchAllClasses().then(applyFilters);
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(key => {
                        $(`#${key}`).addClass('is-invalid');
                        // === GANTI 'class_index' -> 'suffix' ===
                        $(`#${key}-error`).text(errors[key][0]);
                    });
                } else {
                    const msg = xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan data';
                    Swal.fire('Error', msg, 'error');
                }
            }
        });
    }

    function confirmDelete(classId, className) {
        Swal.fire({
            title: 'Apakah Anda yakin?',
            html: `Anda akan menghapus kelas <strong>${className}</strong>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33', cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: API.SHOW_CLASS(classId),
                    method: 'DELETE',
                    success: function (response) {
                        Swal.fire({
                            icon: 'success', title: 'Terhapus!',
                            text: response.message || 'Data berhasil dihapus',
                            timer: 2000, showConfirmButton: false
                        });
                        fetchAllClasses().then(applyFilters);
                    },
                    error: (xhr) => {
                         const msg = xhr.responseJSON?.message || 'Gagal menghapus data';
                         Swal.fire('Error', msg, 'error');
                    }
                });
            }
        });
    }

    // ========================================================================
    // START APPLICATION
    // ========================================================================
    initializeApp();
});
