@php
    $dt = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->format('Y-m-d\TH:i') : null;
    $locOpts = $locations->mapWithKeys(fn ($l) => [$l->id => $l->name.' ('.$l->city.', '.$l->country.')'])->all();
@endphp
<div class="grid gap-4 md:grid-cols-2">
    <x-select name="transport_mode" label="Transport mode" :options="['sea' => '🚢 Sea Freight', 'air' => '✈️ Air Freight', 'land' => '🚚 Land Freight']" :value="$shipment->transport_mode?->value ?? 'sea'" id="modeSel" required />
    <div></div>
    <div data-mode-group="sea" class="grid gap-4 md:col-span-2 md:grid-cols-2">
        <x-field name="vessel_name" label="Vessel name" :value="$shipment->vessel_name" maxlength="100" />
        <x-field name="container_number" label="Container number" :value="$shipment->container_number" maxlength="20" />
    </div>
    <div data-mode-group="air" class="hidden grid gap-4 md:col-span-2 md:grid-cols-2">
        <x-field name="flight_number" label="Flight number" :value="$shipment->flight_number" maxlength="20" />
        <x-field name="airline" label="Cargo airline" :value="$shipment->airline" maxlength="100" />
    </div>
    <div data-mode-group="land" class="hidden grid gap-4 md:col-span-2 md:grid-cols-2">
        <x-field name="vehicle_number" label="Vehicle number" :value="$shipment->vehicle_number" maxlength="30" />
        <x-field name="driver_name" label="Driver name (optional)" :value="$shipment->driver_name" maxlength="100" />
    </div>
    <x-select name="customer_id" label="Customer" :options="$customers->mapWithKeys(fn ($c) => [$c->id => ($c->company ?: $c->name).' — '.$c->email])->all()" :value="$shipment->customer_id" placeholder="Select customer" required />
    <x-select name="carrier_id" label="Carrier" :options="$carriers->pluck('name', 'id')->all()" :value="$shipment->carrier_id" placeholder="Select carrier" required />
    <x-select name="origin_id" label="Origin" :options="$locOpts" :value="$shipment->origin_id" placeholder="Select origin" required />
    <x-select name="destination_id" label="Destination" :options="$locOpts" :value="$shipment->destination_id" placeholder="Select destination" required />
    <x-field name="cargo_type" label="Cargo type" :value="$shipment->cargo_type" maxlength="100" required />
    <div class="grid grid-cols-2 gap-3">
        <x-field name="weight_kg" label="Weight (kg)" type="number" step="0.01" min="0.01" :value="$shipment->weight_kg" required />
        <x-field name="volume_cbm" label="Volume (CBM)" type="number" step="0.01" min="0" :value="$shipment->volume_cbm" />
    </div>
    <x-field name="expected_departure" label="Expected departure" type="datetime-local" :value="$dt($shipment->expected_departure)" required />
    <x-field name="expected_arrival" label="Expected arrival" type="datetime-local" :value="$dt($shipment->expected_arrival)" required />
    <x-field name="estimated_arrival" label="Estimated arrival (ETA)" type="datetime-local" :value="$dt($shipment->estimated_arrival)" />
</div>

@push('scripts')
<script>
(() => {
    const sel = document.getElementById('modeSel');
    const sync = () => document.querySelectorAll('[data-mode-group]').forEach(g => g.classList.toggle('hidden', g.dataset.modeGroup !== sel.value));
    sel.addEventListener('change', sync); sync();
})();
</script>
@endpush
