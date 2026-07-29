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
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('document_type', 20)->default('cedula');
            $table->string('document_number', 30)->unique();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('email')->nullable()->index();
            $table->string('phone', 30);
            $table->date('birth_date')->nullable();
            $table->string('license_number', 50)->nullable();
            $table->date('license_expiry')->nullable();
            $table->string('address')->nullable();
            $table->string('city', 80)->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('vehicle_brands', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 80)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('vehicle_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 80)->unique();
            $table->decimal('daily_rate', 14, 2);
            $table->decimal('deposit_amount', 14, 2)->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('vehicle_models', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_brand_id')->constrained()->restrictOnDelete();
            $table->string('name', 80);
            $table->unsignedSmallInteger('year');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['vehicle_brand_id', 'name', 'year']);
        });

        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_model_id')->constrained()->restrictOnDelete();
            $table->foreignId('vehicle_category_id')->constrained()->restrictOnDelete();
            $table->string('code', 30)->unique();
            $table->string('plate', 20)->unique();
            $table->string('vin', 50)->nullable();
            $table->string('color', 40);
            $table->string('transmission', 20)->default('automatic');
            $table->string('fuel_type', 20)->default('gasoline');
            $table->unsignedTinyInteger('seats')->default(5);
            $table->unsignedInteger('mileage')->default(0);
            $table->decimal('daily_rate_override', 14, 2)->nullable();
            $table->string('status', 20)->default('available')->index();
            $table->date('acquisition_date')->nullable();
            $table->unsignedInteger('next_maintenance_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('vehicle_maintenances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('maintenance_type', 60);
            $table->dateTime('scheduled_at');
            $table->dateTime('completed_at')->nullable();
            $table->unsignedInteger('mileage')->nullable();
            $table->decimal('cost', 14, 2)->default(0);
            $table->string('provider')->nullable();
            $table->string('status', 20)->default('scheduled')->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('reservations', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('vehicle_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('start_at')->index();
            $table->dateTime('end_at')->index();
            $table->string('pickup_location')->default('Oficina principal');
            $table->string('return_location')->default('Oficina principal');
            $table->decimal('daily_rate', 14, 2);
            $table->decimal('estimated_total', 14, 2);
            $table->string('status', 20)->default('pending')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('rentals', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->dateTime('start_at');
            $table->dateTime('expected_return_at');
            $table->dateTime('returned_at')->nullable();
            $table->unsignedInteger('opening_mileage');
            $table->unsignedInteger('closing_mileage')->nullable();
            $table->decimal('fuel_out', 5, 2)->default(100);
            $table->decimal('fuel_in', 5, 2)->nullable();
            $table->decimal('daily_rate', 14, 2);
            $table->decimal('deposit_amount', 14, 2)->default(0);
            $table->decimal('subtotal', 14, 2);
            $table->decimal('fees', 14, 2)->default(0);
            $table->decimal('taxes', 14, 2)->default(0);
            $table->decimal('total', 14, 2);
            $table->string('status', 20)->default('open')->index();
            $table->text('notes')->nullable();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('inspections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rental_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->string('type', 20)->index();
            $table->dateTime('inspected_at');
            $table->unsignedInteger('mileage');
            $table->decimal('fuel_level', 5, 2);
            $table->string('body_condition', 30);
            $table->string('interior_condition', 30);
            $table->string('tires_condition', 30);
            $table->text('accessories')->nullable();
            $table->text('damages')->nullable();
            $table->text('photos')->nullable();
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['rental_id', 'type']);
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 40)->unique();
            $table->foreignId('rental_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->date('issued_at')->index();
            $table->date('due_at')->nullable();
            $table->decimal('subtotal', 14, 2);
            $table->decimal('tax', 14, 2)->default(0);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('total', 14, 2);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('balance', 14, 2);
            $table->string('status', 20)->default('pending')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->string('receipt_number', 40)->unique();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->dateTime('paid_at')->index();
            $table->string('method', 20);
            $table->string('reference', 80)->nullable();
            $table->decimal('amount', 14, 2);
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('group', 50)->default('general')->index();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string');
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 30)->index();
            $table->string('auditable_type')->index();
            $table->unsignedBigInteger('auditable_id')->index();
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['auditable_type', 'auditable_id']);
        });

        if (DB::connection()->getDriverName() === 'sqlsrv') {
            DB::statement('CREATE UNIQUE INDEX customers_license_number_unique ON customers (license_number) WHERE license_number IS NOT NULL');
            DB::statement('CREATE UNIQUE INDEX vehicles_vin_unique ON vehicles (vin) WHERE vin IS NOT NULL');
            DB::statement('CREATE UNIQUE INDEX rentals_reservation_id_unique ON rentals (reservation_id) WHERE reservation_id IS NOT NULL');
        } else {
            Schema::table('customers', fn (Blueprint $table) => $table->unique('license_number'));
            Schema::table('vehicles', fn (Blueprint $table) => $table->unique('vin'));
            Schema::table('rentals', fn (Blueprint $table) => $table->unique('reservation_id'));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('inspections');
        Schema::dropIfExists('rentals');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('vehicle_maintenances');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('vehicle_models');
        Schema::dropIfExists('vehicle_categories');
        Schema::dropIfExists('vehicle_brands');
        Schema::dropIfExists('customers');
    }
};
