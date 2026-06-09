<?php

namespace App\Modules\InvoiceVerification\Domain\Enums;

enum TransactionStep: string
{
    case INITIAL_DOCUMENT_GENERATION = 'INITIAL_DOCUMENT_GENERATION';
    case INITIAL_APPROVAL = 'INITIAL_APPROVAL';
    case VENDOR_INVOICE_INPUT = 'VENDOR_INVOICE_INPUT';
    case ADMIN_DOCUMENT_REVIEW = 'ADMIN_DOCUMENT_REVIEW';
    case KADEP_REVIEW = 'KADEP_REVIEW';
    case KADIV_REVIEW = 'KADIV_REVIEW';
    case INTERNAL_DOCUMENT_UPLOAD = 'INTERNAL_DOCUMENT_UPLOAD';
    case VENDOR_DOCUMENT_REVIEW = 'VENDOR_DOCUMENT_REVIEW';
    case ACCOUNTING_ADMINISTRATION = 'ACCOUNTING_ADMINISTRATION';
    case ACCOUNTING_INVOICING = 'ACCOUNTING_INVOICING';
    case ACCOUNTING_VERIFICATION = 'ACCOUNTING_VERIFICATION';
    case FINALIZATION = 'FINALIZATION';
    case FINANCE_PROCESS = 'FINANCE_PROCESS';
    case ARCHIVE = 'ARCHIVE';

    public function label(): string
    {
        return match ($this) {
            self::INITIAL_DOCUMENT_GENERATION => 'Generate Dokumen Awal',
            self::INITIAL_APPROVAL => 'Approval Dokumen Awal',
            self::VENDOR_INVOICE_INPUT => 'Input Tagihan Vendor',
            self::ADMIN_DOCUMENT_REVIEW => 'Review Dokumen Admin',
            self::KADEP_REVIEW => 'Kadep Review',
            self::KADIV_REVIEW => 'Kadiv Review',
            self::INTERNAL_DOCUMENT_UPLOAD => 'Scan dan Upload Dokumen',
            self::VENDOR_DOCUMENT_REVIEW => 'Pengecekan Hasil Pekerjaan',
            self::ACCOUNTING_ADMINISTRATION => 'Accounting - Administration',
            self::ACCOUNTING_INVOICING => 'Accounting - Invoicing',
            self::ACCOUNTING_VERIFICATION => 'Verifikasi Akuntansi',
            self::FINALIZATION => 'Persiapan Finance',
            self::FINANCE_PROCESS => 'Proses Finance',
            self::ARCHIVE => 'Arsip Final',
        };
    }
}
