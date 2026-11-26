<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Services\AuditService;
use App\Services\GeocodingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LocationController extends Controller
{
    public const TYPES = ['PORT', 'AIRPORT', 'WAREHOUSE', 'HUB', 'CITY'];

    public function __construct(private AuditService $audit, private GeocodingService $geocoder) {}

    public function index(): View
    {
        return view('locations.index', ['locations' => Location::orderBy('country')->orderBy('name')->paginate(15), 'types' => self::TYPES]);
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'country_code' => ['nullable', 'alpha', 'size:2'],
            'type' => ['required', Rule::in(self::TYPES)],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ];
    }

    /** If coordinates are empty, geocode "name, city, country" via the external API (with graceful failure). */
    private function withCoordinates(array $data): array|RedirectResponse
    {
        if (($data['latitude'] ?? null) === null || ($data['longitude'] ?? null) === null) {
            $hit = $this->geocoder->geocode("{$data['name']}, {$data['city']}, {$data['country']}")
                ?? $this->geocoder->geocode("{$data['city']}, {$data['country']}");

            if (! $hit) {
                return back()->withInput()->withErrors(['latitude' => 'Automatic geocoding is unavailable or found nothing. Please enter latitude and longitude manually.']);
            }
            $data['latitude'] = $hit['lat'];
            $data['longitude'] = $hit['lng'];
            $data['country_code'] = ($data['country_code'] ?? null) ?: ($hit['country_code'] ?? null);
        }

        if (! empty($data['country_code'])) {
            $data['country_code'] = strtoupper($data['country_code']);
        }

        return $data;
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->withCoordinates($request->validate($this->rules()));
        if ($data instanceof RedirectResponse) {
            return $data;
        }
        $location = Location::create($data);
        $this->audit->log($request->user(), 'location.created', $location, null, $location->only(['name', 'city', 'country']));

        return redirect()->route('locations.index')->with('success', 'Location created.');
    }

    public function edit(Location $location): View
    {
        return view('locations.edit', ['location' => $location, 'types' => self::TYPES]);
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $data = $this->withCoordinates($request->validate($this->rules()));
        if ($data instanceof RedirectResponse) {
            return $data;
        }
        $old = $location->only(['name', 'city', 'country', 'latitude', 'longitude']);
        $location->update($data);
        $this->audit->log($request->user(), 'location.updated', $location, $old, $location->only(['name', 'city', 'country', 'latitude', 'longitude']));

        return redirect()->route('locations.index')->with('success', 'Location updated.');
    }

    public function destroy(Request $request, Location $location): RedirectResponse
    {
        $used = \App\Models\Shipment::withTrashed()->where('origin_id', $location->id)->orWhere('destination_id', $location->id)->exists();
        if ($used) {
            return back()->withErrors(['location' => 'This location is used by shipments and cannot be deleted.']);
        }
        $this->audit->log($request->user(), 'location.deleted', $location, $location->only(['name', 'city']), null);
        $location->delete();

        return redirect()->route('locations.index')->with('success', 'Location deleted.');
    }
}
