<?php

namespace Tests\Unit;

use App\Enums\ShipmentStatus;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

class ShipmentStatusTest extends TestCase
{
    public function test_happy_path_transitions(): void
    {
        $this->assertTrue(ShipmentStatus::CREATED->canTransitionTo(ShipmentStatus::BOOKED));
        $this->assertTrue(ShipmentStatus::BOOKED->canTransitionTo(ShipmentStatus::PICKED_UP));
        $this->assertTrue(ShipmentStatus::PICKED_UP->canTransitionTo(ShipmentStatus::IN_TRANSIT));
        $this->assertTrue(ShipmentStatus::IN_TRANSIT->canTransitionTo(ShipmentStatus::DELIVERED));
    }

    public function test_skipping_or_going_backwards_is_not_allowed(): void
    {
        $this->assertFalse(ShipmentStatus::CREATED->canTransitionTo(ShipmentStatus::DELIVERED));
        $this->assertFalse(ShipmentStatus::IN_TRANSIT->canTransitionTo(ShipmentStatus::BOOKED));
        $this->assertFalse(ShipmentStatus::PICKED_UP->canTransitionTo(ShipmentStatus::CANCELLED));
    }

    public function test_terminal_statuses_have_no_next(): void
    {
        $this->assertTrue(ShipmentStatus::DELIVERED->isTerminal());
        $this->assertSame([], ShipmentStatus::DELIVERED->allowedNext());
        $this->assertSame([], ShipmentStatus::CANCELLED->allowedNext());
    }

    public function test_assert_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);
        ShipmentStatus::DELIVERED->assertCanTransitionTo(ShipmentStatus::CREATED);
    }
}
