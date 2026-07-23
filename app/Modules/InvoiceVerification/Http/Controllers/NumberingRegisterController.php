<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Models\NumberingRegister;
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
        $sort = in_array($request->query('sort'), ['register_number', 'vendor_name', 'invoice_number', 'memo_number', 'invoice_value', 'generated_at'], true)
            ? $request->query('sort')
            : 'generated_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $search = trim((string) $request->query('search', ''));

        $registers = $this->baseQuery($search)
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return view('invoice-verification.numbering-registers.index', compact('registers', 'sort', 'direction', 'search'));
    }

    public function export(Request $request)
    {
        abort_unless($request->user()?->hasRole(RoleCode::ADMIN_DIVISI, RoleCode::AKUNTANSI, RoleCode::FINANCE), 403);

        $search = trim((string) $request->query('search', ''));
        $registers = $this->baseQuery($search)
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

        $payload = $request->validate([
            'register_number' => ['required', 'string', 'max:255'],
            'received_date' => ['required', 'date'],
            'invoice_number' => ['required', 'string', 'max:255'],
            'invoice_date' => ['nullable', 'date'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'regex:/^\d{6,30}$/'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'invoice_value' => ['nullable', 'numeric', 'min:0'],
            'ppn_value' => ['nullable', 'numeric', 'min:0'],
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

    private function baseQuery(string $search): Builder
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
            ->when($search !== '', function ($query) use ($search) {
                $needle = '%'.mb_strtolower($search).'%';

                $query->where(function ($innerQuery) use ($needle) {
                    $innerQuery
                        ->whereRaw('LOWER(register_number) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(vendor_name) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(invoice_number) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(memo_number) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(bank_name) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(account_number) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(account_name) LIKE ?', [$needle]);
                });
            });
    }
}
