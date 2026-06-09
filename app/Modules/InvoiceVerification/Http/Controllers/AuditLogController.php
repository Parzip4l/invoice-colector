<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        abort_if($request->user()?->hasRole(RoleCode::VENDOR), 403);

        $auditLogs = AuditLog::query()
            ->when($request->string('module')->toString(), fn ($query, $module) => $query->where('module', $module))
            ->when($request->string('action')->toString(), fn ($query, $action) => $query->where('action', $action))
            ->when($request->string('transaction_id')->toString(), fn ($query, $transactionId) => $query->where('transaction_id', $transactionId))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('invoice-verification.audit-logs.index', compact('auditLogs'));
    }
}
