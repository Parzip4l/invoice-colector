<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Domain\Enums\ApprovalStatus;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Models\ApprovalTransaction;
use App\Modules\InvoiceVerification\Http\Requests\ProcessApprovalRequest;
use App\Modules\InvoiceVerification\Services\ApprovalWorkflowService;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function __construct(
        protected ApprovalWorkflowService $approvalWorkflowService,
    ) {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', ApprovalTransaction::class);

        $user = $request->user();
        $status = $request->query('status');
        $search = trim((string) $request->query('search', ''));

        $query = ApprovalTransaction::query()
            ->with([
                'transaction.transactionType',
                'transaction.vendor',
                'transaction.owner',
                'transaction.invoiceMetadata',
                'approvalFlow',
                'approver',
            ])
            ->where(function ($sub) use ($user) {
                $sub->where('approver_user_id', $user->id);

                if ($user->hasRole(RoleCode::KEPALA_DEPARTEMEN)) {
                    $sub->orWhere(function ($q) use ($user) {
                        $q->whereHas('approvalFlow', fn ($af) => $af->where('step_no', 1))
                            ->whereHas('transaction', fn ($t) => $t->where('department_id', $user->department_id));
                    });
                }

                if ($user->hasRole(RoleCode::KEPALA_DIVISI)) {
                    $sub->orWhere(function ($q) use ($user) {
                        $q->whereHas('approvalFlow', fn ($af) => $af->where('step_no', 2))
                            ->whereHas('transaction', fn ($t) => $t->where('division_id', $user->division_id));
                    });
                }

                if ($user->hasRole(RoleCode::FINANCE)) {
                    $sub->orWhere(function ($q) {
                        $q->whereHas('approvalFlow', fn ($af) => $af->where('step_no', 3));
                    });
                }

                if ($user->hasRole(RoleCode::ADMIN_DIVISI)) {
                    $sub->orWhereRaw('1 = 1');
                }
            })
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $needle = '%'.mb_strtolower($search).'%';
                $q->whereHas('transaction', function ($t) use ($needle) {
                    $t->whereRaw('LOWER(registration_number) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(title) LIKE ?', [$needle])
                        ->orWhereHas('vendor', fn ($v) => $v->whereRaw('LOWER(name) LIKE ?', [$needle]))
                        ->orWhereHas('owner', fn ($o) => $o->whereRaw('LOWER(name) LIKE ?', [$needle]));
                });
            })
            ->latest('created_at');

        $approvals = $query->paginate(10)->withQueryString();

        return view('invoice-verification.approvals.index', compact('approvals', 'status', 'search'));
    }

    public function update(ProcessApprovalRequest $request, ApprovalTransaction $approvalTransaction)
    {
        $this->authorize('process', $approvalTransaction);

        $targetStatus = ApprovalStatus::from($request->validated('status'));
        $notes = $request->validated('notes');

        $transaction = $this->approvalWorkflowService->process(
            $approvalTransaction,
            $request->user(),
            $targetStatus,
            $notes,
        );

        $message = $targetStatus === ApprovalStatus::APPROVED
            ? 'Persetujuan berhasil diproses.'
            : 'Transaksi berhasil ditolak dan dikembalikan ke pemohon untuk revisi.';

        return redirect()
            ->back()
            ->with('success', $message);
    }
}
