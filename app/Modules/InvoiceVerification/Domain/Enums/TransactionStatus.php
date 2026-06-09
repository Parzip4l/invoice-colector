<?php

namespace App\Modules\InvoiceVerification\Domain\Enums;

enum TransactionStatus: string
{
    case DRAFT = 'DRAFT';
    case VENDOR_INPUT = 'VENDOR_INPUT';
    case ADMIN_REVIEW = 'ADMIN_REVIEW';
    case WAITING_APPROVAL = 'WAITING_APPROVAL';
    case ADMIN_GENERATE_DOCUMENTS = 'ADMIN_GENERATE_DOCUMENTS';
    case DOCUMENT_COLLECTION = 'DOCUMENT_COLLECTION';
    case VENDOR_REVIEW = 'VENDOR_REVIEW';
    case ACCOUNTING_VERIFICATION = 'ACCOUNTING_VERIFICATION';
    case REVISION_IN_PROGRESS = 'REVISION_IN_PROGRESS';
    case FINANCE_PROCESS = 'FINANCE_PROCESS';
    case COMPLETED = 'COMPLETED';
    case ARCHIVED = 'ARCHIVED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::VENDOR_INPUT => 'Input Vendor',
            self::ADMIN_REVIEW => 'In Review Admin',
            self::WAITING_APPROVAL => 'Menunggu Approval',
            self::ADMIN_GENERATE_DOCUMENTS => 'Menunggu Generate PPA',
            self::DOCUMENT_COLLECTION => 'Pengumpulan Dokumen',
            self::VENDOR_REVIEW => 'In Review',
            self::ACCOUNTING_VERIFICATION => 'In Review Accounting',
            self::REVISION_IN_PROGRESS => 'Revisi Berjalan',
            self::FINANCE_PROCESS => 'Proses Finance',
            self::COMPLETED => 'Approved',
            self::ARCHIVED => 'Diarsipkan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-secondary-subtle text-secondary',
            self::VENDOR_INPUT => 'bg-info-subtle text-info',
            self::ADMIN_REVIEW => 'bg-primary-subtle text-primary',
            self::WAITING_APPROVAL => 'bg-warning-subtle text-warning',
            self::ADMIN_GENERATE_DOCUMENTS => 'bg-info-subtle text-info',
            self::DOCUMENT_COLLECTION => 'bg-info-subtle text-info',
            self::VENDOR_REVIEW => 'bg-primary-subtle text-primary',
            self::ACCOUNTING_VERIFICATION => 'bg-dark-subtle text-dark',
            self::REVISION_IN_PROGRESS => 'bg-danger-subtle text-danger',
            self::FINANCE_PROCESS => 'bg-success-subtle text-success',
            self::COMPLETED => 'bg-success-subtle text-success',
            self::ARCHIVED => 'bg-light text-dark',
        };
    }
}
