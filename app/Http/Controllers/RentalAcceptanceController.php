<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Models\RentalAcceptance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RentalAcceptanceController extends Controller
{
    public function store(Request $request, Rental $rental): RedirectResponse
    {
        $data = $request->validate([
            'signer_name' => ['required', 'string', 'min:3', 'max:255'],
            'acknowledge' => ['required', 'accepted'],
        ]);

        DB::transaction(function () use ($data, $rental, $request): void {
            $rental = Rental::query()->lockForUpdate()->findOrFail($rental->getKey());
            if ($rental->status !== 'open') {
                throw ValidationException::withMessages(['rental' => 'Solo se puede registrar aceptación en alquileres abiertos.']);
            }

            if (RentalAcceptance::query()->where('rental_id', $rental->getKey())
                ->where('signer_role', 'operator')->exists()) {
                throw ValidationException::withMessages(['rental' => 'Este alquiler ya tiene un acuse de operador.']);
            }

            RentalAcceptance::query()->create([
                'company_id' => $rental->company_id,
                'rental_id' => $rental->getKey(),
                'recorded_by' => auth()->id(),
                'signer_role' => 'operator',
                'signer_name' => $data['signer_name'],
                'document_hash' => hash('sha256', json_encode([
                    $rental->code, $rental->customer_id, $rental->vehicle_id,
                    $rental->start_at->toIso8601String(),
                    $rental->expected_return_at->toIso8601String(), (string) $rental->total,
                ], JSON_THROW_ON_ERROR)),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'accepted_at' => now(),
            ]);
        }, 3);

        return back()->with('status', 'Acuse interno registrado. No equivale a firma digital certificada del cliente.');
    }
}
