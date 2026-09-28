<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->string('public_domain', 190)->nullable()->unique();
        });

        Schema::table('reservations', function (Blueprint $table): void {
            $table->decimal('base_total', 14, 2)->nullable();
            $table->decimal('extras_total', 14, 2)->default(0);
            $table->decimal('discount_total', 14, 2)->default(0);
            $table->string('promo_code', 40)->nullable();
            $table->text('pricing_breakdown')->nullable();
            $table->string('cancellation_reason', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropColumn([
                'base_total',
                'extras_total',
                'discount_total',
                'promo_code',
                'pricing_breakdown',
                'cancellation_reason',
            ]);
        });

        Schema::table('companies', function (Blueprint $table): void {
            $table->dropUnique(['public_domain']);
            $table->dropColumn('public_domain');
        });
    }
};
