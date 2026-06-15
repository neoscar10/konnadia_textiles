<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'mobile_number')) {
                $table->string('mobile_number', 30)->nullable()->unique()->after('email');
            }
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('password');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->cascadeOnDelete();
            }
        });

        // Safe Backfill
        $customers = DB::table('customers')->whereNull('user_id')->get();
        foreach ($customers as $customer) {
            $userId = null;
            if ($customer->email) {
                $userId = DB::table('users')->where('email', $customer->email)->value('id');
            }
            if (!$userId && $customer->mobile_number) {
                $userId = DB::table('users')->where('mobile_number', $customer->mobile_number)->value('id');
            }

            if (!$userId) {
                $email = $customer->email ?? 'customer_' . $customer->id . '@kannodiatextiles.com';
                $emailExists = DB::table('users')->where('email', $email)->exists();
                if ($emailExists) {
                    $email = 'customer_alt_' . $customer->id . '_' . time() . '@kannodiatextiles.com';
                }

                $userId = DB::table('users')->insertGetId([
                    'name' => $customer->contact_person,
                    'email' => $email,
                    'mobile_number' => $customer->mobile_number,
                    'password' => bcrypt('Password@123'),
                    'is_active' => $customer->is_active,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $roleId = DB::table('roles')->where('name', 'customer')->value('id');
                if (!$roleId) {
                    $roleId = DB::table('roles')->insertGetId([
                        'name' => 'customer',
                        'guard_name' => 'web',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    DB::table('roles')->insert([
                        'name' => 'customer',
                        'guard_name' => 'api',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => $roleId,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $userId,
                ]);
            }

            DB::table('customers')->where('id', $customer->id)->update([
                'user_id' => $userId
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'mobile_number')) {
                $table->dropColumn('mobile_number');
            }
            if (Schema::hasColumn('users', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
