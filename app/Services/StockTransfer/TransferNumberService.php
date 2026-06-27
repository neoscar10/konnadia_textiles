<?php

namespace App\Services\StockTransfer;

use App\Models\ProductTransfer;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TransferNumberService
{
    /**
     * Generate the next transfer number: TRF-YYYYMM-0001.
     */
    public function generate(): string
    {
        $prefix = 'TRF-' . Carbon::now()->format('Ym') . '-';

        $lastTransfer = ProductTransfer::withTrashed()
            ->where('transfer_number', 'like', "{$prefix}%")
            ->orderBy('transfer_number', 'desc')
            ->first();

        if (!$lastTransfer) {
            return $prefix . '0001';
        }

        $lastNumber = intval(Str::after($lastTransfer->transfer_number, $prefix));
        $newNumber = $lastNumber + 1;

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
