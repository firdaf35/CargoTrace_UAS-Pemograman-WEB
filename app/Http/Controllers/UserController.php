<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): View
    {
        return view('users.index', ['users' => User::orderBy('role')->orderBy('name')->paginate(15), 'roles' => Role::cases()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'username' => ['nullable', 'alpha_dash', 'max:50', 'unique:users,username'],
            'role' => ['required', Rule::in(Role::values())],
            'password' => ['required', Password::min(8)],
        ]);
        $user = new User(collect($data)->except('role')->all());
        $user->role = Role::from($data['role']);
        $user->save();
        $this->audit->log($request->user(), 'user.created', $user, null, $user->only(['name', 'email', 'role']));

        return redirect()->route('users.index')->with('success', 'User created.');
    }

    public function edit(User $user): View
    {
        return view('users.edit', ['user' => $user, 'roles' => Role::cases()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user)],
            'username' => ['nullable', 'alpha_dash', 'max:50', Rule::unique('users', 'username')->ignore($user)],
            'role' => ['required', Rule::in(Role::values())],
            'password' => ['nullable', Password::min(8)],
        ]);

        if ($user->id === $request->user()->id && $data['role'] !== Role::ADMIN->value) {
            return back()->withErrors(['role' => 'You cannot remove your own administrator role.']);
        }

        $old = $user->only(['name', 'email', 'role']);
        $user->fill(collect($data)->except(['role', 'password'])->all());
        $user->role = Role::from($data['role']);
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();
        $this->audit->log($request->user(), 'user.updated', $user, $old, $user->only(['name', 'email', 'role']));

        return redirect()->route('users.index')->with('success', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }
        if ($user->shipments()->withTrashed()->exists()) {
            return back()->withErrors(['user' => 'This user owns shipments and cannot be deleted.']);
        }
        $this->audit->log($request->user(), 'user.deleted', $user, $user->only(['name', 'email']), null);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted.');
    }
}
