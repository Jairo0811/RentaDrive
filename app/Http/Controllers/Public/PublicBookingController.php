<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Operations\Services\ReferenceNumberService;
use App\Domain\Operations\Services\ReservationAvailabilityService;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Rules\DominicanCedula;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class PublicBookingController extends Controller
{
    public function show(Company $company, TenantContext $tenant): View
    {
        $this->activateTenant($company, $tenant);

        return view('public.booking', [
            'company' => $company,
            'branches' => $this->activeBranches($company),
            'categories' => VehicleCategory::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'vehicles' => collect(),
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

        return view('public.booking', [
            'company' => $company,
            'branches' => $this->activeBranches($company),
            'categories' => VehicleCategory::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'vehicles' => $vehicles,
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
    ): View {
        $this->activateTenant($company, $tenant);
        $quote = $this->resolveQuote($request, $company, $tenant, $availability);

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
    ): RedirectResponse {
        $this->activateTenant($company, $tenant);
        $quote = $this->resolveQuote($request, $company, $tenant, $availability);

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
            'license_number' => ['nullable', 'string', 'max:50'],
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
        ): Reservation {
            $customer = Customer::query()->firstOrCreate(
                ['document_number' => $customerData['document_number']],
                [
                    'document_type' => $customerData['document_type'],
                    'first_name' => $customerData['first_name'],
                    'last_name' => $customerData['last_name'],
                    'email' => $customerData['email'],
                    'phone' => $customerData['phone'],
                    'license_number' => $customerData['license_number'] ?? null,
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
                'vehicle_category_id' => $vehicle->vehicle_category_id,
                'vehicle_id' => $vehicle->getKey(),
                'start_at' => $quote['startAt'],
                'end_at' => $quote['endAt'],
                'pickup_location' => $branch->name,
                'return_location' => $branch->name,
                'daily_rate' => $quote['dailyRate'],
                'estimated_total' => $quote['estimatedTotal'],
                'status' => 'pending',
                'notes' => 'Reserva creada desde el portal público.',
                'created_by' => null,
            ]);
        });

        return redirect()->route('public.booking.confirmation', [
            'company' => $company->slug,
            'code' => $reservation->code,
        ]);
    }

    public function confirmation(
        Company $company,
        string $code,
        TenantContext $tenant,
    ): View {
        $this->activateTenant($company, $tenant);

        $reservation = Reservation::query()
            ->with(['vehicle.model.brand', 'category'])
            ->where('code', $code)
            ->firstOrFail();

        return view('public.confirmation', compact('company', 'reservation'));
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
     * @return array{
     *     branch: Branch,
     *     vehicle: Vehicle,
     *     startAt: CarbonImmutable,
     *     endAt: CarbonImmutable,
     *     days: int,
     *     dailyRate: float,
     *     estimatedTotal: float
     * }
     */
    private function resolveQuote(
        Request $request,
        Company $company,
        TenantContext $tenant,
        ReservationAvailabilityService $availability,
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

        $days = $availability->rentalDays($startAt, $endAt);
        $dailyRate = $vehicle->effective_daily_rate;
        $estimatedTotal = round($days * $dailyRate, 2);

        return compact(
            'branch',
            'vehicle',
            'startAt',
            'endAt',
            'days',
            'dailyRate',
            'estimatedTotal',
        );
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

    private function activeBranches(Company $company)
    {
        return $company->branches()
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get();
    }
}
