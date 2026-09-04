<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('legal_name', 160)->nullable();
            $table->string('rnc', 20)->nullable()->index();
            $table->string('slug', 120)->unique();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('currency', 3)->default('DOP');
            $table->string('timezone', 80)->default('America/Santo_Domingo');
            $table->string('status', 20)->default('active')->index();
            $table->text('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('code', 30);
            $table->string('address')->nullable();
            $table->string('city', 80)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('company_id')
                ->nullable()
                ->constrained('companies')
                ->noActionOnDelete();

            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->noActionOnDelete();
        });

        $now = now();

        $companyId = DB::table('companies')->insertGetId([
            'name' => 'RentaDrive',
            'legal_name' => 'RentaDrive Legacy',
            'slug' => 'rentadrive-legacy',
            'currency' => 'DOP',
            'timezone' => 'America/Santo_Domingo',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $branchId = DB::table('branches')->insertGetId([
            'company_id' => $companyId,
            'name' => 'Sucursal Principal',
            'code' => 'PRINCIPAL',
            'is_primary' => true,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('users')
            ->whereNull('company_id')
            ->update([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['company_id']);
            $table->dropColumn(['branch_id', 'company_id']);
        });

        Schema::dropIfExists('branches');
        Schema::dropIfExists('companies');
    }
};
