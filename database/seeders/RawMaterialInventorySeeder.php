<?php

namespace Database\Seeders;

use App\Models\RawMaterialCategory;
use App\Models\RawMaterial;
use App\Models\InventoryBatch;
use App\Models\Task;
use Illuminate\Database\Seeder;

class RawMaterialInventorySeeder extends Seeder
{
    /**
     * Run the database seeds for Raw Material Management & Inventory Batches.
     */
    public function run(): void
    {
        // 1. Ensure Tasks exist
        $cutting = Task::firstOrCreate(['code' => 'TSK-001'], ['name' => 'Cutting', 'status' => true]);
        $stitching = Task::firstOrCreate(['code' => 'TSK-002'], ['name' => 'Stitching', 'status' => true]);
        $finishing = Task::firstOrCreate(['code' => 'TSK-003'], ['name' => 'Finishing', 'status' => true]);
        $qc = Task::firstOrCreate(['code' => 'TSK-004'], ['name' => 'Quality Check (QC)', 'status' => true]);
        $ironing = Task::firstOrCreate(['code' => 'TSK-005'], ['name' => 'Ironing', 'status' => true]);
        $packing = Task::firstOrCreate(['code' => 'TSK-006'], ['name' => 'Packing', 'status' => true]);

        // 2. Create Categories
        $fabricCat = RawMaterialCategory::firstOrCreate(
            ['code' => 'CAT-FAB'],
            ['name' => 'Fabric']
        );

        $subsidiaryCat = RawMaterialCategory::firstOrCreate(
            ['code' => 'CAT-SUB'],
            ['name' => 'Subsidiary Raw Material']
        );

        $packagingCat = RawMaterialCategory::firstOrCreate(
            ['code' => 'CAT-PKG'],
            ['name' => 'Packaging']
        );

        // 3. Link Categories to Tasks
        $cutting->rawMaterialCategories()->syncWithoutDetaching([$fabricCat->id]);
        $stitching->rawMaterialCategories()->syncWithoutDetaching([$subsidiaryCat->id]);
        $finishing->rawMaterialCategories()->syncWithoutDetaching([$subsidiaryCat->id]);
        $qc->rawMaterialCategories()->syncWithoutDetaching([$subsidiaryCat->id]);
        $ironing->rawMaterialCategories()->syncWithoutDetaching([$packagingCat->id]);
        $packing->rawMaterialCategories()->syncWithoutDetaching([$packagingCat->id]);

        // 4. Create Fabric Raw Materials & Batches
        $fabric = RawMaterial::firstOrCreate(
            ['code' => 'RM-FAB-001'],
            [
                'raw_material_category_id' => $fabricCat->id,
                'name' => 'Cotton Fabric 100% (Grade A)',
                'unit' => 'Meters',
            ]
        );

        InventoryBatch::firstOrCreate(
            ['batch_number' => 'BAT-FAB-2026-001'],
            [
                'raw_material_id' => $fabric->id,
                'received_quantity' => 1500.00,
                'balance_quantity' => 1200.00,
                'unit' => 'Meters',
                'unit_cost' => 180.00,
            ]
        );

        InventoryBatch::firstOrCreate(
            ['batch_number' => 'BAT-FAB-2026-002'],
            [
                'raw_material_id' => $fabric->id,
                'received_quantity' => 800.00,
                'balance_quantity' => 800.00,
                'unit' => 'Meters',
                'unit_cost' => 175.00,
            ]
        );

        // 5. Create Subsidiary Raw Materials & Batches
        $thread = RawMaterial::firstOrCreate(
            ['code' => 'RM-SUB-001'],
            [
                'raw_material_category_id' => $subsidiaryCat->id,
                'name' => 'Polyester Thread Spool 1000m',
                'unit' => 'Rolls',
            ]
        );

        InventoryBatch::firstOrCreate(
            ['batch_number' => 'BAT-THR-2026-001'],
            [
                'raw_material_id' => $thread->id,
                'received_quantity' => 500.00,
                'balance_quantity' => 480.00,
                'unit' => 'Rolls',
                'unit_cost' => 45.00,
            ]
        );

        $elastic = RawMaterial::firstOrCreate(
            ['code' => 'RM-SUB-002'],
            [
                'raw_material_category_id' => $subsidiaryCat->id,
                'name' => 'Cotton Elastic Band 25mm',
                'unit' => 'Meters',
            ]
        );

        InventoryBatch::firstOrCreate(
            ['batch_number' => 'BAT-ELAS-2026-001'],
            [
                'raw_material_id' => $elastic->id,
                'received_quantity' => 1000.00,
                'balance_quantity' => 950.00,
                'unit' => 'Meters',
                'unit_cost' => 12.00,
            ]
        );

        // 6. Create Packaging Materials & Batches
        $polyBag = RawMaterial::firstOrCreate(
            ['code' => 'RM-PKG-001'],
            [
                'raw_material_category_id' => $packagingCat->id,
                'name' => 'Poly Packing Bag 18x24',
                'unit' => 'Pieces',
            ]
        );

        InventoryBatch::firstOrCreate(
            ['batch_number' => 'BAT-PKG-2026-001'],
            [
                'raw_material_id' => $polyBag->id,
                'received_quantity' => 3000.00,
                'balance_quantity' => 2800.00,
                'unit' => 'Pieces',
                'unit_cost' => 3.50,
            ]
        );

        $carton = RawMaterial::firstOrCreate(
            ['code' => 'RM-PKG-002'],
            [
                'raw_material_category_id' => $packagingCat->id,
                'name' => 'Master Shipping Carton Box',
                'unit' => 'Pieces',
            ]
        );

        InventoryBatch::firstOrCreate(
            ['batch_number' => 'BAT-BOX-2026-001'],
            [
                'raw_material_id' => $carton->id,
                'received_quantity' => 200.00,
                'balance_quantity' => 190.00,
                'unit' => 'Pieces',
                'unit_cost' => 65.00,
            ]
        );
    }
}
