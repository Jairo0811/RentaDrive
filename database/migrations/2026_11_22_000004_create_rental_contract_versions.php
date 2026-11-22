<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_contract_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->noActionOnDelete();
            $table->foreignId('rental_id')->constrained('rentals')->noActionOnDelete();
            $table->unsignedSmallInteger('version');
            $table->string('reason', 40);
            $table->char('document_hash', 64);
            $table->text('snapshot');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['rental_id', 'version'], 'rental_contract_versions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_contract_versions');
    }
};
