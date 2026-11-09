<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * SRS FR14: admins create, deactivate and manage user accounts and roles.
 *
 * Every route here sits behind the admin role. Two guardrails protect against
 * locking everyone out: an admin cannot deactivate or demote themselves, and
 * the last active admin can never be deactivated or demoted by anyone.
 */
class UserController extends Controller
{
    public function index()
    {
        $users = User::orderByRaw('deactivated_at IS NOT NULL')
            ->orderBy('name')
            ->withCount(['analyses', 'featureRegistries'])
            ->get();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create', ['roles' => User::ROLES]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = new User();
        // role is deliberately not mass assignable, so a user can never grant
        // themselves a role through the profile form; it is set explicitly here.
        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ])->save();

        return redirect()->route('admin.users.index')
            ->with('success', "Account created for {$user->name}.");
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', ['user' => $user, 'roles' => User::ROLES]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
        ]);

        if ($validated['role'] !== $user->role) {
            $this->guardRoleChange($request, $user);
        }

        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ])->save();

        return redirect()->route('admin.users.index')
            ->with('success', "{$user->name} updated.");
    }

    public function deactivate(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            throw ValidationException::withMessages(['user' => 'You cannot deactivate your own account.']);
        }

        if ($user->isLastActiveAdmin()) {
            throw ValidationException::withMessages(['user' => 'This is the only active admin. Assign another admin before deactivating this account.']);
        }

        DB::transaction(function () use ($user) {
            $user->forceFill(['deactivated_at' => now()])->save();

            // Remember-me tokens would otherwise keep signing the user back in.
            $user->forceFill(['remember_token' => null])->save();

            // With database sessions, ending them here removes access at once.
            // Under any other driver EnsureUserIsActive still signs the user
            // out on their next request.
            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            }
        });

        return back()->with('success', "{$user->name} has been deactivated and signed out.");
    }

    public function reactivate(User $user)
    {
        $user->forceFill(['deactivated_at' => null])->save();

        return back()->with('success', "{$user->name} has been reactivated.");
    }

    protected function guardRoleChange(Request $request, User $user): void
    {
        if ($user->is($request->user())) {
            throw ValidationException::withMessages(['role' => 'You cannot change your own role.']);
        }

        if ($user->isLastActiveAdmin()) {
            throw ValidationException::withMessages(['role' => 'This is the only active admin. Assign another admin before changing this role.']);
        }
    }
}
