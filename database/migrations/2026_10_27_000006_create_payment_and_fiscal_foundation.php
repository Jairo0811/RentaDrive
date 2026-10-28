<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dateTime('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->noActionOnDelete();
            $table->string('void_reason', 255)->nullable();
        });

        Schema::create('payment_intents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->noActionOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained('reservations')->noActionOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->noActionOnDelete();
            $table->uuid('public_id')->unique();
            $table->string('kind', 20);
            $table->string('gateway', 30);
            $table->string('status', 20)->default('pending');
            $table->char('currency', 3);
            $table->decimal('amount', 14, 2);
            $table->string('provider_reference', 120)->nullable();
            $table->dateTime('captured_at')->nullable();
            $table->dateTime('refunded_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status']);
        });

        Schema::create('payment_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->noActionOnDelete();
            $table->foreignId('payment_intent_id')->constrained('payment_intents')->noActionOnDelete();
            $table->string('gateway', 30);
            $table->string('event_id', 120);
            $table->string('event_type', 30);
            $table->string('payload_hash', 64);
            $table->timestamp('processed_at');
            $table->unique(['gateway', 'event_id'], 'payment_events_gateway_event_unique');
            $table->index(['company_id', 'payment_intent_id']);
        });

        Schema::create('fiscal_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained('companies')->noActionOnDelete();
            $table->string('legal_name', 255);
            $table->string('rnc', 9);
            $table->string('address', 255)->nullable();
            $table->string('fiscal_mode', 20)->default('standard');
            $table->string('ecf_status', 30)->default('not_certified');
            $table->timestamps();
        });

        Schema::create('fiscal_drafts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->noActionOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->noActionOnDelete();
            $table->string('kind', 20);
            $table->string('status', 20)->default('draft');
            $table->string('issuer_rnc', 9);
            $table->string('buyer_document', 30)->nullable();
            $table->decimal('subtotal', 14, 2);
            $table->decimal('tax', 14, 2);
            $table->decimal('total', 14, 2);
            $table->text('snapshot');
            $table->timestamps();
            $table->unique(['company_id', 'invoice_id'], 'fiscal_drafts_company_invoice_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_drafts');
        Schema::dropIfExists('fiscal_profiles');
        Schema::dropIfExists('payment_events');
        Schema::dropIfExists('payment_intents');
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['voided_by']);
            $table->dropColumn(['voided_at', 'voided_by', 'void_reason']);
        });
    }
};
