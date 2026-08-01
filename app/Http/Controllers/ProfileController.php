<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

final class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')
            ->with('status', 'profile-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        /** @var User $user */
        $user = $request->user();

        /*
         * Cerrar la sesión ANTES de eliminar el usuario.
         */
        Auth::logout();

        DB::transaction(function () use ($user): void {

            if (DB::getSchemaBuilder()->hasTable('reservations')) {
                DB::table('reservations')
                    ->where('created_by', $user->id)
                    ->update(['created_by' => null]);
            }

            if (DB::getSchemaBuilder()->hasTable('rentals')) {
                DB::table('rentals')
                    ->where('opened_by', $user->id)
                    ->update(['opened_by' => null]);

                DB::table('rentals')
                    ->where('closed_by', $user->id)
                    ->update(['closed_by' => null]);
            }

            if (DB::getSchemaBuilder()->hasTable('inspections')) {
                DB::table('inspections')
                    ->where('inspected_by', $user->id)
                    ->update(['inspected_by' => null]);
            }

            if (DB::getSchemaBuilder()->hasTable('payments')) {
                DB::table('payments')
                    ->where('received_by', $user->id)
                    ->update(['received_by' => null]);
            }

            if (DB::getSchemaBuilder()->hasTable('audit_logs')) {
                DB::table('audit_logs')
                    ->where('user_id', $user->id)
                    ->update(['user_id' => null]);
            }

            $user->syncRoles([]);

            DB::table('users')
                ->where('id', $user->id)
                ->delete();
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
