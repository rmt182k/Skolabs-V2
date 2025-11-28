$(function () {
    // ======================================================================
    // KONFIGURASI & SETUP
    // ======================================================================
    $.ajaxSetup({
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
    });

    const SUBMISSION_ID = $('#submission_id').val();
    const CLASS_ID = $('#class_id').val();
    const TASK_ID = $('#task_id').val();

    const API = {
        GET_SUBMISSION: `/api/submissions/${SUBMISSION_ID}/details`,
        SAVE_GRADES: `/api/submissions/${SUBMISSION_ID}/grade`
    };

    // Cache DOM Elements
    const $loading = $('#loading-state');
    const $error = $('#error-state');
    const $errorMessage = $('#error-message');
    const $form = $('#grading-form');
    const $questionsContainer = $('#questions-container');
    const $saveBtn = $('#save-grade-btn');
    const $totalScore = $('#total-score');
    const $maxScore = $('#max-score');
    const $progressBar = $('#progress-bar');

    // Cache Templates
    const templates = {
        question: $('#question-template')[0].content,
        competencyAllocation: $('#competency-allocation-template')[0].content,
        competencyItem: $('#competency-item-template')[0].content
    };

    let submissionData = null;

    // ======================================================================
    // INISIALISASI & LOAD DATA
    // ======================================================================
    function initializeApp() {
        showLoading();

        $.get(API.GET_SUBMISSION)
            .done(response => {
                if (response.success && response.data) {
                    submissionData = response.data;
                    populateSubmissionInfo(submissionData);
                    renderQuestions(submissionData.answers);
                    updateTotalScore();
                    showForm();
                } else {
                    throw new Error(response.message || 'Format data tidak valid');
                }
            })
            .fail(xhr => {
                const msg = xhr.responseJSON?.message || 'Gagal memuat data penilaian. Silakan refresh halaman.';
                showError(msg);
                console.error('❌ Error loading submission:', xhr);
            });
    }

    // ======================================================================
    // UI STATE MANAGEMENT
    // ======================================================================
    function showLoading() {
        $loading.show();
        $form.hide();
        $error.hide();
    }

    function showForm() {
        $loading.hide();
        $error.hide();
        $form.fadeIn(300);
    }

    function showError(message) {
        $loading.hide();
        $form.hide();
        $errorMessage.text(message);
        $error.fadeIn(300);
    }

    // ======================================================================
    // POPULATE SUBMISSION INFO
    // ======================================================================
    function populateSubmissionInfo(data) {
        $('#task-title').text(data.task_title);
        $('#student-name').text(data.student_name);
        $('#student-nis').text(data.student_nis || 'N/A');
        $('#submitted-at').text(data.submitted_at_formatted);

        const $statusBadge = $('#submission-status');
        $statusBadge.text(data.status_text).removeClass();

        // Dynamic badge color
        const badgeClass = {
            'graded': 'badge bg-success',
            'late': 'badge bg-warning text-dark',
            'submitted': 'badge bg-primary'
        }[data.status] || 'badge bg-secondary';

        $statusBadge.addClass(badgeClass);

        if (data.teacher_feedback) {
            $('#teacher-feedback').val(data.teacher_feedback);
        }
    }

    // ======================================================================
    // RENDER QUESTIONS
    // ======================================================================
    function renderQuestions(answers) {
        $questionsContainer.empty();
        let totalMaxScore = 0;

        answers.forEach((answer, index) => {
            totalMaxScore += answer.question_score;

            const questionFragment = document.importNode(templates.question, true);
            const $card = $(questionFragment.querySelector('.question-card'));

            // Set attributes
            $card.attr({
                'data-answer-id': answer.answer_id,
                'data-question-id': answer.question_id,
                'data-question-type': answer.question_type
            });

            // Populate content
            $card.find('.question-number').text(`Pertanyaan ${index + 1}`);
            $card.find('.question-text').text(answer.question_text);
            $card.find('.question-score-badge').text(`${answer.question_score} Poin`);
            $card.find('.max-score-span').text(answer.question_score);

            // Student answer
            const $studentAnswer = $card.find('.student-answer-content');
            if (answer.student_answer) {
                $studentAnswer.html(answer.student_answer);
            } else {
                $studentAnswer.html('<em class="text-muted"><i class="fas fa-ban me-2"></i>Siswa tidak menjawab soal ini</em>');
            }

            // Check if auto-graded
            if (answer.question_type === 'multiple_choice' ||
                (answer.question_type === 'short_answer' && answer.score_awarded !== null)) {

                $card.find('.auto-grade-info').show();
                $card.find('.answer-score-input').val(answer.score_awarded || 0).prop('readonly', false);
                $card.find('.teacher-comment-input').val(answer.teacher_comment || '');
                $card.find('.manual-grade-section').show();

            } else if (answer.question_type === 'essay' || answer.question_type === 'short_answer') {
                $card.find('.manual-grade-section').show();
                $card.find('.answer-score-input').val(answer.score_awarded || 0);
                $card.find('.teacher-comment-input').val(answer.teacher_comment || '');
            }

            // Render competencies
            if (answer.competency_allocations && answer.competency_allocations.length > 0) {
                renderCompetencies($card, answer.competency_allocations);
            }

            $questionsContainer.append($card);
        });

        $maxScore.text(totalMaxScore);
    }

    // ======================================================================
    // RENDER COMPETENCIES
    // ======================================================================
    function renderCompetencies($questionCard, allocations) {
        const $allocContainer = $questionCard.find('.competency-allocations-container');
        const allocFragment = document.importNode(templates.competencyAllocation, true);
        const $allocWrapper = $(allocFragment.querySelector('.competency-section'));
        const $compList = $allocWrapper.find('.competency-list');

        allocations.forEach(comp => {
            const compFragment = document.importNode(templates.competencyItem, true);
            const $compItem = $(compFragment.querySelector('.competency-item'));

            $compItem.attr('data-competency-id', comp.competency_id);
            $compItem.find('.competency-name').text(comp.competency_name);
            $compItem.find('.competency-max-score').text(comp.max_contribution_score);

            const $compInput = $compItem.find('.competency-score-input');
            $compInput.attr('max', comp.max_contribution_score);
            $compInput.val(comp.score_awarded || 0);

            updateCompetencyProgress($compItem, comp.score_awarded || 0, comp.max_contribution_score);

            $compList.append($compItem);
        });

        $allocContainer.append($allocWrapper);
    }

    // ======================================================================
    // UPDATE COMPETENCY PROGRESS BAR
    // ======================================================================
    function updateCompetencyProgress($item, current, max) {
        const percentage = max > 0 ? Math.round((current / max) * 100) : 0;
        const $bar = $item.find('.competency-progress-bar');

        $bar.css('width', percentage + '%')
            .attr('aria-valuenow', percentage)
            .text(percentage + '%');

        // Dynamic colors with smooth transition
        $bar.removeClass('bg-danger bg-warning bg-success bg-info');
        if (percentage === 0) {
            $bar.addClass('bg-secondary');
        } else if (percentage < 50) {
            $bar.addClass('bg-danger');
        } else if (percentage < 75) {
            $bar.addClass('bg-warning');
        } else if (percentage < 100) {
            $bar.addClass('bg-info');
        } else {
            $bar.addClass('bg-success');
        }
    }

    // ======================================================================
    // UPDATE TOTAL SCORE & PROGRESS
    // ======================================================================
    function updateTotalScore() {
        let total = 0;
        const max = parseFloat($maxScore.text()) || 1;

        $('.answer-score-input').each(function() {
            const val = parseFloat($(this).val()) || 0;
            total += val;
        });

        $totalScore.text(total.toFixed(2));

        // Update progress bar
        const percentage = (total / max) * 100;
        $progressBar.css('width', percentage + '%').attr('aria-valuenow', percentage);
    }

    // ======================================================================
    // VALIDATION
    // ======================================================================
    function validateGrades() {
        let isValid = true;
        const errors = [];

        // Clear previous validation states
        $('.is-invalid').removeClass('is-invalid');

        $('.question-card').each(function(index) {
            const $card = $(this);
            const maxScore = parseFloat($card.find('.max-score-span').text()) || 0;
            const $answerInput = $card.find('.answer-score-input');
            const answerScore = parseFloat($answerInput.val()) || 0;

            // Validate answer score
            if (answerScore > maxScore) {
                errors.push(`Soal ${index + 1}: Skor jawaban (${answerScore}) melebihi skor maksimal (${maxScore}).`);
                $answerInput.addClass('is-invalid');
                isValid = false;
            } else if (answerScore < 0) {
                errors.push(`Soal ${index + 1}: Skor tidak boleh negatif.`);
                $answerInput.addClass('is-invalid');
                isValid = false;
            }

            // Validate competency scores
            $card.find('.competency-score-input').each(function() {
                const $compInput = $(this);
                const compMax = parseFloat($compInput.attr('max')) || 0;
                const compScore = parseFloat($compInput.val()) || 0;
                const compName = $compInput.closest('.competency-item').find('.competency-name').text();

                if (compScore > compMax) {
                    errors.push(`Soal ${index + 1}, ${compName}: Skor (${compScore}) melebihi maksimal (${compMax}).`);
                    $compInput.addClass('is-invalid');
                    isValid = false;
                } else if (compScore < 0) {
                    errors.push(`Soal ${index + 1}, ${compName}: Skor tidak boleh negatif.`);
                    $compInput.addClass('is-invalid');
                    isValid = false;
                }
            });
        });

        if (!isValid) {
            Swal.fire({
                icon: 'error',
                title: 'Validasi Gagal',
                html: '<div class="text-start">' + errors.map(e => `• ${e}`).join('<br>') + '</div>',
                confirmButtonColor: '#dc3545'
            });
        }

        return isValid;
    }

    // ======================================================================
    // BUILD PAYLOAD
    // ======================================================================
    function buildGradePayload() {
        const gradesData = [];

        $('.question-card').each(function() {
            const $card = $(this);
            const answerId = $card.data('answer-id');
            const answerScore = parseFloat($card.find('.answer-score-input').val()) || 0;
            const teacherComment = $card.find('.teacher-comment-input').val().trim();

            const competencies = [];
            $card.find('.competency-score-input').each(function() {
                const $row = $(this).closest('.competency-item');
                const score = parseFloat($(this).val()) || 0;

                if (score > 0) { // Only include competencies with score
                    competencies.push({
                        competency_id: $row.data('competency-id'),
                        score_awarded: score
                    });
                }
            });

            gradesData.push({
                answer_id: answerId,
                score_awarded: answerScore,
                teacher_comment: teacherComment || null,
                competency_evaluations: competencies
            });
        });

        return {
            grades: gradesData,
            teacher_feedback: $('#teacher-feedback').val().trim() || null
        };
    }

    // ======================================================================
    // SAVE GRADES
    // ======================================================================
    function handleSaveGrades() {
        if (!validateGrades()) return;

        Swal.fire({
            title: 'Simpan Penilaian?',
            html: 'Nilai ini akan dikirim ke siswa dan tidak dapat diubah kembali.<br><strong>Pastikan semua penilaian sudah benar.</strong>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-check me-2"></i>Ya, Simpan!',
            cancelButtonText: '<i class="fas fa-times me-2"></i>Batal',
            reverseButtons: true
        }).then(result => {
            if (result.isConfirmed) {
                processSaveGrades();
            }
        });
    }

    function processSaveGrades() {
        const originalHtml = $saveBtn.html();
        $saveBtn.prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...');

        const payload = buildGradePayload();
        console.log('📤 Payload Penilaian:', JSON.stringify(payload, null, 2));

        $.ajax({
            url: API.SAVE_GRADES,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: response.message || 'Penilaian berhasil disimpan!',
                    confirmButtonColor: '#28a745',
                    allowOutsideClick: false
                }).then(() => {
                    window.location.href = `/classes/${CLASS_ID}/tasks/${TASK_ID}/submissions`;
                });
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan penilaian.';
                console.error('❌ Error saving grades:', xhr);

                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Menyimpan!',
                    text: msg,
                    confirmButtonColor: '#dc3545'
                });

                $saveBtn.prop('disabled', false).html(originalHtml);
            }
        });
    }

    // ======================================================================
    // EVENT LISTENERS
    // ======================================================================

    // Update total score when answer score changes
    $questionsContainer.on('input', '.answer-score-input', function() {
        updateTotalScore();
    });

    // Update competency progress when score changes
    $questionsContainer.on('input', '.competency-score-input', function() {
        const $row = $(this).closest('.competency-item');
        const max = parseFloat($(this).attr('max')) || 0;
        const current = parseFloat($(this).val()) || 0;
        updateCompetencyProgress($row, current, max);
    });

    // Save button click
    $saveBtn.on('click', handleSaveGrades);

    // Prevent accidental page leave
    let formChanged = false;
    $questionsContainer.on('input change', 'input, textarea', function() {
        formChanged = true;
    });

    $(window).on('beforeunload', function(e) {
        if (formChanged) {
            const message = 'Anda memiliki perubahan yang belum disimpan. Yakin ingin meninggalkan halaman?';
            e.returnValue = message;
            return message;
        }
    });

    // Mark as saved when submit success
    $saveBtn.on('click', function() {
        formChanged = false;
    });

    // ======================================================================
    // INITIALIZE APP
    // ======================================================================
    initializeApp();
});
