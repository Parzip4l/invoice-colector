<?php

namespace App\Modules\InvoiceVerification\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\InvoiceVerification\Domain\Enums\ApprovalStatus;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStep;
use App\Modules\InvoiceVerification\Domain\Models\ApprovalTransaction;
use App\Modules\InvoiceVerification\Services\ApprovalWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PortalApprovalController extends Controller
{
    public function __construct(private readonly ApprovalWorkflowService $workflow) {}

    public function index(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return $this->unauthorized();
        }
        $data = $request->validate(['approver_email' => ['required', 'email'], 'status' => ['nullable', 'string']]);
        $status = strtoupper((string) ($data['status'] ?? 'PENDING'));
        $rows = ApprovalTransaction::query()
            ->with(['transaction.transactionType', 'transaction.owner', 'transaction.vendor', 'transaction.invoiceMetadata', 'approvalFlow', 'approver'])
            ->whereHas('approver', fn ($q) => $q->whereRaw('LOWER(email) = ?', [mb_strtolower($data['approver_email'])]))
            ->where('status', $status)
            ->latest('created_at')->get()
            ->filter(fn (ApprovalTransaction $approval) => $status !== ApprovalStatus::PENDING->value || $this->isActiveStep($approval))
            ->map(fn (ApprovalTransaction $approval) => $this->serialize($approval))->values();

        return response()->json(['success' => true, 'data' => ['data' => $rows]]);
    }

    public function show(Request $request, ApprovalTransaction $approvalTransaction): JsonResponse
    {
        if (! $this->authorized($request)) {
            return $this->unauthorized();
        }
        $data = $request->validate(['approver_email' => ['required', 'email']]);
        $approvalTransaction->load(['transaction.transactionType', 'transaction.owner', 'transaction.vendor', 'transaction.invoiceMetadata', 'approvalFlow', 'approver']);
        if (strcasecmp((string) $approvalTransaction->approver?->email, $data['approver_email']) !== 0) {
            return response()->json(['message' => 'Anda tidak berwenang melihat approval ini.'], 403);
        }

        return response()->json(['success' => true, 'data' => $this->serialize($approvalTransaction)]);
    }

    public function decision(Request $request, ApprovalTransaction $approvalTransaction): JsonResponse
    {
        if (! $this->authorized($request)) {
            return $this->unauthorized();
        }
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'comment' => ['nullable', 'string', 'max:2000'],
            'portal_reference_id' => ['required', 'uuid'],
            'approver' => ['required', 'array'],
            'approver.email' => ['required', 'email'],
            'approver.name' => ['nullable', 'string', 'max:255'],
        ]);
        $approvalTransaction->load(['approver', 'approvalFlow', 'transaction']);
        if (strcasecmp((string) $approvalTransaction->approver?->email, $data['approver']['email']) !== 0) {
            return response()->json(['message' => 'Approver Portal tidak sesuai dengan approver transaksi.'], 403);
        }
        try {
            $transaction = $this->workflow->process(
                $approvalTransaction,
                $approvalTransaction->approver,
                $data['status'] === 'approved' ? ApprovalStatus::APPROVED : ApprovalStatus::REJECTED,
                $data['comment'] ?? null,
            );
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first() ?: 'Approval tidak dapat diproses.';
            $status = str_contains(mb_strtolower($message), 'sudah pernah') ? 409 : 422;

            return response()->json(['message' => $message, 'errors' => $exception->errors()], $status);
        }
        $approvalTransaction->refresh();

        return response()->json(['success' => true, 'message' => 'Approval Invoice Collector berhasil diproses.', 'data' => [
            'invoice_reference_id' => (string) $transaction->id,
            'approval' => $this->serialize($approvalTransaction->load(['transaction.transactionType', 'transaction.owner', 'transaction.vendor', 'transaction.invoiceMetadata', 'approvalFlow', 'approver'])),
        ]]);
    }

    private function authorized(Request $request): bool
    {
        $timestamp = (string) $request->header('X-Invoice-Timestamp');
        $signature = (string) $request->header('X-Invoice-Signature');
        $secret = (string) config('invoice_verification.portal_api.shared_secret');

        return $secret !== '' && ctype_digit($timestamp)
            && abs(now()->timestamp - (int) $timestamp) <= (int) config('invoice_verification.portal_api.signature_ttl', 300)
            && hash_equals(hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret), $signature);
    }

    private function unauthorized(): JsonResponse
    {
        return response()->json(['message' => 'Signature Portal tidak valid.'], 401);
    }

    private function isActiveStep(ApprovalTransaction $approval): bool
    {
        $expected = (int) $approval->approvalFlow?->step_no === 1 ? TransactionStep::KADEP_REVIEW : TransactionStep::KADIV_REVIEW;

        return $approval->transaction?->current_step === $expected;
    }

    private function serialize(ApprovalTransaction $approval): array
    {
        $transaction = $approval->transaction;
        $metadata = $transaction?->invoiceMetadata;

        return [
            'module' => 'invoice_collector', 'approval_id' => (string) $approval->id,
            'invoice_reference_id' => (string) $transaction?->id,
            'status' => strtolower($approval->status->value),
            'step' => ['number' => (int) $approval->approvalFlow?->step_no, 'name' => $approval->approvalFlow?->step_name],
            'transaction' => [
                'id' => (string) $transaction?->id, 'registration_number' => $transaction?->registration_number,
                'title' => $transaction?->title, 'type' => $transaction?->transactionType?->name,
                'vendor' => $transaction?->vendor?->name,
                'amount' => (float) (($metadata?->invoice_value ?? 0) + ($metadata?->ppn_value ?? 0)),
                'requester' => ['name' => $transaction?->owner?->name, 'email' => $transaction?->owner?->email],
            ],
            'approver' => ['name' => $approval->approver?->name, 'email' => $approval->approver?->email],
            'comment' => $approval->notes, 'created_at' => $approval->created_at?->toIso8601String(),
            'acted_at' => $approval->action_at?->toIso8601String(),
        ];
    }
}
