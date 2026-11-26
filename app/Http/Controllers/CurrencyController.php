<?php

namespace App\Http\Controllers;

use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CurrencyController extends Controller
{
    public function __construct(private CurrencyService $currency) {}

    public function index(Request $request): View
    {
        $base = $request->validate(['base' => ['nullable', Rule::in(array_keys(CurrencyService::CURRENCIES))]])['base'] ?? 'USD';

        return view('currency.index', [
            'base' => $base,
            'currencies' => CurrencyService::CURRENCIES,
            'data' => $this->currency->rates($base), // null => error state in the view
        ]);
    }

    public function convert(Request $request): JsonResponse
    {
        $codes = array_keys(CurrencyService::CURRENCIES);
        $v = $request->validate([
            'from' => ['required', Rule::in($codes)],
            'to' => ['required', Rule::in($codes)],
            'amount' => ['required', 'numeric', 'min:0', 'max:1000000000000'],
        ]);

        $r = $this->currency->convert($v['from'], $v['to'], (float) $v['amount']);

        return $r === null
            ? response()->json(['message' => 'Exchange rate service is unavailable. Please try again.'], 503)
            : response()->json(['data' => $r + ['from' => $v['from'], 'to' => $v['to'], 'amount' => (float) $v['amount']]]);
    }
}
