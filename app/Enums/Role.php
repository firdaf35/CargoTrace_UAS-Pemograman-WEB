<?php

namespace App\Enums;

enum Role: string
{
    case ADMIN = 'admin';
    case OPERATOR = 'operator';
    case CUSTOMER = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::OPERATOR => 'Logistics Operator',
            self::CUSTOMER => 'Customer',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
