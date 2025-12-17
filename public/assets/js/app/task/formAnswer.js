$(function () {
    // ======================================================================
    // KONFIGURASI & VARIABEL GLOBAL
    // ======================================================================
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    const CLASS_ID = $('#class_id').val();
    const TASK_ID = $('#task_id').val();

    const API = {
        GET_TASK: `/api/classes/${CLASS_ID}/tasks/${TASK_ID}/student-view`,
        SUBMIT_TASK: `/api/classes/${CLASS_ID}/tasks/${TASK_ID}/submit`
    };

    // Cache DOM
    const $loading = $('#loading-state');
    const $error = $('#error-state');
    const $errorMessage = $('#error-message');
    const $form = $('#answer-form');
    const $questionContainer = $('#question-container');
    const $submitBtn = $('#submit-btn');

    // [BARU] Cache Timer DOM
    const $stickyTimerBar = $('#sticky-timer-bar');
    const $timerDisplay = $('#timer-display');
    let timerInterval = null;
    let autoSubmitTriggered = false;

    // ======================================================================
    // INISIALISASI & MEMUAT DATA
    // ======================================================================

    function initializeApp() {
        $loading.show();
        $form.hide();
        $error.hide();

        $.get(API.GET_TASK)
            .done(response => {
                if (response.success && response.data) {
                    populateTaskDetails(response.data.details);
                    renderQuestions(response.data.questions);

                    // [BARU] Mulai Timer jika ada durasi
                    if (response.data.details.duration_minutes) {
                        handleTimer(response.data);
                    }
                    $form.show();
                } else {
                    throw new Error(response.message || 'Format data tidak valid');
                }
            })
            .fail(xhr => {
                const errorMsg = xhr.responseJSON?.message || 'Gagal memuat tugas. Periksa koneksi Anda.';
                console.error('❌ Error memuat tugas:', errorMsg);
                $errorMessage.text(errorMsg);
                $error.show();
            })
            .always(() => {
                $loading.hide();
            });
    }

    function populateTaskDetails(details) {
        $('#task-title').text(details.title);
        $('#task-subject').text(details.subject_name);
        $('#task-start-time').text(details.start_time_formatted);
        $('#task-end-time').text(details.end_time_formatted);
        $('#task-description').html(details.description ? details.description.replace(/\n/g, '<br>') : '<p class="text-muted"><i>Tidak ada deskripsi.</i></p>');
        $('#task-description').html(details.description ? details.description.replace(/\n/g, '<br>') : '<p class="text-muted"><i>Tidak ada deskripsi.</i></p>');
    }

    // ======================================================================
    // [BARU] LOGIKA TIMER
    // ======================================================================

    function handleTimer(data) {
        const durationMinutes = parseInt(data.details.duration_minutes, 10);
        if (!durationMinutes || durationMinutes <= 0) return;

        // Tampilkan timer bar
        $stickyTimerBar.removeClass('d-none').addClass('d-flex');

        // Hitung waktu selesai berdasarkan started_at user
        const startedAt = new Date(data.user_started_at).getTime();
        const durationMs = durationMinutes * 60 * 1000;
        const endTime = startedAt + durationMs;

        // Sinkronisasi waktu dengan server (opsional, tapi disarankan)
        // Di sini kita pakai waktu browser dulu agar responsif

        updateTimerDisplay(endTime);

        timerInterval = setInterval(() => {
            updateTimerDisplay(endTime);
        }, 1000);
    }

    function updateTimerDisplay(endTime) {
        const now = new Date().getTime();
        const distance = endTime - now;

        if (distance < 0) {
            // Waktu Habis
            clearInterval(timerInterval);
            $timerDisplay.text("00:00:00");
            $timerDisplay.addClass('text-danger');

            if (!autoSubmitTriggered) {
                autoSubmitTriggered = true;
                handleTimeWarning('Waktu habis! Jawaban Anda sedang dikumpulkan otomatis...');
                processSubmission(true); // true = auto submit
            }
            return;
        }

        // Warning jika sisa waktu < 5 menit
        if (distance < 5 * 60 * 1000) {
            $timerDisplay.addClass('text-danger').addClass('blink-animation'); // Tambahkan CSS blink jika mau
        }

        // Format Waktu
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        const formattedTime =
            (hours < 10 ? "0" + hours : hours) + ":" +
            (minutes < 10 ? "0" + minutes : minutes) + ":" +
            (seconds < 10 ? "0" + seconds : seconds);

        $timerDisplay.text(formattedTime);
    }

    function handleTimeWarning(message) {
        Swal.fire({
            icon: 'warning',
            title: 'Perhatian',
            text: message,
            showConfirmButton: false,
            timer: 3000
        });
    }

    // ======================================================================
    // MERENDER SOAL (BUKAN BUILDER)
    // ======================================================================

    function renderQuestions(questions) {
        $questionContainer.empty();

        if (questions.length === 0) {
            $questionContainer.html('<div class="alert alert-info">Tugas ini tidak memiliki pertanyaan.</div>');
            return;
        }

        questions.forEach((question, index) => {
            const questionHtml = `
                <div class="card shadow-sm question-card" id="question-card-${question.id}" data-question-id="${question.id}">
                    <div class="question-header">
                        <div>
                            <span class="question-number">Pertanyaan ${index + 1}</span>
                        </div>
                        <span class="question-score">${question.score} Poin</span>
                    </div>
                    <div class="question-body">
                        <div class="question-text">${question.question_text}</div>

                        <div class="answer-input-container" data-question-type="${question.type}">
                            ${renderAnswerInput(question)}
                        </div>
                    </div>
                </div>
            `;
            $questionContainer.append(questionHtml);
        });
    }

    /**
     * ! INI YANG DIUBAH
     * Merender input jawaban (PG, Esai, dll.)
     */
    function renderAnswerInput(question) {
        const { type, id, options } = question;
        const inputName = `question_${id}`; // Nama unik untuk grup radio button

        if (type === 'multiple_choice') {
            if (options.length === 0) return '<p class="text-danger small"><i>Soal ini tidak memiliki opsi jawaban.</i></p>';

            // Asumsi selalu single answer (radio)

            // Gunakan .map() dengan index untuk mendapatkan huruf A, B, C
            const optionsHtml = options.map((opt, index) => {
                // Konversi index (0, 1, 2...) menjadi huruf (A, B, C...)
                const optionLetter = String.fromCharCode(65 + index); // 65 adalah kode ASCII untuk 'A'

                return `
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="${inputName}" id="option-${opt.id}" value="${opt.id}">
                    <label class="form-check-label" for="option-${opt.id}">
                        <span class="fw-bold me-2">${optionLetter}.</span>
                        ${opt.option_text}
                    </label>
                </div>
            `;
            }).join('');

            return `<div class="option-group">${optionsHtml}</div>`;

        } else if (type === 'short_answer') {
            return `
                <div class="mb-3">
                    <label for="${inputName}" class="form-label small text-muted">Jawaban Singkat:</label>
                    <input type="text" class="form-control" id="${inputName}" name="${inputName}" placeholder="Ketik jawaban Anda...">
                </div>
            `;
        } else if (type === 'essay') {
            return `
                <div class="mb-3">
                    <label for="${inputName}" class="form-label small text-muted">Jawaban Esai:</label>
                    <textarea class="form-control" id="${inputName}" name="${inputName}" rows="5" placeholder="Ketik jawaban Anda..."></textarea>
                </div>
            `;
        }
        return '';
    }

    // ======================================================================
    // PENGUMPULAN JAWABAN
    // ======================================================================

    function handleSubmitClick() {
        Swal.fire({
            title: 'Kumpulkan Jawaban?',
            text: "Apakah Anda yakin ingin mengumpulkan jawaban Anda? Anda tidak dapat mengubahnya lagi setelah dikumpulkan.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Kumpulkan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                processSubmission();
            }
        });
    }



    function processSubmission(isAuto = false) {
        // Stop timer jika ada
        if (timerInterval) clearInterval(timerInterval);

        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Mengumpulkan...');
        if (isAuto) {
            // Tutup semua input
            $form.find('input, textarea, button').prop('disabled', true);
        }

        const payload = buildSubmissionPayload();
        console.log('Payload Jawaban:', JSON.stringify(payload, null, 2));

        $.ajax({
            url: API.SUBMIT_TASK,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: function (response) {
                Swal.fire({
                    title: 'Berhasil!',
                    text: response.message,
                    icon: 'success',
                    allowOutsideClick: false,
                    confirmButtonText: 'Kembali ke Halaman Kelas'
                }).then(() => {
                    // Arahkan kembali ke halaman manage class
                    window.location.href = `/manage-classes/${CLASS_ID}`;
                });
            },
            error: function (xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Terjadi kesalahan. Silakan coba lagi.';
                Swal.fire({
                    title: 'Gagal!',
                    text: errorMsg,
                    icon: 'error',
                });
                $submitBtn.prop('disabled', false).html('<i class="fas fa-check-circle me-2"></i>Kumpulkan Jawaban');
            }
        });
    }

    // ⭐ HANYA BAGIAN INI YANG PERLU DIUBAH

    function buildSubmissionPayload() {
        const answers = [];

        $('.question-card').each(function () {
            const $card = $(this);
            const questionId = $card.data('question-id');
            const type = $card.find('.answer-input-container').data('question-type');

            // Struktur payload baru
            let answerData = {
                question_id: questionId,
                question_option_id: null, // Kirim ID opsi
                answer_text: null         // Kirim teks esai/isian
            };

            if (type === 'multiple_choice') {
                // ⭐ PERBAIKAN: Langsung ambil 'value' (yaitu option_id)
                const selectedOptionId = $card.find(`input[name="question_${questionId}"]:checked`).val();
                if (selectedOptionId) {
                    answerData.question_option_id = parseInt(selectedOptionId, 10);
                }
                // Kita tidak perlu mengirim answer_text untuk PG

            } else if (type === 'short_answer' || type === 'essay') {
                const answerText = $card.find(`input[name="question_${questionId}"], textarea[name="question_${questionId}"]`).val();
                if (answerText && answerText.trim()) {
                    answerData.answer_text = answerText.trim();
                }
            }

            answers.push(answerData);
        });

        return { answers: answers };
    }


    // ======================================================================
    // EVENT LISTENERS & MULAI APLIKASI
    // ======================================================================

    $submitBtn.on('click', handleSubmitClick);

    initializeApp();
});
