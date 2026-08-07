<?php

namespace Database\Seeders;

use App\Models\RawMaterialCategory;
use Illuminate\Database\Seeder;

class RawMaterialCategorySeeder extends Seeder
{
    /**
     * Seed the standard raw material categories.
     *
     * Categories follow standard factory conventions:
     *  - CAT-FAB: Fabric (length_based units — Meters, Yards)
     *  - CAT-SUB: Subsidiary Materials (other units — Pieces, Rolls, Kgs)
     *  - CAT-STITCH: Stitching Materials (other units — Spools, Cones, Pieces)
     *  - CAT-PKG: Packaging Materials (other units — Pieces, Rolls, Boxes)
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Fabric',
                'code' => 'CAT-FAB',
                'unit_type' => 'length_based',
                'description' => 'Primary fabrics measured in length units (Meters, Yards, Feet).',
                'is_active' => true,
            ],
            [
                'name' => 'Subsidiary Materials',
                'code' => 'CAT-SUB',
                'unit_type' => 'other',
                'description' => 'Buttons, zippers, buckles, laces, linings, and other subsidiary materials.',
                'is_active' => true,
            ],
            [
                'name' => 'Stitching Materials',
                'code' => 'CAT-STITCH',
                'unit_type' => 'other',
                'description' => 'Threads, bobbins, needles, spools, and stitching consumables.',
                'is_active' => true,
            ],
            [
                'name' => 'Packaging Materials',
                'code' => 'CAT-PKG',
                'unit_type' => 'other',
                'description' => 'Poly bags, boxes, labels, hangers, tissue paper, and packaging supplies.',
                'is_active' => true,
            ],
            [
                'name' => 'General Overheads / Consumables',
                'code' => 'CAT-OHD',
                'unit_type' => 'other',
                'description' => 'Machine oil, cleaning chemicals, lubricants, maintenance items, and general overhead consumables.',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $cat) {
            RawMaterialCategory::updateOrCreate(
                ['code' => $cat['code']],
                $cat
            );
        }
    }
}
