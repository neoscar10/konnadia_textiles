<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('factory_supervisors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('department')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Add factory_supervisor_id to production_batches as a parallel field
        // (we keep supervisor_id intact to avoid breaking existing data,
        //  and new batches will use factory_supervisor_id)
        Schema::table('production_batches', function (Blueprint $table) {
            $table->unsignedBigInteger('factory_supervisor_id')->nullable()->after('supervisor_id');
            $table->foreign('factory_supervisor_id')->references('id')->on('factory_supervisors')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_batches', function (Blueprint $table) {
            $table->dropForeign(['factory_supervisor_id']);
            $table->dropColumn('factory_supervisor_id');
        });

        Schema::dropIfExists('factory_supervisors');
    }
};
