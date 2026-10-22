<?php

namespace App\Modules\InvoiceVerification\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\TransactionDocument;
use App\Modules\InvoiceVerification\Services\MobileInvoiceCollectorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MobileInvoiceCollectorController extends Controller
{
    public function __construct(private readonly MobileInvoiceCollectorService $invoices) {}

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json(['success' => true, 'data' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role_code?->value ?? $user->role_code,
        ]]);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:150',
            'status' => 'nullable|string|max:60',
            'type' => 'nullable|string|max:60',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $this->invoices->accessibleQuery($request->user())
            ->with(['transactionType', 'vendor', 'owner', 'invoiceMetadata'])
            ->when($validated['search'] ?? null, function ($builder, $search) {
                $builder->where(function ($nested) use ($search) {
                    $nested->where('registration_number', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhereHas('invoiceMetadata', fn ($metadata) => $metadata->where('invoice_number', 'like', "%{$search}%"))
                        ->orWhereHas('vendor', fn ($vendor) => $vendor->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($validated['status'] ?? null, fn ($builder, $status) => $builder->where('status', strtoupper($status)))
            ->when($validated['type'] ?? null, fn ($builder, $type) => $builder->whereHas('transactionType', fn ($transactionType) => $transactionType->where('code', strtoupper($type))))
            ->latest();

        $paginator = $query->paginate((int) ($validated['per_page'] ?? 15));

        return response()->json([
            'success' => true,
            'data' => $paginator->through(fn (Transaction $transaction) => $this->invoices->serializeSummary($transaction)),
        ]);
    }

    public function show(Request $request, Transaction $transaction): JsonResponse
    {
        $this->authorizeAccess($request, $transaction);

        return response()->json(['success' => true, 'data' => $this->invoices->serialize($transaction)]);
    }

    public function history(Request $request, Transaction $transaction): JsonResponse
    {
        $this->authorizeAccess($request, $transaction);

        return response()->json(['success' => true, 'data' => $this->invoices->history($transaction)]);
    }

    public function document(Request $request, Transaction $transaction, TransactionDocument $document): StreamedResponse
    {
        $this->authorizeAccess($request, $transaction);
        abort_unless((string) $document->transaction_id === (string) $transaction->id && $document->is_latest, 404);

        $disk = Storage::disk($document->file_disk);
        abort_unless($disk->exists($document->file_path), 404, 'Dokumen tidak ditemukan.');

        return $disk->download($document->file_path, $document->file_name, [
            'Content-Type' => $document->mime_type ?: 'application/octet-stream',
        ]);
    }

    private function authorizeAccess(Request $request, Transaction $transaction): void
    {
        abort_unless($this->invoices->canAccess($request->user(), $transaction), 403, 'User tidak memiliki akses ke invoice ini.');
    }
}
