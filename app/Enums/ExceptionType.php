<?php

namespace App\Enums;

enum ExceptionType: string
{
    case CUSTOMS_HOLD = 'CUSTOMS_HOLD';
    case WEATHER_DELAY = 'WEATHER_DELAY';
    case PORT_CONGESTION = 'PORT_CONGESTION';
    case DAMAGED = 'DAMAGED';
    case MISROUTED = 'MISROUTED';
    case DOCUMENTATION_ISSUE = 'DOCUMENTATION_ISSUE';

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
