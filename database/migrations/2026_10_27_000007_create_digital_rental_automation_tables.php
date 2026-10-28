<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_acceptances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->noActionOnDelete();
            $table->foreignId('rental_id')->constrained('rentals')->noActionOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->noActionOnDelete();
            $table->string('signer_name', 255);
            $table->string('signer_role', 30);
            $table->string('document_hash', 64);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->dateTime('accepted_at');
            $table->timestamps();
            $table->unique(['company_id', 'rental_id', 'signer_role'], 'rental_acceptances_unique_signer');
        });

        Schema::create('automation_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->noActionOnDelete();
            $table->string('event_key', 120);
            $table->string('recipient', 255);
            $table->string('channel', 20);
            $table->string('status', 20)->default('queued');
            $table->dateTime('sent_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'event_key', 'recipient', 'channel'], 'automation_deliveries_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_deliveries');
        Schema::dropIfExists('rental_acceptances');
    }
};
