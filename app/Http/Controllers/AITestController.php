<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Exception;
use Illuminate\Support\Facades\Log;

class AITestController extends Controller
{
    // Kita pinjam definisi provider dari AIGradingController
    // Pastikan ini tetap sinkron jika Anda mengubah di controller lain.
    private const AI_PROVIDERS = [
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
                'gemini-2.0-flash' => 'Gemini 2.0 Flash', // Ditambahkan/Diperbarui
                'gemini-1.5-pro' => 'Gemini 1.5 Pro',
                'gemini-1.5-flash' => 'Gemini 1.5 Flash',
            ],
            'default_model' => 'gemini-2.0-flash' // Diubah sesuai cURL Anda
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
     * API: Menjalankan tes koneksi sederhana ke semua provider AI
     * GET /api/ai-test
     */
    public function testAIConnections(Request $request)
    {
        // Menggunakan prompt dari cURL Anda sebagai default
        $prompt = $request->input('prompt', 'Explain how AI works in a few words');
        $results = [];

        foreach (self::AI_PROVIDERS as $key => $config) {
            $apiKeyEnv = strtoupper($key) . '_API_KEY';
            $apiKey = env($apiKeyEnv);

            if (empty($apiKey)) {
                $results[$key] = [
                    'status' => 'SKIPPED',
                    'message' => "{$apiKeyEnv} tidak diset di .env"
                ];
                continue;
            }

            $model = $config['default_model'];
            $startTime = microtime(true);

            try {
                $response = null;
                switch ($key) {
                    case 'gemini':
                        // Gunakan API key spesifik dari cURL jika .env kosong
                        // (Meskipun .env harus diisi)
                        $testApiKey = $apiKey;
                        // Hapus komentar di bawah jika Anda ingin MENGGUNAKAN KUNCI cURL secara paksa
                        // $testApiKey = 'AIzaSyBjitK_Cskbu7Iiw8VFD4Jf5KUovAmv8N4';

                        $response = $this->callGeminiTest($testApiKey, $model, $prompt);
                        break;
                    case 'claude':
                        $response = $this->callClaudeTest($apiKey, $model, $prompt);
                        break;
                    case 'openai':
                        $response = $this->callOpenAITest($apiKey, $model, $prompt);
                        break;
                }

                $duration = round(microtime(true) - $startTime, 2);
                $results[$key] = [
                    'status' => 'SUCCESS',
                    'model' => $model,
                    'duration_ms' => $duration * 1000,
                    'response' => $response
                ];

            } catch (Exception $e) {
                $duration = round(microtime(true) - $startTime, 2);
                $results[$key] = [
                    'status' => 'FAILED',
                    'model' => $model,
                    'duration_ms' => $duration * 1000,
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json($results);
    }

    // Panggilan tes sederhana untuk GEMINI (DIUBAH SESUAI cURL)
    private function callGeminiTest($apiKey, $model, $prompt)
    {
        // URL sekarang TIDAK mengandung API key
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $response = Http::timeout(30)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'X-goog-api-key' => $apiKey // Mengirim key via header, sesuai cURL
            ])
            ->post($url, [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['maxOutputTokens' => 100]
            ]);

        if ($response->successful()) {
            return $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? 'No text response';
        }

        // Jika gagal, lempar error dengan status (misal 401)
        throw new Exception("API request failed: {$response->status()} - {$response->body()}");
    }

    // Panggilan tes sederhana untuk CLAUDE
    private function callClaudeTest($apiKey, $model, $prompt)
    {
        $response = Http::timeout(30)->withHeaders([
            'Content-Type' => 'application/json',
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01'
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'max_tokens' => 100,
            'messages' => [['role' => 'user', 'content' => $prompt]]
        ]);

        if ($response->successful()) {
            return $response->json()['content'][0]['text'] ?? 'No text response';
        }

        throw new Exception("API request failed: {$response->status()} - {$response->body()}");
    }

    // Panggilan tes sederhana untuk OPENAI
    private function callOpenAITest($apiKey, $model, $prompt)
    {
        $response = Http::timeout(30)->withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'max_tokens' => 100,
            'messages' => [['role' => 'user', 'content' => $prompt]]
        ]);

        if ($response->successful()) {
            return $response->json()['choices'][0]['message']['content'] ?? 'No text response';
        }

        throw new Exception("API request failed: {$response->status()} - {$response->body()}");
    }
}
