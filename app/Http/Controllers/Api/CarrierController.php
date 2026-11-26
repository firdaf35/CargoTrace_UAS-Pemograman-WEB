<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarrierController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Carrier::orderBy('name')->paginate(25));
    }

    /** Route is protected by role:admin. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:carriers,name'],
            'code' => ['required', 'string', 'max:10', 'unique:carriers,code'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return response()->json(['data' => Carrier::create($data + ['is_active' => true])], 201);
    }
}
