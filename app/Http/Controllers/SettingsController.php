<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): View
    {
        return view('settings.index', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:150'],
            'current_password' => ['required_with:password', 'nullable', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        $user->fill(['name' => $data['name'], 'company' => $data['company'] ?? null]);
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();
        $this->audit->log($user, 'user.profile_updated', $user, null, ['name' => $user->name, 'password_changed' => ! empty($data['password'])]);

        return back()->with('success', 'Profile updated.');
    }
}
