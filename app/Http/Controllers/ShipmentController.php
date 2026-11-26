<?php

namespace App\Http\Controllers;

use App\Enums\ShipmentStatus;
use App\Http\Requests\StoreShipmentRequest;
use App\Http\Requests\UpdateShipmentRequest;
use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use App\Services\JourneyService;
use App\Services\ShipmentService;
use App\Services\TrackingService;
use App\Services\WeatherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShipmentController extends Controller
{
    public function __construct(private ShipmentService $shipments, private WeatherService $weather) {}

    private function filters(Request $request): array
    {
        $request->validate([
            'from' => ['nullable', 'date'], 'to' => ['nullable', 'date'],
            'carrier_id' => ['nullable', 'integer'], 'origin_id' => ['nullable', 'integer'], 'destination_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'max:20'], 'q' => ['nullable', 'string', 'max:100'],
            'mode' => ['nullable', 'in:sea,air,land'], 'customer_id' => ['nullable', 'integer'],
        ]);

        return $request->only(['q', 'status', 'mode', 'customer_id', 'carrier_id', 'origin_id', 'destination_id', 'from', 'to', 'delayed']);
    }

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        return view('shipments.index', [
            'shipments' => $this->shipments->search($request->user(), $filters, $request->input('sort'), $request->input('dir')),
            'filters' => $filters,
            'sort' => $request->input('sort', 'created_at'),
            'dir' => $request->input('dir', 'desc'),
            'carriers' => Carrier::orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
            'statuses' => array_merge(ShipmentStatus::values(), ['DELAYED']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $query = Shipment::query()->visibleTo($request->user())->filter($filters)->with(['customer', 'carrier', 'origin', 'destination'])->orderByDesc('id');

        // Prevent CSV/formula injection when opened in a spreadsheet.
        $safe = fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v;

        return response()->streamDownload(function () use ($query, $safe) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Shipment No', 'Customer', 'Carrier', 'Origin', 'Destination', 'Cargo', 'Weight kg', 'Expected arrival', 'ETA', 'Status', 'Delay hours', 'Severity']);
            $query->chunk(200, function ($rows) use ($out, $safe) {
                foreach ($rows as $s) {
                    fputcsv($out, array_map($safe, [
                        $s->shipment_number, $s->customer->name, $s->carrier->name, $s->origin->name, $s->destination->name,
                        $s->cargo_type, $s->weight_kg, $s->expected_arrival?->format('Y-m-d H:i'), $s->estimated_arrival?->format('Y-m-d H:i'),
                        $s->display_status, $s->delay_hours, $s->delay_severity?->value,
                    ]));
                }
            });
            fclose($out);
        }, 'shipments-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function create(): View
    {
        Gate::authorize('create', Shipment::class);

        return view('shipments.create', $this->formData(new Shipment));
    }

    public function store(StoreShipmentRequest $request): RedirectResponse
    {
        $shipment = $this->shipments->create($request->validated(), $request->user());

        return redirect()->route('shipments.show', $shipment)->with('success', "Shipment {$shipment->shipment_number} created.");
    }

    public function show(Shipment $shipment, Request $request): View
    {
        Gate::authorize('view', $shipment);

        $shipment->load(['customer', 'carrier', 'origin', 'destination', 'items', 'trackingEvents.creator', 'exceptions.reporter']);

        $at = $shipment->latestLocatedEvent();
        $weather = $at ? $this->weather->current($at->latitude, $at->longitude) : $this->weather->current($shipment->origin->latitude, $shipment->origin->longitude);

        return view('shipments.show', [
            'shipment' => $shipment,
            'map' => $shipment->mapData(),
            'journey' => app(JourneyService::class)->build($shipment),
            'weather' => $weather,
            'weatherPlace' => $at?->location_name ?? $shipment->origin->name,
            'eventTypes' => TrackingService::allowedEventTypes($shipment),
            'locations' => Location::orderBy('name')->get(),
            'canEdit' => $request->user()->can('update', $shipment),
        ]);
    }

    public function edit(Shipment $shipment): View
    {
        Gate::authorize('update', $shipment);

        return view('shipments.edit', $this->formData($shipment));
    }

    public function update(UpdateShipmentRequest $request, Shipment $shipment): RedirectResponse
    {
        $this->shipments->update($shipment, $request->validated(), $request->user());

        return redirect()->route('shipments.show', $shipment)->with('success', 'Shipment updated.');
    }

    public function destroy(Request $request, Shipment $shipment): RedirectResponse
    {
        Gate::authorize('delete', $shipment);
        $this->shipments->delete($shipment, $request->user());

        return redirect()->route('shipments.index')->with('success', 'Shipment deleted.');
    }

    private function formData(Shipment $shipment): array
    {
        return [
            'shipment' => $shipment,
            'customers' => User::where('role', 'customer')->orderBy('name')->get(),
            'carriers' => Carrier::where('is_active', true)->orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
        ];
    }
}
