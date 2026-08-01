<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\InspectionRequest;
use App\Models\Inspection;
use App\Models\Rental;
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
        return view('inspections.form', [
            'inspection' => new Inspection,
            'rentals' => Rental::query()->with(['customer', 'vehicle.model.brand'])->latest()->get(),
            'selectedRental' => $request->integer('rental') ?: null,
        ]);
    }

    public function store(InspectionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        /** @var Rental $rental */
        $rental = Rental::query()->with('vehicle')->findOrFail($data['rental_id']);

        if (Inspection::query()->where('rental_id', $rental->id)->where('type', $data['type'])->exists()) {
            throw ValidationException::withMessages([
                'type' => 'Este alquiler ya tiene una inspección de ese tipo.',
            ]);
        }

        $data['vehicle_id'] = $rental->vehicle_id;
        $data['inspected_by'] = auth()->id();
        $data['photos'] = $this->storePhotos($request);
        $inspection = Inspection::query()->create($data);

        return redirect()->route('inspections.show', $inspection)->with('status', 'Inspección registrada.');
    }

    public function show(Inspection $inspection): View
    {
        $inspection->load(['rental.customer', 'vehicle.model.brand', 'inspector']);

        return view('inspections.show', compact('inspection'));
    }

    public function destroy(Inspection $inspection): RedirectResponse
    {
        foreach ($inspection->photos ?? [] as $path) {
            Storage::disk('public')->delete($path);
        }

        $inspection->delete();

        return redirect()->route('inspections.index')->with('status', 'Inspección eliminada.');
    }

    /**
     * @return array<int, string>
     */
    private function storePhotos(InspectionRequest $request): array
    {
        $paths = [];

        foreach ($request->file('photos', []) as $photo) {
            $paths[] = $photo->store('inspections', 'public');
        }

        return $paths;
    }
}
