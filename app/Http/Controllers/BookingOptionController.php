<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\BookingOptionRequest;
use App\Models\BookingOption;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

final class BookingOptionController extends Controller
{
    public function index(): View
    {
        return view('booking-options.index', [
            'options' => BookingOption::query()
                ->orderBy('type')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(BookingOptionRequest $request): RedirectResponse
    {
        BookingOption::query()->create($request->validated());

        return back()->with('status', 'Opción de reserva creada.');
    }

    public function update(BookingOptionRequest $request, BookingOption $bookingOption): RedirectResponse
    {
        $bookingOption->update($request->validated());

        return back()->with('status', 'Opción de reserva actualizada.');
    }

    public function destroy(BookingOption $bookingOption): RedirectResponse
    {
        if ($bookingOption->reservationOptions()->exists()) {
            throw ValidationException::withMessages([
                'booking_option' => 'Esta opción ya forma parte del historial de reservas. Desactívala en lugar de eliminarla.',
            ]);
        }

        $bookingOption->delete();

        return back()->with('status', 'Opción de reserva eliminada.');
    }
}
