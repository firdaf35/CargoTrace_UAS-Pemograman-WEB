<?php

namespace App\Http\Controllers;

use App\Enums\TransportMode;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransportController extends Controller
{
    public function index(Request $request): View
    {
        $v = $request->validate(['mode' => ['nullable', 'in:sea,air,land'], 'q' => ['nullable', 'string', 'max:100']]);
        $mode = $v['mode'] ?? null;
        $q = $v['q'] ?? null;
        $user = $request->user();

        $stats = collect(TransportMode::cases())->map(function (TransportMode $m) use ($user) {
            $base = fn () => Shipment::query()->visibleTo($user)->where('transport_mode', $m->value);

            return [
                'mode' => $m,
                'total' => $base()->count(),
                'active' => $base()->whereNotIn('status', ['DELIVERED', 'CANCELLED'])->count(),
                'delayed' => $base()->where('is_delayed', true)->count(),
            ];
        });

        $shipments = Shipment::query()->visibleTo($user)
            ->when($mode, fn ($x, $m) => $x->where('transport_mode', $m))
            ->when($q, fn ($x, $t) => $x->where(fn ($w) => $w->where('shipment_number', 'like', "%{$t}%")
                ->orWhere('vessel_name', 'like', "%{$t}%")->orWhere('container_number', 'like', "%{$t}%")
                ->orWhere('flight_number', 'like', "%{$t}%")->orWhere('vehicle_number', 'like', "%{$t}%")))
            ->with(['carrier', 'origin', 'destination', 'trackingEvents'])
            ->orderByDesc('updated_at')->paginate(10)->withQueryString();

        return view('transport.index', ['stats' => $stats, 'shipments' => $shipments, 'mode' => $mode, 'q' => $q]);
    }
}
