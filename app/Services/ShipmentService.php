<?php

namespace App\Services;

use App\Enums\ShipmentStatus;
use App\Enums\TransportMode;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ShipmentService
{
    public const SORTABLE = ['shipment_number', 'status', 'expected_arrival', 'created_at', 'delay_hours'];

    private const AUDITED = ['customer_id', 'carrier_id', 'origin_id', 'destination_id', 'cargo_type', 'weight_kg', 'volume_cbm', 'expected_departure', 'expected_arrival', 'estimated_arrival', 'status', 'transport_mode'];

    public function __construct(
        private EtaService $eta,
        private AuditService $audit,
        private NotificationService $notifications,
    ) {}

    /** Server-side search, filter, sort and pagination (shared by web and API). */
    public function search(User $user, array $filters, ?string $sort, ?string $dir, int $perPage = 10): LengthAwarePaginator
    {
        $sort = in_array($sort, self::SORTABLE, true) ? $sort : 'created_at';
        $dir = strtolower((string) $dir) === 'asc' ? 'asc' : 'desc';

        return Shipment::query()
            ->visibleTo($user)
            ->filter($filters)
            ->with(['customer', 'carrier', 'origin', 'destination'])
            ->orderBy($sort, $dir)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function generateNumber(): string
    {
        do {
            $number = 'SHP-'.now()->format('Y').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Shipment::withTrashed()->where('shipment_number', $number)->exists());

        return $number;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data, User $actor, ?Carbon $createdAt = null): Shipment
    {
        return DB::transaction(function () use ($data, $actor, $createdAt) {
            $data['transport_mode'] = $data['transport_mode'] ?? TransportMode::SEA->value;
            $data = $this->normalizeMode($data, $data['transport_mode']);
            $shipment = new Shipment(Arr::only($data, (new Shipment)->getFillable()));
            $shipment->forceFill([
                'shipment_number' => $this->generateNumber(),
                'status' => ShipmentStatus::CREATED,
                'created_by' => $actor->id,
            ]);
            $shipment->estimated_arrival ??= $shipment->expected_arrival;
            $shipment->save();

            foreach ($data['items'] ?? [] as $item) {
                $shipment->items()->create(Arr::only($item, ['description', 'quantity', 'weight_kg']));
            }

            $shipment->load('origin');
            TrackingEvent::create([
                'shipment_id' => $shipment->id,
                'event_type' => ShipmentStatus::CREATED->value,
                'status_after' => ShipmentStatus::CREATED->value,
                'location_id' => $shipment->origin_id,
                'location_name' => $shipment->origin->name,
                'latitude' => $shipment->origin->latitude,
                'longitude' => $shipment->origin->longitude,
                'occurred_at' => $createdAt ?? now(),
                'description' => 'Shipment record created.',
                'created_by' => $actor->id,
            ]);

            $this->eta->apply($shipment);
            $this->audit->log($actor, 'shipment.created', $shipment, null, $shipment->only(self::AUDITED));
            $this->notifications->notify($shipment->customer_id, 'shipment_created', 'New shipment '.$shipment->shipment_number, 'A shipment has been created for you.', $shipment);

            return $shipment;
        });
    }

    /**
     * Update shipment master data. Status is NOT editable here: it only changes via
     * TrackingService (validated transitions).
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Shipment $shipment, array $data, User $actor): Shipment
    {
        return DB::transaction(function () use ($shipment, $data, $actor) {
            $old = $shipment->only(self::AUDITED);
            $wasDelayed = $shipment->is_delayed;

            $data = $this->normalizeMode($data, $data['transport_mode'] ?? $shipment->mode()->value);
            $shipment->fill(Arr::only($data, $shipment->getFillable()))->save();
            $this->eta->apply($shipment);

            $this->audit->log($actor, 'shipment.updated', $shipment, $old, $shipment->only(self::AUDITED));

            if (! $wasDelayed && $shipment->is_delayed) {
                $this->notifyDelay($shipment);
            }

            return $shipment->refresh();
        });
    }

    public function delete(Shipment $shipment, User $actor): void
    {
        DB::transaction(function () use ($shipment, $actor) {
            $this->audit->log($actor, 'shipment.deleted', $shipment, $shipment->only(self::AUDITED), null);
            $shipment->delete(); // soft delete
        });
    }

    /** Only keep the asset fields that belong to the selected transport mode. */
    private function normalizeMode(array $data, string $mode): array
    {
        $fields = ['sea' => ['vessel_name', 'container_number'], 'air' => ['flight_number', 'airline'], 'land' => ['vehicle_number', 'driver_name']];
        foreach ($fields as $m => $list) {
            if ($m !== $mode) {
                foreach ($list as $f) {
                    $data[$f] = null;
                }
            }
        }

        return $data;
    }

    public function notifyDelay(Shipment $shipment): void
    {
        $msg = "Shipment {$shipment->shipment_number} is delayed by {$shipment->delay_hours}h. {$shipment->delay_reason}";
        $this->notifications->notify($shipment->customer_id, 'shipment_delayed', 'Shipment delayed', $msg, $shipment);
        $this->notifications->notifyStaff('shipment_delayed', 'Shipment delayed', $msg, $shipment);
    }
}
