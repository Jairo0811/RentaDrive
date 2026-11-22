<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('automation_deliveries', function (Blueprint $table): void {
            // Reintentos con retroceso: máx. 3 intentos por aviso (ver AutomationDispatcher).
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->dateTime('last_attempt_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('automation_deliveries', function (Blueprint $table): void {
            $table->dropColumn(['attempts', 'last_attempt_at']);
        });
    }
};
