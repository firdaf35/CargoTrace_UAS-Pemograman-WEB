<?php

namespace App\Enums;

enum TransportMode: string
{
    case SEA = 'sea';
    case AIR = 'air';
    case LAND = 'land';

    public function label(): string
    {
        return match ($this) {
            self::SEA => 'Sea Freight',
            self::AIR => 'Air Freight',
            self::LAND => 'Land Freight',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::SEA => 'ship',
            self::AIR => 'plane',
            self::LAND => 'truck',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::SEA => '🚢',
            self::AIR => '✈️',
            self::LAND => '🚚',
        };
    }

    /** Tailwind classes for badges / icon circles. */
    public function tone(): string
    {
        return match ($this) {
            self::SEA => 'bg-sky-100 text-sky-700',
            self::AIR => 'bg-indigo-100 text-indigo-700',
            self::LAND => 'bg-amber-100 text-amber-700',
        };
    }

    public function originLabel(): string
    {
        return match ($this) {
            self::SEA => 'Origin Port',
            self::AIR => 'Origin Airport',
            self::LAND => 'Origin Location',
        };
    }

    public function destinationLabel(): string
    {
        return match ($this) {
            self::SEA => 'Destination Port',
            self::AIR => 'Destination Airport',
            self::LAND => 'Destination Location',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
