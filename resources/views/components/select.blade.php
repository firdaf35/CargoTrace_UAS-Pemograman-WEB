@props(['name', 'label' => null, 'options' => [], 'value' => null, 'placeholder' => null])
<label class="block text-sm">
    @if ($label)<span class="mb-1 block font-medium text-slate-700">{{ $label }}</span>@endif
    <select name="{{ $name }}"
        {{ $attributes->merge(['class' => 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500']) }}>
        @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $k => $label_)
            <option value="{{ $k }}" @selected((string) old($name, $value) === (string) $k)>{{ $label_ }}</option>
        @endforeach
    </select>
    @error($name)<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
</label>
