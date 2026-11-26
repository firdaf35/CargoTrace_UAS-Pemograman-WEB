<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracking events are append-only history: there is no update/delete route or service for them.
 */
class TrackingEvent extends Model
{
    /** Informational event types that do not change the shipment status. */
    public const INFO_TYPES = ['LOCATION_UPDATE', 'DEPARTED_ORIGIN', 'ARRIVED_AT_HUB', 'DEPARTED_HUB', 'CUSTOMS_CLEARANCE', 'CUSTOMS_CLEARED', 'OUT_FOR_DELIVERY', 'NOTE'];

    protected $fillable = [
        'shipment_id', 'event_type', 'status_after', 'location_id', 'location_name',
        'latitude', 'longitude', 'occurred_at', 'description', 'is_exception', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
            'is_exception' => 'boolean',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
