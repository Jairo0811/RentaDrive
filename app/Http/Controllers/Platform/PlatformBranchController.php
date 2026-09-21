<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\BranchRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Support\Commercial\PlanLimits;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PlatformBranchController extends Controller
{
    public function __construct(private readonly PlanLimits $planLimits) {}

    public function index(Company $company): View
    {
        $branches = $company->branches()
            ->withCount('users')
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get();

        return view('platform.branches.index', compact('company', 'branches'));
    }

    public function create(Company $company): View
    {
        return view('platform.branches.form', [
            'company' => $company,
            'branch' => new Branch,
        ]);
    }

    public function store(BranchRequest $request, Company $company): RedirectResponse
    {
        $this->planLimits->ensureCanAdd($company, 'branches', $company->branches()->count());

        $company->branches()->create([
            ...$request->validated(),
            'is_primary' => false,
            'is_active' => true,
        ]);

        return redirect()
            ->route('platform.companies.branches.index', $company)
            ->with('status', 'Sucursal creada.');
    }

    public function edit(Company $company, Branch $branch): View
    {
        $this->ensureBelongsToCompany($company, $branch);

        return view('platform.branches.form', compact('company', 'branch'));
    }

    public function update(BranchRequest $request, Company $company, Branch $branch): RedirectResponse
    {
        $this->ensureBelongsToCompany($company, $branch);
        $branch->update($request->validated());

        return redirect()
            ->route('platform.companies.branches.index', $company)
            ->with('status', 'Sucursal actualizada.');
    }

    public function toggleStatus(Company $company, Branch $branch): RedirectResponse
    {
        $this->ensureBelongsToCompany($company, $branch);

        if ($branch->is_primary && $branch->is_active) {
            return back()->withErrors([
                'branch' => 'La sucursal principal no puede desactivarse.',
            ]);
        }

        $branch->update(['is_active' => ! $branch->is_active]);

        return back()->with('status', $branch->is_active ? 'Sucursal activada.' : 'Sucursal desactivada.');
    }

    public function destroy(Company $company, Branch $branch): RedirectResponse
    {
        $this->ensureBelongsToCompany($company, $branch);

        if ($branch->is_primary) {
            throw ValidationException::withMessages([
                'branch' => 'La sucursal principal no puede eliminarse.',
            ]);
        }

        $hasRelations = $branch->users()->exists()
            || DB::table('vehicles')->where('branch_id', $branch->getKey())->exists()
            || DB::table('reservations')->where('branch_id', $branch->getKey())->exists()
            || DB::table('rentals')->where('branch_id', $branch->getKey())->exists();

        if ($hasRelations) {
            throw ValidationException::withMessages([
                'branch' => 'La sucursal tiene información relacionada. Desactívala en lugar de eliminarla.',
            ]);
        }

        $branch->delete();

        return redirect()
            ->route('platform.companies.branches.index', $company)
            ->with('status', 'Sucursal eliminada.');
    }

    private function ensureBelongsToCompany(Company $company, Branch $branch): void
    {
        abort_unless($branch->company_id === $company->getKey(), 404);
    }
}
