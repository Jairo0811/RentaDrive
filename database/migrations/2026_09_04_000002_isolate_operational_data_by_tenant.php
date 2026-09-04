<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

return new class extends Migration
{
    /** @var list<string> */
    private array $tenantTables = [
        'customers',
        'vehicle_brands',
        'vehicle_categories',
        'vehicle_models',
        'vehicles',
        'vehicle_maintenances',
        'reservations',
        'rentals',
        'inspections',
        'invoices',
        'payments',
        'settings',
        'audit_logs',
    ];

    /** @var list<string> */
    private array $branchTables = [
        'vehicles',
        'reservations',
        'rentals',
    ];

    public function up(): void
    {
        foreach ($this->tenantTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('company_id')
                    ->nullable()
                    ->constrained('companies')
                    ->noActionOnDelete();
            });
        }

        foreach ($this->branchTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('branch_id')
                    ->nullable()
                    ->constrained('branches')
                    ->noActionOnDelete();
            });
        }

        $companyId = DB::table('companies')
            ->where('slug', 'rentadrive-legacy')
            ->value('id');

        if ($companyId === null) {
            throw new RuntimeException('No se encontró la empresa legacy necesaria para migrar los datos de RentaDrive v1.');
        }

        $branchId = DB::table('branches')
            ->where('company_id', $companyId)
            ->where('is_primary', true)
            ->value('id');

        foreach ($this->tenantTables as $tableName) {
            DB::table($tableName)
                ->whereNull('company_id')
                ->update(['company_id' => $companyId]);
        }

        if ($branchId !== null) {
            foreach ($this->branchTables as $tableName) {
                DB::table($tableName)
                    ->whereNull('branch_id')
                    ->update(['branch_id' => $branchId]);
            }
        }

        $this->dropLegacyUniqueIndexes();
        $this->createTenantUniqueIndexes();
    }

    public function down(): void
    {
        $this->dropTenantUniqueIndexes();
        $this->createLegacyUniqueIndexes();

        foreach (array_reverse($this->branchTables) as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            });
        }

        foreach (array_reverse($this->tenantTables) as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }
    }

    private function dropLegacyUniqueIndexes(): void
    {
        Schema::table('customers', fn (Blueprint $table) => $table->dropUnique(['document_number']));
        Schema::table('vehicle_brands', fn (Blueprint $table) => $table->dropUnique(['name']));
        Schema::table('vehicle_categories', function (Blueprint $table): void {
            $table->dropUnique(['code']);
            $table->dropUnique(['name']);
        });
        Schema::table('vehicle_models', fn (Blueprint $table) => $table->dropUnique(['vehicle_brand_id', 'name', 'year']));
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropUnique(['code']);
            $table->dropUnique(['plate']);
        });
        Schema::table('reservations', fn (Blueprint $table) => $table->dropUnique(['code']));
        Schema::table('rentals', fn (Blueprint $table) => $table->dropUnique(['code']));
        Schema::table('invoices', fn (Blueprint $table) => $table->dropUnique(['number']));
        Schema::table('payments', fn (Blueprint $table) => $table->dropUnique(['receipt_number']));
        Schema::table('settings', fn (Blueprint $table) => $table->dropUnique(['key']));

        if (DB::connection()->getDriverName() === 'sqlsrv') {
            DB::statement('DROP INDEX customers_license_number_unique ON customers');
            DB::statement('DROP INDEX vehicles_vin_unique ON vehicles');
            DB::statement('DROP INDEX rentals_reservation_id_unique ON rentals');
        } else {
            Schema::table('customers', fn (Blueprint $table) => $table->dropUnique(['license_number']));
            Schema::table('vehicles', fn (Blueprint $table) => $table->dropUnique(['vin']));
            Schema::table('rentals', fn (Blueprint $table) => $table->dropUnique(['reservation_id']));
        }
    }

    private function createTenantUniqueIndexes(): void
    {
        Schema::table('customers', fn (Blueprint $table) => $table->unique(['company_id', 'document_number'], 'customers_company_document_unique'));
        Schema::table('vehicle_brands', fn (Blueprint $table) => $table->unique(['company_id', 'name'], 'vehicle_brands_company_name_unique'));
        Schema::table('vehicle_categories', function (Blueprint $table): void {
            $table->unique(['company_id', 'code'], 'vehicle_categories_company_code_unique');
            $table->unique(['company_id', 'name'], 'vehicle_categories_company_name_unique');
        });
        Schema::table('vehicle_models', fn (Blueprint $table) => $table->unique(['company_id', 'vehicle_brand_id', 'name', 'year'], 'vehicle_models_company_identity_unique'));
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->unique(['company_id', 'code'], 'vehicles_company_code_unique');
            $table->unique(['company_id', 'plate'], 'vehicles_company_plate_unique');
        });
        Schema::table('reservations', fn (Blueprint $table) => $table->unique(['company_id', 'code'], 'reservations_company_code_unique'));
        Schema::table('rentals', fn (Blueprint $table) => $table->unique(['company_id', 'code'], 'rentals_company_code_unique'));
        Schema::table('invoices', fn (Blueprint $table) => $table->unique(['company_id', 'number'], 'invoices_company_number_unique'));
        Schema::table('payments', fn (Blueprint $table) => $table->unique(['company_id', 'receipt_number'], 'payments_company_receipt_unique'));
        Schema::table('settings', fn (Blueprint $table) => $table->unique(['company_id', 'key'], 'settings_company_key_unique'));

        if (DB::connection()->getDriverName() === 'sqlsrv') {
            DB::statement(
                'CREATE UNIQUE INDEX customers_company_license_unique
                 ON customers (company_id, license_number)
                 WHERE license_number IS NOT NULL'
            );
            DB::statement(
                'CREATE UNIQUE INDEX vehicles_company_vin_unique
                 ON vehicles (company_id, vin)
                 WHERE vin IS NOT NULL'
            );
            DB::statement(
                'CREATE UNIQUE INDEX rentals_company_reservation_unique
                 ON rentals (company_id, reservation_id)
                 WHERE reservation_id IS NOT NULL'
            );
        } else {
            Schema::table('customers', fn (Blueprint $table) => $table->unique(['company_id', 'license_number'], 'customers_company_license_unique'));
            Schema::table('vehicles', fn (Blueprint $table) => $table->unique(['company_id', 'vin'], 'vehicles_company_vin_unique'));
            Schema::table('rentals', fn (Blueprint $table) => $table->unique(['company_id', 'reservation_id'], 'rentals_company_reservation_unique'));
        }
    }

    private function dropTenantUniqueIndexes(): void
    {
        Schema::table('customers', fn (Blueprint $table) => $table->dropUnique('customers_company_document_unique'));
        Schema::table('vehicle_brands', fn (Blueprint $table) => $table->dropUnique('vehicle_brands_company_name_unique'));
        Schema::table('vehicle_categories', function (Blueprint $table): void {
            $table->dropUnique('vehicle_categories_company_code_unique');
            $table->dropUnique('vehicle_categories_company_name_unique');
        });
        Schema::table('vehicle_models', fn (Blueprint $table) => $table->dropUnique('vehicle_models_company_identity_unique'));
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropUnique('vehicles_company_code_unique');
            $table->dropUnique('vehicles_company_plate_unique');
        });
        Schema::table('reservations', fn (Blueprint $table) => $table->dropUnique('reservations_company_code_unique'));
        Schema::table('rentals', fn (Blueprint $table) => $table->dropUnique('rentals_company_code_unique'));
        Schema::table('invoices', fn (Blueprint $table) => $table->dropUnique('invoices_company_number_unique'));
        Schema::table('payments', fn (Blueprint $table) => $table->dropUnique('payments_company_receipt_unique'));
        Schema::table('settings', fn (Blueprint $table) => $table->dropUnique('settings_company_key_unique'));

        if (DB::connection()->getDriverName() === 'sqlsrv') {
            DB::statement('DROP INDEX customers_company_license_unique ON customers');
            DB::statement('DROP INDEX vehicles_company_vin_unique ON vehicles');
            DB::statement('DROP INDEX rentals_company_reservation_unique ON rentals');
        } else {
            Schema::table('customers', fn (Blueprint $table) => $table->dropUnique('customers_company_license_unique'));
            Schema::table('vehicles', fn (Blueprint $table) => $table->dropUnique('vehicles_company_vin_unique'));
            Schema::table('rentals', fn (Blueprint $table) => $table->dropUnique('rentals_company_reservation_unique'));
        }
    }

    private function createLegacyUniqueIndexes(): void
    {
        Schema::table('customers', fn (Blueprint $table) => $table->unique('document_number'));
        Schema::table('vehicle_brands', fn (Blueprint $table) => $table->unique('name'));
        Schema::table('vehicle_categories', function (Blueprint $table): void {
            $table->unique('code');
            $table->unique('name');
        });
        Schema::table('vehicle_models', fn (Blueprint $table) => $table->unique(['vehicle_brand_id', 'name', 'year']));
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->unique('code');
            $table->unique('plate');
        });
        Schema::table('reservations', fn (Blueprint $table) => $table->unique('code'));
        Schema::table('rentals', fn (Blueprint $table) => $table->unique('code'));
        Schema::table('invoices', fn (Blueprint $table) => $table->unique('number'));
        Schema::table('payments', fn (Blueprint $table) => $table->unique('receipt_number'));
        Schema::table('settings', fn (Blueprint $table) => $table->unique('key'));

        if (DB::connection()->getDriverName() === 'sqlsrv') {
            DB::statement(
                'CREATE UNIQUE INDEX customers_license_number_unique
                 ON customers (license_number)
                 WHERE license_number IS NOT NULL'
            );
            DB::statement(
                'CREATE UNIQUE INDEX vehicles_vin_unique
                 ON vehicles (vin)
                 WHERE vin IS NOT NULL'
            );
            DB::statement(
                'CREATE UNIQUE INDEX rentals_reservation_id_unique
                 ON rentals (reservation_id)
                 WHERE reservation_id IS NOT NULL'
            );
        } else {
            Schema::table('customers', fn (Blueprint $table) => $table->unique('license_number'));
            Schema::table('vehicles', fn (Blueprint $table) => $table->unique('vin'));
            Schema::table('rentals', fn (Blueprint $table) => $table->unique('reservation_id'));
        }
    }
};
