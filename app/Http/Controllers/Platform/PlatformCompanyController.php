<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Domain\Security\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\CompanyStoreRequest;
use App\Http\Requests\Platform\CompanyUpdateRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class PlatformCompanyController extends Controller
{
    public function index(Request $request): View
    {
        $statuses = ['trial', 'active', 'suspended', 'cancelled'];

        $companies = Company::query()
            ->withCount(['branches', 'users'])
            ->when($request->string('q')->isNotEmpty(), function ($query) use ($request): void {
                $search = '%'.$request->string('q')->value().'%';
                $query->where(fn ($query) => $query
                    ->where('name', 'like', $search)
                    ->orWhere('legal_name', 'like', $search)
                    ->orWhere('rnc', 'like', $search)
                    ->orWhere('slug', 'like', $search));
            })
            ->when(
                in_array($request->string('status')->value(), $statuses, true),
                fn ($query) => $query->where('status', $request->string('status')->value()),
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('platform.companies.index', compact('companies'));
    }

    public function create(): View
    {
        return view('platform.companies.form', ['company' => new Company]);
    }

    public function store(CompanyStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $company = DB::transaction(function () use ($data): Company {
            $status = $data['status'];
            $trialEndsAt = $status === 'trial'
                ? now()->addDays((int) config('rentadrive.trial_days', 14))
                : null;

            $company = Company::query()->create([
                'name' => $data['name'],
                'legal_name' => $data['legal_name'] ?? null,
                'rnc' => $data['rnc'] ?? null,
                'slug' => $data['slug'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'currency' => $data['currency'],
                'timezone' => $data['timezone'],
                'status' => $status,
                'plan_code' => $data['plan_code'],
                'trial_ends_at' => $trialEndsAt,
            ]);

            $branch = Branch::query()->create([
                'company_id' => $company->getKey(),
                'name' => $data['branch_name'],
                'code' => $data['branch_code'],
                'address' => $data['branch_address'] ?? null,
                'city' => $data['branch_city'] ?? null,
                'phone' => $data['branch_phone'] ?? null,
                'email' => $data['branch_email'] ?? null,
                'is_primary' => true,
                'is_active' => true,
            ]);

            $administrator = User::query()->create([
                'company_id' => $company->getKey(),
                'branch_id' => $branch->getKey(),
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'email_verified_at' => now(),
                'password' => $data['admin_password'],
                'is_active' => true,
                'is_platform_admin' => false,
            ]);

            $administrator->syncRoles([RoleName::ADMINISTRATOR->value]);

            return $company;
        });

        return redirect()
            ->route('platform.companies.edit', $company)
            ->with('status', 'Empresa creada y administrador inicial provisionado.');
    }

    public function edit(Company $company): View
    {
        return view('platform.companies.form', compact('company'));
    }

    public function update(CompanyUpdateRequest $request, Company $company): RedirectResponse
    {
        $data = $request->validated();

        if ($data['status'] !== 'trial') {
            $data['trial_ends_at'] = null;
        }

        $company->update($data);

        return redirect()
            ->route('platform.companies.edit', $company)
            ->with('status', 'Empresa actualizada.');
    }

    public function suspend(Company $company): RedirectResponse
    {
        $company->update(['status' => 'suspended']);

        return back()->with('status', 'Empresa suspendida. Sus usuarios ya no pueden entrar al tenant.');
    }

    public function activate(Company $company): RedirectResponse
    {
        $company->update([
            'status' => 'active',
            'trial_ends_at' => null,
        ]);

        return back()->with('status', 'Empresa reactivada.');
    }
}
