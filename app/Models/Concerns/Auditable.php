<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

trait Auditable
{
    public static function bootAuditable(): void
    {
        foreach (['created', 'updated', 'deleted'] as $event) {

            static::$event(function (Model $model) use ($event): void {

                if (! Schema::hasTable('audit_logs')) {
                    return;
                }

                /*
                 * Evita auditar la propia tabla de auditoría.
                 */
                if ($model instanceof AuditLog) {
                    return;
                }

                $hidden = $model->getHidden();

                $oldValues = match ($event) {
                    'updated' => Arr::except($model->getOriginal(), $hidden),
                    'deleted' => Arr::except($model->getAttributes(), $hidden),
                    default => null,
                };

                $newValues = $event === 'deleted'
                    ? null
                    : Arr::except($model->getAttributes(), $hidden);

                /*
                 * Si el usuario eliminado es el autenticado,
                 * no conservamos la FK.
                 */
                $userId = Auth::id();

                if (
                    $event === 'deleted'
                    && $model instanceof User
                    && $userId === $model->getKey()
                ) {
                    $userId = null;
                }

                AuditLog::withoutEvents(function () use (
                    $userId,
                    $event,
                    $model,
                    $oldValues,
                    $newValues
                ): void {

                    AuditLog::query()->create([
                        'user_id' => $userId,
                        'event' => $event,
                        'auditable_type' => $model::class,
                        'auditable_id' => $model->getKey(),
                        'old_values' => $oldValues,
                        'new_values' => $newValues,
                        'ip_address' => request()?->ip(),
                        'user_agent' => request()?->userAgent(),
                    ]);
                });
            });
        }
    }
}
