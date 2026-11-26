<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $items = AppNotification::where('user_id', $request->user()->id)->with('shipment')->latest()->paginate(15);

        return view('notifications.index', ['items' => $items]);
    }

    public function read(Request $request, AppNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->update(['read_at' => now()]);

        return $notification->shipment_id
            ? redirect()->route('shipments.show', $notification->shipment_id)
            : back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        AppNotification::where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
