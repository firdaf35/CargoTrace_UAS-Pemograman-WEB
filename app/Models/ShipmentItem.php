<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentItem extends Model
{
    protected $fillable = ['shipment_id', 'description', 'quantity', 'weight_kg'];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'weight_kg' => 'float'];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
