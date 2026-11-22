<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_acceptances', function (Blueprint $table): void {
            // 1 = huella heredada (código, cliente, vehículo, fechas, total); 2 = instantánea canónica completa.
            $table->unsignedSmallInteger('hash_version')->default(1);
            $table->text('document_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('rental_acceptances', function (Blueprint $table): void {
            $table->dropColumn(['hash_version', 'document_snapshot']);
        });
    }
};
