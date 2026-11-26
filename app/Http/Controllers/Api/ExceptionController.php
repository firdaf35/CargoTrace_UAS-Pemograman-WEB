<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExceptionRequest;
use App\Http\Requests\UpdateExceptionRequest;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Services\ExceptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ExceptionController extends Controller
{
    public function __construct(private ExceptionService $exceptions) {}

    public function index(Shipment $shipment): JsonResponse
    {
        Gate::authorize('view', $shipment);

        return response()->json(['data' => $shipment->exceptions()->get()]);
    }

    public function store(StoreExceptionRequest $request, Shipment $shipment): JsonResponse
    {
        $exception = $this->exceptions->create($shipment, $request->validated(), $request->user());

        return response()->json(['data' => $exception->refresh()], 201);
    }

    public function update(UpdateExceptionRequest $request, ShipmentException $exception): JsonResponse
    {
        return response()->json(['data' => $this->exceptions->update($exception, $request->validated(), $request->user())]);
    }
}
