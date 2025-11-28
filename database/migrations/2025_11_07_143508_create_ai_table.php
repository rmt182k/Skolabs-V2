<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->string('model_name')->comment('Nama model yang mudah dibaca (e.g., GPT-4o)');
            $table->string('provider')->comment('Penyedia layanan (e.g., OpenAI, Google, Anthropic)');
            $table->string('api_identifier')->comment('String ID model untuk panggilan API (e.g., gpt-4o)');
            $table->string('api_key_env')->comment('Nama variabel .env yang menyimpan API Key (e.g., OPENAI_API_KEY)');
            $table->boolean('is_active')->default(true)->comment('Apakah model ini aktif dan bisa dipilih?');
            $table->timestamps();
        });

        Schema::create('system_ai_settings', function (Blueprint $table) {
            $table->id();
            $table->string('task_key')->unique()->comment('Kunci programatik (e.g., GRADE_ESSAY)');
            $table->string('task_name')->comment('Nama fitur yang mudah dibaca (e.g., Penilaian Esai)');
            $table->text('description')->nullable()->comment('Penjelasan untuk fitur AI ini');

            // Relasi ke model AI yang dipilih
            $table->unsignedBigInteger('ai_model_id')->nullable()->comment('Model AI yang ditugaskan untuk tugas ini');
            $table->text('prompt_template')->nullable();
            $table->foreign('ai_model_id')->references('id')->on('ai_models')->onDelete('set null');

            $table->boolean('is_enabled')->default(true)->comment('Apakah fitur AI ini diaktifkan?');
            $table->timestamps();
        });

        DB::table('ai_models')->insert([
            [
                'model_name' => 'GPT-4o (OpenAI)',
                'provider' => 'OpenAI',
                'api_identifier' => 'gpt-4o',
                'api_key_env' => 'OPENAI_API_KEY',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'model_name' => 'Gemini 1.5 Pro (Google)',
                'provider' => 'Google',
                'api_identifier' => 'gemini-1.5-pro-latest',
                'api_key_env' => 'GOOGLE_API_KEY',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'model_name' => 'GPT-3.5 Turbo (OpenAI)',
                'provider' => 'OpenAI',
                'api_identifier' => 'gpt-3.5-turbo',
                'api_key_env' => 'OPENAI_API_KEY',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);

        DB::table('system_ai_settings')->insert([
            [
                'task_key' => 'GRADE_ESSAY',
                'task_name' => 'Penilaian Esai Otomatis',
                'description' => 'AI menilai esai siswa dan memberikan skor + feedback dalam format JSON.',
                'ai_model_id' => 1,
                'is_enabled' => true,
                'prompt_template' => "Anda adalah asisten guru. Nilai jawaban esai berikut.\nSoal: {{question_text}} (Skor Maks: {{max_score}})\nKunci Jawaban/Rubrik: {{question_rubric}}\nJawaban Siswa: {{student_answer}}\n\nBerikan output HANYA dalam format JSON:\n{\n  \"ai_suggested_score\": (skor numerik),\n  \"ai_feedback\": \"(penjelasan singkat penilaian Anda)\"\n}",
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'task_key' => 'ANALYZE_COMPETENCY',
                'task_name' => 'Analisa Kompetensi Siswa',
                'description' => 'AI menganalisa jawaban siswa dan menentukan kompetensi yang dikuasai.',
                'ai_model_id' => 2,
                'is_enabled' => true,
                'prompt_template' => "Anda adalah analis kurikulum.\nSoal: {{question_text}}\nJawaban Siswa: {{student_answer}}\nDaftar Kompetensi: {{competency_list_json}}\n\nBerikan output HANYA dalam format JSON array, kompetensi mana yang DIBUKTIKAN oleh jawaban siswa:\n[\n  { \"competency_id\": (id), \"score_awarded\": (skor_untuk_kompetensi_ini), \"evaluation_note\": \"(alasan singkat)\" }\n]",
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'task_key' => 'GENERATE_RECOMMENDATION',
                'task_name' => 'Rekomendasi Belajar Personal',
                'description' => 'AI memberikan rekomendasi materi belajar berdasarkan hasil analisa kompetensi.',
                'ai_model_id' => 3,
                'is_enabled' => true,
                'prompt_template' => "Siswa ini baru saja mengerjakan tugas.\nHasil Analisis Kompetensi: {{competency_analysis_json}}\nFeedback Guru: {{teacher_feedback}}\n\nBerikan output HANYA dalam format JSON array berisi 3 rekomendasi belajar singkat (maks 10 kata per rekomendasi):\n[\n  { \"type\": \"video\", \"title\": \"(judul)\" },\n  { \"type\": \"reading\", \"title\": \"(judul)\" }\n]",
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_ai_settings');
        Schema::dropIfExists('ai_models');
    }
};
