<?php

namespace App\Http\Controllers;

use App\Exceptions\AnthropicApiKeyMissingException;
use App\Exceptions\AnthropicServiceException;
use App\Http\Requests\AnalyseFeedbackRequest;
use App\Services\AnalyseFeedbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AnalyseFeedbackController extends Controller
{
    public function __construct(
        private readonly AnalyseFeedbackService $analyseFeedbackService
    ) {}

    public function store(AnalyseFeedbackRequest $request): JsonResponse
    {
        try {
            $result = $this->analyseFeedbackService->analyse(
                trim((string) $request->input('feedback_text'))
            );

            return response()->json($result, 200);
        } catch (AnthropicApiKeyMissingException) {
            return response()->json(
                ['message' => 'API_KEY_MISSING'],
                503
            );
        } catch (AnthropicServiceException $e) {
            Log::error('Feedback analysis service error', ['message' => $e->getMessage()]);

            return response()->json(
                ['message' => $e->getMessage()],
                502
            );
        } catch (\Throwable $e) {
            Log::error('Unexpected error in feedback analysis', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(
                ['message' => 'INTERNAL_ERROR'],
                500
            );
        }
    }
}
