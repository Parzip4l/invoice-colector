<?php $__env->startSection('content'); ?>
<?php echo $__env->make('layouts.partials.page-title', ['title' => 'Transactions', 'subtitle' => 'Transaction Detail'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php echo $__env->make('invoice-verification.partials.flash', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<?php
    $user = auth()->user();
    $statusValue = $transaction->status?->value;
    $stepValue = $transaction->current_step?->value;
    $isVendorRevisionView = $user?->hasRole(\App\Modules\InvoiceVerification\Domain\Enums\RoleCode::VENDOR)
        && in_array($statusValue, [
            \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::REVISION_IN_PROGRESS->value,
            \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::NOT_APPROVED->value,
        ], true);
    $invoiceNumber = $transaction->invoiceMetadata?->invoice_number ?: $transaction->title;
    $contractNumber = $transaction->agreementReference?->contract_number
        ?? $transaction->contract_number
        ?? $transaction->invoiceMetadata?->contract_number
        ?? '-';
    $memoNumber = $transaction->memoRequest?->memo_number ?? $transaction->invoiceMetadata?->memo_number ?? '-';
    $documentCount = $transaction->latestDocuments->count();
    $generatedCount = $transaction->generatedDocuments->count() + ($transaction->ppaVerificationSheet?->file_path ? 1 : 0);
    $invoiceValue = $transaction->invoiceMetadata?->invoice_value;

    $stageDefinitions = [
        [
            'key' => 'draft',
            'label' => 'Draft',
            'icon' => 'solar:document-add-outline',
            'statuses' => [\App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::DRAFT->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::VENDOR_INPUT->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::REVISION_IN_PROGRESS->value],
            'steps' => [\App\Modules\InvoiceVerification\Domain\Enums\TransactionStep::VENDOR_INVOICE_INPUT->value],
        ],
        [
            'key' => 'submitted',
            'label' => 'Submitted',
            'icon' => 'solar:plain-2-outline',
            'statuses' => [\App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::DOCUMENT_COLLECTION->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::SUBMITTED->value],
            'steps' => [\App\Modules\InvoiceVerification\Domain\Enums\TransactionStep::INTERNAL_DOCUMENT_UPLOAD->value],
        ],
        [
            'key' => 'in-review',
            'label' => 'In Review',
            'icon' => 'solar:checklist-minimalistic-outline',
            'statuses' => [
                \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::IN_REVIEW->value,
                \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::NOT_APPROVED->value,
                \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::ACCOUNTING_VERIFICATION->value,
                \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::ADMIN_REVIEW->value,
                \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::VENDOR_REVIEW->value,
                \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::ADMIN_GENERATE_DOCUMENTS->value,
                \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::WAITING_APPROVAL->value,
            ],
            'steps' => [
                \App\Modules\InvoiceVerification\Domain\Enums\TransactionStep::ACCOUNTING_ADMINISTRATION->value,
                \App\Modules\InvoiceVerification\Domain\Enums\TransactionStep::ACCOUNTING_INVOICING->value,
                \App\Modules\InvoiceVerification\Domain\Enums\TransactionStep::ACCOUNTING_VERIFICATION->value,
                \App\Modules\InvoiceVerification\Domain\Enums\TransactionStep::ADMIN_DOCUMENT_REVIEW->value,
                \App\Modules\InvoiceVerification\Domain\Enums\TransactionStep::VENDOR_DOCUMENT_REVIEW->value,
                \App\Modules\InvoiceVerification\Domain\Enums\TransactionStep::INITIAL_DOCUMENT_GENERATION->value,
                \App\Modules\InvoiceVerification\Domain\Enums\TransactionStep::INITIAL_APPROVAL->value,
                \App\Modules\InvoiceVerification\Domain\Enums\TransactionStep::KADEP_REVIEW->value,
                \App\Modules\InvoiceVerification\Domain\Enums\TransactionStep::KADIV_REVIEW->value,
            ],
        ],
        [
            'key' => 'received',
            'label' => 'Received',
            'icon' => 'solar:inbox-line-outline',
            'statuses' => [\App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::RECEIVED->value],
            'steps' => [],
        ],
        [
            'key' => 'scheduling-payment',
            'label' => 'Scheduling Payment',
            'icon' => 'solar:wallet-money-outline',
            'statuses' => [\App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::SCHEDULING_PAYMENT->value],
            'steps' => [\App\Modules\InvoiceVerification\Domain\Enums\TransactionStep::FINANCE_PROCESS->value],
        ],
        [
            'key' => 'paid',
            'label' => 'Paid',
            'icon' => 'solar:check-circle-outline',
            'statuses' => [\App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::PAID->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::COMPLETED->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::ARCHIVED->value],
            'steps' => [\App\Modules\InvoiceVerification\Domain\Enums\TransactionStep::FINALIZATION->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStep::ARCHIVE->value],
        ],
    ];

    $currentStageIndex = collect($stageDefinitions)->search(function ($stage) use ($statusValue, $stepValue) {
        return in_array($statusValue, $stage['statuses'], true) || in_array($stepValue, $stage['steps'], true);
    });
    $currentStageIndex = $currentStageIndex === false ? 0 : $currentStageIndex;
    if (in_array($statusValue, [\App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::COMPLETED->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::ARCHIVED->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::PAID->value], true)) {
        $currentStageIndex = count($stageDefinitions) - 1;
    }

    $currentStage = $stageDefinitions[$currentStageIndex];
    $currentStepLabel = match ($transaction->status) {
        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::DRAFT,
        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::VENDOR_INPUT => \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::DRAFT->label(),
        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::REVISION_IN_PROGRESS,
        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::NOT_APPROVED => \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::NOT_APPROVED->label(),
        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::ACCOUNTING_VERIFICATION,
        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::ADMIN_REVIEW,
        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::VENDOR_REVIEW,
        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::ADMIN_GENERATE_DOCUMENTS,
        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::WAITING_APPROVAL => \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::IN_REVIEW->label(),
        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::COMPLETED,
        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::ARCHIVED => \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::PAID->label(),
        default => $transaction->status?->label() ?? 'Draft',
    };
    $helperTone = in_array($statusValue, [\App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::REVISION_IN_PROGRESS->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::NOT_APPROVED->value], true) ? 'warning' : 'primary';
    if (in_array($statusValue, [\App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::PAID->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::COMPLETED->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::ARCHIVED->value], true)) {
        $helperTone = 'success';
    }
    $helperText = in_array($statusValue, [\App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::REVISION_IN_PROGRESS->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::NOT_APPROVED->value], true)
        ? 'Transaksi sedang dalam tahap revisi dan menunggu perbaikan dokumen dari vendor.'
        : 'Saat ini transaksi berada pada tahap '.$currentStage['label'].' dengan fokus kerja: '.$currentStepLabel.'.';
    $showWorkflowHelper = ! in_array($statusValue, [
        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::PAID->value,
        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::COMPLETED->value,
        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::ARCHIVED->value,
    ], true);

    $overviewItems = [
        ['label' => 'Vendor', 'value' => $transaction->vendor?->name ?? '-', 'icon' => 'solar:buildings-2-outline'],
        ['label' => 'Divisi / Departemen', 'value' => $transaction->division?->name ?? '-', 'meta' => $transaction->department?->name, 'icon' => 'solar:users-group-rounded-outline'],
        ['label' => 'Memo', 'value' => $memoNumber, 'route' => $transaction->memoRequest?->file_path ? route('invoice-verification.master-data.memo-requests.preview', $transaction->memoRequest) : null, 'link' => 'Lihat memo', 'preview' => true, 'icon' => 'solar:notes-outline'],
        ['label' => 'Nomor Kontrak', 'value' => $contractNumber, 'route' => $transaction->agreementReference?->file_path ? route('invoice-verification.master-data.agreement-references.preview', $transaction->agreementReference) : null, 'link' => 'Lihat kontrak', 'preview' => true, 'icon' => 'solar:document-text-outline'],
        ['label' => 'Jenis Transaksi', 'value' => $transaction->transactionType?->name ?? '-', 'icon' => 'solar:tag-outline'],
        ['label' => 'Created By', 'value' => $transaction->creator?->name ?? 'System', 'icon' => 'solar:user-id-outline'],
        ['label' => 'Tanggal Dibuat', 'value' => $transaction->created_at?->format('d M Y H:i') ?? '-', 'icon' => 'solar:calendar-date-outline'],
        ['label' => 'Current Step', 'value' => $currentStepLabel, 'icon' => 'solar:flag-outline'],
    ];

    $nextActionTitle = match ($statusValue) {
        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::DRAFT->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::VENDOR_INPUT->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::REVISION_IN_PROGRESS->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::NOT_APPROVED->value => 'Upload dokumen vendor',
        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::SUBMITTED->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::ACCOUNTING_VERIFICATION->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::IN_REVIEW->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::ADMIN_REVIEW->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::VENDOR_REVIEW->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::ADMIN_GENERATE_DOCUMENTS->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::WAITING_APPROVAL->value => 'Verifikasi akuntansi',
        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::RECEIVED->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::SCHEDULING_PAYMENT->value => 'Lanjutkan proses finance',
        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::COMPLETED->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::ARCHIVED->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::PAID->value => 'Transaksi selesai',
        default => 'Lanjutkan workflow',
    };

    $nextActionDescription = match ($statusValue) {
        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::DRAFT->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::VENDOR_INPUT->value => 'Vendor perlu melengkapi data dan dokumen pendukung sebelum submit.',
        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::REVISION_IN_PROGRESS->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::NOT_APPROVED->value => 'Perbaiki dokumen yang dikembalikan agar transaksi bisa masuk review ulang.',
        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::ADMIN_REVIEW->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::VENDOR_REVIEW->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::ADMIN_GENERATE_DOCUMENTS->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::WAITING_APPROVAL->value => 'Transaksi sedang dipetakan ke tahap In Review sesuai workflow baru.',
        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::RECEIVED->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::SCHEDULING_PAYMENT->value => 'Finance dapat memproses pembayaran berdasarkan transaksi yang sudah Received.',
        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::COMPLETED->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::ARCHIVED->value, \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::PAID->value => 'Transaksi sudah selesai sampai Paid.',
        default => 'Ikuti action utama sesuai role untuk menjaga transaksi tetap bergerak.',
    };
?>

<style>
    .transaction-detail-page {
        --signal-primary: #e21a1a;
        --signal-primary-dark: #b91515;
        --signal-surface: #ffffff;
        --signal-soft: #f6f8fb;
        --signal-border: rgba(31, 41, 55, .09);
        --signal-muted: #6b7280;
        --signal-heading: #172033;
        --signal-shadow: 0 14px 34px rgba(27, 36, 54, .08);
        color: var(--signal-heading);
    }

    .transaction-detail-page .workflow-card {
        border: 1px solid var(--signal-border);
        border-radius: 16px;
        background: var(--signal-surface);
        box-shadow: var(--signal-shadow);
    }

    .transaction-detail-page .workflow-header {
        padding: 22px 24px;
        background: linear-gradient(135deg, rgba(226, 26, 26, .08), rgba(255, 255, 255, .94) 48%, rgba(22, 163, 74, .07));
    }

    .transaction-detail-page .workflow-kicker,
    .transaction-detail-page .section-kicker,
    .transaction-detail-page .detail-label {
        color: var(--signal-muted);
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
    }

    .transaction-detail-page .workflow-title {
        margin: 0;
        color: var(--signal-heading);
        font-size: 1.45rem;
        font-weight: 750;
        letter-spacing: 0;
    }

    .transaction-detail-page .workflow-subtitle {
        color: var(--signal-muted);
        font-size: .92rem;
    }

    .transaction-detail-page .badge {
        border-radius: 999px;
        padding: .42rem .68rem;
        font-weight: 700;
    }

    .transaction-detail-page .btn {
        border-radius: 10px;
        font-weight: 650;
    }

    .transaction-detail-page .btn-primary {
        border-color: var(--signal-primary);
        background: var(--signal-primary);
        box-shadow: 0 8px 18px rgba(226, 26, 26, .16);
    }

    .transaction-detail-page .btn-primary:hover {
        border-color: var(--signal-primary-dark);
        background: var(--signal-primary-dark);
    }

    .transaction-detail-page .btn-outline-primary {
        border-color: rgba(226, 26, 26, .32);
        color: var(--signal-primary);
    }

    .transaction-detail-page .btn-outline-primary:hover {
        border-color: var(--signal-primary);
        background: var(--signal-primary);
        color: #ffffff;
    }

    .transaction-detail-page .workflow-progress {
        padding: 22px 24px;
    }

    .transaction-detail-page .stage-track {
        display: grid;
        grid-template-columns: repeat(var(--stage-count), minmax(108px, 1fr));
        gap: 0;
        overflow-x: auto;
        padding-bottom: 6px;
    }

    .transaction-detail-page .stage-item {
        position: relative;
        min-width: 108px;
        padding: 0 8px;
        text-align: center;
    }

    .transaction-detail-page .stage-item::before {
        content: "";
        position: absolute;
        top: 20px;
        right: 50%;
        left: -50%;
        height: 2px;
        background: #e5e7eb;
        z-index: 0;
    }

    .transaction-detail-page .stage-item:first-child::before {
        display: none;
    }

    .transaction-detail-page .stage-item.is-complete::before,
    .transaction-detail-page .stage-item.is-current::before,
    .transaction-detail-page .stage-item.is-issue::before {
        background: #16a34a;
    }

    .transaction-detail-page .stage-node {
        position: relative;
        z-index: 1;
        width: 42px;
        height: 42px;
        margin: 0 auto 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #d1d5db;
        border-radius: 999px;
        background: #ffffff;
        color: #9ca3af;
        box-shadow: 0 6px 14px rgba(17, 24, 39, .06);
    }

    .transaction-detail-page .stage-item.is-complete .stage-node {
        border-color: #16a34a;
        background: #16a34a;
        color: #ffffff;
    }

    .transaction-detail-page .stage-item.is-current .stage-node {
        border-color: var(--signal-primary);
        background: #ffffff;
        color: var(--signal-primary);
        box-shadow: 0 0 0 6px rgba(226, 26, 26, .10);
    }

    .transaction-detail-page .stage-item.is-issue .stage-node {
        border-color: #f97316;
        background: #fff7ed;
        color: #f97316;
        box-shadow: 0 0 0 6px rgba(249, 115, 22, .12);
    }

    .transaction-detail-page .stage-label {
        display: block;
        min-height: 34px;
        color: #374151;
        font-size: .78rem;
        font-weight: 750;
        line-height: 1.25;
    }

    .transaction-detail-page .stage-time {
        display: block;
        margin-top: 4px;
        color: var(--signal-muted);
        font-size: .72rem;
    }

    .transaction-detail-page .workflow-helper,
    .transaction-detail-page .quick-summary {
        border-radius: 14px;
        padding: 14px 16px;
        background: #f9fafb;
        border: 1px solid var(--signal-border);
    }

    .transaction-detail-page .workflow-helper.is-warning {
        border-color: rgba(249, 115, 22, .18);
        background: #fff7ed;
    }

    .transaction-detail-page .workflow-helper.is-success {
        border-color: rgba(22, 163, 74, .18);
        background: #f0fdf4;
    }

    .transaction-detail-page .content-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.65fr) minmax(320px, .85fr);
        gap: 20px;
    }

    .transaction-detail-page .section-card .card-header,
    .transaction-detail-page .section-card .card-body {
        border-color: var(--signal-border);
    }

    .transaction-detail-page .section-card .card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        padding: 18px 20px 12px;
        background: transparent;
    }

    .transaction-detail-page .section-card .card-body {
        padding: 18px 20px 20px;
    }

    .transaction-detail-page .section-title {
        margin: 0;
        color: var(--signal-heading);
        font-size: 1rem;
        font-weight: 750;
    }

    .transaction-detail-page .overview-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .transaction-detail-page .overview-item {
        min-height: 104px;
        border: 1px solid var(--signal-border);
        border-radius: 14px;
        background: #fbfcfe;
        padding: 14px;
    }

    .transaction-detail-page .overview-icon,
    .transaction-detail-page .summary-icon,
    .transaction-detail-page .empty-icon {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: rgba(226, 26, 26, .09);
        color: var(--signal-primary);
    }

    .transaction-detail-page .detail-value {
        color: var(--signal-heading);
        font-weight: 750;
        line-height: 1.35;
        word-break: break-word;
    }

    .transaction-detail-page .metadata-form .form-label {
        color: var(--signal-muted);
        font-size: .76rem;
        font-weight: 750;
        margin-bottom: 6px;
    }

    .transaction-detail-page .metadata-form .form-control {
        min-height: 42px;
        border-color: var(--signal-border);
        border-radius: 10px;
        background: #fbfcfe;
    }

    .transaction-detail-page .meta-stat {
        border: 1px solid var(--signal-border);
        border-radius: 12px;
        padding: 12px;
        background: #fbfcfe;
    }

    .transaction-detail-page .modern-table thead th {
        border-top: 0;
        border-bottom: 1px solid var(--signal-border);
        background: #f9fafb;
        color: var(--signal-muted);
        font-size: .72rem;
        font-weight: 750;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .transaction-detail-page .modern-table tbody td {
        padding-top: 14px;
        padding-bottom: 14px;
        border-color: rgba(31, 41, 55, .07);
        vertical-align: middle;
    }

    .transaction-detail-page .empty-state {
        border: 1px dashed rgba(107, 114, 128, .28);
        border-radius: 14px;
        background: #fbfcfe;
        padding: 24px;
        text-align: center;
    }

    .transaction-detail-page .activity-list {
        position: relative;
    }

    .transaction-detail-page .activity-item {
        position: relative;
        display: flex;
        gap: 12px;
        padding-bottom: 18px;
    }

    .transaction-detail-page .activity-item:not(:last-child)::before {
        content: "";
        position: absolute;
        top: 35px;
        bottom: 0;
        left: 17px;
        width: 2px;
        background: #e5e7eb;
    }

    .transaction-detail-page .activity-node {
        position: relative;
        z-index: 1;
        flex: 0 0 36px;
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: rgba(226, 26, 26, .09);
        color: var(--signal-primary);
    }

    .transaction-detail-page .audit-item {
        border: 1px solid var(--signal-border);
        border-radius: 12px;
        padding: 12px;
        background: #fbfcfe;
    }

    @media (max-width: 1199.98px) {
        .transaction-detail-page .content-grid {
            grid-template-columns: 1fr;
        }

        .transaction-detail-page .overview-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .transaction-detail-page .workflow-header,
        .transaction-detail-page .workflow-progress,
        .transaction-detail-page .section-card .card-body,
        .transaction-detail-page .section-card .card-header {
            padding-left: 16px;
            padding-right: 16px;
        }

        .transaction-detail-page .workflow-title {
            font-size: 1.18rem;
        }

        .transaction-detail-page .overview-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="transaction-detail-page">
    <div class="workflow-card workflow-header mb-3">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div class="flex-grow-1">
                <div class="workflow-kicker mb-2">Transaction Detail</div>
                <h3 class="workflow-title"><?php echo e($invoiceNumber); ?></h3>
                <div class="workflow-subtitle mt-1">
                    <?php echo e($transaction->registration_number); ?> · <?php echo e($transaction->transactionType?->name ?? 'Transaksi'); ?>

                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                    <?php echo $__env->make('invoice-verification.components.status-badge', ['value' => $transaction->status], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    <span class="badge bg-light text-dark border"><?php echo e($currentStepLabel); ?></span>
                    <?php if($documentCount > 0): ?>
                        <span class="badge bg-success-subtle text-success"><?php echo e($documentCount); ?> dokumen aktif</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 flex-wrap">
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('uploadDocuments', $transaction)): ?>
                    <a href="<?php echo e(route('invoice-verification.transactions.documents.show', $transaction)); ?>" class="btn btn-outline-primary">
                        <iconify-icon icon="solar:upload-outline" class="me-1"></iconify-icon>Upload Dokumen Vendor
                    </a>
                <?php endif; ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $transaction)): ?>
                    <?php if(in_array($statusValue, [
                        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::DRAFT->value,
                        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::NOT_APPROVED->value,
                    ], true)): ?>
                        <form method="POST" action="<?php echo e(route('invoice-verification.transactions.submit', $transaction)); ?>">
                            <?php echo csrf_field(); ?>
                            <button class="btn btn-primary">
                                <iconify-icon icon="solar:plain-2-outline" class="me-1"></iconify-icon>Submit Transaksi
                            </button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
                <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('invoice-metadata-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' })">
                    <iconify-icon icon="solar:pen-new-square-outline" class="me-1"></iconify-icon>Update Metadata
                </button>
                <?php if($transaction->isPpa() && ! $user?->hasRole(\App\Modules\InvoiceVerification\Domain\Enums\RoleCode::VENDOR)): ?>
                    <span class="btn btn-outline-secondary disabled">Upload PPA Vendor</span>
                <?php endif; ?>
                <?php if($transaction->isPpa() && $transaction->ppaVerificationSheet): ?>
                    <a href="<?php echo e(route('invoice-verification.transactions.ppa-verification-sheets.edit', $transaction)); ?>" class="btn btn-outline-secondary">Lembar PPA</a>
                <?php endif; ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('verifyAccounting', $transaction)): ?>
                    <a href="<?php echo e(route('invoice-verification.transactions.accounting-verifications.edit', $transaction)); ?>" class="btn btn-primary">
                        <iconify-icon icon="solar:verified-check-outline" class="me-1"></iconify-icon>Review / Approve
                    </a>
                <?php endif; ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('processFinance', $transaction)): ?>
                    <?php if(in_array($statusValue, [
                        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::RECEIVED->value,
                        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::SCHEDULING_PAYMENT->value,
                    ], true)): ?>
                        <a href="<?php echo e(route('invoice-verification.finance.index')); ?>" class="btn btn-primary">Proses Finance</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="workflow-card workflow-progress mb-3">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
            <div>
                <div class="section-kicker mb-1">Workflow Progress</div>
                <h5 class="section-title">Stage transaksi</h5>
            </div>
            <span class="badge bg-light text-dark border">Current: <?php echo e($currentStage['label']); ?></span>
        </div>
        <div class="stage-track" style="--stage-count: <?php echo e(count($stageDefinitions)); ?>;">
            <?php $__currentLoopData = $stageDefinitions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $stage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $stageHistory = $transaction->statusHistory->first(function ($history) use ($stage) {
                        return in_array($history->to_status, $stage['statuses'], true)
                            || in_array($history->to_step, $stage['steps'], true);
                    });
                    $stageState = $index < $currentStageIndex ? 'is-complete' : ($index === $currentStageIndex ? 'is-current' : 'is-pending');
                    if ($index === $currentStageIndex && in_array($statusValue, [
                        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::PAID->value,
                        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::COMPLETED->value,
                        \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::ARCHIVED->value,
                    ], true)) {
                        $stageState = 'is-complete';
                    }
                    if ($index === $currentStageIndex && $statusValue === \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::REVISION_IN_PROGRESS->value) {
                        $stageState = 'is-issue';
                    }
                ?>
                <button class="stage-item <?php echo e($stageState); ?> border-0 bg-transparent" type="button" title="<?php echo e($stage['label']); ?>">
                    <span class="stage-node">
                        <iconify-icon icon="<?php echo e(in_array($stageState, ['is-complete'], true) ? 'solar:check-circle-bold' : $stage['icon']); ?>"></iconify-icon>
                    </span>
                    <span class="stage-label"><?php echo e($stage['label']); ?></span>
                    <span class="stage-time"><?php echo e($stageHistory?->created_at?->format('d M H:i') ?? 'Pending'); ?></span>
                </button>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php if($showWorkflowHelper): ?>
            <div class="workflow-helper is-<?php echo e($helperTone); ?> mt-3 d-flex gap-2 align-items-start">
                <iconify-icon icon="<?php echo e($helperTone === 'warning' ? 'solar:danger-triangle-outline' : 'solar:info-circle-outline'); ?>" class="fs-4 text-<?php echo e($helperTone); ?>"></iconify-icon>
                <div>
                    <div class="fw-semibold"><?php echo e($currentStage['label']); ?></div>
                    <div class="text-muted small"><?php echo e($helperText); ?></div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="content-grid">
        <div class="d-grid gap-3">
            <div class="card workflow-card section-card">
                <div class="card-header">
                    <div>
                        <div class="section-kicker mb-1">Overview</div>
                        <h5 class="section-title">Transaction Overview</h5>
                    </div>
                    <span class="badge bg-light text-dark border"><?php echo e($transaction->registration_number); ?></span>
                </div>
                <div class="card-body">
                    <div class="overview-grid">
                        <?php $__currentLoopData = $overviewItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="overview-item">
                                <div class="d-flex align-items-start gap-2 mb-3">
                                    <span class="overview-icon"><iconify-icon icon="<?php echo e($item['icon']); ?>"></iconify-icon></span>
                                    <div class="min-w-0">
                                        <div class="detail-label"><?php echo e($item['label']); ?></div>
                                    </div>
                                </div>
                                <div class="detail-value"><?php echo e($item['value']); ?></div>
                                <?php if(!empty($item['meta'])): ?>
                                    <div class="text-muted small mt-1"><?php echo e($item['meta']); ?></div>
                                <?php endif; ?>
                                <?php if(!empty($item['route']) && !empty($item['preview'])): ?>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-2 mt-2 fw-semibold"
                                        data-file-preview-url="<?php echo e($item['route']); ?>"
                                        data-file-preview-title="<?php echo e($item['label']); ?> - <?php echo e($item['value']); ?>"
                                    >
                                        <iconify-icon icon="solar:eye-outline" class="fs-16"></iconify-icon>
                                        <span><?php echo e($item['link']); ?></span>
                                    </button>
                                <?php elseif(!empty($item['route'])): ?>
                                    <a href="<?php echo e($item['route']); ?>" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2 mt-2 fw-semibold">
                                        <iconify-icon icon="solar:document-text-outline" class="fs-16"></iconify-icon>
                                        <span><?php echo e($item['link']); ?></span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>

            <?php if(false && ! $isVendorRevisionView): ?>
                <div class="card workflow-card section-card">
                    <div class="card-header">
                        <div>
                            <div class="section-kicker mb-1">System Documents</div>
                            <h5 class="section-title">Generated Initial Documents</h5>
                        </div>
                        <?php if($generatedCount > 0): ?>
                            <span class="badge bg-success-subtle text-success"><?php echo e($generatedCount); ?> item</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if($transaction->generatedDocuments->isNotEmpty()): ?>
                            <div class="table-responsive">
                                <table class="table modern-table table-centered mb-0">
                                    <thead>
                                        <tr>
                                            <th>Dokumen</th>
                                            <th>Nomor</th>
                                            <th>Status Generate</th>
                                            <th>Approval Mode</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $__currentLoopData = $transaction->generatedDocuments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $generatedDocument): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold"><?php echo e(str($generatedDocument->document_code)->replace('_', ' ')->title()); ?></div>
                                                    <div class="text-muted small">Generated by SIGNAL</div>
                                                </td>
                                                <td><?php echo e($generatedDocument->document_number ?? '-'); ?></td>
                                                <td><span class="badge bg-info-subtle text-info"><?php echo e(str($generatedDocument->generation_status->value)->replace('_', ' ')->title()); ?></span></td>
                                                <td><span class="badge bg-light text-dark border"><?php echo e(str($generatedDocument->approval_mode->value)->replace('_', ' ')->title()); ?></span></td>
                                                <td class="text-end">
                                                    <div class="d-inline-flex gap-2">
                                                        <button
                                                            type="button"
                                                            class="btn btn-outline-primary d-inline-flex align-items-center gap-2"
                                                            data-file-preview-url="<?php echo e(route('invoice-verification.generated-documents.preview', $generatedDocument)); ?>"
                                                            data-file-preview-title="<?php echo e(str($generatedDocument->document_code)->replace('_', ' ')->title()); ?> - <?php echo e($generatedDocument->document_number ?? $transaction->registration_number); ?>"
                                                        >
                                                            <iconify-icon icon="solar:eye-outline" class="fs-18"></iconify-icon>
                                                            <span>Lihat Dokumen</span>
                                                        </button>
                                                        <a href="<?php echo e(route('invoice-verification.generated-documents.show', $generatedDocument)); ?>" class="btn btn-sm btn-outline-secondary">Detail</a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <span class="empty-icon mb-3"><iconify-icon icon="solar:document-add-outline"></iconify-icon></span>
                                <div class="fw-semibold">Belum ada dokumen awal.</div>
                                <div class="text-muted small mt-1">Generate dokumen setelah metadata dan lampiran utama siap.</div>
                                <?php if($user?->hasRole(\App\Modules\InvoiceVerification\Domain\Enums\RoleCode::ADMIN_DIVISI) && $statusValue === \App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus::ADMIN_GENERATE_DOCUMENTS->value): ?>
                                    <form method="POST" action="<?php echo e(route('invoice-verification.transactions.admin-documents.generate', $transaction)); ?>" class="mt-3">
                                        <?php echo csrf_field(); ?>
                                        <button class="btn btn-sm btn-primary">Generate Dokumen</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if($transaction->ppaVerificationSheet?->file_path): ?>
                            <div class="audit-item mt-3 d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                <div>
                                    <div class="fw-semibold">Lembar Checklist PPA</div>
                                    <div class="text-muted small"><?php echo e($transaction->ppaVerificationSheet->file_name ?? 'Checklist PPA'); ?></div>
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <button
                                        type="button"
                                        class="btn btn-outline-primary d-inline-flex align-items-center gap-2"
                                        data-file-preview-url="<?php echo e(route('invoice-verification.transactions.ppa-verification-sheets.preview', $transaction)); ?>"
                                        data-file-preview-title="Lembar Checklist PPA - <?php echo e($transaction->registration_number); ?>"
                                    >
                                        <iconify-icon icon="solar:eye-outline" class="fs-18"></iconify-icon>
                                        <span>Lihat Dokumen</span>
                                    </button>
                                    <a href="<?php echo e(route('invoice-verification.transactions.ppa-verification-sheets.edit', $transaction)); ?>" class="btn btn-sm btn-outline-secondary">Detail</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card workflow-card section-card">
                <div class="card-header">
                    <div>
                        <div class="section-kicker mb-1">Attachments</div>
                        <h5 class="section-title">Dokumen Transaksi</h5>
                    </div>
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('uploadDocuments', $transaction)): ?>
                        <a href="<?php echo e(route('invoice-verification.transactions.documents.show', $transaction)); ?>" class="btn btn-sm btn-outline-primary">Upload Dokumen</a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php echo $__env->make('invoice-verification.components.document-table', ['documents' => $transaction->latestDocuments, 'transaction' => $transaction], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </div>
            </div>

            <?php if($mismatches && ! $isVendorRevisionView): ?>
                <div class="card workflow-card section-card">
                    <div class="card-header">
                        <div>
                            <div class="section-kicker mb-1">Review Notes</div>
                            <h5 class="section-title">Mismatch Checklist PPA</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table modern-table table-centered mb-0">
                                <thead>
                                    <tr>
                                        <th>Dokumen</th>
                                        <th>Checklist</th>
                                        <th>File Aktual</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $mismatches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td><?php echo e($item['document_name']); ?></td>
                                            <td><?php echo e($item['checklist_status']); ?></td>
                                            <td><?php echo e($item['actual_available'] ? 'Available' : 'Missing'); ?></td>
                                            <td>
                                                <span class="badge <?php echo e($item['is_mismatch'] ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success'); ?>">
                                                    <?php echo e($item['is_mismatch'] ? 'Mismatch' : 'Match'); ?>

                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if (! ($isVendorRevisionView)): ?>
            <div class="d-grid gap-3 align-content-start">
                <div id="invoice-metadata-card" class="card workflow-card section-card">
                    <div class="card-header">
                        <div>
                            <div class="section-kicker mb-1">Invoice</div>
                            <h5 class="section-title">Invoice Metadata</h5>
                        </div>
                        <span class="badge bg-light text-dark border">Editable</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="meta-stat">
                                    <div class="detail-label">Nilai Invoice</div>
                                    <div class="fw-semibold mt-1"><?php echo e($invoiceValue ? 'Rp '.number_format((float) $invoiceValue, 0, ',', '.') : '-'); ?></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="meta-stat">
                                    <div class="detail-label">Tanggal</div>
                                    <div class="fw-semibold mt-1"><?php echo e($transaction->invoiceMetadata?->invoice_date?->format('d M Y') ?? '-'); ?></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="meta-stat">
                                    <div class="detail-label">Rekening Pembayaran</div>
                                    <div class="fw-semibold mt-1"><?php echo e($transaction->invoiceMetadata?->account_number ?? '-'); ?></div>
                                    <div class="text-muted small"><?php echo e($transaction->invoiceMetadata?->account_name ?? 'Atas nama belum diisi'); ?></div>
                                </div>
                            </div>
                        </div>
                        <form method="POST" action="<?php echo e(route('invoice-verification.transactions.invoice-metadata.update', $transaction)); ?>" class="metadata-form row g-3">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('PUT'); ?>
                            <div class="col-12">
                                <label class="form-label">Nomor Invoice</label>
                                <input type="text" class="form-control <?php $__errorArgs = ['invoice_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="invoice_number" value="<?php echo e(old('invoice_number', $transaction->invoiceMetadata?->invoice_number)); ?>" maxlength="255" required>
                                <?php $__errorArgs = ['invoice_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Tanggal Invoice</label>
                                <input type="date" class="form-control <?php $__errorArgs = ['invoice_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="invoice_date" value="<?php echo e(old('invoice_date', optional($transaction->invoiceMetadata?->invoice_date)->format('Y-m-d'))); ?>">
                                <?php $__errorArgs = ['invoice_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Bank</label>
                                <input type="text" class="form-control <?php $__errorArgs = ['bank_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="bank_name" value="<?php echo e(old('bank_name', $transaction->invoiceMetadata?->bank_name)); ?>" maxlength="255">
                                <?php $__errorArgs = ['bank_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Nomor Rekening</label>
                                <input type="text" class="form-control <?php $__errorArgs = ['account_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="account_number" value="<?php echo e(old('account_number', $transaction->invoiceMetadata?->account_number)); ?>" inputmode="numeric" pattern="[0-9]{6,30}" maxlength="30" autocomplete="off" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                <?php $__errorArgs = ['account_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Atas Nama Rekening</label>
                                <input type="text" class="form-control <?php $__errorArgs = ['account_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="account_name" value="<?php echo e(old('account_name', $transaction->invoiceMetadata?->account_name)); ?>" maxlength="255">
                                <?php $__errorArgs = ['account_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Nilai Invoice</label>
                                <input type="text" class="form-control <?php $__errorArgs = ['invoice_value'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" inputmode="numeric" name="invoice_value" value="<?php echo e(old('invoice_value', $transaction->invoiceMetadata?->invoice_value)); ?>" data-rupiah-input>
                                <?php $__errorArgs = ['invoice_value'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-outline-primary w-100">Perbarui Metadata</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card workflow-card section-card">
                    <div class="card-header">
                        <div>
                            <div class="section-kicker mb-1">Next Action</div>
                            <h5 class="section-title">Quick Summary</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="quick-summary">
                            <div class="d-flex gap-3">
                                <span class="summary-icon"><iconify-icon icon="solar:round-arrow-right-up-outline"></iconify-icon></span>
                                <div>
                                    <div class="fw-semibold"><?php echo e($nextActionTitle); ?></div>
                                    <div class="text-muted small mt-1"><?php echo e($nextActionDescription); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php echo $__env->make('invoice-verification.components.timeline', ['histories' => $transaction->statusHistory], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

                <div class="card workflow-card section-card">
                    <div class="card-header">
                        <div>
                            <div class="section-kicker mb-1">System Log</div>
                            <h5 class="section-title">Audit Trail</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php $__empty_1 = true; $__currentLoopData = $auditLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <div class="audit-item">
                                    <div class="d-flex justify-content-between gap-2">
                                        <div class="fw-semibold"><?php echo e(str($log->action)->replace('_', ' ')->title()); ?></div>
                                        <small class="text-muted"><?php echo e($log->created_at?->format('d M H:i')); ?></small>
                                    </div>
                                    <div class="text-muted small mt-1"><?php echo e(str($log->module)->replace('-', ' ')->title()); ?></div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <div class="empty-state">
                                    <span class="empty-icon mb-3"><iconify-icon icon="solar:shield-check-outline"></iconify-icon></span>
                                    <div class="fw-semibold">Belum ada aktivitas audit.</div>
                                    <div class="text-muted small mt-1">Belum ada aktivitas audit yang tercatat untuk transaksi ini.</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php echo $__env->make('invoice-verification.partials.rupiah-input', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php echo $__env->make('invoice-verification.components.file-preview-modal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Transaction Detail'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/muhamadsobirin/Public/LRTJ App/invoice-colector/resources/views/invoice-verification/transactions/show.blade.php ENDPATH**/ ?>