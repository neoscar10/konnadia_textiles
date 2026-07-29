<?php

namespace App\Services\Manufacturing;

use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Http\JsonResponse;

class ProductionBatchService
{
    use ApiResponseTrait;

    /**
     * Check if all child jobs of a production batch are completed and update batch status.
     *
     * @param mixed $batchId
     * @return JsonResponse
     */
    public function checkAndCompleteBatch($batchId): JsonResponse
    {
        try {
            $batch = DB::transaction(function () use ($batchId) {
                $batch = ProductionBatch::with(['jobs'])->findOrFail($batchId);

                $totalJobs = $batch->jobs->count();
                $completedJobs = $batch->jobs->where('status', 'completed')->count();

                if ($totalJobs > 0 && $totalJobs === $completedJobs) {
                    $batch->update(['status' => 'Completed']);
                }

                return $batch;
            });

            return $this->successResponse(
                "Production Batch {$batch->batch_code} status evaluated.",
                ['batch' => $batch],
                200
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), ['error' => $e->getMessage()], 400);
        }
    }
}
