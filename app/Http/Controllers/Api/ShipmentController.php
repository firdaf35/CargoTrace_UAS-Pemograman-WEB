<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShipmentRequest;
use App\Http\Requests\UpdateShipmentRequest;
use App\Http\Resources\ShipmentResource;
use App\Models\Shipment;
use App\Services\ShipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ShipmentController extends Controller
{
    public function __construct(private ShipmentService $shipments) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'from' => ['nullable', 'date'], 'to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'dir' => ['nullable', 'in:asc,desc'], 'mode' => ['nullable', 'in:sea,air,land'],
        ]);

        $paginator = $this->shipments->search(
            $request->user(),
            $request->only(['q', 'status', 'mode', 'customer_id', 'carrier_id', 'origin_id', 'destination_id', 'from', 'to', 'delayed']),
            $request->input('sort'),
            $request->input('dir'),
            (int) $request->input('per_page', 10)
        );

        return ShipmentResource::collection($paginator);
    }

    public function store(StoreShipmentRequest $request): JsonResponse
    {
        $shipment = $this->shipments->create($request->validated(), $request->user());

        return (new ShipmentResource($shipment->load(['customer', 'carrier', 'origin', 'destination', 'items'])))
            ->response()->setStatusCode(201);
    }

    public function show(Shipment $shipment): ShipmentResource
    {
        Gate::authorize('view', $shipment);

        return new ShipmentResource($shipment->load(['customer', 'carrier', 'origin', 'destination', 'items']));
    }

    public function update(UpdateShipmentRequest $request, Shipment $shipment): ShipmentResource
    {
        $shipment = $this->shipments->update($shipment, $request->validated(), $request->user());

        return new ShipmentResource($shipment->load(['customer', 'carrier', 'origin', 'destination', 'items']));
    }

    public function destroy(Request $request, Shipment $shipment): JsonResponse
    {
        Gate::authorize('delete', $shipment);
        $this->shipments->delete($shipment, $request->user());

        return response()->json(['message' => 'Shipment deleted.']);
    }
}
