<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Shipment;
use App\Models\User;

class NotificationService
{
    public function notify(int|User $user, string $type, string $title, string $message, ?Shipment $shipment = null): AppNotification
    {
        return AppNotification::create([
            'user_id' => $user instanceof User ? $user->id : $user,
            'shipment_id' => $shipment?->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
        ]);
    }

    public function notifyStaff(string $type, string $title, string $message, ?Shipment $shipment = null): void
    {
        User::whereIn('role', ['admin', 'operator'])->get()
            ->each(fn (User $u) => $this->notify($u, $type, $title, $message, $shipment));
    }
}
