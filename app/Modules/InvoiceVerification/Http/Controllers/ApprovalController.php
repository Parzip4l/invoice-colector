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

        $sort = in_array($request->query('sort'), ['transaction', 'step', 'status', 'created_at'], true)
            ? $request->query('sort')
            : 'created_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $approvalQuery = ApprovalTransaction::query()
            ->select('approval_transactions.*')
            ->with(['transaction.transactionType', 'approvalFlow'])
            ->where('approver_user_id', auth()->id())
            ->when(auth()->user()?->hasRole(RoleCode::KEPALA_DIVISI), function ($query) {
                $query->whereDoesntHave('transaction.approvalTransactions', function ($approvalQuery) {
                    $approvalQuery->where('status', ApprovalStatus::PENDING)
                        ->whereHas('approvalFlow', fn ($flowQuery) => $flowQuery->where('step_code', RoleCode::KEPALA_DEPARTEMEN->value));
                });
            })
            ->when($search !== '', function ($query) use ($search) {
                $needle = '%'.mb_strtolower($search).'%';

                $query->where(function ($innerQuery) use ($needle) {
                    $innerQuery
                        ->whereRaw('LOWER(notes) LIKE ?', [$needle])
                        ->orWhereHas('transaction', function ($transactionQuery) use ($needle) {
                            $transactionQuery
                                ->whereRaw('LOWER(registration_number) LIKE ?', [$needle])
                                ->orWhereRaw('LOWER(title) LIKE ?', [$needle]);
                        })
                        ->orWhereHas('approvalFlow', fn ($flowQuery) => $flowQuery->whereRaw('LOWER(step_name) LIKE ?', [$needle]));
                });
            })
            ->when($status !== '', function ($query) use ($status) {
                if (in_array($status, array_column(ApprovalStatus::cases(), 'value'), true)) {
                    $query->where('approval_transactions.status', $status);
                }
            });

        if ($sort === 'transaction') {
            $approvalQuery
                ->leftJoin('transactions', 'transactions.id', '=', 'approval_transactions.transaction_id')
                ->orderBy('transactions.registration_number', $direction);
        } elseif ($sort === 'step') {
            $approvalQuery
                ->leftJoin('approval_flows', 'approval_flows.id', '=', 'approval_transactions.approval_flow_id')
                ->orderBy('approval_flows.step_no', $direction);
        } else {
            $approvalQuery->orderBy('approval_transactions.'.$sort, $direction);
        }

        $approvalTransactions = $approvalQuery
            ->paginate(10)
            ->withQueryString();

        return view('invoice-verification.approvals.index', compact('approvalTransactions', 'sort', 'direction', 'search', 'status'));
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
