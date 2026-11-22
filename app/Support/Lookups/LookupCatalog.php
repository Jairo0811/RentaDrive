<?php

declare(strict_types=1);

namespace App\Support\Lookups;

use App\Domain\Security\Enums\PermissionName;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Rental;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Búsqueda acotada para los selectores con búsqueda del servidor.
 *
 * Todas las consultas pasan por los modelos, por lo que el TenantScope global limita
 * siempre el resultado a la empresa activa; no se acepta ningún identificador de empresa.
 */
final class LookupCatalog
{
    public const LIMIT = 15;

    public const TYPES = ['customers', 'vehicles', 'rentals', 'invoices'];

    private const MAX_TERM_LENGTH = 80;

    /**
     * @return list<string>
     */
    public static function permissions(string $type): array
    {
        return match ($type) {
            'customers', 'vehicles' => [PermissionName::MANAGE_RESERVATIONS->value, PermissionName::MANAGE_RENTALS->value],
            'rentals' => [PermissionName::MANAGE_INSPECTIONS->value],
            'invoices' => [PermissionName::RECORD_PAYMENTS->value, PermissionName::MANAGE_PAYMENTS->value],
            default => [],
        };
    }

    /**
     * @return array{data: list<array<string, mixed>>, more: bool}
     */
    public function search(string $type, string $term, ?string $scope = null): array
    {
        $term = trim(mb_substr($term, 0, self::MAX_TERM_LENGTH));
        $query = $this->query($type, $scope);
        $this->applyTerm($type, $query, $term);

        /** @var Collection<int, Model> $rows */
        $rows = $query->limit(self::LIMIT + 1)->get();

        return [
            'data' => $rows->take(self::LIMIT)->map(fn (Model $row): array => $this->present($type, $row))->values()->all(),
            'more' => $rows->count() > self::LIMIT,
        ];
    }

    /**
     * Etiqueta de un registro ya elegido (edición o reenvío tras un error de validación).
     *
     * @return array<string, mixed>|null
     */
    public function find(string $type, int|string|null $id, ?string $scope = null): ?array
    {
        if (! in_array($type, self::TYPES, true) || $id === null || $id === '' || ! ctype_digit((string) $id)) {
            return null;
        }

        $row = $this->query($type, null)->whereKey((int) $id)->first();

        return $row ? $this->present($type, $row) : null;
    }

    /**
     * @return Builder<Model>
     */
    private function query(string $type, ?string $scope): Builder
    {
        return match ($type) {
            'customers' => Customer::query()->where('status', 'active')->orderBy('first_name')->orderBy('last_name')->orderBy('id'),
            'vehicles' => Vehicle::query()
                ->with(['model.brand', 'category'])
                ->whereNotIn('status', $scope === 'rental' ? ['rented', 'maintenance', 'inactive'] : ['maintenance', 'inactive'])
                ->orderBy('code')
                ->orderBy('id'),
            'rentals' => Rental::query()->with(['customer', 'vehicle.model.brand'])->latest('id'),
            'invoices' => Invoice::query()->with('customer')->where('balance', '>', 0)->orderBy('due_at')->orderBy('id'),
            default => abort(404),
        };
    }

    /**
     * @param  Builder<Model>  $query
     */
    private function applyTerm(string $type, Builder $query, string $term): void
    {
        if ($term === '') {
            return;
        }

        $like = '%'.$term.'%';

        match ($type) {
            'customers' => $query->where(function (Builder $query) use ($like): void {
                $query->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('document_number', 'like', $like);
            }),
            'vehicles' => $query->where(function (Builder $query) use ($like): void {
                $query->where('code', 'like', $like)
                    ->orWhere('plate', 'like', $like)
                    ->orWhereHas('model', fn (Builder $model) => $model->where('name', 'like', $like)
                        ->orWhereHas('brand', fn (Builder $brand) => $brand->where('name', 'like', $like)));
            }),
            'rentals' => $query->where(function (Builder $query) use ($like): void {
                $query->where('code', 'like', $like)
                    ->orWhereHas('customer', fn (Builder $customer) => $customer->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like))
                    ->orWhereHas('vehicle', fn (Builder $vehicle) => $vehicle->where('plate', 'like', $like)->orWhere('code', 'like', $like));
            }),
            'invoices' => $query->where(function (Builder $query) use ($like): void {
                $query->where('number', 'like', $like)
                    ->orWhereHas('customer', fn (Builder $customer) => $customer->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like));
            }),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function present(string $type, Model $row): array
    {
        return match (true) {
            $row instanceof Customer => [
                'id' => $row->getKey(),
                'label' => $row->full_name,
                'detail' => $row->maskedDocumentNumber(),
                'meta' => [],
            ],
            $row instanceof Vehicle => [
                'id' => $row->getKey(),
                'label' => $row->display_name,
                'detail' => trim(($row->category?->name ?? '').' · RD$ '.number_format($row->effective_daily_rate, 2).'/día', ' ·'),
                'meta' => [
                    'category' => $row->vehicle_category_id,
                    'rate' => $row->effective_daily_rate,
                    'mileage' => $row->mileage,
                    'deposit' => (float) ($row->category?->deposit_amount ?? 0),
                ],
            ],
            $row instanceof Rental => [
                'id' => $row->getKey(),
                'label' => $row->code,
                'detail' => trim(($row->customer?->full_name ?? '').' · '.($row->vehicle?->display_name ?? ''), ' ·'),
                'meta' => [],
            ],
            $row instanceof Invoice => [
                'id' => $row->getKey(),
                'label' => $row->number,
                'detail' => trim(($row->customer?->full_name ?? '').' · RD$ '.number_format((float) $row->balance, 2), ' ·'),
                'meta' => [],
            ],
            default => abort(404),
        };
    }
}
