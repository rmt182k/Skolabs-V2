<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\GradeService; // Panggil GradeService
use Exception;

class AIGradingController extends Controller
{
    protected $gradeService;

    // Inject GradeService
    public function __construct(GradeService $gradeService)
    {
        $this->gradeService = $gradeService;
    }

    public function getAvailableProviders()
    {
        $providers = $this->gradeService->getProviders();
        return response()->json(['success' => true, 'providers' => $providers]);
    }

    // FASE 1: AI ANALYSIS (Bulk)
    public function runAIAnalysis(Request $request, $submission_id)
    {
        try {
            $provider = $request->input('provider');
            $model = $request->input('model');

            // Panggil Service untuk proses berat
            $stats = $this->gradeService->processBulkGrading($submission_id, $provider, $model);

            $msg = "AI Selesai. {$stats['processed']} sukses, {$stats['errors']} gagal. Menunggu review guru.";
            return response()->json([
                'success' => true,
                'message' => $msg
            ]);

        } catch (Exception $e) {
            Log::error("Global AI Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // RETRY SINGLE ANSWER
    public function retrySingleAnswerAnalysis(Request $request, $submission_id, $answer_id)
    {
        try {
            $provider = $request->input('provider');
            $model = $request->input('model');

            // Panggil Service
            $data = $this->gradeService->processSingleRetry($submission_id, $answer_id, $provider, $model);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil generate ulang.',
                'data' => $data
            ]);

        } catch (Exception $e) {
            // Tangkap error rate limit khusus untuk memberikan feedback UI yang sesuai
            if (str_contains($e->getMessage(), '429') || str_contains($e->getMessage(), 'Quota')) {
                return response()->json(['success' => false, 'message' => 'Server AI sibuk (Rate Limit). Tunggu sebentar.'], 429);
            }
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // FASE 2: TEACHER GRADING
    public function saveTeacherGrading(Request $request, $submission_id)
    {
        try {
            $this->gradeService->saveTeacherReview(
                $submission_id,
                $request->input('answers', []),
                $request->input('general_feedback'),
                auth()->id()
            );

            return response()->json(['success' => true, 'message' => 'Nilai tersimpan.']);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
