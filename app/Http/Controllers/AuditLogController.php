<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Compliance\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $query = AuditLog::with('user')
            ->where('school_id', session('school_id'))
            ->latest();

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->string('action'));
        }

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->string('entity_type'));
        }

        $auditLogs = $query->paginate(15)->through(fn (AuditLog $log) => [
            'id' => $log->id,
            'user' => $log->user ? ['name' => $log->user->name] : null,
            'action' => $log->action,
            'entity_type' => $log->entity_type,
            'entity_id' => $log->entity_id,
            'ip_address' => $log->ip_address,
            'created_at' => $log->created_at?->toIso8601String(),
        ]);

        return inertia('audit-logs/index', ['auditLogs' => $auditLogs]);
    }
}
