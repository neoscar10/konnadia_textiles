<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SaveProductRoutingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'routing_tasks' => 'required|array|min:1',
            'routing_tasks.*.task_id' => 'required|exists:tasks,id',
            'routing_tasks.*.sequence_number' => 'required|numeric|min:1',
            'routing_tasks.*.standard_labor_rate' => 'required|numeric|min:0',
            'routing_tasks.*.is_final_step' => 'nullable|boolean',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $tasks = $this->input('routing_tasks', []);

            // Check exactly ONE final step is designated
            $finalCount = count(array_filter($tasks, fn($t) => !empty($t['is_final_step'])));
            if ($finalCount !== 1) {
                $validator->errors()->add('routing_tasks', 'Exactly one task must be designated as the Final Production Step.');
            }

            // Check no duplicate task_ids
            $taskIds = array_column($tasks, 'task_id');
            if (count($taskIds) !== count(array_unique($taskIds))) {
                $validator->errors()->add('routing_tasks', 'Duplicate tasks detected. Each manufacturing stage task may only appear once per product routing.');
            }
        });
    }
}
