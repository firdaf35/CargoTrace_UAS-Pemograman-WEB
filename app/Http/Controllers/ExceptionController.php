<?php

namespace App\Http\Controllers;

use App\Enums\ExceptionStatus;
use App\Enums\Severity;
use App\Http\Requests\StoreExceptionRequest;
use App\Http\Requests\UpdateExceptionRequest;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Services\ExceptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExceptionController extends Controller
{
    public function __construct(private ExceptionService $exceptions) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $request->validate(['status' => ['nullable', 'string', 'max:20'], 'severity' => ['nullable', 'string', 'max:10']]);

        $items = ShipmentException::query()
            ->whereHas('shipment', fn ($q) => $q->visibleTo($user))
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->input('severity'), fn ($q, $v) => $q->where('severity', $v))
            ->with(['shipment.customer', 'reporter'])
            ->orderByRaw("case status when 'OPEN' then 0 when 'INVESTIGATING' then 1 else 2 end")
            ->orderByDesc('detected_at')
            ->paginate(12)->withQueryString();

        return view('exceptions.index', [
            'items' => $items,
            'statuses' => ExceptionStatus::values(),
            'severities' => Severity::values(),
        ]);
    }

    public function store(StoreExceptionRequest $request, Shipment $shipment): RedirectResponse
    {
        $this->exceptions->create($shipment, $request->validated(), $request->user());

        return redirect()->route('shipments.show', $shipment)->with('success', 'Exception reported.');
    }

    public function update(UpdateExceptionRequest $request, ShipmentException $exception): RedirectResponse
    {
        $this->exceptions->update($exception, $request->validated(), $request->user());

        return back()->with('success', 'Exception updated.');
    }
}
