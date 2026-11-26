<?php

namespace App\Enums;

use Illuminate\Validation\ValidationException;

/**
 * Shipment lifecycle. The transition map below is the single source of truth:
 * CREATED -> BOOKED -> PICKED_UP -> IN_TRANSIT -> DELIVERED (terminal)
 * CANCELLED is only reachable from CREATED/BOOKED (terminal).
 */
enum ShipmentStatus: string
{
    case CREATED = 'CREATED';
    case BOOKED = 'BOOKED';
    case PICKED_UP = 'PICKED_UP';
    case IN_TRANSIT = 'IN_TRANSIT';
    case DELIVERED = 'DELIVERED';
    case CANCELLED = 'CANCELLED';

    /** @return array<int, self> */
    public function allowedNext(): array
    {
        return match ($this) {
            self::CREATED => [self::BOOKED, self::CANCELLED],
            self::BOOKED => [self::PICKED_UP, self::CANCELLED],
            self::PICKED_UP => [self::IN_TRANSIT],
            self::IN_TRANSIT => [self::DELIVERED],
            self::DELIVERED, self::CANCELLED => [],
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::DELIVERED || $this === self::CANCELLED;
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }

    public function assertCanTransitionTo(self $next, string $field = 'status'): void
    {
        if (! $this->canTransitionTo($next)) {
            $allowed = collect($this->allowedNext())->map->value->implode(', ') ?: 'none (terminal status)';

            throw ValidationException::withMessages([
                $field => "Invalid status transition {$this->value} -> {$next->value}. Allowed next: {$allowed}.",
            ]);
        }
    }

    public function label(): string
    {
        return ucwords(strtolower(str_replace('_', ' ', $this->value)));
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
