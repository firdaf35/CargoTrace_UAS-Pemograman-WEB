<?php

namespace App\Http\Controllers;

use App\Models\Carrier;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CarrierController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): View
    {
        return view('carriers.index', ['carriers' => Carrier::withCount(['shipments', 'shipments as delayed_count' => fn ($q) => $q->where('is_delayed', true)])->orderBy('name')->get()]);
    }

    private function rules(?Carrier $carrier = null): array
    {
        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('carriers', 'name')->ignore($carrier)],
            'code' => ['required', 'string', 'max:10', Rule::unique('carriers', 'code')->ignore($carrier)],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $data['is_active'] = $request->boolean('is_active', true);
        $carrier = Carrier::create($data);
        $this->audit->log($request->user(), 'carrier.created', $carrier, null, $carrier->only(['name', 'code', 'is_active']));

        return redirect()->route('carriers.index')->with('success', 'Carrier created.');
    }

    public function edit(Carrier $carrier): View
    {
        return view('carriers.edit', ['carrier' => $carrier]);
    }

    public function update(Request $request, Carrier $carrier): RedirectResponse
    {
        $data = $request->validate($this->rules($carrier));
        $data['is_active'] = $request->boolean('is_active');
        $old = $carrier->only(['name', 'code', 'is_active']);
        $carrier->update($data);
        $this->audit->log($request->user(), 'carrier.updated', $carrier, $old, $carrier->only(['name', 'code', 'is_active']));

        return redirect()->route('carriers.index')->with('success', 'Carrier updated.');
    }

    public function destroy(Request $request, Carrier $carrier): RedirectResponse
    {
        if ($carrier->shipments()->withTrashed()->exists()) {
            return back()->withErrors(['carrier' => 'This carrier has shipments and cannot be deleted. Deactivate it instead.']);
        }
        $this->audit->log($request->user(), 'carrier.deleted', $carrier, $carrier->only(['name', 'code']), null);
        $carrier->delete();

        return redirect()->route('carriers.index')->with('success', 'Carrier deleted.');
    }
}
