<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Models\CompiledDocument;
use App\Modules\InvoiceVerification\Services\AuditLogService;
use App\Modules\InvoiceVerification\Services\Contracts\DocumentCompiler;
use App\Modules\InvoiceVerification\Services\FinalizationService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CompiledDocumentController extends Controller
{
    public function __construct(
        protected FinalizationService $finalizationService,
        protected DocumentCompiler $documentCompiler,
        protected AuditLogService $auditLogService,
    ) {}

    public function index(Request $request)
    {
        $sort = in_array($request->query('sort'), ['transaction', 'compiled_file_name', 'total_files', 'compiled_at'], true)
            ? $request->query('sort')
            : 'compiled_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $search = trim((string) $request->query('search', ''));

        $compiledQuery = CompiledDocument::query()
            ->select('compiled_documents.*')
            ->with('transaction.transactionType')
            ->whereHas('transaction', function ($transactionQuery) use ($request) {
                $user = $request->user();

                if ($user->hasRole(RoleCode::ADMIN_DIVISI, RoleCode::AKUNTANSI, RoleCode::FINANCE)) {
                    return;
                }

                if ($user->hasRole(RoleCode::VENDOR)) {
                    $vendorId = $user->linkedVendor()?->id;

                    $transactionQuery->where(function ($query) use ($user, $vendorId) {
                        $query->where('owner_user_id', $user->id);

                        if ($vendorId) {
                            $query->orWhere('vendor_id', $vendorId);
                        }
                    });

                    return;
                }

                if ($user->hasRole(RoleCode::USER_DIVISI)) {
                    $transactionQuery->where('owner_user_id', $user->id);

                    return;
                }

                if ($user->hasRole(RoleCode::KEPALA_DEPARTEMEN)) {
                    $transactionQuery->where(function ($query) use ($user) {
                        $query->where('department_id', $user->department_id)
                            ->orWhereHas('approvalTransactions', fn ($approvalQuery) => $approvalQuery->where('approver_user_id', $user->id));
                    });

                    return;
                }

                if ($user->hasRole(RoleCode::KEPALA_DIVISI)) {
                    $transactionQuery->where(function ($query) use ($user) {
                        $query->where('division_id', $user->division_id)
                            ->orWhereHas('approvalTransactions', fn ($approvalQuery) => $approvalQuery->where('approver_user_id', $user->id));
                    });

                    return;
                }

                $transactionQuery->whereRaw('1 = 0');
            })
            ->when($search !== '', function ($query) use ($search) {
                $needle = '%'.mb_strtolower($search).'%';

                $query->where(function ($innerQuery) use ($needle) {
                    $innerQuery
                        ->whereRaw('LOWER(compiled_documents.compiled_file_name) LIKE ?', [$needle])
                        ->orWhereHas('transaction', fn ($transactionQuery) => $transactionQuery->whereRaw('LOWER(registration_number) LIKE ?', [$needle]));
                });
            });

        if ($sort === 'transaction') {
            $compiledQuery
                ->leftJoin('transactions', 'transactions.id', '=', 'compiled_documents.transaction_id')
                ->orderBy('transactions.registration_number', $direction);
        } else {
            $compiledQuery->orderBy('compiled_documents.'.$sort, $direction);
        }

        $compiledDocuments = $compiledQuery
            ->paginate(10)
            ->withQueryString();

        return view('invoice-verification.compiled-documents.index', compact('compiledDocuments', 'sort', 'direction', 'search'));
    }

    public function show(CompiledDocument $compiledDocument)
    {
        $compiledDocument->load(['transaction', 'items', 'compiler', 'archivedBy']);
        $this->authorize('view', $compiledDocument->transaction);

        return view('invoice-verification.compiled-documents.show', compact('compiledDocument'));
    }

    public function download(Request $request, CompiledDocument $compiledDocument): BinaryFileResponse
    {
        $compiledDocument->load(['transaction.transactionType', 'items']);
        $transaction = $compiledDocument->transaction;

        $this->authorize('view', $transaction);

        $documents = $this->finalizationService->collectCompiledDocuments($transaction);
        $result = $this->documentCompiler->compileTemporary($transaction, $documents);

        $this->auditLogService->log(
            module: 'compiled_document',
            action: 'download_on_demand',
            actor: $request->user(),
            transaction: $transaction,
            referenceType: CompiledDocument::class,
            referenceId: $compiledDocument->id,
            newValue: [
                'file_name' => $result['file_name'],
                'total_files' => $result['total_files'],
                'stored_on_server' => false,
            ],
        );

        return response()
            ->download(
                $result['temporary_path'],
                $result['file_name'],
                ['Content-Type' => 'application/pdf'],
            )
            ->deleteFileAfterSend(true);
    }
}
