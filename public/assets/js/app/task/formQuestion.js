$(function () {

    // ======================================================================
    // [GABUNGAN] KONFIGURASI & VARIABEL GLOBAL
    // ======================================================================

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // [GABUNGAN] Ambil ID dari input hidden
    const CLASS_ID = parseInt($('#class_id').val(), 10);
    const TASK_ID = parseInt($('#task_id').val(), 10) || null; // Akan jadi null jika 'create'

    // [GABUNGAN] Flag untuk mode
    const IS_EDIT_MODE = TASK_ID !== null;

    // [GABUNGAN] API Endpoints
    const API = {
        STORE_TASK: (classId) => `/api/classes/${classId}/tasks/store`,
        UPDATE_TASK: (classId, taskId) => `/api/classes/${classId}/tasks/${taskId}/update`,
        GET_TASK_DETAILS: (classId, taskId) => `/api/classes/${classId}/tasks/${taskId}/details`,
        // [DIHAPUS] API.SEARCH_COMPETENCIES dihapus
        // [BARU] API untuk ambil daftar mapel
        GET_SCHEDULED_SUBJECTS: (classId) => `/api/classes/${classId}/schedule`
    };

    // [GABUNGAN] Tentukan URL dan Teks Tombol berdasarkan mode
    const SAVE_API_URL = IS_EDIT_MODE ? API.UPDATE_TASK(CLASS_ID, TASK_ID) : API.STORE_TASK(CLASS_ID);
    const SAVE_METHOD = IS_EDIT_MODE ? 'PUT' : 'POST';
    const SAVE_BUTTON_TEXT_DEFAULT = IS_EDIT_MODE ? 'Update Tugas' : 'Simpan Tugas';
    const SAVE_BUTTON_TEXT_LOADING = IS_EDIT_MODE ? 'Memperbarui...' : 'Menyimpan...';

    // Cache elemen DOM
    const questionBuilder = $('#question-builder');
    const emptyState = $('#empty-state');
    const questionCounter = $('#question-counter');
    const messageArea = $('#messageArea');
    const saveBtn = $('#saveBtn');

    // Cache semua template
    const templates = {
        question: $('#question-template')[0].content,
        answerShortAnswer: $('#answer-short-answer-template')[0].content,
        answerEssay: $('#answer-essay-template')[0].content,
        answerMc: $('#answer-mc-template')[0].content,
        mcOption: $('#mc-option-template')[0].content,
        // [DIHAPUS] competencyRow dihapus
    };

    // ======================================================================
    // [GABUNGAN] INISIALISASI APLIKASI
    // ======================================================================

    function initializeApp() {
        if (!CLASS_ID || isNaN(CLASS_ID)) {
            console.error('❌ FATAL: CLASS_ID tidak ditemukan.');
            showMessage('error',
                '<b>Error Kritis:</b> ID Kelas tidak ditemukan. Halaman ini tidak dapat digunakan.');
            saveBtn.prop('disabled', true);
            return;
        }

        // Inisialisasi Select2 Tipe
        $('#type').select2({
            theme: 'bootstrap-5',
            placeholder: 'Pilih Tipe'
        });

        // [BARU & DIPERBAIKI] Inisialisasi Select2 Mata Pelajaran
        $('#subject_id').select2({
            theme: 'bootstrap-5',
            placeholder: 'Pilih Mata Pelajaran',
            ajax: {
                // [CATATAN] Pastikan URL API ini benar-benar mengarah ke API
                // yang mengembalikan JSON jadwal seperti yang Anda tunjukkan
                url: API.GET_SCHEDULED_SUBJECTS(CLASS_ID),
                dataType: 'json',
                delay: 250,
                processResults: function (response) {
                    // Perbaikan dimulai di sini
                    if (response.success && Array.isArray(response.data)) {

                        // Gunakan Map untuk menyimpan mata pelajaran unik (menghapus duplikat)
                        const uniqueSubjects = new Map();

                        response.data.forEach(item => {
                            // Pastikan subject_id ada dan belum ditambahkan
                            if (item.subject_id && item.subject_name && !uniqueSubjects.has(item.subject_id)) {
                                uniqueSubjects.set(item.subject_id, {
                                    id: item.subject_id,
                                    text: item.subject_name // Ambil subject_name
                                });
                            }
                        });

                        // Ubah Map kembali menjadi array
                        const results = Array.from(uniqueSubjects.values());

                        return {
                            results: results
                        };
                    } else {
                        console.error("Gagal memuat mata pelajaran:", response.message || "Format data salah");
                        return {
                            results: []
                        };
                    }
                    // Perbaikan selesai
                },
                cache: true
            }
        });


        attachEventListeners();
        updateUI();

        // [GABUNGAN] Logika kondisional
        if (IS_EDIT_MODE) {
            // Mode Edit: Muat data
            console.log(`✅ Halaman "Edit Tugas" diinisialisasi. Kelas: ${CLASS_ID}, Tugas: ${TASK_ID}`);
            loadTaskData();
        } else {
            // Mode Create: Set default start time
            console.log(`✅ Halaman "Buat Tugas" diinisialisasi. Kelas: ${CLASS_ID}`);
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            $('#start_time').val(now.toISOString().slice(0, 16));
            saveBtn.prop('disabled', false); // Aktifkan tombol (tidak ada yg di-load)
        }
    }

    // ======================================================================
    // [GABUNGAN] FUNGSI MEMUAT & MENGISI DATA TUGAS (Hanya untuk Mode Edit)
    // ======================================================================

    function loadTaskData() {
        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Memuat Data...');

        $.ajax({
            url: API.GET_TASK_DETAILS(CLASS_ID, TASK_ID),
            method: 'GET',
            success: function (response) {
                if (response.success && response.data) {
                    console.log("Data diterima:", response.data);
                    populateTaskDetails(response.data);
                    populateQuestions(response.data.questions || []);
                    updateUI();
                    saveBtn.prop('disabled', false).html(
                        `<i class="fas fa-save me-2"></i>${SAVE_BUTTON_TEXT_DEFAULT}`);
                } else {
                    throw new Error(response.message || 'Format data tidak sesuai.');
                }
            },
            error: function (xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Gagal memuat data tugas.';
                console.error('❌ Error memuat data:', errorMsg);
                showMessage('error',
                    `<b>Gagal Memuat Data:</b> ${errorMsg}. Halaman ini tidak dapat digunakan.`
                );
                saveBtn.html('<i class="fas fa-exclamation-triangle me-2"></i>Gagal Memuat');
            }
        });
    }

    function populateTaskDetails(data) {
        $('#title').val(data.title);
        $('#type').val(data.type).trigger('change');
        $('#total_possible_score').val(data.total_possible_score);
        $('#start_time').val(data.start_time);
        $('#end_time').val(data.end_time);
        $('#description').val(data.description);

        // [BARU] Pre-select Mata Pelajaran
        if (data.subject_id && data.subject_name) {
            // Buat Opsi baru
            const preselectedSubject = new Option(data.subject_name, data.subject_id, true, true);
            // Tambahkan ke Select2 dan trigger 'change'
            $('#subject_id').append(preselectedSubject).trigger('change');
        }
    }

    function populateQuestions(questionsData) {
        questionBuilder.empty();
        if (questionsData && questionsData.length > 0) {
            questionsData.forEach(qData => {
                addQuestion(qData);
            });
        }
    }

    // ======================================================================
    // FUNGSI MEMBANGUN FORM SOAL (MODIFIKASI UNTUK PRE-FILL)
    // ======================================================================

    function addQuestion(data = null) {
        const newQuestionFragment = document.importNode(templates.question, true);
        const questionCard = $(newQuestionFragment.querySelector('.question-card'));

        if (data) {
            questionCard.find('.question-text').val(data.question_text);
            questionCard.find('.question-score').val(data.score);
            questionCard.find('.question-type-select').val(data.type);
        }

        questionBuilder.append(questionCard);
        const selectElement = questionCard.find('.question-type-select')[0];
        renderAnswerContainer(selectElement, data);

        // [DIHAPUS] Logika untuk pre-fill competency row dihapus
    }

    function renderAnswerContainer(selectElement, data = null) {
        const questionCard = $(selectElement).closest('.question-card');
        const answerContainer = questionCard.find('.answer-container');
        const selectedType = $(selectElement).val();
        answerContainer.empty();

        const optionsData = (data && data.options) ? data.options : [];

        if (selectedType === 'short_answer') {
            const node = document.importNode(templates.answerShortAnswer, true);
            if (optionsData.length > 0) {
                $(node).find('.correct-answer-input').val(optionsData[0].option_text);
            }
            answerContainer.append(node);
        } else if (selectedType === 'essay') {
            const node = document.importNode(templates.answerEssay, true);
            if (optionsData.length > 0) {
                $(node).find('.correct-answer-textarea').val(optionsData[0].option_text);
            }
            answerContainer.append(node);
        } else if (selectedType === 'multiple_choice') {
            const node = document.importNode(templates.answerMc, true);
            const mcAnswerWrapper = $(node.firstElementChild);
            const addBtn = mcAnswerWrapper.find('.add-option-btn')[0];
            const optionsList = mcAnswerWrapper.find('.mc-options-list');
            answerContainer.append(mcAnswerWrapper);

            let allowMultiple = false;
            if (optionsData.length > 0) {
                if (optionsData.filter(o => o.is_correct).length > 1) {
                    allowMultiple = true;
                }
                optionsData.forEach(optData => {
                    addMcOption(addBtn, optData);
                });
            } else {
                addMcOption(addBtn, null);
                addMcOption(addBtn, null);
            }

            if (allowMultiple) {
                mcAnswerWrapper.find('.allow-multiple-answers-cb').prop('checked', true);
            }
            updateMcInputType(optionsList, allowMultiple);
        }
    }

    function addMcOption(button, data = null) {
        const optionsList = $(button).siblings('.mc-options-list');
        const newOptionNode = document.importNode(templates.mcOption, true);
        const newOption = $(newOptionNode.firstElementChild);
        optionsList.append(newOption);

        if (data) {
            newOption.find('.option-input').val(data.option_text);
            newOption.find('.correct-answer-selector').prop('checked', data.is_correct);
        }

        const allowMultiple = $(button).closest('.answer-container').find('.allow-multiple-answers-cb').is(
            ':checked');
        updateMcInputType(optionsList, allowMultiple);
        reorderOptions(optionsList);
    }

    // [DIHAPUS] Fungsi addCompetencyRow() telah dihapus seluruhnya.

    // ======================================================================
    // FUNGSI BANTUAN (HELPERS) & UI
    // ======================================================================

    function updateMcInputType(optionsList, allowMultiple) {
        const questionIndex = optionsList.closest('.question-card').index();
        const inputs = optionsList.find('.correct-answer-selector');
        if (allowMultiple) {
            inputs.attr('type', 'checkbox').attr('name', null);
        } else {
            const checkedInputs = inputs.filter(':checked');
            if (checkedInputs.length > 1) {
                checkedInputs.slice(1).prop('checked', false);
            }
            inputs.attr('type', 'radio').attr('name', `correct_answer_q${questionIndex}`);
        }
    }

    function updateUI() {
        const questionsCount = $('.question-card').length;
        questionCounter.text(`${questionsCount} Pertanyaan${questionsCount !== 1 ? '' : ''}`);
        emptyState.toggle(questionsCount === 0);
        $('.question-card').each((index, el) => {
            const $card = $(el);
            $card.find('.question-number').text(index + 1);
            // [DIHAPUS] updateCompetencySummary($card); dihapus
        });
    }

    function reorderOptions(optionsList) {
        const options = optionsList.find('.mc-option');
        options.each((index, el) => $(el).find('.option-label').text(String.fromCharCode(65 + index)));
        options.find('.btn-remove-option').toggle(options.length > 2);
    }

    function showMessage(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        messageArea.html(`
                        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                            ${message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `);
        window.scrollTo(0, 0);
    }

    // [DIHAPUS] Fungsi updateCompetencySummary() telah dihapus seluruhnya.

    // ======================================================================
    // VALIDASI & PAYLOAD
    // ======================================================================

    function validateForm() {
        let isValid = true;
        const errors = [];
        $('.is-invalid').removeClass('is-invalid');
        $('.select2-container.is-invalid').removeClass('is-invalid');

        if (!CLASS_ID) {
            errors.push('ID Kelas tidak ditemukan.');
            isValid = false;
        }
        if (!$('#title').val().trim()) {
            errors.push('Judul Tugas wajib diisi.');
            $('#title').addClass('is-invalid');
            isValid = false;
        }

        // [BARU] Validasi Mata Pelajaran
        if (!$('#subject_id').val()) {
            errors.push('Mata Pelajaran wajib dipilih.');
            $('#subject_id').next('.select2-container').addClass('is-invalid');
            isValid = false;
        }

        if (!$('#type').val()) {
            errors.push('Tipe Tugas wajib dipilih.');
            $('#type').next('.select2-container').addClass('is-invalid');
            isValid = false;
        }
        const startTime = $('#start_time').val();
        const endTime = $('#end_time').val();
        if (!startTime) {
            errors.push('Waktu Mulai wajib diisi.');
            $('#start_time').addClass('is-invalid');
            isValid = false;
        }
        if (!endTime) {
            errors.push('Waktu Selesai wajib diisi.');
            $('#end_time').addClass('is-invalid');
            isValid = false;
        }
        if (startTime && endTime && new Date(startTime) >= new Date(endTime)) {
            errors.push('Waktu Selesai harus setelah Waktu Mulai.');
            $('#start_time, #end_time').addClass('is-invalid');
            isValid = false;
        }
        if ($('.question-card').length === 0) {
            errors.push('Minimal harus ada 1 pertanyaan.');
            isValid = false;
        }

        $('.question-card').each(function (index) {
            const $card = $(this);
            const qText = $card.find('.question-text');
            const qScoreInput = $card.find('.question-score');
            const qScore = parseInt(qScoreInput.val(), 10) || 0;
            const qType = $card.find('.question-type-select').val();

            if (!qText.val().trim()) {
                errors.push(`Pertanyaan ${index + 1}: Teks pertanyaan wajib diisi.`);
                qText.addClass('is-invalid');
                isValid = false;
            }
            if (qScore < 1) {
                errors.push(`Pertanyaan ${index + 1}: Skor harus angka positif.`);
                qScoreInput.addClass('is-invalid');
                isValid = false;
            }

            if (qType === 'multiple_choice') {
                if ($card.find('.option-input').filter((i, el) => $(el).val().trim()).length < 2) {
                    errors.push(`Pertanyaan ${index + 1}: Minimal 2 opsi jawaban.`);
                    isValid = false;
                }
                if ($card.find('.correct-answer-selector:checked').length === 0) {
                    errors.push(`Pertanyaan ${index + 1}: Minimal 1 jawaban benar harus dipilih.`);
                    isValid = false;
                }
            } else if (qType === 'short_answer') {
                const correctAnswerInput = $card.find('.correct-answer-input');
                if (!correctAnswerInput.val().trim()) {
                    errors.push(`Pertanyaan ${index + 1}: Kunci jawaban wajib diisi.`);
                    correctAnswerInput.addClass('is-invalid');
                    isValid = false;
                }
            }

            // [DIHAPUS] Seluruh blok validasi kompetensi telah dihapus dari sini.

        });

        if (!isValid && errors.length > 0) {
            const uniqueErrors = [...new Set(errors)];
            showMessage('error', '<b>Harap perbaiki kesalahan berikut:</b><br>- ' + uniqueErrors.join(
                '<br>- '));
        }
        return isValid;
    }

    function buildPayload() {
        let totalScore = 0;
        const questionsPayload = [];

        $('.question-card').each(function (index) {
            const $card = $(this);
            const questionType = $card.find('.question-type-select').val();
            const questionScore = parseInt($card.find('.question-score').val(), 10) || 0;
            totalScore += questionScore;

            // [DIHAPUS] Logika 'competencyAllocations' dihapus

            const questionData = {
                question_text: $card.find('.question-text').val().trim(),
                type: questionType,
                score: questionScore,
                options: [],
                // [DIHAPUS] competency_allocations: competencyAllocations dihapus
            };

            if (questionType === 'multiple_choice') {
                $card.find('.mc-option').each(function () {
                    const optionText = $(this).find('.option-input').val().trim();
                    if (optionText) {
                        questionData.options.push({
                            option_text: optionText,
                            is_correct: $(this).find('.correct-answer-selector').is(
                                ':checked')
                        });
                    }
                });
            } else if (questionType === 'short_answer') {
                questionData.options.push({
                    option_text: $card.find('.correct-answer-input').val().trim(),
                    is_correct: true
                });
            } else if (questionType === 'essay') {
                const modelAnswer = $card.find('.correct-answer-textarea').val().trim();
                if (modelAnswer) {
                    questionData.options.push({
                        option_text: modelAnswer,
                        is_correct: false
                    });
                }
            }
            questionsPayload.push(questionData);
        });

        let finalTotalScore = parseInt($('#total_possible_score').val(), 10) || 0;
        if (finalTotalScore === 0) {
            finalTotalScore = totalScore;
        }

        const taskPayload = {
            title: $('#title').val().trim(),
            class_id: CLASS_ID, // Ambil dari variabel JS global

            // [BARU] Tambahkan subject_id ke payload
            subject_id: $('#subject_id').val(),

            type: $('#type').val(),
            total_possible_score: finalTotalScore,
            start_time: $('#start_time').val(),
            end_time: $('#end_time').val(),
            description: $('#description').val().trim(),
            questions: questionsPayload
        };

        return taskPayload;
    }

    // ======================================================================
    // [GABUNGAN] FUNGSI UTAMA: PENANGANAN FORM (Create & Update)
    // ======================================================================

    function handleSaveTask() {
        if (!validateForm()) return;

        const originalText = `<i class="fas fa-save me-2"></i>${SAVE_BUTTON_TEXT_DEFAULT}`;
        saveBtn.prop('disabled', true).html(
            `<i class="fas fa-spinner fa-spin me-2"></i>${SAVE_BUTTON_TEXT_LOADING}`);

        const payload = buildPayload();
        console.log(
            `Data Payload (mode: ${IS_EDIT_MODE ? 'Edit' : 'Create'}) yang akan dikirim ke ${SAVE_API_URL}`,
            JSON.stringify(payload, null, 2));

        $.ajax({
            url: SAVE_API_URL, // [GABUNGAN] URL Dinamis
            method: SAVE_METHOD,
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: function (response) {
                showMessage('success', response.message || 'Tugas berhasil disimpan!');
                setTimeout(() => {
                    // [PERBAIKAN] Arahkan kembali ke halaman manage class (sesuai request Anda sebelumnya)
                    window.location.href =
                        `/manage-classes/${CLASS_ID}`;
                }, 2000);
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    let errorMessages = '<b>Harap perbaiki kesalahan berikut:</b><br>';
                    $.each(errors, (key, value) => {
                        errorMessages += `- ${value[0]}\n`;
                    });
                    showMessage('error', errorMessages);
                } else {
                    const errorMsg = xhr.responseJSON?.message || xhr.responseText ||
                        'Terjadi kesalahan server.';
                    showMessage('error', `<b>Gagal Menyimpan:</b><br>${errorMsg}`);
                }
            },
            complete: function () {
                saveBtn.prop('disabled', false).html(originalText);
            }
        });
    }


    // ======================================================================
    // EVENT LISTENERS
    // ======================================================================

    function attachEventListeners() {
        // [GABUNGAN] Arahkan ke satu fungsi save
        saveBtn.on('click', handleSaveTask);

        $('#cancelBtn').on('click', function () {
            if (confirm('Apakah Anda yakin ingin membatalkan? Perubahan tidak akan disimpan.')) {
                // [PERBAIKAN] Arahkan kembali ke halaman manage class
                window.location.href = `/manage-classes/${CLASS_ID}`;
            }
        });

        $('#add-question-btn').on('click', () => {
            addQuestion(null); // Panggil tanpa data
            updateUI();
        });

        questionBuilder.on('change', '.question-type-select', function () {
            renderAnswerContainer(this, null); // Panggil tanpa data
        });

        questionBuilder.on('click', '.btn-remove-question', function () {
            if (confirm('Apakah Anda yakin ingin menghapus pertanyaan ini?')) {
                $(this).closest('.question-card').remove();
                updateUI();
            }
        });

        questionBuilder.on('click', '.add-option-btn', function () {
            addMcOption(this, null);
        });

        questionBuilder.on('click', '.btn-remove-option', function () {
            const option = $(this).closest('.mc-option');
            const optionsList = option.closest('.mc-options-list');
            option.remove();
            reorderOptions(optionsList);
        });

        questionBuilder.on('change', '.allow-multiple-answers-cb', function () {
            const isChecked = $(this).is(':checked');
            const optionsList = $(this).closest('.answer-container').find('.mc-options-list');
            updateMcInputType(optionsList, isChecked);
        });

        questionBuilder.on('change input', '.question-score', function () {
            let totalScore = 0;
            $('.question-score').each(function () {
                totalScore += parseInt($(this).val(), 10) || 0;
            });
            if (parseInt($('#total_possible_score').val(), 10) === 0) {
                $('#total_possible_score').val(totalScore);
            }
            // [DIHAPUS] updateCompetencySummary dihapus
        });

        // [DIHAPUS] Event listener untuk '.btn-add-competency', '.btn-remove-competency', dan '.competency-score' dihapus.
    }

    // ======================================================================
    // MULAI APLIKASI
    // ======================================================================
    initializeApp();

});
