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

        $sort = in_array($request->query('sort'), ['created_at', 'module', 'action', 'transaction_id', 'reference_type'], true)
            ? $request->query('sort')
            : 'created_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $search = trim((string) $request->query('search', ''));
        $module = (string) $request->query('module', '');
        $action = (string) $request->query('action', '');
        $transactionId = (string) $request->query('transaction_id', '');

        $auditLogs = AuditLog::query()
            ->when($module !== '', fn ($query) => $query->where('module', $module))
            ->when($action !== '', fn ($query) => $query->where('action', $action))
            ->when($transactionId !== '', fn ($query) => $query->where('transaction_id', $transactionId))
            ->when($search !== '', function ($query) use ($search) {
                $needle = '%'.mb_strtolower($search).'%';

                $query->where(function ($innerQuery) use ($needle) {
                    $innerQuery
                        ->whereRaw('LOWER(module) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(action) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(reference_type) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(reference_id) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(transaction_id) LIKE ?', [$needle]);
                });
            })
            ->orderBy($sort, $direction)
            ->paginate(20)
            ->withQueryString();

        return view('invoice-verification.audit-logs.index', compact('auditLogs', 'sort', 'direction', 'search', 'module', 'action', 'transactionId'));
    }
}
