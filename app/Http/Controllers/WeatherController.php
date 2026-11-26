<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Services\WeatherForecastService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WeatherController extends Controller
{
    public function __construct(private WeatherForecastService $weather) {}

    /** Origin/destination of recent active shipments become weather cards (loaded asynchronously). */
    public function index(Request $request): View
    {
        $shipments = Shipment::query()->visibleTo($request->user())
            ->whereNotIn('status', ['DELIVERED', 'CANCELLED'])
            ->with(['origin', 'destination'])->latest()->limit(6)->get();

        $places = [];
        foreach ($shipments as $s) {
            foreach ([['Origin', $s->origin], ['Destination', $s->destination]] as [$role, $l]) {
                $places[$l->id.'-'.$role] = [
                    'role' => $role, 'shipment' => $s->shipment_number, 'name' => $l->city, 'country' => $l->country,
                    'cc' => $l->country_code, 'lat' => $l->latitude, 'lng' => $l->longitude,
                ];
            }
        }

        return view('weather.index', [
            'places' => array_values($places),
            'provider' => config('services.openweather.api_key') ? 'OpenWeatherMap' : 'Open-Meteo',
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $q = $request->validate(['q' => ['required', 'string', 'min:2', 'max:80']])['q'];
        $results = $this->weather->search($q);

        return $results === null
            ? response()->json(['message' => 'City search service is unavailable. Please try again.'], 503)
            : response()->json(['results' => $results]);
    }

    public function data(Request $request): JsonResponse
    {
        $v = $request->validate(['lat' => ['required', 'numeric', 'between:-90,90'], 'lng' => ['required', 'numeric', 'between:-180,180']]);
        $data = $this->weather->forecast((float) $v['lat'], (float) $v['lng']);

        return $data === null
            ? response()->json(['message' => 'Weather service is unavailable. Please try again.'], 503)
            : response()->json(['data' => $data]);
    }
}
