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
        Schema::table('companies', function (Blueprint $table): void {
            $table->dateTime('onboarding_completed_at')->nullable()->index();
        });

        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('plan_code', 40);
            $table->string('status', 24)->index();
            $table->string('billing_cycle', 20)->default('monthly');
            $table->string('provider', 40)->default('manual');
            $table->string('provider_reference', 190)->nullable()->index();
            $table->dateTime('started_at');
            $table->dateTime('trial_ends_at')->nullable();
            $table->dateTime('current_period_starts_at')->nullable();
            $table->dateTime('current_period_ends_at')->nullable();
            $table->dateTime('grace_ends_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        Schema::create('backup_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->string('disk', 80);
            $table->string('path', 500);
            $table->string('status', 24)->default('running')->index();
            $table->unsignedBigInteger('byte_size')->nullable();
            $table->string('checksum_sha256', 64)->nullable();
            $table->dateTime('started_at');
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->text('error')->nullable();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->noActionOnDelete();
            $table->text('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'completed_at']);
        });

        $now = now();

        DB::table('companies')->update([
            'onboarding_completed_at' => $now,
        ]);

        foreach (DB::table('companies')->get() as $company) {
            $subscriptionStatus = match ((string) $company->status) {
                'trial' => 'trialing',
                'active' => 'active',
                'cancelled' => 'cancelled',
                default => 'past_due',
            };

            DB::table('subscriptions')->insert([
                'company_id' => $company->id,
                'plan_code' => $company->plan_code ?? 'starter',
                'status' => $subscriptionStatus,
                'billing_cycle' => 'monthly',
                'provider' => 'manual',
                'started_at' => $now,
                'trial_ends_at' => $company->trial_ends_at ?? null,
                'cancelled_at' => $subscriptionStatus === 'cancelled' ? $now : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_snapshots');
        Schema::dropIfExists('subscriptions');

        Schema::table('companies', function (Blueprint $table): void {
            $table->dropIndex(['onboarding_completed_at']);
            $table->dropColumn('onboarding_completed_at');
        });
    }
};
