<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Payments\Services\PaymentIntentService;
use App\Models\Company;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class DepositController extends Controller
{
    public function store(Company $company, string $token, PaymentIntentService $payments): RedirectResponse
    {
        $reservation = Reservation::query()->where('source', 'public')
            ->where('public_token', $token)->firstOrFail();

        $payments->beginReservationDeposit($reservation);

        return back()->with('status', 'Depósito registrado en modo de prueba. No se ha cobrado dinero real.');
    }

    public function webhook(Request $request, PaymentIntentService $payments): JsonResponse
    {
        $data = $request->validate([
            'event_id' => ['required', 'string', 'max:120'],
            'intent_id' => ['required', 'uuid'],
            'event_type' => ['required', 'in:captured,refunded'],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'currency' => ['required', 'in:DOP'],
            'reference' => ['nullable', 'string', 'max:120'],
        ]);

        $payments->receive($data, $request->getContent(), $request->header('X-RentaDrive-Signature'));

        return response()->json(['accepted' => true]);
    }
}
