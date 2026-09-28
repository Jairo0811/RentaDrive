<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\InspectionRequest;
use App\Models\Inspection;
use App\Models\Rental;
use App\Support\DigitalRental\InspectionEvidenceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class InspectionController extends Controller
{
    public function index(Request $request): View
    {
        $inspections = Inspection::query()
            ->with(['rental.customer', 'vehicle.model.brand', 'inspector'])
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->latest('inspected_at')
            ->paginate(15)
            ->withQueryString();

        return view('inspections.index', compact('inspections'));
    }

    public function create(Request $request): View
    {
        return $this->formView(
            $request->integer('rental') ?: null,
            $request->string('type')->value() ?: null,
            $request->string('mode')->value() ?: 'standard',
        );
    }

    public function mobileDelivery(Rental $rental): View
    {
        $this->assertOpenRental($rental);

        return $this->formView($rental->getKey(), 'delivery', 'mobile');
    }

    public function mobileReturn(Rental $rental): View
    {
        $this->assertOpenRental($rental);

        return $this->formView($rental->getKey(), 'return', 'mobile');
    }

    public function store(
        InspectionRequest $request,
        InspectionEvidenceService $evidence,
    ): RedirectResponse {
        $data = $request->validated();

        /** @var Rental $rental */
        $rental = Rental::query()->with('vehicle')->findOrFail($data['rental_id']);
        $this->assertOpenRental($rental);

        if (Inspection::query()->where('rental_id', $rental->id)->where('type', $data['type'])->exists()) {
            throw ValidationException::withMessages([
                'type' => 'Este alquiler ya tiene una inspección de ese tipo.',
            ]);
        }

        if ((int) $data['mileage'] < (int) $rental->opening_mileage) {
            throw ValidationException::withMessages([
                'mileage' => 'El kilometraje de la inspección no puede ser menor que el kilometraje de salida.',
            ]);
        }

        $damageItems = $this->damageItems($data);
        unset(
            $data['mode'],
            $data['damage_area'],
            $data['damage_severity'],
            $data['damage_description'],
        );

        $data['vehicle_id'] = $rental->vehicle_id;
        $data['inspected_by'] = auth()->id();
        $data['photos'] = $this->storePhotos($request, $rental);
        $data['damage_items'] = $damageItems;

        $inspection = Inspection::query()->create($data);
        $inspection = $evidence->seal($inspection);

        return redirect()
            ->route('inspections.show', $inspection)
            ->with('status', 'Inspección registrada y evidencia sellada.');
    }

    public function show(Inspection $inspection): View
    {
        $inspection->load(['rental.customer', 'vehicle.model.brand', 'inspector']);

        return view('inspections.show', compact('inspection'));
    }

    public function destroy(
        Inspection $inspection,
        InspectionEvidenceService $evidence,
    ): RedirectResponse {
        $evidence->assertCanDelete($inspection);

        foreach ($inspection->photos ?? [] as $path) {
            Storage::disk('public')->delete($path);
        }

        $inspection->delete();

        return redirect()->route('inspections.index')->with('status', 'Inspección eliminada.');
    }

    private function formView(?int $rentalId, ?string $type, string $mode): View
    {
        $rentals = Rental::query()
            ->with(['customer', 'vehicle.model.brand'])
            ->where('status', 'open')
            ->latest()
            ->get();

        return view('inspections.form', [
            'inspection' => new Inspection,
            'rentals' => $rentals,
            'selectedRental' => $rentalId,
            'fixedType' => in_array($type, ['delivery', 'return'], true) ? $type : null,
            'mode' => $mode === 'mobile' ? 'mobile' : 'standard',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{area:string,severity:string,description:string}>
     */
    private function damageItems(array $data): array
    {
        $areas = (array) ($data['damage_area'] ?? []);
        $severities = (array) ($data['damage_severity'] ?? []);
        $descriptions = (array) ($data['damage_description'] ?? []);
        $items = [];

        foreach ($areas as $index => $area) {
            $area = trim((string) $area);
            $description = trim((string) ($descriptions[$index] ?? ''));

            if ($area === '' && $description === '') {
                continue;
            }

            $items[] = [
                'area' => $area !== '' ? $area : 'Sin área especificada',
                'severity' => (string) ($severities[$index] ?? 'minor'),
                'description' => $description,
            ];
        }

        return $items;
    }

    /**
     * @return array<int, string>
     */
    private function storePhotos(InspectionRequest $request, Rental $rental): array
    {
        $paths = [];

        foreach ($request->file('photos', []) as $photo) {
            $paths[] = $photo->store(
                'inspections/'.$rental->company_id.'/'.$rental->code,
                'public',
            );
        }

        return $paths;
    }

    private function assertOpenRental(Rental $rental): void
    {
        if ($rental->status !== 'open') {
            throw ValidationException::withMessages([
                'rental' => 'Solo los alquileres abiertos admiten check-in/check-out.',
            ]);
        }
    }
}
