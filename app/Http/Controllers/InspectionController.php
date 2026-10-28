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
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $data['photos'] = [];
        $disk = (string) config('rentadrive.evidence.disk', 'local');
        abort_unless(in_array($disk, ['local', 's3'], true), 503, 'Almacenamiento privado no configurado.');
        $data['photo_disk'] = $disk;
        $inspection = Inspection::query()->create($data);

        $paths = [];
        try {
            foreach ($request->file('photos', []) as $photo) {
                $paths[] = $photo->store('rentadrive-evidence/'.$inspection->company_id.'/'.$inspection->getKey(), $disk);
            }
            $inspection->update(['photos' => $paths]);
        } catch (\Throwable $exception) {
            foreach ($paths as $path) {
                Storage::disk($disk)->delete($path);
            }
            $inspection->delete();

            throw $exception;
        }

        return redirect()->route('inspections.show', $inspection)->with('status', 'Inspección registrada.');
    }

    public function show(Inspection $inspection): View
    {
        $inspection->load(['rental.customer', 'vehicle.model.brand', 'inspector']);

        return view('inspections.show', compact('inspection'));
    }

    public function photo(Inspection $inspection, int $index): StreamedResponse
    {
        $photos = $inspection->photos ?? [];
        abort_unless(array_key_exists($index, $photos), 404);
        $path = $photos[$index];
        abort_unless(is_string($path), 404);

        // Compatibilidad con registros legacy: su carpeta pública debe migrarse y depurarse.
        $disk = str_starts_with($path, 'rentadrive-evidence/')
            ? ($inspection->photo_disk ?: 'local')
            : 'public';
        abort_unless(in_array($disk, ['local', 's3', 'public'], true), 404);
        abort_unless(Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->response($path, null, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(Inspection $inspection): RedirectResponse
    {
        throw ValidationException::withMessages([
            'inspection' => 'Las inspecciones ya registradas forman parte del expediente auditable y no se eliminan.',
        ]);
    }
}
