<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Branch;
use App\Models\User;
use App\Support\Commercial\PlanLimits;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

final class UserController extends Controller
{
    public function __construct(private readonly PlanLimits $planLimits) {}

    public function index(Request $request): View
    {
        $users = User::query()
            ->where('company_id', $request->user()?->company_id)
            ->with(['roles', 'branch'])
            ->when($request->string('q')->isNotEmpty(), function ($query) use ($request): void {
                $search = '%'.$request->string('q')->value().'%';
                $query->where(fn ($query) => $query
                    ->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        return $this->formView(new User);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $company = $request->user()?->company;
        abort_unless($company !== null, 403);

        $this->planLimits->ensureCanAdd($company, 'users', $company->users()->count());

        $data = $request->validated();
        $role = $data['role'];
        unset($data['role'], $data['password_confirmation']);

        $data['company_id'] = $company->getKey();

        $user = User::query()->create($data);
        $user->syncRoles([$role]);

        return redirect()->route('users.index')->with('status', 'Usuario creado.');
    }

    public function edit(User $user): View
    {
        $this->ensureSameCompany($user);

        return $this->formView($user);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $this->ensureSameCompany($user);

        $data = $request->validated();
        $role = $data['role'];
        unset($data['role'], $data['password_confirmation']);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);
        $user->syncRoles([$role]);

        return redirect()->route('users.index')->with('status', 'Usuario actualizado.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->ensureSameCompany($user);
        Gate::authorize('delete', $user);
        $user->delete();

        return redirect()->route('users.index')->with('status', 'Usuario eliminado.');
    }

    private function formView(User $user): View
    {
        $companyId = auth()->user()?->company_id;

        return view('users.form', [
            'user' => $user,
            'roles' => Role::query()->orderBy('name')->get(),
            'branches' => Branch::query()
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->orderByDesc('is_primary')
                ->orderBy('name')
                ->get(),
        ]);
    }

    private function ensureSameCompany(User $user): void
    {
        abort_unless(
            auth()->user() instanceof User && auth()->user()->company_id === $user->company_id,
            404,
        );
    }
}
