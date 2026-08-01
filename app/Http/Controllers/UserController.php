<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

final class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->with('roles')
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
        $data = $request->validated();
        $role = $data['role'];
        unset($data['role'], $data['password_confirmation']);

        $user = User::query()->create($data);
        $user->syncRoles([$role]);

        return redirect()->route('users.index')->with('status', 'Usuario creado.');
    }

    public function edit(User $user): View
    {
        return $this->formView($user);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
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
        Gate::authorize('delete', $user);
        $user->delete();

        return redirect()->route('users.index')->with('status', 'Usuario eliminado.');
    }

    private function formView(User $user): View
    {
        return view('users.form', [
            'user' => $user,
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }
}
