@can('manageExceptions', $ex->shipment)
    @if (count($ex->status->allowedNext()))
        <form method="POST" action="{{ route('exceptions.update', $ex) }}" class="mt-2 flex flex-wrap items-center gap-2">
            @csrf @method('PATCH')
            <select name="status" class="rounded-lg border border-slate-300 px-2 py-1 text-xs">
                @foreach ($ex->status->allowedNext() as $n)<option value="{{ $n->value }}">{{ $n->value }}</option>@endforeach
            </select>
            <input name="resolution_notes" maxlength="1000" placeholder="Notes (optional)" class="w-48 rounded-lg border border-slate-300 px-2 py-1 text-xs">
            <button class="rounded-lg bg-slate-800 px-3 py-1 text-xs text-white hover:bg-slate-900">Update</button>
        </form>
    @endif
@endcan
