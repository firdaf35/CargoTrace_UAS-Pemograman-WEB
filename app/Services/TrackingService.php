<?php

namespace App\Services;

use App\Enums\ShipmentStatus;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TrackingService
{
    public function __construct(
        private EtaService $eta,
        private ShipmentService $shipments,
        private AuditService $audit,
        private NotificationService $notifications,
    ) {}

    /** Event types accepted by the API/UI for a shipment in the given status. */
    public static function allowedEventTypes(Shipment $shipment): array
    {
        $next = array_map(fn (ShipmentStatus $s) => $s->value, $shipment->status->allowedNext());

        return $shipment->status->isTerminal() ? [] : array_merge($next, TrackingEvent::INFO_TYPES);
    }

    /**
     * Append a historical tracking event. Existing events are never overwritten.
     * Status-type events are validated against the lifecycle state machine.
     *
     * @param  array<string, mixed>  $data
     */
    public function addEvent(Shipment $shipment, array $data, User $actor): TrackingEvent
    {
        return DB::transaction(function () use ($shipment, $data, $actor) {
            $shipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);

            if ($shipment->status->isTerminal()) {
                throw ValidationException::withMessages([
                    'event_type' => "Shipment {$shipment->shipment_number} is {$shipment->status->value}; no further tracking events can be added.",
                ]);
            }

            $occurredAt = ! empty($data['occurred_at']) ? Carbon::parse($data['occurred_at']) : now();

            if ($occurredAt->gt(now()->addMinutes(5))) {
                throw ValidationException::withMessages(['occurred_at' => 'Event time cannot be in the future.']);
            }

            $last = TrackingEvent::where('shipment_id', $shipment->id)->orderByDesc('occurred_at')->orderByDesc('id')->first();
            if ($last && $occurredAt->lt($last->occurred_at)) {
                throw ValidationException::withMessages([
                    'occurred_at' => 'Event time must not be earlier than the last event ('.$last->occurred_at->format('Y-m-d H:i').').',
                ]);
            }

            $type = (string) $data['event_type'];
            $newStatus = ShipmentStatus::tryFrom($type);

            if ($newStatus) {
                $shipment->status->assertCanTransitionTo($newStatus, 'event_type');
            } elseif (! in_array($type, TrackingEvent::INFO_TYPES, true)) {
                throw ValidationException::withMessages(['event_type' => 'Unknown event type.']);
            }

            $location = ! empty($data['location_id']) ? Location::find($data['location_id']) : null;

            $event = TrackingEvent::create([
                'shipment_id' => $shipment->id,
                'event_type' => $type,
                'status_after' => ($newStatus ?? $shipment->status)->value,
                'location_id' => $location?->id,
                'location_name' => $data['location_name'] ?? $location?->name,
                'latitude' => $data['latitude'] ?? $location?->latitude,
                'longitude' => $data['longitude'] ?? $location?->longitude,
                'occurred_at' => $occurredAt,
                'description' => $data['description'] ?? null,
                'is_exception' => false,
                'created_by' => $actor->id,
            ]);

            $wasDelayed = $shipment->is_delayed;
            $oldStatus = $shipment->status;

            if ($newStatus) {
                $shipment->status = $newStatus;
                if ($newStatus === ShipmentStatus::DELIVERED) {
                    $shipment->actual_arrival = $occurredAt;
                }
            }
            if (! empty($data['estimated_arrival'])) {
                $shipment->estimated_arrival = Carbon::parse($data['estimated_arrival']);
            }
            $shipment->save();
            $this->eta->apply($shipment);

            $this->audit->log($actor, 'tracking.added', $shipment, ['status' => $oldStatus->value], ['status' => $shipment->status->value, 'event_type' => $type]);

            if ($newStatus) {
                $this->notifications->notify(
                    $shipment->customer_id, 'status_changed',
                    "Shipment {$shipment->shipment_number}: {$newStatus->label()}",
                    "Status changed from {$oldStatus->label()} to {$newStatus->label()}.",
                    $shipment
                );
            }
            if (! $wasDelayed && $shipment->is_delayed) {
                $this->shipments->notifyDelay($shipment);
            }

            return $event->load(['location', 'creator']);
        });
    }
}
