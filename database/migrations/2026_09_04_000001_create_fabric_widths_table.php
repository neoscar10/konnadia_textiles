<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fabric_widths', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('value', 8, 2);
            $table->string('unit')->default('Inch');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        // Seed standard initial fabric widths matching prototype
        $now = now();
        DB::table('fabric_widths')->insert([
            ['name' => '36 Inch', 'value' => 36.00, 'unit' => 'Inch', 'status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => '44 Inch', 'value' => 44.00, 'unit' => 'Inch', 'status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => '58 Inch', 'value' => 58.00, 'unit' => 'Inch', 'status' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => '60 Inch', 'value' => 60.00, 'unit' => 'Inch', 'status' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('fabric_widths');
    }
};
