<?php

declare(strict_types=1);

namespace App\Support\DigitalRental;

use App\Models\Rental;
use App\Models\RentalSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class RentalSignatureService
{
    public function contractHash(Rental $rental): string
    {
        $rental->loadMissing(['customer', 'vehicle.model.brand', 'vehicle.category', 'invoice']);

        $snapshot = [
            'company_id' => $rental->company_id,
            'rental_code' => $rental->code,
            'customer' => [
                'id' => $rental->customer_id,
                'document' => $rental->customer->document_number,
                'name' => $rental->customer->full_name,
            ],
            'vehicle' => [
                'id' => $rental->vehicle_id,
                'plate' => $rental->vehicle->plate,
                'vin' => $rental->vehicle->vin,
                'name' => $rental->vehicle->display_name,
            ],
            'period' => [
                'start_at' => $rental->start_at?->toIso8601String(),
                'expected_return_at' => $rental->expected_return_at?->toIso8601String(),
            ],
            'financial' => [
                'daily_rate' => (string) $rental->daily_rate,
                'deposit_amount' => (string) $rental->deposit_amount,
                'subtotal' => (string) $rental->subtotal,
                'fees' => (string) $rental->fees,
                'taxes' => (string) $rental->taxes,
                'total' => (string) $rental->total,
                'invoice_number' => $rental->invoice?->number,
            ],
        ];

        return hash(
            'sha256',
            json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        );
    }

    public function sign(
        Rental $rental,
        Request $request,
        string $signedName,
        string $signatureData,
    ): RentalSignature {
        if ($rental->renterSignature()->exists()) {
            throw ValidationException::withMessages([
                'signature' => 'Este contrato ya fue firmado por el arrendatario.',
            ]);
        }

        $binary = $this->decodeSignature($signatureData);
        $path = 'rental-signatures/'.$rental->company_id.'/'.$rental->code.'-'.bin2hex(random_bytes(10)).'.png';

        Storage::disk('local')->put($path, $binary);

        try {
            return DB::transaction(fn (): RentalSignature => RentalSignature::query()->create([
                'rental_id' => $rental->getKey(),
                'role' => 'renter',
                'signer_name' => trim($signedName),
                'signer_document' => $rental->customer->document_number,
                'signature_path' => $path,
                'contract_hash' => $this->contractHash($rental),
                'accepted_terms_at' => now(),
                'signed_at' => now(),
                'ip_hash' => $request->ip() ? hash('sha256', $request->ip()) : null,
                'user_agent_hash' => $request->userAgent() ? hash('sha256', $request->userAgent()) : null,
                'created_by' => auth()->id(),
            ]));
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
    }

    public function dataUri(?RentalSignature $signature): ?string
    {
        if ($signature === null || ! Storage::disk('local')->exists($signature->signature_path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode(
            Storage::disk('local')->get($signature->signature_path),
        );
    }

    private function decodeSignature(string $signatureData): string
    {
        if (! preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=]+)$/', $signatureData, $matches)) {
            throw ValidationException::withMessages([
                'signature' => 'La firma digital tiene un formato inválido.',
            ]);
        }

        $binary = base64_decode($matches[1], true);

        if ($binary === false || strlen($binary) < 100 || strlen($binary) > 2_000_000) {
            throw ValidationException::withMessages([
                'signature' => 'La firma digital está vacía o excede el tamaño permitido.',
            ]);
        }

        $imageInfo = @getimagesizefromstring($binary);

        if ($imageInfo === false || ($imageInfo['mime'] ?? null) !== 'image/png') {
            throw ValidationException::withMessages([
                'signature' => 'La firma debe ser una imagen PNG válida.',
            ]);
        }

        return $binary;
    }
}
