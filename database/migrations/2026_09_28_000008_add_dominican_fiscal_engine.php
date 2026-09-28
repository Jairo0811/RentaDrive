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
        Schema::create('fiscal_sequences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('document_type', 3);
            $table->string('prefix', 3);
            $table->unsignedBigInteger('next_number')->default(1);
            $table->unsignedBigInteger('end_number')->nullable();
            $table->unsignedTinyInteger('sequence_length');
            $table->date('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'document_type']);
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('fiscal_document_type', 3)->nullable();
            $table->string('ncf', 20)->nullable();
            $table->string('fiscal_status', 24)->default('draft')->index();
            $table->string('fiscal_provider', 40)->nullable();
            $table->string('fiscal_reference', 160)->nullable();
            $table->decimal('tax_rate', 5, 2)->default(18);
            $table->decimal('taxable_amount', 14, 2)->default(0);
            $table->decimal('exempt_amount', 14, 2)->default(0);
            $table->text('fiscal_payload')->nullable();
            $table->text('fiscal_response')->nullable();
            $table->dateTime('fiscal_issued_at')->nullable();
        });

        if (DB::getDriverName() === 'sqlsrv') {
            DB::statement('CREATE UNIQUE INDEX invoices_company_ncf_unique ON invoices(company_id, ncf) WHERE ncf IS NOT NULL');
        } else {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->unique(['company_id', 'ncf']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropUnique('invoices_company_ncf_unique');
            $table->dropColumn([
                'fiscal_document_type',
                'ncf',
                'fiscal_status',
                'fiscal_provider',
                'fiscal_reference',
                'tax_rate',
                'taxable_amount',
                'exempt_amount',
                'fiscal_payload',
                'fiscal_response',
                'fiscal_issued_at',
            ]);
        });

        Schema::dropIfExists('fiscal_sequences');
    }
};
