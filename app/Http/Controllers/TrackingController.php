<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTrackingEventRequest;
use App\Models\Shipment;
use App\Enums\TransportMode;
use App\Models\TrackingEvent;
use App\Services\TrackingMapService;
use Illuminate\Http\JsonResponse;
use App\Services\TrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackingController extends Controller
{
    public function __construct(private TrackingService $tracking) {}

    /** Tracking feed: latest events of all visible shipments + search by shipment number. */
    public function index(Request $request): View
    {
        $request->validate(['q' => ['nullable', 'string', 'max:50']]);
        $user = $request->user();
        $q = trim((string) $request->input('q'));

        $selected = $q !== '' ? Shipment::query()->visibleTo($user)->where('shipment_number', $q)->value('id') : null;

        $events = TrackingEvent::query()
            ->whereHas('shipment', fn ($s) => $s->visibleTo($user)->when($q !== '', fn ($w) => $w->where('shipment_number', 'like', "%{$q}%")))
            ->with(['shipment', 'creator'])
            ->orderByDesc('occurred_at')->orderByDesc('id')
            ->paginate(10)->withQueryString();

        return view('tracking.index', [
            'events' => $events, 'q' => $q, 'selected' => $selected,
            'modes' => TransportMode::cases(),
            'statuses' => array_merge(\App\Enums\ShipmentStatus::values(), ['DELAYED']),
        ]);
    }

    /** JSON for the world map (database data only; positions are last-reported, not live GPS). */
    public function data(Request $request, TrackingMapService $map): JsonResponse
    {
        $f = $request->validate([
            'mode' => ['nullable', 'in:sea,air,land'], 'status' => ['nullable', 'string', 'max:20'], 'q' => ['nullable', 'string', 'max:50'],
        ]);

        return response()->json(['data' => $map->payload($request->user(), $f), 'generated_at' => now()->toIso8601String()]);
    }

    public function store(StoreTrackingEventRequest $request, Shipment $shipment): RedirectResponse
    {
        $this->tracking->addEvent($shipment, $request->validated(), $request->user());

        return redirect()->route('shipments.show', $shipment)->with('success', 'Tracking event added.');
    }
}
