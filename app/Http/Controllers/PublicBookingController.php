<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Booking\Services\BookingQuoteService;
use App\Domain\Operations\Services\ReferenceNumberService;
use App\Domain\Operations\Services\ReservationAvailabilityService;
use App\Http\Requests\PublicBookingAvailabilityRequest;
use App\Http\Requests\PublicBookingQuoteRequest;
use App\Http\Requests\PublicBookingRequest;
use App\Models\BookingOption;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\ReservationBookingOption;
use App\Models\VehicleCategory;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PublicBookingController extends Controller
{
    public function show(Company $company): View
    {
        return view('public-booking.home', [
            'company' => $company,
            'branches' => $company->branches()
                ->where('is_active', true)
                ->orderByDesc('is_primary')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function availability(
        PublicBookingAvailabilityRequest $request,
        Company $company,
        ReservationAvailabilityService $availability,
    ): View {
        $data = $request->validated();
        $branch = Branch::query()
            ->where('company_id', $company->getKey())
            ->findOrFail($data['branch_id']);
        $startAt = Carbon::parse($data['start_at']);
        $endAt = Carbon::parse($data['end_at']);
        $days = $availability->rentalDays($startAt, $endAt);

        $categories = VehicleCategory::query()
            ->where('is_active', true)
            ->when(
                ! empty($data['vehicle_category_id']),
                fn ($query) => $query->whereKey($data['vehicle_category_id']),
            )
            ->orderBy('daily_rate')
            ->get()
            ->map(function (VehicleCategory $category) use ($availability, $branch, $startAt, $endAt, $days): VehicleCategory {
                $category->setAttribute('available_count', $availability->availableCategoryCount(
                    $branch,
                    $category,
                    $startAt,
                    $endAt,
                ));
                $category->setAttribute('quote_subtotal', round($days * (float) $category->daily_rate, 2));

                return $category;
            });

        return view('public-booking.availability', compact(
            'company',
            'branch',
            'startAt',
            'endAt',
            'days',
            'categories',
        ));
    }

    public function create(
        PublicBookingQuoteRequest $request,
        Company $company,
        BookingQuoteService $quotes,
    ): View {
        $data = $request->validated();
        $branch = Branch::query()->where('company_id', $company->getKey())->findOrFail($data['branch_id']);
        $category = VehicleCategory::query()->findOrFail($data['vehicle_category_id']);
        $startAt = Carbon::parse($data['start_at']);
        $endAt = Carbon::parse($data['end_at']);
        $quote = $quotes->quote($branch, $category, $startAt, $endAt);
        $options = BookingOption::query()
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('public-booking.create', compact(
            'company',
            'branch',
            'category',
            'startAt',
            'endAt',
            'quote',
            'options',
        ));
    }

    public function store(
        PublicBookingRequest $request,
        Company $company,
        BookingQuoteService $quotes,
        ReferenceNumberService $references,
    ): RedirectResponse {
        $data = $request->validated();

        $reservation = DB::transaction(function () use ($data, $company, $quotes, $references): Reservation {
            $branch = Branch::query()
                ->where('company_id', $company->getKey())
                ->whereKey($data['branch_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $category = VehicleCategory::query()->findOrFail($data['vehicle_category_id']);
            $startAt = Carbon::parse($data['start_at']);
            $endAt = Carbon::parse($data['end_at']);
            $quote = $quotes->quote(
                $branch,
                $category,
                $startAt,
                $endAt,
                $data['booking_option_ids'] ?? [],
            );

            $customer = Customer::query()
                ->where('document_number', $data['document_number'])
                ->first();

            if ($customer !== null && mb_strtolower((string) $customer->email) !== mb_strtolower($data['email'])) {
                throw ValidationException::withMessages([
                    'document_number' => 'El documento ya existe con datos de contacto diferentes. Comunícate con la empresa para continuar.',
                ]);
            }

            if ($customer === null) {
                $customer = Customer::query()->create([
                    'document_type' => $data['document_type'],
                    'document_number' => $data['document_number'],
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'status' => 'active',
                ]);
            } else {
                $customer->update([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'phone' => $data['phone'],
                ]);
            }

            $reservation = Reservation::query()->create([
                'company_id' => $company->getKey(),
                'branch_id' => $branch->getKey(),
                'code' => $references->generate(Reservation::class, 'code', 'RES'),
                'customer_id' => $customer->getKey(),
                'vehicle_category_id' => $category->getKey(),
                'vehicle_id' => null,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'pickup_location' => $branch->name,
                'return_location' => $branch->name,
                'daily_rate' => $quote['daily_rate'],
                'subtotal' => $quote['subtotal'],
                'extras_total' => $quote['extras_total'],
                'deposit_amount' => $quote['deposit_amount'],
                'estimated_total' => $quote['estimated_total'],
                'status' => 'pending',
                'source' => 'public',
                'public_token' => Str::random(48),
                'notes' => $data['notes'] ?? null,
                'created_by' => null,
            ]);

            foreach ($quote['options'] as $option) {
                ReservationBookingOption::query()->create([
                    'company_id' => $company->getKey(),
                    'reservation_id' => $reservation->getKey(),
                    'booking_option_id' => $option['id'],
                    'quantity' => 1,
                    'unit_price' => $option['unit_price'],
                    'total' => $option['total'],
                ]);
            }

            return $reservation;
        }, 3);

        return redirect()->route('public-booking.confirmation', [
            'company' => $company->slug,
            'token' => $reservation->public_token,
        ]);
    }

    public function confirmation(Company $company, string $token): View
    {
        $reservation = Reservation::query()
            ->where('source', 'public')
            ->where('public_token', $token)
            ->with(['customer', 'category', 'branch', 'bookingOptions.option', 'paymentIntents'])
            ->firstOrFail();

        return view('public-booking.confirmation', compact('company', 'reservation'));
    }

    public function cancel(Company $company, string $token): RedirectResponse
    {
        $reservation = Reservation::query()
            ->where('source', 'public')
            ->where('public_token', $token)
            ->firstOrFail();

        if (! in_array($reservation->status, ['pending', 'confirmed'], true)) {
            throw ValidationException::withMessages([
                'reservation' => 'Esta reserva ya no puede cancelarse desde el portal.',
            ]);
        }

        if ($reservation->start_at->isPast()) {
            throw ValidationException::withMessages([
                'reservation' => 'La fecha de retiro ya inició. Comunícate con la empresa para gestionar la reserva.',
            ]);
        }

        $reservation->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return redirect()
            ->route('public-booking.confirmation', [
                'company' => $company->slug,
                'token' => $reservation->public_token,
            ])
            ->with('status', 'Tu reserva fue cancelada.');
    }
}
