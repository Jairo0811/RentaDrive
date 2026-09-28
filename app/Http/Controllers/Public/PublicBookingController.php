<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Operations\Services\ReferenceNumberService;
use App\Domain\Operations\Services\ReservationAvailabilityService;
use App\Http\Controllers\Controller;
use App\Mail\PublicReservationCreated;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Rules\DominicanCedula;
use App\Support\Commercial\BookingConfig;
use App\Support\Commercial\BookingPricingService;
use App\Support\Notifications\WhatsAppBookingNotifier;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

final class PublicBookingController extends Controller
{
    public function show(Company $company, TenantContext $tenant): View
    {
        $this->activateTenant($company, $tenant);

        return view('public.booking', [
            'company' => $company,
            'branches' => $this->activeBranches($company),
            'categories' => VehicleCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'vehicles' => collect(),
            'quotes' => collect(),
            'search' => [],
            'days' => null,
            'searchPerformed' => false,
        ]);
    }

    public function search(
        Request $request,
        Company $company,
        TenantContext $tenant,
        ReservationAvailabilityService $availability,
        BookingPricingService $pricingService,
    ): View {
        $this->activateTenant($company, $tenant);
        $search = $this->validateSearch($request, $company);

        $branch = $this->activeBranches($company)->firstWhere('id', (int) $search['branch_id']);
        abort_unless($branch instanceof Branch, 404);

        $tenant->set($company, $branch);

        $startAt = CarbonImmutable::parse((string) $search['start_at'], $company->timezone);
        $endAt = CarbonImmutable::parse((string) $search['end_at'], $company->timezone);
        $days = $availability->rentalDays($startAt, $endAt);

        $vehicles = Vehicle::query()
            ->with(['model.brand', 'category'])
            ->where('branch_id', $branch->getKey())
            ->whereNotIn('status', ['maintenance', 'inactive'])
            ->when(
                ! empty($search['category_id']),
                fn ($query) => $query->where('vehicle_category_id', (int) $search['category_id']),
            )
            ->orderBy('vehicle_category_id')
            ->orderBy('code')
            ->get()
            ->filter(fn (Vehicle $vehicle): bool => $availability->isVehicleAvailable($vehicle, $startAt, $endAt))
            ->values();

        $quotes = $vehicles->mapWithKeys(
            fn (Vehicle $vehicle): array => [
                $vehicle->getKey() => $pricingService->quote($company, $vehicle, $startAt, $endAt),
            ],
        );

        return view('public.booking', [
            'company' => $company,
            'branches' => $this->activeBranches($company),
            'categories' => VehicleCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'vehicles' => $vehicles,
            'quotes' => $quotes,
            'search' => $search,
            'days' => $days,
            'searchPerformed' => true,
        ]);
    }

    public function create(
        Request $request,
        Company $company,
        TenantContext $tenant,
        ReservationAvailabilityService $availability,
        BookingPricingService $pricingService,
        BookingConfig $config,
    ): View {
        $this->activateTenant($company, $tenant);
        $quote = $this->resolveQuote($request, $company, $tenant, $availability, $pricingService, $config);

        return view('public.reserve', [
            'company' => $company,
            ...$quote,
        ]);
    }

