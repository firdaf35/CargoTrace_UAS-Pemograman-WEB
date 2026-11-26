<?php

namespace App\Enums;

use Illuminate\Validation\ValidationException;

enum ExceptionStatus: string
{
    case OPEN = 'OPEN';
    case INVESTIGATING = 'INVESTIGATING';
    case RESOLVED = 'RESOLVED';

    /** @return array<int, self> */
    public function allowedNext(): array
    {
        return match ($this) {
            self::OPEN => [self::INVESTIGATING, self::RESOLVED],
            self::INVESTIGATING => [self::RESOLVED],
            self::RESOLVED => [],
        };
    }

    public function assertCanTransitionTo(self $next, string $field = 'status'): void
    {
        if (! in_array($next, $this->allowedNext(), true)) {
            throw ValidationException::withMessages([
                $field => "Invalid exception status transition {$this->value} -> {$next->value}.",
            ]);
        }
    }

    public function isActive(): bool
    {
        return $this !== self::RESOLVED;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
