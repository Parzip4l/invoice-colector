<?php

namespace App\Modules\InvoiceVerification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Models\NumberingRegister;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NumberingRegisterController extends Controller
{
    public function index()
    {
        $registers = NumberingRegister::query()
            ->with('transaction.transactionType')
            ->latest('generated_at')
            ->paginate(10);

        return view('invoice-verification.numbering-registers.index', compact('registers'));
    }

    public function update(Request $request, NumberingRegister $numberingRegister): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(RoleCode::ADMIN_DIVISI, RoleCode::AKUNTANSI), 403);

        $payload = $request->validate([
            'register_number' => ['required', 'string', 'max:255'],
            'received_date' => ['required', 'date'],
            'invoice_number' => ['required', 'string', 'max:255'],
            'invoice_date' => ['nullable', 'date'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'invoice_value' => ['nullable', 'numeric', 'min:0'],
            'ppn_value' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        $numberingRegister->update($payload);

        return redirect()
            ->route('invoice-verification.numbering-registers.index')
            ->with('success', 'Data penomoran berhasil diperbarui.');
    }
}
