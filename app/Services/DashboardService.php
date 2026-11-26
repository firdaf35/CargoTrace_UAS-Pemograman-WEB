<?php

namespace App\Services;

use App\Enums\ExceptionStatus;
use App\Enums\ShipmentStatus;
use App\Enums\TransportMode;
use App\Models\AppNotification;
use App\Models\TrackingEvent;
use App\Models\Carrier;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;

/** All dashboard numbers come from the database (no static values). */
class DashboardService
{
    /** @return array<string, mixed> */
    public function summary(User $user): array
    {
        $base = fn () => Shipment::query()->visibleTo($user);

        $total = $base()->count();
        $delayed = $base()->where('is_delayed', true)->count();
        $openExceptions = ShipmentException::query()
            ->whereIn('status', [ExceptionStatus::OPEN->value, ExceptionStatus::INVESTIGATING->value])
            ->whereHas('shipment', fn ($q) => $q->visibleTo($user))
            ->count();

        $byStatus = $base()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        $statusChart = [
            'labels' => ['Created', 'Booked', 'Picked up', 'In transit', 'Delivered', 'Delayed', 'Open exceptions'],
            'values' => [
                (int) ($byStatus['CREATED'] ?? 0),
                (int) ($byStatus['BOOKED'] ?? 0),
                (int) ($byStatus['PICKED_UP'] ?? 0),
                (int) ($byStatus['IN_TRANSIT'] ?? 0),
                (int) ($byStatus['DELIVERED'] ?? 0),
                $delayed,
                $openExceptions,
            ],
        ];

        $carriers = Carrier::query()->orderBy('name')->get()->map(function (Carrier $c) use ($user) {
            $q = fn () => Shipment::query()->visibleTo($user)->where('carrier_id', $c->id);

            return ['name' => $c->name, 'total' => $q()->count(), 'delayed' => $q()->where('is_delayed', true)->count()];
        })->filter(fn ($c) => $c['total'] > 0)->values();

        $delayedTotal = $carriers->sum('delayed');
        $carrierChart = $carriers->map(fn ($c) => [
            'name' => $c['name'],
            'total' => $c['total'],
            'delayed' => $c['delayed'],
            'share' => $delayedTotal > 0 ? round($c['delayed'] / $delayedTotal * 100, 1) : 0,
        ])->all();

        return [
            'total' => $total,
            'in_transit' => (int) ($byStatus['IN_TRANSIT'] ?? 0),
            'delivered' => (int) ($byStatus['DELIVERED'] ?? 0),
            'delayed' => $delayed,
            'open_exceptions' => $openExceptions,
            'delay_rate' => $total > 0 ? round($delayed / $total * 100, 1) : 0.0,
            'exception_rate' => $total > 0 ? round($openExceptions / $total * 100, 1) : 0.0,
            'status_chart' => $statusChart,
            'carrier_chart' => $carrierChart,
            'by_mode' => collect(TransportMode::cases())->map(function (TransportMode $m) use ($base) {
                $q = fn () => $base()->where('transport_mode', $m->value);

                return [
                    'mode' => $m->value, 'label' => $m->label(), 'icon' => $m->icon(), 'tone' => $m->tone(),
                    'total' => $q()->count(),
                    'active' => $q()->whereNotIn('status', ['DELIVERED', 'CANCELLED'])->count(),
                    'delayed' => $q()->where('is_delayed', true)->count(),
                ];
            })->all(),
            'activity' => TrackingEvent::query()
                ->whereHas('shipment', fn ($q) => $q->visibleTo($user))
                ->with('shipment')->orderByDesc('occurred_at')->orderByDesc('id')->limit(7)->get(),
            'notifications' => AppNotification::where('user_id', $user->id)->latest()->limit(5)->get(),
            'recent' => $base()->with(['customer', 'carrier', 'origin', 'destination'])->latest()->limit(5)->get(),
        ];
    }

    /** Active shipments with route and last known position (for the Leaflet map). */
    public function mapPoints(User $user): array
    {
        return Shipment::query()->visibleTo($user)
            ->whereNotIn('status', [ShipmentStatus::DELIVERED->value, ShipmentStatus::CANCELLED->value])
            ->with(['origin', 'destination', 'trackingEvents'])
            ->latest()->limit(12)->get()
            ->map(function (Shipment $s) {
                $at = $s->latestLocatedEvent();

                return [
                    'number' => $s->shipment_number,
                    'status' => $s->display_status,
                    'origin' => ['name' => $s->origin->name, 'lat' => $s->origin->latitude, 'lng' => $s->origin->longitude],
                    'destination' => ['name' => $s->destination->name, 'lat' => $s->destination->latitude, 'lng' => $s->destination->longitude],
                    'current' => $at ? ['lat' => $at->latitude, 'lng' => $at->longitude, 'name' => $at->location_name, 'time' => $at->occurred_at->format('Y-m-d H:i')] : null,
                ];
            })->values()->all();
    }

    /**
     * Operational KPIs per carrier (on-time rate, average delay, exception resolution time).
     *
     * @return array<int, array<string, mixed>>
     */
    public function carrierReport(): array
    {
        return Carrier::query()->orderBy('name')->get()->map(function (Carrier $c) {
            $shipments = Shipment::where('carrier_id', $c->id)->get();
            $delivered = $shipments->where('status', ShipmentStatus::DELIVERED);
            $onTime = $delivered->filter(fn (Shipment $s) => ! $s->is_delayed)->count();
            $delayedList = $shipments->where('is_delayed', true);
            $exceptions = ShipmentException::whereHas('shipment', fn ($q) => $q->where('carrier_id', $c->id))->get();
            $resolved = $exceptions->filter(fn ($e) => $e->resolved_at);

            return [
                'carrier' => $c->name,
                'total' => $shipments->count(),
                'delivered' => $delivered->count(),
                'delayed' => $delayedList->count(),
                'delay_rate' => $shipments->count() ? round($delayedList->count() / $shipments->count() * 100, 1) : 0.0,
                'on_time_rate' => $delivered->count() ? round($onTime / $delivered->count() * 100, 1) : null,
                'avg_delay_hours' => $delayedList->count() ? round($delayedList->avg('delay_hours'), 1) : 0.0,
                'exceptions' => $exceptions->count(),
                'avg_resolution_hours' => $resolved->count() ? round($resolved->avg(fn ($e) => $e->resolutionHours()), 1) : null,
            ];
        })->all();
    }
}
