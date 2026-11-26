<?php

namespace App\Services;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;

/**
 * Builds the 8-stage journey stepper from REAL tracking events.
 * A stage is only "done" when a matching event exists; "Delivered" additionally
 * requires the shipment status to be DELIVERED.
 */
class JourneyService
{
    public const STAGES = [
        ['key' => 'created', 'label' => 'Shipment Created', 'icon' => 'package-plus', 'types' => ['CREATED']],
        ['key' => 'picked_up', 'label' => 'Picked Up', 'icon' => 'package-check', 'types' => ['PICKED_UP']],
        ['key' => 'departed', 'label' => 'Departed Origin', 'icon' => 'log-out', 'types' => ['DEPARTED_ORIGIN', 'IN_TRANSIT']],
        ['key' => 'in_transit', 'label' => 'In Transit', 'icon' => 'navigation', 'types' => ['IN_TRANSIT', 'LOCATION_UPDATE']],
        ['key' => 'hub', 'label' => 'Arrived at Transit Hub', 'icon' => 'warehouse', 'types' => ['ARRIVED_AT_HUB', 'DEPARTED_HUB']],
        ['key' => 'customs', 'label' => 'Customs Clearance', 'icon' => 'shield-check', 'types' => ['CUSTOMS_CLEARANCE', 'CUSTOMS_CLEARED']],
        ['key' => 'out', 'label' => 'Out for Delivery', 'icon' => 'truck', 'types' => ['OUT_FOR_DELIVERY']],
        ['key' => 'delivered', 'label' => 'Delivered', 'icon' => 'circle-check', 'types' => ['DELIVERED']],
    ];

    /** @return array<int, array<string, mixed>> */
    public function build(Shipment $shipment): array
    {
        $events = $shipment->trackingEvents->where('is_exception', false);
        $stages = [];
        $firstPending = null;

        foreach (self::STAGES as $i => $stage) {
            $match = $events->first(fn ($e) => in_array($e->event_type, $stage['types'], true));
            $done = (bool) $match;
            if ($stage['key'] === 'delivered') {
                $done = $done && $shipment->status === ShipmentStatus::DELIVERED;
            }
            if (! $done && $firstPending === null) {
                $firstPending = $i;
            }
            $stages[] = $stage + [
                'done' => $done,
                'at' => $done ? $match->occurred_at : null,
                'location' => $done ? $match->location_name : null,
                'current' => false,
            ];
        }

        if ($firstPending !== null && ! $shipment->status->isTerminal()) {
            $stages[$firstPending]['current'] = true;
        }

        return $stages;
    }
}
