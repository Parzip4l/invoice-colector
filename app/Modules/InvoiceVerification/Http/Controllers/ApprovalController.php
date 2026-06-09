<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Domain\Enums\ApprovalStatus;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Models\ApprovalTransaction;
use App\Modules\InvoiceVerification\Http\Requests\ProcessApprovalRequest;
use App\Modules\InvoiceVerification\Services\ApprovalWorkflowService;

class ApprovalController extends Controller
{
    public function __construct(
        protected ApprovalWorkflowService $approvalWorkflowService,
    ) {
    }

    public function index()
    {
        $this->authorize('viewAny', ApprovalTransaction::class);

        $approvalTransactions = ApprovalTransaction::query()
            ->with(['transaction.transactionType', 'approvalFlow'])
            ->where('approver_user_id', auth()->id())
            ->when(auth()->user()?->hasRole(RoleCode::KEPALA_DIVISI), function ($query) {
                $query->whereDoesntHave('transaction.approvalTransactions', function ($approvalQuery) {
                    $approvalQuery->where('status', ApprovalStatus::PENDING)
                        ->whereHas('approvalFlow', fn ($flowQuery) => $flowQuery->where('step_code', RoleCode::KEPALA_DEPARTEMEN->value));
                });
            })
            ->latest()
            ->paginate(10);

        return view('invoice-verification.approvals.index', compact('approvalTransactions'));
    }

    public function update(ProcessApprovalRequest $request, ApprovalTransaction $approvalTransaction)
    {
        $this->approvalWorkflowService->process(
            $approvalTransaction,
            $request->user(),
            ApprovalStatus::from($request->validated('status')),
            $request->validated('notes'),
        );

        return redirect()
            ->route('invoice-verification.approvals.index')
            ->with('success', 'Approval berhasil diproses.');
    }
}
