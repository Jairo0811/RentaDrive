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
        Schema::create('booking_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('name', 120);
            $table->string('type', 20)->index();
            $table->string('pricing_mode', 20)->default('flat');
            $table->decimal('price', 14, 2);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });

        Schema::table('reservations', function (Blueprint $table): void {
            $table->string('public_token', 64)->nullable();
            $table->string('source', 20)->default('internal')->index();
            $table->decimal('subtotal', 14, 2)->nullable();
            $table->decimal('extras_total', 14, 2)->default(0);
            $table->decimal('deposit_amount', 14, 2)->default(0);
        });

        DB::statement('CREATE UNIQUE INDEX reservations_public_token_unique ON reservations (public_token) WHERE public_token IS NOT NULL');

        Schema::create('reservation_booking_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->foreignId('booking_option_id')->constrained('booking_options')->noActionOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('total', 14, 2);
            $table->timestamps();

            $table->unique(['reservation_id', 'booking_option_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_booking_options');
        Schema::dropIfExists('booking_options');

        DB::statement("IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'reservations_public_token_unique' AND object_id = OBJECT_ID('reservations')) DROP INDEX reservations_public_token_unique ON reservations");

        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropColumn([
                'public_token',
                'source',
                'subtotal',
                'extras_total',
                'deposit_amount',
            ]);
        });
    }
};
