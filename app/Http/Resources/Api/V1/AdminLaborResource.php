<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminLaborResource extends JsonResource
{
    /**
     * Transform the labor resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'mobile_number' => $this->mobile_number,
            'status' => (bool) $this->status,
            'payment_method' => $this->payment_method,
            'payment_method_label' => $this->payment_method === 'monthly_salary' ? 'Monthly Salary' : 'Job Work (Piece Rate)',
            'monthly_salary' => $this->monthly_salary !== null ? (float) $this->monthly_salary : null,
            'formatted_monthly_salary' => $this->payment_method === 'monthly_salary' && $this->monthly_salary !== null 
                ? '₹' . number_format((float) $this->monthly_salary, 2) 
                : 'N/A (Piece Rate)',
            'authorized_tasks' => $this->whenLoaded('tasks', function () {
                return $this->tasks->map(function ($task) {
                    return [
                        'id' => $task->id,
                        'name' => $task->name,
                        'code' => $task->code,
                        'is_labor_required' => (bool) $task->is_labor_required,
                    ];
                });
            }),
            'allocations_count' => $this->whenCounted('allocations', $this->allocations_count, function () {
                return $this->allocations()->count();
            }),
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}
