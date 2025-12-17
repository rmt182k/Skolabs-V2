<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\AIEngineService;
use Exception;

class GradeService
{
    protected $aiEngine;

    public function __construct(AIEngineService $aiEngine)
    {
        $this->aiEngine = $aiEngine;
    }

    /**
     * Get list of providers (Helper)
     */
    public function getProviders()
    {
        $providers = [];
        foreach (AIEngineService::AI_PROVIDERS as $key => $config) {
            $providers[] = [
                'id' => $key,
                'name' => $config['name'],
                'configured' => !empty(env(strtoupper($key) . '_API_KEY')),
                'models' => $config['models'],
                'default_model' => $config['default_model']
            ];
        }
        return $providers;
    }

    /**
     * Orchestrate Bulk Grading Process
     */
    public function processBulkGrading($submissionId, $provider, $model)
    {
        // Set limit waktu eksekusi (karena ada sleep/retry)
        set_time_limit(300);

        $submission = $this->getSubmissionData($submissionId);
        if (!$submission) {
            throw new Exception('Data submission tidak ditemukan');
        }

        // Update status awal
        DB::table('task_submissions')->where('id', $submissionId)->update(['status' => 'ai_processing']);

        $answers = $this->getAnswersForAI($submissionId);
        $processed = 0;
        $errors = 0;

        foreach ($answers as $index => $answer) {

            // Logika Smart Retry dipindahkan ke sini
            $result = $this->attemptGradingWithRetry($provider, $answer, $submission, $model);

            if ($result['success']) {
                $processed++;
            } else {
                $errors++;
            }

            // Jeda antar soal
            if ($index < count($answers) - 1) {
                sleep(2);
            }
        }

        // Finalize status
        DB::table('task_submissions')->where('id', $submissionId)->update([
            'status' => 'pending_review',
            'updated_at' => now()
        ]);

        return [
            'processed' => $processed,
            'errors' => $errors
        ];
    }

    /**
     * Orchestrate Single Retry Process
     */
    public function processSingleRetry($submissionId, $answerId, $provider, $model)
    {
        set_time_limit(10);

        $submission = $this->getSubmissionData($submissionId);
        if (!$submission)
            throw new Exception('Data submission tidak ditemukan');

        $answer = $this->getAnswersForAI($submissionId, $answerId)->first();
        if (!$answer)
            throw new Exception('Jawaban tidak ditemukan');

        // Panggil helper retry logic (coba sekali, handle 429)
        $result = $this->attemptGradingWithRetry($provider, $answer, $submission, $model, 1); // Max retry 1 kali untuk manual

        if ($result['success']) {
            // Ambil data yang baru saja disimpan untuk dikembalikan ke frontend
            return [
                'score' => $result['data']['score'],
                'feedback' => $result['data']['feedback']
            ];
        } else {
            throw new Exception($result['message'] ?? 'Gagal memproses ulang.');
        }
    }

    /**
     * Handle Manual Grading Save by Teacher
     */
    public function saveTeacherReview($submissionId, $answersData, $generalFeedback, $teacherId)
    {
        DB::beginTransaction();
        try {
            $totalScore = 0;

            foreach ($answersData as $item) {
                DB::table('task_submission_answers')
                    ->where('id', $item['id'])
                    ->where('task_submission_id', $submissionId)
                    ->update([
                        'score_awarded' => $item['score_awarded'],
                        'teacher_comment' => $item['teacher_comment'] ?? null,
                        'is_correct' => $item['is_correct'] ?? null,
                        'updated_at' => now()
                    ]);
                $totalScore += $item['score_awarded'];
            }

            DB::table('task_submissions')->where('id', $submissionId)->update([
                'final_grade' => $totalScore,
                'teacher_feedback' => $generalFeedback,
                'status' => 'graded',
                'graded_by' => $teacherId,
                'graded_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // =================================================================
    // INTERNAL HELPERS (Logic Retry & DB)
    // =================================================================

    private function attemptGradingWithRetry($provider, $answer, $submission, $model, $maxRetries = 3)
    {
        $attempt = 0;
        $success = false;
        $lastErrorMessage = '';
        $aiResult = null;

        while ($attempt < $maxRetries && !$success) {
            try {
                $attempt++;

                // Panggil AI Engine
                $aiResult = $this->aiEngine->gradeAnswer($provider, $answer, $submission, $model);

                if ($aiResult['success']) {
                    $this->saveAIResultToDB($answer->answer_id, $aiResult);
                    $success = true;
                } else {
                    throw new Exception($aiResult['error'] ?? 'AI response unsuccessful');
                }
            } catch (Exception $e) {
                $msg = $e->getMessage();
                $lastErrorMessage = $msg;

                // Handle Rate Limit
                if (str_contains($msg, '429') || str_contains($msg, 'Quota') || str_contains($msg, 'Resource has been exhausted')) {
                    Log::warning("Rate Limit ID {$answer->answer_id}. Attempt $attempt. Waiting...");
                    sleep(20 + ($attempt * 5));
                } else {
                    break; // Error fatal (bukan limit), stop retry
                }
            }
        }

        if (!$success) {
            Log::error("Failed ID {$answer->answer_id}. Error: $lastErrorMessage");
            DB::table('task_submission_answers')->where('id', $answer->answer_id)->update([
                'ai_processing_status' => 'failed',
                'ai_feedback' => 'System Error: ' . substr($lastErrorMessage, 0, 200)
            ]);
            return ['success' => false, 'message' => $lastErrorMessage];
        }

        return ['success' => true, 'data' => $aiResult];
    }

    private function saveAIResultToDB($answerId, $result)
    {
        DB::table('task_submission_answers')->where('id', $answerId)->update([
            'ai_suggested_score' => $result['score'],
            'ai_feedback' => $result['feedback'],
            'ai_raw_results' => json_encode($result['raw_results']),
            'ai_processing_status' => 'completed',
            'updated_at' => now()
        ]);
    }

    private function getSubmissionData($sid)
    {
        return DB::table('task_submissions as ts')
            ->join('tasks as t', 'ts.task_id', '=', 't.id')
            ->join('users as u', 'ts.student_id', '=', 'u.id')
            ->where('ts.id', $sid)
            ->select('ts.id', 't.title as task_title', 't.description as task_description', 'u.name as student_name')
            ->first();
    }

    private function getAnswersForAI($sid, $singleAnswerId = null)
    {
        $query = DB::table('task_submission_answers as tsa')
            ->join('questions as q', 'tsa.question_id', '=', 'q.id')
            ->leftJoin('question_options as qo', function ($join) {
                $join->on('q.id', '=', 'qo.question_id')->where('qo.is_correct', true);
            })
            ->where('tsa.task_submission_id', $sid)
            ->select(
                'tsa.id as answer_id',
                'tsa.answer_text',
                'tsa.question_option_id',
                'q.question_text',
                'q.type as question_type',
                'q.score as max_score',
                'q.explanation',
                'qo.option_text as correct_answer_text'
            );

        if ($singleAnswerId) {
            $query->where('tsa.id', $singleAnswerId);
            return $query;
        }
        return $query->get();
    }
}
