<?php

namespace Database\Seeders;

use App\Models\ManufacturingProduct;
use App\Models\Task;
use App\Models\Labor;
use Illuminate\Database\Seeder;

class ManufacturingProductSeeder extends Seeder
{
    /**
     * Run the database seeds for testing Manufacturing & Wage calculations.
     */
    public function run(): void
    {
        // 1. Ensure all standard manufacturing tasks exist
        $cutting = Task::firstOrCreate(['code' => 'TSK-001'], ['name' => 'Cutting', 'status' => true]);
        $stitching = Task::firstOrCreate(['code' => 'TSK-002'], ['name' => 'Stitching', 'status' => true]);
        $finishing = Task::firstOrCreate(['code' => 'TSK-003'], ['name' => 'Finishing', 'status' => true]);
        $qc = Task::firstOrCreate(['code' => 'TSK-004'], ['name' => 'Quality Check (QC)', 'status' => true]);
        $ironing = Task::firstOrCreate(['code' => 'TSK-005'], ['name' => 'Ironing', 'status' => true]);
        $packing = Task::firstOrCreate(['code' => 'TSK-006'], ['name' => 'Packing', 'status' => true]);

        // 2. Create sample Manufacturing Product with task-specific rates
        $product = ManufacturingProduct::firstOrCreate(
            ['code' => 'MP-BED-001'],
            [
                'name' => 'King Size Bedsheet',
                'standard_labor_rate' => 15.00, // default rate ₹15
            ]
        );

        // Attach full 6-stage routing sequence with sequence_number
        $product->tasks()->sync([
            $cutting->id => ['sequence_number' => 1, 'standard_labor_rate' => 12.50],
            $stitching->id => ['sequence_number' => 2, 'standard_labor_rate' => 20.00],
            $finishing->id => ['sequence_number' => 3, 'standard_labor_rate' => 10.00],
            $qc->id => ['sequence_number' => 4, 'standard_labor_rate' => 8.00],
            $ironing->id => ['sequence_number' => 5, 'standard_labor_rate' => 6.00],
            $packing->id => ['sequence_number' => 6, 'standard_labor_rate' => 5.00],
        ]);

        // 3. Ensure laborers exist with authorized tasks & payment methods
        $salariedLabor = Labor::firstOrCreate(
            ['code' => 'LAB-0001'],
            [
                'name' => 'Ramesh Kumar',
                'mobile_number' => '+91 9876543210',
                'payment_method' => 'monthly_salary',
                'monthly_salary' => 25000.00,
                'status' => true,
            ]
        );
        $salariedLabor->tasks()->syncWithoutDetaching([$cutting->id, $stitching->id, $finishing->id, $qc->id, $ironing->id, $packing->id]);

        $pieceRateLaborA = Labor::firstOrCreate(
            ['code' => 'LAB-0002'],
            [
                'name' => 'Suresh Verma',
                'mobile_number' => '+91 9876543211',
                'payment_method' => 'job_work',
                'monthly_salary' => null,
                'status' => true,
            ]
        );
        $pieceRateLaborA->tasks()->syncWithoutDetaching([$cutting->id, $stitching->id, $finishing->id, $qc->id, $ironing->id, $packing->id]);

        $pieceRateLaborB = Labor::firstOrCreate(
            ['code' => 'LAB-0003'],
            [
                'name' => 'Anita Devi',
                'mobile_number' => '+91 9876543212',
                'payment_method' => 'job_work',
                'monthly_salary' => null,
                'status' => true,
            ]
        );
        $pieceRateLaborB->tasks()->syncWithoutDetaching([$cutting->id, $stitching->id, $finishing->id, $qc->id, $ironing->id, $packing->id]);
    }
}
