<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabel Tasks (Tugas/Ujian/Kuis)
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('teacher_id')->comment('Guru yang membuat tugas');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['task', 'quiz', 'exam'])->default('task');
            $table->unsignedInteger('total_possible_score')->default(0)->comment('Total skor maksimum dari semua pertanyaan');
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->unsignedInteger('duration_minutes')->nullable()->comment('Durasi pengerjaan dalam menit (untuk quiz/exam)');
            $table->boolean('shuffle_questions')->default(false)->comment('Acak urutan soal');
            $table->boolean('shuffle_options')->default(false)->comment('Acak pilihan jawaban');
            $table->boolean('show_result_immediately')->default(true)->comment('Tampilkan hasil langsung setelah submit');
            $table->enum('status', ['draft', 'published', 'closed'])->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['class_id', 'subject_id', 'status']);
        });

        // Tabel Task Submissions (Pengumpulan Tugas Siswa)
        Schema::create('task_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('student_id');
            $table->timestamp('started_at')->nullable()->comment('Waktu mulai mengerjakan');
            $table->timestamp('submitted_at')->nullable()->comment('Waktu submit');
            $table->unsignedInteger('duration_seconds')->nullable()->comment('Durasi pengerjaan dalam detik');
            $table->decimal('final_grade', 5, 2)->nullable()->comment('Nilai akhir numerik total (0-100)');
            $table->text('teacher_feedback')->nullable()->comment('Feedback umum dari guru');
            $table->enum('status', [
                'not_started',    // Belum dikerjakan
                'in_progress',    // Sedang dikerjakan siswa
                'submitted',      // Selesai dikerjakan, MENUNGGU ANTRIAN AI
                'ai_processing',  // Sedang diproses oleh AI
                'pending_review', // Selesai oleh AI, MENUNGGU APPROVAL GURU
                'graded',         // Selesai di-approve Guru
                'late'
            ])->default('not_started');
            $table->unsignedBigInteger('graded_by')->nullable()->comment('ID guru yang menilai');
            $table->timestamp('graded_at')->nullable();
            $table->unsignedSmallInteger('rank')->nullable()->comment('Peringkat siswa di kelas');
            $table->decimal('class_average', 5, 2)->nullable()->comment('Rata-rata nilai kelas (untuk perbandingan)');
            $table->timestamps();

            $table->unique(['task_id', 'student_id']);
            $table->index(['student_id', 'status']);
            $table->index(['task_id', 'final_grade']);
        });

        // Tabel Questions (Pertanyaan/Soal)
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->text('question_text');
            $table->text('question_image')->nullable()->comment('Path gambar soal (jika ada)');
            $table->enum('type', ['multiple_choice', 'essay', 'short_answer', 'true_false', 'matching'])->comment('Tipe soal');
            $table->unsignedInteger('score')->default(10)->comment('Skor maksimum pertanyaan ini');
            $table->unsignedSmallInteger('order')->default(0)->comment('Urutan soal');
            $table->text('explanation')->nullable()->comment('Penjelasan/pembahasan jawaban');
            $table->timestamps();

            $table->index(['task_id', 'order']);
        });

        // Tabel Question Options (Pilihan Jawaban untuk Multiple Choice)
        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id');
            $table->text('option_text');
            $table->text('option_image')->nullable()->comment('Path gambar pilihan (jika ada)');
            $table->boolean('is_correct')->default(false);
            $table->unsignedSmallInteger('order')->default(0)->comment('Urutan pilihan');
            $table->timestamps();

            $table->index('question_id');
        });

        // Tabel Task Submission Answers (Jawaban Siswa)
        Schema::create('task_submission_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_submission_id');
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('question_option_id')->nullable()->comment('ID pilihan untuk multiple choice');
            $table->text('answer_text')->nullable()->comment('Jawaban text untuk essay/short answer');

            // --- KOLOM UNTUK AI (Hasil Analisis Awal) ---
            // AI HANYA MENULIS KE SINI
            $table->decimal('ai_suggested_score', 5, 2)->nullable()->comment('Skor yang disarankan oleh AI');
            $table->text('ai_feedback')->nullable()->comment('Feedback/evaluasi awal dari AI');
            $table->json('ai_raw_results')->nullable();
            $table->enum('ai_processing_status', ['pending', 'completed', 'failed'])->default('pending');

            // --- KOLOM UNTUK GURU (Keputusan Final / Approval) ---
            // INI YANG DIISI/DIEDIT OLEH GURU
            $table->decimal('score_awarded', 5, 2)->nullable()->comment('Skor FINAL yang diberikan guru');
            $table->text('teacher_comment')->nullable()->comment('Komentar FINAL dari guru');

            $table->boolean('is_correct')->nullable()->comment('Penilaian FINAL Benar/Salah dari guru');
            $table->timestamp('answered_at')->nullable()->comment('Waktu menjawab soal ini');
            $table->timestamps();

            $table->unique(['task_submission_id', 'question_id']);
            $table->index('task_submission_id');
        });

        // Tabel Competencies (Kompetensi/Kemampuan)
        Schema::create('competencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Kode kompetensi (e.g., BIO-SEL-01)');
            $table->string('name')->comment('Nama kompetensi');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('subject_id')->comment('Relasi ke mata pelajaran');
            $table->enum('level', ['basic', 'intermediate', 'advanced'])->default('basic')->comment('Level kesulitan kompetensi');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['subject_id', 'is_active']);
        });

        // Tabel Question Competency Allocation (Alokasi Kompetensi per Soal)
        Schema::create('question_competency_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('competency_id');
            $table->unsignedInteger('max_contribution_score')->comment('Skor maksimum kompetensi ini dari soal ini');
            $table->unsignedSmallInteger('weight_percentage')->default(100)->comment('Bobot persentase (jika 1 soal menguji beberapa kompetensi)');
            $table->timestamps();

            $table->unique(['question_id', 'competency_id']);
            $table->index('competency_id');
        });

        // Tabel Answer Competency Evaluations (Evaluasi Kompetensi per Jawaban)
        Schema::create('answer_competency_evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_submission_answer_id');
            $table->unsignedBigInteger('competency_id');
            $table->decimal('score_awarded', 5, 2)->comment('Skor yang diberikan untuk kompetensi ini pada jawaban tersebut');
            $table->text('evaluation_note')->nullable()->comment('Catatan evaluasi khusus kompetensi');
            $table->timestamps();

            $table->unique(['task_submission_answer_id', 'competency_id'], 'answer_competency_unique');
            $table->index('competency_id');
        });

        // Tabel Learning Recommendations (Rekomendasi Pembelajaran)
        Schema::create('learning_recommendations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_submission_id');
            $table->unsignedBigInteger('competency_id')->nullable()->comment('Kompetensi yang perlu ditingkatkan');
            $table->string('type')->comment('Jenis rekomendasi');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('url')->nullable()->comment('Link ke resource');
            $table->enum('priority', ['high', 'medium', 'low'])->default('medium');
            $table->boolean('is_completed')->default(false)->comment('Apakah siswa sudah mengakses/menyelesaikan');
            $table->timestamps();

            $table->index(['task_submission_id', 'priority']);
        });

        // Tabel Submission Statistics (Statistik Agregat untuk Optimasi Query)
        Schema::create('submission_statistics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_submission_id')->unique();
            $table->unsignedSmallInteger('total_questions')->default(0);
            $table->unsignedSmallInteger('answered_questions')->default(0);
            $table->unsignedSmallInteger('correct_answers')->default(0)->comment('Untuk soal objective');
            $table->decimal('multiple_choice_score', 5, 2)->default(0);
            $table->decimal('essay_score', 5, 2)->default(0);
            $table->decimal('short_answer_score', 5, 2)->default(0);
            $table->timestamps();

            $table->index('task_submission_id');
        });

        Schema::create('submission_competency_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('submission_statistic_id');
            $table->unsignedBigInteger('competency_id');
            $table->decimal('score', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['submission_statistic_id', 'competency_id'], 'submission_competency_unique');
        });

        // Tabel Task Class Statistics (Statistik Level Kelas - untuk perbandingan)
        Schema::create('task_class_statistics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id')->unique();
            $table->unsignedSmallInteger('total_students')->default(0);
            $table->unsignedSmallInteger('submitted_count')->default(0);
            $table->unsignedSmallInteger('graded_count')->default(0);
            $table->decimal('average_score', 5, 2)->nullable();
            $table->decimal('highest_score', 5, 2)->nullable();
            $table->decimal('lowest_score', 5, 2)->nullable();
            $table->decimal('median_score', 5, 2)->nullable();
            $table->unsignedInteger('average_duration_seconds')->nullable();
            // $table->json('score_distribution')->nullable(); // <-- DIHAPUS
            $table->timestamps();

            $table->index('task_id');
        });

        Schema::create('task_score_distributions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_class_statistic_id');
            $table->string('score_range', 20)->comment('Contoh: 0-20, 21-40, dst.');
            $table->unsignedInteger('student_count')->default(0);
            $table->timestamps();

            $table->unique(['task_class_statistic_id', 'score_range'], 'task_score_dist_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop tabel tambahan dulu
        Schema::dropIfExists('task_score_distributions');
        Schema::dropIfExists('submission_competency_scores');

        // Drop sisa tabel
        Schema::dropIfExists('task_class_statistics');
        Schema::dropIfExists('submission_statistics');
        Schema::dropIfExists('learning_recommendations');
        Schema::dropIfExists('answer_competency_evaluations');
        Schema::dropIfExists('question_competency_allocations');
        Schema::dropIfExists('competencies');
        Schema::dropIfExists('task_submission_answers');
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('task_submissions');
        Schema::dropIfExists('tasks');
    }
};
