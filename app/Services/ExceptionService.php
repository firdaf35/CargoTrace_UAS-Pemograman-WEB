<?php

namespace App\Services;

use App\Enums\ExceptionStatus;
use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\TrackingEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExceptionService
{
    public function __construct(
        private AuditService $audit,
        private NotificationService $notifications,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(Shipment $shipment, array $data, User $actor): ShipmentException
    {
        if ($shipment->status === ShipmentStatus::CANCELLED) {
            throw ValidationException::withMessages(['type' => 'Cannot report exceptions on a cancelled shipment.']);
        }

        $detectedAt = ! empty($data['detected_at']) ? Carbon::parse($data['detected_at']) : now();
        if ($detectedAt->gt(now()->addMinutes(5))) {
            throw ValidationException::withMessages(['detected_at' => 'Detection time cannot be in the future.']);
        }

        return DB::transaction(function () use ($shipment, $data, $actor, $detectedAt) {
            $exception = new ShipmentException([
                'shipment_id' => $shipment->id,
                'type' => $data['type'],
                'severity' => $data['severity'],
                'description' => $data['description'] ?? null,
                'detected_at' => $detectedAt,
                'reported_by' => $actor->id,
            ]);
            $exception->status = ExceptionStatus::OPEN;
            $exception->save();

            $shipment->load('trackingEvents');
            $at = $shipment->latestLocatedEvent();

            TrackingEvent::create([
                'shipment_id' => $shipment->id,
                'event_type' => 'EXCEPTION',
                'status_after' => $shipment->status->value,
                'location_name' => $at?->location_name,
                'latitude' => $at?->latitude,
                'longitude' => $at?->longitude,
                'occurred_at' => $detectedAt,
                'description' => "Exception {$exception->type->value} ({$exception->severity->value}): ".($data['description'] ?? 'no details'),
                'is_exception' => true,
                'created_by' => $actor->id,
            ]);

            $this->audit->log($actor, 'exception.created', $exception, null, $exception->only(['type', 'severity', 'status']));

            $msg = "Exception {$exception->type->label()} ({$exception->severity->value}) reported on shipment {$shipment->shipment_number}.";
            $this->notifications->notify($shipment->customer_id, 'exception_opened', 'Shipment exception', $msg, $shipment);
            $this->notifications->notifyStaff('exception_opened', 'Shipment exception', $msg, $shipment);

            return $exception;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(ShipmentException $exception, array $data, User $actor, ?Carbon $at = null): ShipmentException
    {
        return DB::transaction(function () use ($exception, $data, $actor, $at) {
            $old = $exception->only(['status', 'severity', 'resolution_notes']);
            $resolvedNow = false;

            if (! empty($data['status'])) {
                $next = ExceptionStatus::from($data['status']);
                if ($next !== $exception->status) {
                    $exception->status->assertCanTransitionTo($next);
                    $exception->status = $next;
                    if ($next === ExceptionStatus::RESOLVED) {
                        $exception->resolved_at = $at ?? now();
                        $exception->resolved_by = $actor->id;
                        $resolvedNow = true;
                    }
                }
            }
            if (! empty($data['severity'])) {
                $exception->severity = $data['severity'];
            }
            if (array_key_exists('resolution_notes', $data)) {
                $exception->resolution_notes = $data['resolution_notes'];
            }
            $exception->save();

            if ($resolvedNow) {
                $shipment = $exception->shipment;
                TrackingEvent::create([
                    'shipment_id' => $shipment->id,
                    'event_type' => 'EXCEPTION_RESOLVED',
                    'status_after' => $shipment->status->value,
                    'occurred_at' => $exception->resolved_at,
                    'description' => "Exception {$exception->type->value} resolved.".($exception->resolution_notes ? ' '.$exception->resolution_notes : ''),
                    'is_exception' => false,
                    'created_by' => $actor->id,
                ]);
                $this->notifications->notify($shipment->customer_id, 'exception_resolved', 'Exception resolved', "Exception {$exception->type->label()} on {$shipment->shipment_number} has been resolved.", $shipment);
            }

            $this->audit->log($actor, 'exception.updated', $exception, $old, $exception->only(['status', 'severity', 'resolution_notes']));

            return $exception->refresh();
        });
    }
}
