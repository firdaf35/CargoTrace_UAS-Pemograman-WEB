@props(['name', 'label', 'type' => 'text', 'value' => null])
<label class="block text-sm">
    <span class="mb-1 block font-medium text-slate-700">{{ $label }}</span>
    <input type="{{ $type }}" name="{{ $name }}" value="{{ old($name, $value) }}"
        {{ $attributes->merge(['class' => 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500']) }}>
    @error($name)<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
</label>
