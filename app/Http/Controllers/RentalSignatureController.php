<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Support\DigitalRental\RentalSignatureService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class RentalSignatureController extends Controller
{
    public function create(
        Rental $rental,
        RentalSignatureService $signatures,
    ): View {
        $rental->load([
            'customer',
            'vehicle.model.brand',
            'vehicle.category',
            'invoice',
            'renterSignature',
        ]);

        return view('rentals.signature', [
            'rental' => $rental,
            'contractHash' => $signatures->contractHash($rental),
        ]);
    }

    public function store(
        Request $request,
        Rental $rental,
        RentalSignatureService $signatures,
    ): RedirectResponse {
        $data = $request->validate([
            'signed_name' => ['required', 'string', 'max:160'],
            'signature_data' => ['required', 'string', 'max:3000000'],
            'accept_terms' => ['accepted'],
        ]);

        $signature = $signatures->sign(
            $rental->loadMissing(['customer', 'vehicle.model.brand', 'vehicle.category', 'invoice']),
            $request,
            $data['signed_name'],
            $data['signature_data'],
        );

        return redirect()
            ->route('rentals.show', $rental)
            ->with('status', 'Contrato firmado digitalmente. Evidencia '.$signature->contract_hash.'.');
    }
}
