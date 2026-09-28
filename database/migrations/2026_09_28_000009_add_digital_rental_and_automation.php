<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table): void {
            $table->text('accessories_checklist')->nullable();
            $table->text('damage_items')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('evidence_hash', 64)->nullable()->index();
            $table->dateTime('sealed_at')->nullable()->index();
        });

        Schema::create('rental_signatures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('rental_id')->constrained('rentals')->cascadeOnDelete();
            $table->string('role', 24)->default('renter');
            $table->string('signer_name', 160);
            $table->string('signer_document', 40)->nullable();
            $table->string('signature_path');
            $table->string('contract_hash', 64);
            $table->dateTime('accepted_terms_at');
            $table->dateTime('signed_at');
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent_hash', 64)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->noActionOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'rental_id', 'role']);
        });

        Schema::create('automation_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('event_key', 190);
            $table->string('channel', 20);
            $table->string('subject_type', 120);
            $table->unsignedBigInteger('subject_id');
            $table->string('recipient', 255);
            $table->string('title', 255);
            $table->text('message');
            $table->string('status', 24)->default('pending')->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->dateTime('sent_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'event_key', 'channel']);
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::table('vehicles', function (Blueprint $table): void {
            $table->date('insurance_expires_at')->nullable()->index();
            $table->date('registration_expires_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropColumn(['insurance_expires_at', 'registration_expires_at']);
        });

        Schema::dropIfExists('automation_deliveries');
        Schema::dropIfExists('rental_signatures');

        Schema::table('inspections', function (Blueprint $table): void {
            $table->dropColumn([
                'accessories_checklist',
                'damage_items',
                'latitude',
                'longitude',
                'evidence_hash',
                'sealed_at',
            ]);
        });
    }
};
