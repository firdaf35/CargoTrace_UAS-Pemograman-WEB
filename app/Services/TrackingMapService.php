<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;

/**
 * JSON payload for the world tracking map. Everything comes from the database.
 * "current" is the LAST REPORTED location (latest tracking event with coordinates),
 * not a real-time GPS position.
 */
class TrackingMapService
{
    /** @param array<string, mixed> $f */
    public function payload(User $user, array $f = []): array
    {
        $query = Shipment::query()->visibleTo($user)
            ->when($f['mode'] ?? null, fn ($q, $v) => $q->where('transport_mode', $v))
            ->when($f['status'] ?? null, fn ($q, $v) => $v === 'DELAYED' ? $q->where('is_delayed', true) : $q->where('status', $v))
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where('shipment_number', 'like', "%{$v}%"))
            ->with(['carrier', 'origin', 'destination', 'trackingEvents'])
            ->orderByDesc('updated_at')
            ->limit(150);

        return $query->get()->map(function (Shipment $s) {
            $point = fn ($l) => ['name' => $l->name, 'city' => $l->city, 'country' => $l->country, 'lat' => $l->latitude, 'lng' => $l->longitude];
            $located = $s->trackingEvents->whereNotNull('latitude')->where('is_exception', false)->values();
            $last = $s->latestLocatedEvent();
            $latest = $s->trackingEvents->sortByDesc('occurred_at')->first();

            return [
                'id' => $s->id,
                'number' => $s->shipment_number,
                'mode' => $s->mode()->value,
                'mode_label' => $s->mode()->label(),
                'emoji' => $s->mode()->emoji(),
                'status' => $s->display_status,
                'raw_status' => $s->status->value,
                'carrier' => $s->carrier->name,
                'asset' => $s->assetName(),
                'origin' => $point($s->origin),
                'destination' => $point($s->destination),
                'departure' => $s->expected_departure?->format('Y-m-d H:i'),
                'eta' => ($s->actual_arrival ?? $s->estimated_arrival)?->format('Y-m-d H:i'),
                'delay_hours' => $s->delay_hours,
                'last_updated' => $latest?->occurred_at->format('Y-m-d H:i'),
                'current' => $last ? ['lat' => $last->latitude, 'lng' => $last->longitude, 'name' => $last->location_name, 'time' => $last->occurred_at->format('Y-m-d H:i'), 'kind' => 'last_reported'] : null,
                'path' => $located->map(fn (TrackingEvent $e) => [$e->latitude, $e->longitude])->all(),
                'events' => $s->trackingEvents->map(fn (TrackingEvent $e) => [
                    'type' => $e->event_type,
                    'time' => $e->occurred_at->format('Y-m-d H:i'),
                    'location' => $e->location_name,
                    'description' => $e->description,
                    'exception' => $e->is_exception,
                    'lat' => $e->latitude,
                    'lng' => $e->longitude,
                ])->values()->all(),
                'url' => route('shipments.show', $s),
            ];
        })->values()->all();
    }
}
