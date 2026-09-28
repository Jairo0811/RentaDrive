<?php

declare(strict_types=1);

namespace App\Support\Fiscal;

use App\Models\Company;
use App\Models\FiscalSequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class FiscalSequenceService
{
    /**
     * @var array<string,int>
     */
    private const LENGTHS = [
        'B01' => 8,
        'B02' => 8,
        'E31' => 10,
        'E32' => 10,
    ];

    public function next(string $documentType): string
    {
        if (! isset(self::LENGTHS[$documentType])) {
            throw ValidationException::withMessages([
                'fiscal_document_type' => 'Secuencia fiscal no soportada.',
            ]);
        }

        return DB::transaction(function () use ($documentType): string {
            /** @var FiscalSequence|null $sequence */
            $sequence = FiscalSequence::query()
                ->where('document_type', $documentType)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                throw ValidationException::withMessages([
                    'fiscal_document_type' => 'No existe una secuencia activa para '.$documentType.'.',
                ]);
            }

            if ($sequence->expires_at !== null && $sequence->expires_at->isPast()) {
                throw ValidationException::withMessages([
                    'fiscal_document_type' => 'La secuencia '.$documentType.' está vencida.',
                ]);
            }

            if ($sequence->end_number !== null && $sequence->next_number > $sequence->end_number) {
                throw ValidationException::withMessages([
                    'fiscal_document_type' => 'La secuencia '.$documentType.' está agotada.',
                ]);
            }

            $number = $sequence->next_number;
            $sequence->increment('next_number');

            return $sequence->prefix.str_pad(
                (string) $number,
                $sequence->sequence_length,
                '0',
                STR_PAD_LEFT,
            );
        });
    }

    public function syncFromText(Company $company, string $raw): void
    {
        $seen = [];

        DB::transaction(function () use ($company, $raw, &$seen): void {
            foreach (preg_split('/\R/', $raw) ?: [] as $line) {
                $parts = array_map('trim', explode('|', $line));

                if ($parts === [''] || count($parts) < 2) {
                    continue;
                }

                $type = strtoupper($parts[0]);
                $next = $parts[1] ?? null;
                $end = $parts[2] ?? null;
                $expires = $parts[3] ?? null;

                if (! isset(self::LENGTHS[$type]) || ! is_numeric($next)) {
                    throw ValidationException::withMessages([
                        'fiscal_sequences' => 'Secuencia inválida: '.$line,
                    ]);
                }

                $nextNumber = max(1, (int) $next);
                $endNumber = $end !== null && $end !== '' ? (int) $end : null;

                if ($endNumber !== null && $endNumber < $nextNumber) {
                    throw ValidationException::withMessages([
                        'fiscal_sequences' => 'El final de la secuencia no puede ser menor que el próximo número.',
                    ]);
                }

                FiscalSequence::query()->updateOrCreate(
                    ['document_type' => $type],
                    [
                        'prefix' => $type,
                        'next_number' => $nextNumber,
                        'end_number' => $endNumber,
                        'sequence_length' => self::LENGTHS[$type],
                        'expires_at' => $expires !== null && $expires !== '' ? $expires : null,
                        'is_active' => true,
                    ],
                );

                $seen[] = $type;
            }

            FiscalSequence::query()
                ->when($seen !== [], fn ($query) => $query->whereNotIn('document_type', $seen))
                ->when($seen === [], fn ($query) => $query)
                ->update(['is_active' => false]);
        });
    }

    public function serialize(Company $company): string
    {
        return FiscalSequence::query()
            ->orderBy('document_type')
            ->get()
            ->map(fn (FiscalSequence $sequence): string => implode('|', [
                $sequence->document_type,
                $sequence->next_number,
                $sequence->end_number ?? '',
                $sequence->expires_at?->format('Y-m-d') ?? '',
            ]))
            ->implode(PHP_EOL);
    }
}
