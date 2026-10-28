<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Fiscal\Services\FiscalDraftService;
use App\Models\FiscalDraft;
use App\Models\FiscalProfile;
use App\Models\Invoice;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class FiscalController extends Controller
{
    public function edit(TenantContext $tenant): View
    {
        return view('fiscal.edit', [
            'profile' => FiscalProfile::query()->where('company_id', $tenant->companyId())->first(),
            'drafts' => FiscalDraft::query()->with('invoice')->latest()->paginate(10),
        ]);
    }

    public function update(Request $request, TenantContext $tenant): RedirectResponse
    {
        $data = $request->validate([
            'legal_name' => ['required', 'string', 'max:255'],
            'rnc' => ['required', 'regex:/^\d{9}$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'fiscal_mode' => ['required', Rule::in(['standard', 'exempt'])],
        ]);

        FiscalProfile::query()->updateOrCreate(
            ['company_id' => $tenant->companyId()],
            $data,
        );

        return back()->with('status', 'Perfil fiscal guardado. RNC no verificado ante DGII; emisión e-CF no habilitada.');
    }

    public function prepare(Request $request, Invoice $invoice, FiscalDraftService $service): RedirectResponse
    {
        $data = $request->validate(['kind' => ['required', Rule::in(['consumer', 'credit'])]]);
        $service->prepare($invoice, $data['kind']);

        return back()->with('status', 'Borrador fiscal generado. No constituye un NCF ni e-CF autorizado.');
    }
}
