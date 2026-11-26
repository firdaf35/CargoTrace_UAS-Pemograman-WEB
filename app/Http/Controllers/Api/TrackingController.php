<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrackingEventRequest;
use App\Models\Shipment;
use App\Services\TrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class TrackingController extends Controller
{
    public function __construct(private TrackingService $tracking) {}

    public function index(Shipment $shipment): JsonResponse
    {
        Gate::authorize('view', $shipment);
        $shipment->load('trackingEvents.creator:id,name');
        $current = $shipment->latestLocatedEvent();

        return response()->json([
            'data' => $shipment->trackingEvents->map(fn ($e) => [
                'id' => $e->id,
                'event_type' => $e->event_type,
                'status_after' => $e->status_after,
                'location_name' => $e->location_name,
                'latitude' => $e->latitude,
                'longitude' => $e->longitude,
                'occurred_at' => $e->occurred_at,
                'description' => $e->description,
                'is_exception' => $e->is_exception,
                'created_by' => $e->creator?->name,
            ])->values(),
            'current_location' => $current ? ['name' => $current->location_name, 'latitude' => $current->latitude, 'longitude' => $current->longitude, 'as_of' => $current->occurred_at] : null,
        ]);
    }

    public function store(StoreTrackingEventRequest $request, Shipment $shipment): JsonResponse
    {
        $event = $this->tracking->addEvent($shipment, $request->validated(), $request->user());

        return response()->json([
            'data' => $event->only(['id', 'shipment_id', 'event_type', 'status_after', 'location_name', 'latitude', 'longitude', 'occurred_at', 'description']),
            'shipment_status' => $shipment->refresh()->status->value,
        ], 201);
    }
}
