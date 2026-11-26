<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) ($request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? ''));

        $customers = User::query()->where('role', 'customer')
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")->orWhere('company', 'like', "%{$q}%")))
            ->withCount(['shipments', 'shipments as delayed_count' => fn ($s) => $s->where('is_delayed', true), 'shipments as active_count' => fn ($s) => $s->whereNotIn('status', ['DELIVERED', 'CANCELLED'])])
            ->orderBy('company')->orderBy('name')->paginate(12)->withQueryString();

        return view('customers.index', ['customers' => $customers, 'q' => $q]);
    }
}
