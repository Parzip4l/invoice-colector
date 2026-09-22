<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Models\NumberingRegister;
use App\Modules\InvoiceVerification\Domain\Models\TransactionType;
use App\Modules\InvoiceVerification\Services\NumberingRegisterExportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NumberingRegisterController extends Controller
{
    public function __construct(
        protected NumberingRegisterExportService $exportService,
    ) {
    }

    public function index(Request $request)
    {
        abort_unless($request->user()?->hasRole(RoleCode::ADMIN_DIVISI, RoleCode::AKUNTANSI, RoleCode::FINANCE), 403);

        $sort = in_array($request->query('sort'), ['register_number', 'vendor_name', 'invoice_number', 'invoice_date', 'memo_number', 'contract_number', 'invoice_value', 'upload_date', 'generated_at'], true)
            ? $request->query('sort')
            : 'generated_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $filters = $this->filters($request);

        $registers = $this->baseQuery($filters)
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();
        $statuses = TransactionStatus::workflowCases();
        $transactionTypes = TransactionType::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('invoice-verification.numbering-registers.index', compact('registers', 'sort', 'direction', 'filters', 'statuses', 'transactionTypes'));
    }

    public function export(Request $request)
    {
        abort_unless($request->user()?->hasRole(RoleCode::ADMIN_DIVISI, RoleCode::AKUNTANSI, RoleCode::FINANCE), 403);

        $filters = $this->filters($request);
        $registers = $this->baseQuery($filters)
            ->orderBy('received_date')
            ->orderBy('register_number')
            ->get();
        $path = tempnam(sys_get_temp_dir(), 'numbering-register-').'.xlsx';

        $this->exportService->export($registers, $path);

        return response()
            ->download($path, 'numbering-register-'.now()->format('Ymd-His').'.xlsx')
            ->deleteFileAfterSend(true);
    }

    public function update(Request $request, NumberingRegister $numberingRegister): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(RoleCode::ADMIN_DIVISI, RoleCode::AKUNTANSI), 403);

        if ($request->filled('account_number')) {
            $request->merge(['account_number' => preg_replace('/[\s-]+/', '', (string) $request->input('account_number'))]);
        }

        $request->merge([
            'hardcopy_received' => $request->boolean('hardcopy_received'),
        ]);

        $payload = $request->validate([
            'register_number' => ['required', 'string', 'max:255'],
            'received_date' => ['required', 'date'],
            'invoice_number' => ['required', 'string', 'max:255'],
            'invoice_date' => ['nullable', 'date'],
            'upload_date' => ['nullable', 'date'],
            'hardcopy_received' => ['boolean'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'regex:/^\d{6,30}$/'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'contract_number' => ['nullable', 'string', 'max:255'],
            'invoice_value' => ['nullable', 'numeric', 'min:0'],
            'ppn_value' => ['nullable', 'numeric', 'min:0'],
            'pph_value' => ['nullable', 'numeric', 'min:0'],
            'discount_deduction_stamp_value' => ['nullable', 'numeric'],
            'tax_invoice_number' => ['nullable', 'string', 'max:255'],
            'tax_invoice_date' => ['nullable', 'date'],
            'gr_number' => ['nullable', 'string', 'max:255'],
            'journal_type' => ['nullable', 'string', 'max:255'],
            'payment_expedition_date' => ['nullable', 'date'],
            'payment_date' => ['nullable', 'date'],
            'journal_status' => ['nullable', 'string', 'max:255'],
            'return_date' => ['nullable', 'date'],
            'return_amount' => ['nullable', 'numeric', 'min:0'],
            'spuk_status' => ['nullable', 'string', 'max:255'],
            'spuk_notes' => ['nullable', 'string'],
            'department_head_name' => ['nullable', 'string', 'max:255'],
            'division_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ], [
            'account_number.regex' => 'Nomor rekening hanya boleh berisi angka, 6 sampai 30 digit.',
            'invoice_value.numeric' => 'Nilai invoice harus berupa angka.',
            'invoice_value.min' => 'Nilai invoice tidak boleh kurang dari 0.',
        ]);

        $numberingRegister->update($payload);

        return redirect()
            ->route('invoice-verification.numbering-registers.index')
            ->with('success', 'Data penomoran berhasil diperbarui.');
    }

    private function filters(Request $request): array
    {
        return [
            'search' => trim((string) $request->query('search', '')),
            'status' => $request->query('status'),
            'transaction_type_id' => $request->query('transaction_type_id'),
            'received_from' => $request->query('received_from'),
            'received_to' => $request->query('received_to'),
            'invoice_from' => $request->query('invoice_from'),
            'invoice_to' => $request->query('invoice_to'),
            'upload_from' => $request->query('upload_from'),
            'upload_to' => $request->query('upload_to'),
            'generated_from' => $request->query('generated_from'),
            'generated_to' => $request->query('generated_to'),
            'invoice_value_min' => $request->query('invoice_value_min'),
            'invoice_value_max' => $request->query('invoice_value_max'),
        ];
    }

    private function baseQuery(array $filters): Builder
    {
        return NumberingRegister::query()
            ->with([
                'transaction.transactionType',
                'transaction.vendor',
                'transaction.division',
                'transaction.owner',
                'transaction.parentSpuTransaction',
                'transaction.latestDocuments.documentType',
            ])
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $needle = '%'.mb_strtolower($filters['search']).'%';

                $query->where(function ($innerQuery) use ($needle) {
                    $innerQuery
                        ->whereRaw('LOWER(register_number) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(vendor_name) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(invoice_number) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(memo_number) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(contract_number) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(bank_name) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(account_number) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(account_name) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(tax_invoice_number) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(department_head_name) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(division_name) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(description) LIKE ?', [$needle]);
                });
            })
            ->when($filters['status'], fn ($query, $status) => $query->whereHas('transaction', fn ($transactionQuery) => $transactionQuery->where('status', $status)))
            ->when($filters['transaction_type_id'], fn ($query, $typeId) => $query->whereHas('transaction', fn ($transactionQuery) => $transactionQuery->where('transaction_type_id', $typeId)))
            ->when($filters['received_from'], fn ($query, $date) => $query->whereDate('received_date', '>=', $date))
            ->when($filters['received_to'], fn ($query, $date) => $query->whereDate('received_date', '<=', $date))
            ->when($filters['invoice_from'], fn ($query, $date) => $query->whereDate('invoice_date', '>=', $date))
            ->when($filters['invoice_to'], fn ($query, $date) => $query->whereDate('invoice_date', '<=', $date))
            ->when($filters['upload_from'], fn ($query, $date) => $query->whereDate('upload_date', '>=', $date))
            ->when($filters['upload_to'], fn ($query, $date) => $query->whereDate('upload_date', '<=', $date))
            ->when($filters['generated_from'], fn ($query, $date) => $query->whereDate('generated_at', '>=', $date))
            ->when($filters['generated_to'], fn ($query, $date) => $query->whereDate('generated_at', '<=', $date))
            ->when($filters['invoice_value_min'], fn ($query, $amount) => $query->where('invoice_value', '>=', (float) preg_replace('/[^0-9.]/', '', (string) $amount)))
            ->when($filters['invoice_value_max'], fn ($query, $amount) => $query->where('invoice_value', '<=', (float) preg_replace('/[^0-9.]/', '', (string) $amount)));
    }
}
