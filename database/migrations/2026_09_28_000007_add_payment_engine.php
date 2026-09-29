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
        Schema::table('reservations', function (Blueprint $table): void {
            $table->decimal('deposit_required', 14, 2)->default(0);
            $table->decimal('deposit_paid', 14, 2)->default(0);
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->foreignId('invoice_id')->nullable()->change();
            $table->foreignId('reservation_id')->nullable()->constrained('reservations')->noActionOnDelete();
            $table->string('gateway', 40)->default('manual');
            $table->string('gateway_transaction_id', 120)->nullable();
            $table->string('currency', 3)->default('DOP');
            $table->string('status', 24)->default('completed')->index();
            $table->decimal('refunded_amount', 14, 2)->default(0);
            $table->string('idempotency_key', 160)->nullable();
        });

        Schema::create('payment_intents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->noActionOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained('reservations')->noActionOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->noActionOnDelete();
            $table->string('public_id', 64)->unique();
            $table->string('purpose', 24);
            $table->string('gateway', 40);
            $table->string('gateway_reference', 120)->nullable();
            $table->string('idempotency_key', 160);
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3);
            $table->string('status', 24)->default('pending')->index();
            $table->text('checkout_url')->nullable();
            $table->text('metadata')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'idempotency_key']);
        });

        Schema::create('payment_refunds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained('payments')->noActionOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('status', 24)->default('completed')->index();
            $table->string('reference', 120)->nullable();
            $table->string('reason', 500)->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->noActionOnDelete();
            $table->dateTime('refunded_at');
            $table->text('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->noActionOnDelete();
            $table->string('provider', 40);
            $table->string('event_id', 160);
            $table->string('event_type', 80);
            $table->string('payload_hash', 64);
            $table->text('payload');
            $table->string('status', 24)->default('received')->index();
            $table->dateTime('processed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_id']);
        });

        if (DB::getDriverName() === 'sqlsrv') {
            DB::statement('CREATE UNIQUE INDEX payments_company_idempotency_unique ON payments(company_id, idempotency_key) WHERE idempotency_key IS NOT NULL');
            DB::statement('CREATE UNIQUE INDEX payments_company_gateway_transaction_unique ON payments(company_id, gateway, gateway_transaction_id) WHERE gateway_transaction_id IS NOT NULL');
        } else {
            Schema::table('payments', function (Blueprint $table): void {
                $table->unique(['company_id', 'idempotency_key']);
                $table->unique(['company_id', 'gateway', 'gateway_transaction_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('payment_refunds');
        Schema::dropIfExists('payment_intents');

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique('payments_company_idempotency_unique');
            $table->dropUnique('payments_company_gateway_transaction_unique');
            $table->dropForeign(['reservation_id']);
            $table->dropColumn([
                'reservation_id',
                'gateway',
                'gateway_transaction_id',
                'currency',
                'status',
                'refunded_amount',
                'idempotency_key',
            ]);
            $table->foreignId('invoice_id')->nullable(false)->change();
        });

        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropColumn(['deposit_required', 'deposit_paid']);
        });
    }
};
