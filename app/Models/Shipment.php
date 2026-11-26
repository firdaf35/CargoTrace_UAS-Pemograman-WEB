<?php

namespace App\Models;

use App\Enums\Severity;
use App\Enums\TransportMode;
use App\Enums\ShipmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shipment extends Model
{
    use HasFactory, SoftDeletes;

    // Status, delay fields, shipment_number and created_by are NOT mass-assignable:
    // they can only be changed by services (business rules), never by raw request input.
    protected $fillable = [
        'customer_id', 'carrier_id', 'origin_id', 'destination_id', 'cargo_type',
        'weight_kg', 'volume_cbm', 'expected_departure', 'expected_arrival', 'estimated_arrival',
        'transport_mode', 'vessel_name', 'container_number', 'flight_number', 'airline', 'vehicle_number', 'driver_name',
    ];

    protected function casts(): array
    {
        return [
            'status' => ShipmentStatus::class,
            'transport_mode' => TransportMode::class,
            'delay_severity' => Severity::class,
            'expected_departure' => 'datetime',
            'expected_arrival' => 'datetime',
            'estimated_arrival' => 'datetime',
            'actual_arrival' => 'datetime',
            'is_delayed' => 'boolean',
            'delay_hours' => 'float',
            'weight_kg' => 'float',
            'volume_cbm' => 'float',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    public function origin(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'origin_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'destination_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(TrackingEvent::class)->orderBy('occurred_at')->orderBy('id');
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(ShipmentException::class)->orderByDesc('detected_at');
    }

    /** Status shown in the UI: DELAYED overrides while the shipment is still moving. */
    protected function displayStatus(): Attribute
    {
        return Attribute::get(fn () => $this->is_delayed && ! $this->status->isTerminal() ? 'DELAYED' : $this->status->value);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->isCustomer() ? $query->where('customer_id', $user->id) : $query;
    }

    /** @param array<string, mixed> $f */
    public function scopeFilter(Builder $query, array $f): Builder
    {
        return $query
            ->when($f['q'] ?? null, function (Builder $q, $v) {
                $q->where(function (Builder $w) use ($v) {
                    $w->where('shipment_number', 'like', "%{$v}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$v}%")->orWhere('company', 'like', "%{$v}%"));
                });
            })
            ->when($f['status'] ?? null, fn (Builder $q, $v) => $v === 'DELAYED' ? $q->where('is_delayed', true) : $q->where('status', $v))
            ->when($f['mode'] ?? null, fn (Builder $q, $v) => $q->where('transport_mode', $v))
            ->when($f['customer_id'] ?? null, fn (Builder $q, $v) => $q->where('customer_id', $v))
            ->when($f['carrier_id'] ?? null, fn (Builder $q, $v) => $q->where('carrier_id', $v))
            ->when($f['origin_id'] ?? null, fn (Builder $q, $v) => $q->where('origin_id', $v))
            ->when($f['destination_id'] ?? null, fn (Builder $q, $v) => $q->where('destination_id', $v))
            ->when($f['from'] ?? null, fn (Builder $q, $v) => $q->whereDate('expected_arrival', '>=', $v))
            ->when($f['to'] ?? null, fn (Builder $q, $v) => $q->whereDate('expected_arrival', '<=', $v))
            ->when(($f['delayed'] ?? '') !== '' && $f['delayed'] !== null, fn (Builder $q) => $q->where('is_delayed', filter_var($f['delayed'], FILTER_VALIDATE_BOOLEAN)));
    }

    public function mode(): TransportMode
    {
        return $this->transport_mode ?? TransportMode::SEA;
    }

    /** Vessel / flight / vehicle shown as the "asset" of the shipment. */
    public function assetName(): ?string
    {
        return match ($this->mode()) {
            TransportMode::SEA => $this->vessel_name,
            TransportMode::AIR => trim(implode(' ', array_filter([$this->airline, $this->flight_number]))) ?: null,
            TransportMode::LAND => $this->vehicle_number,
        };
    }

    /** @return array<string, ?string> */
    public function assetDetails(): array
    {
        return match ($this->mode()) {
            TransportMode::SEA => ['Vessel' => $this->vessel_name, 'Container No.' => $this->container_number],
            TransportMode::AIR => ['Flight No.' => $this->flight_number, 'Airline' => $this->airline],
            TransportMode::LAND => ['Vehicle No.' => $this->vehicle_number, 'Driver' => $this->driver_name],
        };
    }

    /** Latest event that carries coordinates = "current location". */
    public function latestLocatedEvent(): ?TrackingEvent
    {
        return $this->trackingEvents->whereNotNull('latitude')->sortByDesc('occurred_at')->first();
    }

    /** @return array<string, mixed> */
    public function mapData(): array
    {
        $point = fn (?Location $l) => $l ? ['name' => $l->name, 'city' => $l->city, 'lat' => $l->latitude, 'lng' => $l->longitude] : null;

        return [
            'origin' => $point($this->origin),
            'destination' => $point($this->destination),
            'events' => $this->trackingEvents->whereNotNull('latitude')->map(fn (TrackingEvent $e) => [
                'lat' => $e->latitude,
                'lng' => $e->longitude,
                'type' => $e->event_type,
                'status' => $e->status_after,
                'time' => $e->occurred_at->format('Y-m-d H:i'),
                'location' => $e->location_name,
                'exception' => $e->is_exception,
            ])->values()->all(),
        ];
    }
}
