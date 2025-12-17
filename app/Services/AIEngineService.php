<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class AIEngineService
{
    public const AI_PROVIDERS = [
        'claude' => [
            'name' => 'Anthropic Claude',
            'models' => [
                'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet (Recommended)',
                'claude-3-opus-20240229' => 'Claude 3 Opus (Most Accurate)',
                'claude-3-haiku-20240307' => 'Claude 3 Haiku (Fastest)',
            ],
            'default_model' => 'claude-3-5-sonnet-20241022'
        ],
        'gemini' => [
            'name' => 'Google Gemini',
            'models' => [
                'gemini-2.5-pro' => 'Gemini 2.5 Pro (Recommended)',
                'gemini-2.0-flash' => 'Gemini 2.0 Flash',
                'gemini-2.5-flash-lite' => 'Gemini 2.5 Flash Lite',
                'gemini-1.5-pro' => 'Gemini 1.5 Pro',
                'gemini-1.5-flash' => 'Gemini 1.5 Flash',
            ],
            'default_model' => 'gemini-2.5-flash-lite'
        ],
        'openai' => [
            'name' => 'OpenAI GPT',
            'models' => [
                'gpt-4o' => 'GPT-4o (Recommended)',
                'gpt-4o-mini' => 'GPT-4o Mini',
                'gpt-4-turbo' => 'GPT-4 Turbo',
            ],
            'default_model' => 'gpt-4o'
        ]
    ];

    /**
     * =========================================================================
     * PUBLIC METHODS (Business Logic Entrances)
     * =========================================================================
     */

    /**
     * Case 1: Grading (Menilai Jawaban Siswa - Skor & Feedback)
     */
    public function gradeAnswer($provider, $answer, $submission, $model = null)
    {
        // 1. Resolve Provider & Model (Gunakan Default jika null)
        $provider = $provider ?? env('AI_GRADING_PROVIDER', 'gemini');
        $model = $model ?? self::AI_PROVIDERS[$provider]['default_model'];

        // 2. Build Prompt Spesifik Grading
        $prompt = $this->constructGradingPrompt($answer, $submission);

        // 3. Kirim ke AI
        $aiResult = $this->executeRequest($provider, $model, $prompt);

        if (!$aiResult['success'])
            return $aiResult;

        // 4. Parse Hasil
        $parsed = $this->parseGradingResponse($aiResult['text'], $answer->max_score, $answer);
        $parsed['raw_results'] = $aiResult['raw'];

        return $parsed;
    }

    /**
     * Case 2: Report Generation (Analisis Kompetensi - Output JSON)
     */
    public function generateCompetencyReport($submission, $answers, $subjectName)
    {
        // 1. Resolve Provider & Model dari ENV (Tidak lagi hardcode)
        $provider = env('AI_GRADING_PROVIDER', 'gemini'); // Ambil dari .env

        // Pastikan provider ada di list, jika tidak fallback ke gemini
        if (!isset(self::AI_PROVIDERS[$provider])) {
            $provider = 'gemini';
        }

        // Ambil default model untuk provider tersebut
        $model = self::AI_PROVIDERS[$provider]['default_model'];

        // 2. Build Prompt Spesifik Reporting
        $prompt = $this->constructReportPrompt($submission, $answers, $subjectName);

        // 3. Kirim ke AI
        $aiResult = $this->executeRequest($provider, $model, $prompt);

        if (!$aiResult['success']) {
            Log::error("Report AI Error: " . ($aiResult['error'] ?? 'Unknown'));
            return null;
        }

        // 4. Parse JSON
        return $this->parseReportResponse($aiResult['text']);
    }

    /**
     * =========================================================================
     * CORE ENGINE (Networking Layer - Agnostic)
     * =========================================================================
     */
    private function executeRequest($provider, $model, $prompt)
    {
        switch ($provider) {
            case 'claude':
                return $this->callClaudeAPI($prompt, $model);
            case 'gemini':
                return $this->callGeminiAPI($prompt, $model);
            case 'openai':
                return $this->callOpenAIAPI($prompt, $model);
            default:
                return ['success' => false, 'error' => "Provider '{$provider}' tidak didukung"];
        }
    }

    private function callGeminiAPI($prompt, $model)
    {
        try {
            $apiKey = env('GEMINI_API_KEY');
            if (!$apiKey)
                throw new Exception('GEMINI_API_KEY missing');

            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

            $response = Http::timeout(60)
                ->withHeaders(['Content-Type' => 'application/json', 'X-goog-api-key' => $apiKey])
                ->post($url, [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    // Token lebih besar untuk report
                    'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 4096]
                ]);

            if ($response->successful()) {
                $result = $response->json();
                if (!isset($result['candidates']))
                    return ['success' => false, 'error' => 'Safety block triggered'];

                $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

                return [
                    'success' => true,
                    'text' => $text,
                    'raw' => ['provider' => 'gemini', 'model' => $model, 'raw_response' => $text]
                ];
            }
            return ['success' => false, 'error' => 'API Error: ' . $response->body()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function callClaudeAPI($prompt, $model)
    {
        try {
            $apiKey = env('ANTHROPIC_API_KEY');
            if (!$apiKey)
                throw new Exception('ANTHROPIC_API_KEY missing');

            $response = Http::timeout(60)
                ->withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json'
                ])
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => $model,
                    'max_tokens' => 4096,
                    'messages' => [['role' => 'user', 'content' => $prompt]]
                ]);

            if ($response->successful()) {
                $result = $response->json();
                $text = $result['content'][0]['text'] ?? '';
                return [
                    'success' => true,
                    'text' => $text,
                    'raw' => ['provider' => 'claude', 'model' => $model, 'raw_response' => $text]
                ];
            }
            return ['success' => false, 'error' => 'API Error: ' . $response->body()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function callOpenAIAPI($prompt, $model)
    {
        try {
            $apiKey = env('OPENAI_API_KEY');
            if (!$apiKey)
                throw new Exception('OPENAI_API_KEY missing');

            $response = Http::timeout(60)
                ->withHeaders(['Authorization' => 'Bearer ' . $apiKey, 'Content-Type' => 'application/json'])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are an expert academic assistant.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => 0.7
                ]);

            if ($response->successful()) {
                $result = $response->json();
                $text = $result['choices'][0]['message']['content'] ?? '';
                return [
                    'success' => true,
                    'text' => $text,
                    'raw' => ['provider' => 'openai', 'model' => $model, 'raw_response' => $text]
                ];
            }
            return ['success' => false, 'error' => 'API Error: ' . $response->body()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * =========================================================================
     * PROMPT BUILDERS
     * =========================================================================
     */

    private function constructGradingPrompt($answer, $submission)
    {
        $prompt = "Tugas: {$submission->task_title}\nDeskripsi: {$submission->task_description}\n";
        $prompt .= "Pertanyaan ({$answer->question_type}): {$answer->question_text}\n";
        if ($answer->explanation)
            $prompt .= "Kunci/Pembahasan: {$answer->explanation}\n";
        if ($answer->correct_answer_text)
            $prompt .= "Jawaban Benar: {$answer->correct_answer_text}\n";

        $studentAns = $answer->answer_text;
        if ($answer->question_type === 'multiple_choice' && $answer->question_option_id) {
            $studentAns = DB::table('question_options')->where('id', $answer->question_option_id)->value('option_text');
        }
        $prompt .= "Jawaban Siswa: " . ($studentAns ?? 'Tidak menjawab') . "\n\n";

        $prompt .= "Instruksi: Nilai jawaban siswa dari 0 sampai {$answer->max_score}. Berikan feedback konstruktif.\n";
        $prompt .= "Format Respon Wajib:\nSKOR: [angka]\nFEEDBACK: [text]";

        return $prompt;
    }

    private function constructReportPrompt($submission, $answers, $subjectName)
    {
        $prompt = "Anda adalah analis pendidikan ahli. Tugas Anda adalah mengevaluasi hasil ujian dan memberikan insight kompetensi.\n\n";
        $prompt .= "Mata Pelajaran: {$subjectName}\n";
        $prompt .= "Judul Tugas: {$submission->task_title}\n\n";

        $prompt .= "DETAIL JAWABAN SISWA:\n";
        foreach ($answers as $idx => $ans) {
            $status = $ans->is_correct ? "Benar" : "Salah";
            $prompt .= "Q" . ($idx + 1) . ": {$ans->question_text}\n";
            $prompt .= "Ans: " . ($ans->answer_text ?? $ans->selected_option ?? '-') . "\n";
            $prompt .= "Score: {$ans->score_awarded}/{$ans->max_score} ({$status})\n\n";
        }

        $prompt .= "INSTRUKSI OUTPUT (WAJIB JSON MURNI TANPA MARKDOWN):\n";
        $prompt .= "Analisis data di atas. Identifikasi 3-5 Kompetensi Utama. Berikan skor penguasaan (0-100) per kompetensi berdasarkan jawaban siswa.\n";
        $prompt .= "Format JSON:\n";
        $prompt .= "{\n";
        $prompt .= '  "competencies": [{ "name": "Nama Kompetensi", "description": "Deskripsi singkat", "score_percentage": 85 }],' . "\n";
        $prompt .= '  "recommendations": [{ "title": "Judul Saran", "description": "Isi saran", "priority": "high/medium/low", "type": "material" }]' . "\n";
        $prompt .= "}\n";

        return $prompt;
    }

    /**
     * =========================================================================
     * RESPONSE PARSERS
     * =========================================================================
     */

    private function parseGradingResponse($aiText, $maxScore, $answer)
    {
        preg_match('/SKOR:\s*([\d.]+)/i', $aiText, $scoreMatches);
        $score = isset($scoreMatches[1]) ? floatval($scoreMatches[1]) : 0;
        $score = min(max($score, 0), $maxScore);

        preg_match('/FEEDBACK:\s*(.+)/is', $aiText, $feedbackMatches);
        $feedback = isset($feedbackMatches[1]) ? trim($feedbackMatches[1]) : $aiText;

        $isCorrect = ($answer->question_type === 'multiple_choice') ? ($score >= $maxScore) : null;

        return [
            'success' => true,
            'score' => $score,
            'feedback' => $feedback,
            'is_correct' => $isCorrect
        ];
    }

    private function parseReportResponse($jsonText)
    {
        // Bersihkan formatting markdown jika AI menambahkan ```json
        $cleanJson = str_replace(['```json', '```'], '', $jsonText);
        $data = json_decode($cleanJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("JSON Parse Error: " . json_last_error_msg() . " | Raw: " . $jsonText);
            return null;
        }
        return $data;
    }
}