    public function store(
        Request $request,
        Company $company,
        TenantContext $tenant,
        ReservationAvailabilityService $availability,
        ReferenceNumberService $references,
        BookingPricingService $pricingService,
        BookingConfig $config,
        WhatsAppBookingNotifier $whatsApp,
    ): RedirectResponse {
        $this->activateTenant($company, $tenant);
        $quote = $this->resolveQuote($request, $company, $tenant, $availability, $pricingService, $config);

        if ($request->filled('promo_code') && $quote['pricing']['promo_code'] === null) {
            throw ValidationException::withMessages([
                'promo_code' => 'El código promocional no existe o no está vigente.',
            ]);
        }

        $documentType = (string) $request->input('document_type');
        $documentNumber = trim((string) $request->input('document_number'));

        if (in_array($documentType, ['cedula', 'rnc'], true)) {
            $documentNumber = preg_replace('/\D+/', '', $documentNumber) ?? '';
        } elseif ($documentType === 'passport') {
            $documentNumber = strtoupper(preg_replace('/\s+/', '', $documentNumber) ?? '');
        }

        $request->merge(['document_number' => $documentNumber]);

        $documentRules = ['required', 'string'];
        match ($documentType) {
            'cedula' => array_push($documentRules, 'digits:11', new DominicanCedula),
            'rnc' => array_push($documentRules, 'digits:9'),
            'passport' => array_push($documentRules, 'min:6', 'max:20', 'regex:/^[A-Z0-9]+$/i'),
            default => array_push($documentRules, 'min:3', 'max:30'),
        };

        $customerData = $request->validate([
            'document_type' => ['required', Rule::in(['cedula', 'passport', 'rnc', 'other'])],
            'document_number' => $documentRules,
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'terms' => ['accepted'],
        ]);

        /** @var Vehicle $vehicle */
        $vehicle = $quote['vehicle'];
        /** @var Branch $branch */
        $branch = $quote['branch'];

        $reservation = DB::transaction(function () use (
            $company,
            $branch,
            $vehicle,
            $quote,
            $customerData,
            $references,
            $availability,
            $pricingService,
        ): Reservation {
            /** @var Vehicle $lockedVehicle */
            $lockedVehicle = Vehicle::query()
                ->whereKey($vehicle->getKey())
                ->where('branch_id', $branch->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $availability->isVehicleAvailable($lockedVehicle, $quote['startAt'], $quote['endAt'])) {
                throw ValidationException::withMessages([
                    'vehicle_id' => 'Este vehículo acaba de ser reservado para ese período. Busca otra opción disponible.',
                ]);
            }

            $pricing = $pricingService->quote(
                $company,
                $lockedVehicle,
                $quote['startAt'],
                $quote['endAt'],
                $quote['selectedExtraCodes'],
                $quote['requestedPromoCode'],
            );

            if ($quote['requestedPromoCode'] !== null && $pricing['promo_code'] === null) {
                throw ValidationException::withMessages([
                    'promo_code' => 'El código promocional dejó de estar vigente.',
                ]);
            }

            $customer = Customer::query()->firstOrCreate(
                ['document_number' => $customerData['document_number']],
                [
                    'document_type' => $customerData['document_type'],
                    'first_name' => $customerData['first_name'],
                    'last_name' => $customerData['last_name'],
                    'email' => $customerData['email'],
                    'phone' => $customerData['phone'],
                    'status' => 'active',
                ],
            );

            if ($customer->email === null || $customer->phone === '') {
                $customer->fill([
                    'email' => $customer->email ?: $customerData['email'],
                    'phone' => $customer->phone ?: $customerData['phone'],
                ])->save();
            }

            return Reservation::query()->create([
                'code' => $references->generate(Reservation::class, 'code', 'RES'),
                'customer_id' => $customer->getKey(),
                'vehicle_category_id' => $lockedVehicle->vehicle_category_id,
                'vehicle_id' => $lockedVehicle->getKey(),
                'start_at' => $quote['startAt'],
                'end_at' => $quote['endAt'],
                'pickup_location' => $branch->name,
                'return_location' => $branch->name,
                'daily_rate' => $pricing['daily_rate'],
                'base_total' => $pricing['seasonal_total'],
                'extras_total' => $pricing['extras_total'],
                'discount_total' => $pricing['discount_total'],
                'promo_code' => $pricing['promo_code'],
                'pricing_breakdown' => $pricing,
                'estimated_total' => $pricing['estimated_total'],
                'status' => 'pending',
                'notes' => 'Reserva creada desde el portal público.',
                'created_by' => null,
            ]);
        });

        $reservation->load(['customer', 'vehicle.model.brand', 'category']);
        $cancellationUrl = $this->cancellationUrl($company, $reservation, $config);

        if ($config->emailConfirmationEnabled($company) && $reservation->customer?->email !== null) {
            try {
                Mail::to($reservation->customer->email)
                    ->send(new PublicReservationCreated($company, $reservation, $cancellationUrl));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        try {
            $whatsApp->sendCreated($company, $reservation);
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()->route('public.booking.confirmation', [
            'company' => $company->slug,
            'code' => $reservation->code,
        ]);
    }

    public function confirmation(
        Company $company,
        string $code,
        TenantContext $tenant,
        BookingConfig $config,
    ): View {
        $this->activateTenant($company, $tenant);

        $reservation = Reservation::query()
            ->with(['customer', 'vehicle.model.brand', 'category'])
            ->where('code', $code)
            ->firstOrFail();

        return view('public.confirmation', [
            'company' => $company,
            'reservation' => $reservation,
            'cancellationUrl' => $this->cancellationUrl($company, $reservation, $config),
        ]);
    }

    public function cancelShow(
        Company $company,
        string $code,
        TenantContext $tenant,
        BookingConfig $config,
    ): View {
        $this->activateTenant($company, $tenant);
        $reservation = Reservation::query()->where('code', $code)->firstOrFail();
        $deadline = $this->cancellationDeadline($company, $reservation, $config);

        return view('public.cancel', [
            'company' => $company,
            'reservation' => $reservation,
            'deadline' => $deadline,
            'canCancel' => $this->canCancel($company, $reservation, $config),
        ]);
    }

    public function cancelStore(
        Request $request,
        Company $company,
        string $code,
        TenantContext $tenant,
        BookingConfig $config,
    ): RedirectResponse {
        $this->activateTenant($company, $tenant);
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($company, $code, $config, $validated): void {
            $reservation = Reservation::query()
                ->where('code', $code)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $this->canCancel($company, $reservation, $config)) {
                throw ValidationException::withMessages([
                    'reservation' => 'La ventana de cancelación en línea ya cerró.',
                ]);
            }

            $reservation->update([
                'status' => 'cancelled',
                'cancelled_at' => now($company->timezone),
                'cancellation_reason' => $validated['reason'] ?? 'Cancelada por el cliente desde el portal público.',
            ]);
        });

        return redirect()->route('public.booking.confirmation', [
            'company' => $company->slug,
            'code' => $code,
        ])->with('status', 'Reserva cancelada correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateSearch(Request $request, Company $company): array
    {
        return $request->validate([
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(
                    fn ($query) => $query
                        ->where('company_id', $company->getKey())
                        ->where('is_active', true),
                ),
            ],
            'start_at' => ['required', 'date', 'after_or_equal:today'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('vehicle_categories', 'id')->where(
                    fn ($query) => $query
                        ->where('company_id', $company->getKey())
                        ->where('is_active', true),
                ),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveQuote(
        Request $request,
        Company $company,
        TenantContext $tenant,
        ReservationAvailabilityService $availability,
        BookingPricingService $pricingService,
        BookingConfig $config,
    ): array {
        $data = $request->validate([
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(
                    fn ($query) => $query
                        ->where('company_id', $company->getKey())
                        ->where('is_active', true),
                ),
            ],
            'vehicle_id' => [
                'required',
                'integer',
                Rule::exists('vehicles', 'id')->where(
                    fn ($query) => $query->where('company_id', $company->getKey()),
                ),
            ],
            'start_at' => ['required', 'date', 'after_or_equal:today'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'extras' => ['nullable', 'array', 'max:20'],
            'extras.*' => ['string', 'max:40'],
            'promo_code' => ['nullable', 'string', 'max:40'],
        ]);

        $branch = $company->branches()
            ->whereKey((int) $data['branch_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $tenant->set($company, $branch);

        $vehicle = Vehicle::query()
            ->with(['model.brand', 'category'])
            ->whereKey((int) $data['vehicle_id'])
            ->where('branch_id', $branch->getKey())
            ->firstOrFail();

        $startAt = CarbonImmutable::parse((string) $data['start_at'], $company->timezone);
        $endAt = CarbonImmutable::parse((string) $data['end_at'], $company->timezone);

        if (! $availability->isVehicleAvailable($vehicle, $startAt, $endAt)) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'Este vehículo dejó de estar disponible para las fechas seleccionadas. Busca otra opción.',
            ]);
        }

        $selectedExtraCodes = array_values(array_filter((array) ($data['extras'] ?? [])));
        $requestedPromoCode = isset($data['promo_code']) && trim((string) $data['promo_code']) !== ''
            ? strtoupper(trim((string) $data['promo_code']))
            : null;

        $pricing = $pricingService->quote(
            $company,
            $vehicle,
            $startAt,
            $endAt,
            $selectedExtraCodes,
            $requestedPromoCode,
        );

        return [
            'branch' => $branch,
            'vehicle' => $vehicle,
            'startAt' => $startAt,
            'endAt' => $endAt,
            'days' => $pricing['days'],
            'dailyRate' => $pricing['daily_rate'],
            'estimatedTotal' => $pricing['estimated_total'],
            'pricing' => $pricing,
            'availableExtras' => $config->extras($company),
            'selectedExtraCodes' => $selectedExtraCodes,
            'requestedPromoCode' => $requestedPromoCode,
            'promoInvalid' => $requestedPromoCode !== null && $pricing['promo_code'] === null,
        ];
    }

    private function activateTenant(Company $company, TenantContext $tenant): void
    {
        abort_unless(in_array($company->status, ['active', 'trial'], true), 404);

        if (
            $company->status === 'trial'
            && $company->trial_ends_at !== null
            && $company->trial_ends_at->isPast()
        ) {
            abort(404);
        }

        $tenant->set($company);
    }

    private function cancellationDeadline(
        Company $company,
        Reservation $reservation,
        BookingConfig $config,
    ): CarbonImmutable {
        return CarbonImmutable::parse($reservation->start_at, $company->timezone)
            ->subHours($config->cancellationHours($company));
    }

    private function canCancel(
        Company $company,
        Reservation $reservation,
        BookingConfig $config,
    ): bool {
        return in_array($reservation->status, ['pending', 'confirmed'], true)
            && ! $reservation->rental()->exists()
            && CarbonImmutable::now($company->timezone)->lt(
                $this->cancellationDeadline($company, $reservation, $config),
            );
    }

    private function cancellationUrl(
        Company $company,
        Reservation $reservation,
        BookingConfig $config,
    ): ?string {
        if (! $this->canCancel($company, $reservation, $config)) {
            return null;
        }

        $deadline = $this->cancellationDeadline($company, $reservation, $config);

        return URL::temporarySignedRoute(
            'public.booking.cancel.show',
            $deadline,
            ['company' => $company->slug, 'code' => $reservation->code],
        );
    }

    private function activeBranches(Company $company): Collection
    {
        return $company->branches()
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get();
    }
}
