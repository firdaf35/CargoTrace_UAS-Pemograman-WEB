<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;

class ShipmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // list is scoped per role in the query (visibleTo)
    }

    /** Customers only see their own shipments; staff see everything. */
    public function view(User $user, Shipment $shipment): bool
    {
        return $user->isStaff() || $shipment->customer_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    /** Admin can edit anything; operators can edit shipments that are not in a terminal state. */
    public function update(User $user, Shipment $shipment): bool
    {
        return $user->isAdmin() || ($user->isOperator() && ! $shipment->status->isTerminal());
    }

    public function delete(User $user, Shipment $shipment): bool
    {
        return $user->isAdmin();
    }

    public function addTracking(User $user, Shipment $shipment): bool
    {
        return $user->isStaff();
    }

    public function manageExceptions(User $user, Shipment $shipment): bool
    {
        return $user->isStaff();
    }
}
