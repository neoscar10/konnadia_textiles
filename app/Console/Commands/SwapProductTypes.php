<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SwapProductTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'catalog:swap-product-types 
                            {--dry-run : Inspect what would be changed without committing to the database}
                            {--force : Force execution without interactive confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely swap product_type between manufactured and retail for all products and category configurations.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $isForce = (bool) $this->option('force');

        $this->info($isDryRun ? '--- DRY-RUN MODE: SWAP PRODUCT & CATEGORY TYPES ---' : '--- SWAP PRODUCT & CATEGORY TYPES ---');

        // 1. Inspect Products
        $mfgProductsCount = Product::where('product_type', 'manufactured')->count();
        $retailProductsCount = Product::where('product_type', 'retail')->count();
        $otherProductsCount = Product::whereNotIn('product_type', ['manufactured', 'retail'])->orWhereNull('product_type')->count();

        // 2. Inspect Categories
        $categories = Category::whereNotNull('default_product_config')->get();
        $mfgCategoriesCount = 0;
        $retailCategoriesCount = 0;
        $categoriesToUpdate = [];

        foreach ($categories as $category) {
            $config = $category->default_product_config;
            if (is_array($config) && isset($config['product_type'])) {
                if ($config['product_type'] === 'manufactured') {
                    $mfgCategoriesCount++;
                    $config['product_type'] = 'retail';
                    $categoriesToUpdate[] = [
                        'id' => $category->id,
                        'name' => $category->name,
                        'from' => 'manufactured',
                        'to' => 'retail',
                        'new_config' => $config,
                    ];
                } elseif ($config['product_type'] === 'retail') {
                    $retailCategoriesCount++;
                    $config['product_type'] = 'manufactured';
                    $categoriesToUpdate[] = [
                        'id' => $category->id,
                        'name' => $category->name,
                        'from' => 'retail',
                        'to' => 'manufactured',
                        'new_config' => $config,
                    ];
                }
            }
        }

        // Summary Table
        $this->table(
            ['Entity', 'Current Type', 'Target Type', 'Records Count'],
            [
                ['Product', 'manufactured', 'retail', $mfgProductsCount],
                ['Product', 'retail', 'manufactured', $retailProductsCount],
                ['Product (Other/Null)', 'Unchanged', 'Unchanged', $otherProductsCount],
                ['Category Config', 'manufactured', 'retail', $mfgCategoriesCount],
                ['Category Config', 'retail', 'manufactured', $retailCategoriesCount],
            ]
        );

        $totalAffected = $mfgProductsCount + $retailProductsCount + count($categoriesToUpdate);

        if ($totalAffected === 0) {
            $this->warn('No products or category configurations found with "manufactured" or "retail" types. Nothing to swap.');
            return 0;
        }

        if ($isDryRun) {
            $this->info("DRY-RUN completed. Total records that would be swapped: {$totalAffected}");
            return 0;
        }

        // Prompt for confirmation if not forced
        if (!$isForce && !$this->confirm("Are you sure you want to swap {$totalAffected} records in the database?", false)) {
            $this->warn('Operation cancelled by user.');
            return 1;
        }

        $this->output->write('Swapping values in database... ');

        DB::transaction(function () use ($categoriesToUpdate) {
            $hasIsManufactured = Schema::hasColumn('products', 'is_manufactured');

            // Atomic SQL update for Products using CASE expression
            if ($hasIsManufactured) {
                DB::table('products')->update([
                    'product_type' => DB::raw("CASE 
                        WHEN product_type = 'manufactured' THEN 'retail' 
                        WHEN product_type = 'retail' THEN 'manufactured' 
                        ELSE product_type 
                    END"),
                    'is_manufactured' => DB::raw("CASE 
                        WHEN product_type = 'manufactured' THEN 0 
                        WHEN product_type = 'retail' THEN 1 
                        ELSE is_manufactured 
                    END"),
                ]);
            } else {
                DB::table('products')->update([
                    'product_type' => DB::raw("CASE 
                        WHEN product_type = 'manufactured' THEN 'retail' 
                        WHEN product_type = 'retail' THEN 'manufactured' 
                        ELSE product_type 
                    END"),
                ]);
            }

            // Update Categories default_product_config JSON
            foreach ($categoriesToUpdate as $item) {
                Category::where('id', $item['id'])->update([
                    'default_product_config' => json_encode($item['new_config']),
                ]);
            }
        });

        $this->info('Done!');
        $this->info("Successfully swapped {$mfgProductsCount} manufactured products to retail, {$retailProductsCount} retail products to manufactured, and " . count($categoriesToUpdate) . " category configurations.");

        return 0;
    }
}
