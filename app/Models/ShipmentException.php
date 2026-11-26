<?php

namespace App\Models;

use App\Enums\ExceptionStatus;
use App\Enums\ExceptionType;
use App\Enums\Severity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentException extends Model
{
    protected $table = 'shipment_exceptions';

    protected $fillable = ['shipment_id', 'type', 'severity', 'description', 'detected_at', 'reported_by'];

    protected function casts(): array
    {
        return [
            'type' => ExceptionType::class,
            'severity' => Severity::class,
            'status' => ExceptionStatus::class,
            'detected_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function resolutionHours(): ?float
    {
        return $this->resolved_at ? round(($this->resolved_at->getTimestamp() - $this->detected_at->getTimestamp()) / 3600, 2) : null;
    }
}
