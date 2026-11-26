<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['entity' => ['nullable', 'string', 'max:50'], 'action' => ['nullable', 'string', 'max:50']]);

        $logs = AuditLog::query()
            ->when($request->input('entity'), fn ($q, $v) => $q->where('entity_type', $v))
            ->when($request->input('action'), fn ($q, $v) => $q->where('action', 'like', "%{$v}%"))
            ->with('user')->orderByDesc('id')->paginate(20)->withQueryString();

        return view('audit.index', [
            'logs' => $logs,
            'entities' => AuditLog::query()->distinct()->orderBy('entity_type')->pluck('entity_type'),
        ]);
    }
}
