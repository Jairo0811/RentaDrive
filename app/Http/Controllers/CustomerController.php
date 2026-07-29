<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = Customer::query()
            ->withCount(['reservations', 'rentals'])
            ->when($request->string('q')->isNotEmpty(), function ($query) use ($request): void {
                $search = '%'.$request->string('q')->value().'%';
                $query->where(function ($query) use ($search): void {
                    $query->where('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search)
                        ->orWhere('document_number', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhere('phone', 'like', $search);
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('customers.form', ['customer' => new Customer()]);
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        $customer = Customer::query()->create($request->validated());

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', 'Cliente registrado correctamente.');
    }

    public function show(Customer $customer): View
    {
        $customer->load([
            'reservations' => fn ($query) => $query->latest()->limit(10),
            'rentals' => fn ($query) => $query->with('vehicle.model.brand')->latest()->limit(10),
            'invoices' => fn ($query) => $query->latest('issued_at')->limit(10),
        ]);

        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer): View
    {
        return view('customers.form', compact('customer'));
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', 'Cliente actualizado correctamente.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->reservations()->exists() || $customer->rentals()->exists()) {
            throw ValidationException::withMessages([
                'customer' => 'El cliente tiene operaciones relacionadas. Suspéndelo en lugar de eliminarlo.',
            ]);
        }

        $customer->delete();

        return redirect()->route('customers.index')->with('status', 'Cliente eliminado.');
    }
}
