<?php

namespace App\Http\Requests\Api\V1\Production;

use App\Models\ManufacturingProduct;
use Illuminate\Foundation\Http\FormRequest;

class RecordJobAlterationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_manufacturing_product_id' => ['required', 'integer', 'exists:manufacturing_products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $jobId = $this->route('id');
            $job = \App\Models\ProductionJob::find($jobId);

            if (!$job) {
                return;
            }

            $sourceProduct = $job->manufacturingProduct;
            $targetProduct = ManufacturingProduct::find($this->target_manufacturing_product_id);

            if ($sourceProduct && $targetProduct) {
                $sourceArea = (float)$sourceProduct->length * (float)$sourceProduct->width;
                $targetArea = (float)$targetProduct->length * (float)$targetProduct->width;

                if ($targetArea >= $sourceArea) {
                    $validator->errors()->add('target_manufacturing_product_id', "Cannot alter to a product of equal or larger area ({$targetProduct->title} area: {$targetArea} vs source {$sourceProduct->title} area: {$sourceArea}).");
                }
            }
        });
    }
}
