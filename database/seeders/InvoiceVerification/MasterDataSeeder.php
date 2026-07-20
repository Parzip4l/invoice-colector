<?php

namespace Database\Seeders\InvoiceVerification;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\ApprovalMode;
use App\Modules\InvoiceVerification\Domain\Enums\DocumentCode;
use App\Modules\InvoiceVerification\Domain\Enums\DocumentSourceType;
use App\Modules\InvoiceVerification\Domain\Enums\DocumentUploadMode;
use App\Modules\InvoiceVerification\Domain\Enums\TemplateType;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionTypeCode;
use App\Modules\InvoiceVerification\Domain\Enums\UploadScheme;
use App\Modules\InvoiceVerification\Domain\Models\AgreementReference;
use App\Modules\InvoiceVerification\Domain\Models\Bank;
use App\Modules\InvoiceVerification\Domain\Models\DocumentType;
use App\Modules\InvoiceVerification\Domain\Models\MemoRequest;
use App\Modules\InvoiceVerification\Domain\Models\TemplateReference;
use App\Modules\InvoiceVerification\Domain\Models\TransactionType;
use App\Modules\InvoiceVerification\Domain\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $banks = collect([
            ['code' => 'BCA', 'name' => 'Bank Central Asia'],
            ['code' => 'BRI', 'name' => 'Bank Rakyat Indonesia'],
            ['code' => 'BNI', 'name' => 'Bank Negara Indonesia'],
            ['code' => 'MANDIRI', 'name' => 'Bank Mandiri'],
        ])->mapWithKeys(fn (array $bank) => [
            $bank['code'] => Bank::updateOrCreate(['code' => $bank['code']], $bank),
        ]);

        $vendors = collect([
            [
                'vendor_code' => 'VND-0001',
                'name' => 'PT Cipta Sarana Prima',
                'npwp' => '01.234.567.8-901.000',
                'address' => 'Jakarta Selatan',
                'contact_name' => 'Rina Vendor',
                'contact_email' => 'vendor@demo.local',
                'contact_phone' => '08120000001',
                'default_bank_id' => $banks['BCA']->id,
                'default_account_number' => '1234567890',
            ],
            [
                'vendor_code' => 'VND-0002',
                'name' => 'PT Logistik Infrastruktur Nusantara',
                'npwp' => '09.876.543.2-100.000',
                'address' => 'Bekasi',
                'contact_name' => 'Dewi Vendor',
                'contact_email' => 'vendor.logistik@demo.local',
                'contact_phone' => '08120000002',
                'default_bank_id' => $banks['MANDIRI']->id,
                'default_account_number' => '9988776655',
            ],
            [
                'vendor_code' => 'VND-0003',
                'name' => 'PT Sinyal Teknologi Rail',
                'npwp' => '03.456.789.0-123.000',
                'address' => 'Tangerang Selatan',
                'contact_name' => 'Bagus Vendor',
                'contact_email' => 'vendor.teknologi@demo.local',
                'contact_phone' => '08120000003',
                'default_bank_id' => $banks['BNI']->id,
                'default_account_number' => '7788990011',
            ],
            [
                'vendor_code' => 'VND-0004',
                'name' => 'PT Prima Konstruksi Rel',
                'npwp' => '04.567.890.1-234.000',
                'address' => 'Jakarta Timur',
                'contact_name' => 'Maya Vendor',
                'contact_email' => 'vendor.konstruksi@demo.local',
                'contact_phone' => '08120000004',
                'default_bank_id' => $banks['BRI']->id,
                'default_account_number' => '1122334455',
            ],
        ])->mapWithKeys(fn (array $vendor) => [
            $vendor['vendor_code'] => Vendor::updateOrCreate(['vendor_code' => $vendor['vendor_code']], $vendor),
        ]);

        $types = collect([
            ['code' => TransactionTypeCode::PPA, 'name' => 'PPA Kontrak', 'upload_scheme' => UploadScheme::PPA_DETAILED],
            ['code' => TransactionTypeCode::PPA_NON_CONTRACT, 'name' => 'PPA Non Kontrak', 'upload_scheme' => UploadScheme::COMBINED],
            ['code' => TransactionTypeCode::SPU, 'name' => 'Surat Permintaan Uang Muka', 'upload_scheme' => UploadScheme::COMBINED],
            ['code' => TransactionTypeCode::SPUK, 'name' => 'Surat Pertanggungjawaban Uang Muka Kerja', 'upload_scheme' => UploadScheme::COMBINED],
            ['code' => TransactionTypeCode::KAS_KECIL, 'name' => 'Kas Kecil', 'upload_scheme' => UploadScheme::COMBINED],
        ])->mapWithKeys(fn (array $type) => [
            $type['code']->value => TransactionType::updateOrCreate(
                ['code' => $type['code']->value],
                [
                    'name' => $type['name'],
                    'upload_scheme' => $type['upload_scheme']->value,
                ],
            ),
        ]);

        $documentTypes = [
            ['transaction_type' => TransactionTypeCode::PPA, 'code' => DocumentCode::PPA_LEMBAR_AWAL, 'name' => 'Lembar Awal PPA', 'source_type' => DocumentSourceType::SYSTEM, 'upload_mode' => DocumentUploadMode::SINGLE_SLOT, 'approval_mode' => ApprovalMode::MAIN_FLOW, 'is_required' => true, 'sort_order' => 1],
            ['transaction_type' => TransactionTypeCode::PPA, 'code' => DocumentCode::PPA_LEMBAR_VERIFIKASI, 'name' => 'Lembar Verifikasi', 'source_type' => DocumentSourceType::SYSTEM, 'upload_mode' => DocumentUploadMode::STRUCTURED_FORM, 'approval_mode' => ApprovalMode::DIVISION_ONLY, 'is_required' => true, 'sort_order' => 2],
            ['transaction_type' => TransactionTypeCode::PPA, 'code' => DocumentCode::PPA_INVOICE, 'name' => 'Invoice', 'source_type' => DocumentSourceType::VENDOR, 'upload_mode' => DocumentUploadMode::SINGLE_SLOT, 'approval_mode' => ApprovalMode::NONE, 'is_required' => true, 'sort_order' => 3],
            ['transaction_type' => TransactionTypeCode::PPA, 'code' => DocumentCode::PPA_KWITANSI, 'name' => 'Kwitansi', 'source_type' => DocumentSourceType::VENDOR, 'upload_mode' => DocumentUploadMode::SINGLE_SLOT, 'approval_mode' => ApprovalMode::NONE, 'is_required' => true, 'sort_order' => 4],
            ['transaction_type' => TransactionTypeCode::PPA, 'code' => DocumentCode::PPA_FAKTUR_PAJAK, 'name' => 'Faktur Pajak', 'source_type' => DocumentSourceType::VENDOR, 'upload_mode' => DocumentUploadMode::SINGLE_SLOT, 'approval_mode' => ApprovalMode::NONE, 'is_required' => true, 'sort_order' => 5],
            ['transaction_type' => TransactionTypeCode::PPA, 'code' => DocumentCode::PPA_BAPP, 'name' => 'BAPP', 'source_type' => DocumentSourceType::VENDOR, 'upload_mode' => DocumentUploadMode::SINGLE_SLOT, 'approval_mode' => ApprovalMode::NONE, 'is_required' => true, 'sort_order' => 6],
            ['transaction_type' => TransactionTypeCode::PPA, 'code' => DocumentCode::PPA_BAST, 'name' => 'BAST', 'source_type' => DocumentSourceType::VENDOR, 'upload_mode' => DocumentUploadMode::SINGLE_SLOT, 'approval_mode' => ApprovalMode::NONE, 'is_required' => true, 'sort_order' => 7],
            ['transaction_type' => TransactionTypeCode::PPA, 'code' => DocumentCode::PPA_MEMO_PERMOHONAN, 'name' => 'Memo Permohonan', 'source_type' => DocumentSourceType::INTERNAL, 'upload_mode' => DocumentUploadMode::SINGLE_SLOT, 'approval_mode' => ApprovalMode::NONE, 'is_required' => true, 'sort_order' => 8],
            ['transaction_type' => TransactionTypeCode::PPA, 'code' => DocumentCode::PPA_PERJANJIAN, 'name' => 'Perjanjian (PKS / SPK / PO)', 'source_type' => DocumentSourceType::INTERNAL, 'upload_mode' => DocumentUploadMode::SINGLE_SLOT, 'approval_mode' => ApprovalMode::NONE, 'is_required' => true, 'sort_order' => 9],
            ['transaction_type' => TransactionTypeCode::PPA, 'code' => DocumentCode::PPA_LAMPIRAN_PEKERJAAN, 'name' => 'Lampiran Pekerjaan', 'source_type' => DocumentSourceType::VENDOR, 'upload_mode' => DocumentUploadMode::SINGLE_SLOT, 'approval_mode' => ApprovalMode::NONE, 'is_required' => true, 'sort_order' => 10],
            ['transaction_type' => TransactionTypeCode::PPA, 'code' => DocumentCode::PPA_LAPORAN_PEKERJAAN, 'name' => 'Laporan Pekerjaan', 'source_type' => DocumentSourceType::VENDOR, 'upload_mode' => DocumentUploadMode::SINGLE_SLOT, 'approval_mode' => ApprovalMode::NONE, 'is_required' => true, 'sort_order' => 11],
            ['transaction_type' => TransactionTypeCode::PPA_NON_CONTRACT, 'code' => DocumentCode::PNK_INVOICE, 'name' => 'Invoice', 'source_type' => DocumentSourceType::VENDOR, 'upload_mode' => DocumentUploadMode::SINGLE_SLOT, 'approval_mode' => ApprovalMode::NONE, 'is_required' => true, 'sort_order' => 1],
            ['transaction_type' => TransactionTypeCode::PPA_NON_CONTRACT, 'code' => DocumentCode::PNK_KWITANSI, 'name' => 'Kwitansi', 'source_type' => DocumentSourceType::VENDOR, 'upload_mode' => DocumentUploadMode::SINGLE_SLOT, 'approval_mode' => ApprovalMode::NONE, 'is_required' => true, 'sort_order' => 2],
            ['transaction_type' => TransactionTypeCode::PPA_NON_CONTRACT, 'code' => DocumentCode::PNK_LEMBAR_TANDA_TERIMA, 'name' => 'Lembar Tanda Terima Dokumen', 'source_type' => DocumentSourceType::VENDOR, 'upload_mode' => DocumentUploadMode::SINGLE_SLOT, 'approval_mode' => ApprovalMode::NONE, 'is_required' => true, 'sort_order' => 3],
            ['transaction_type' => TransactionTypeCode::PPA_NON_CONTRACT, 'code' => DocumentCode::PNK_DOKUMEN_PENDUKUNG, 'name' => 'Dokumen Pendukung', 'source_type' => DocumentSourceType::VENDOR, 'upload_mode' => DocumentUploadMode::SINGLE_SLOT, 'approval_mode' => ApprovalMode::NONE, 'is_required' => false, 'sort_order' => 4],
            ['transaction_type' => TransactionTypeCode::SPU, 'code' => DocumentCode::SPU_COMBINED_INTERNAL, 'name' => 'Dokumen Internal SPU', 'source_type' => DocumentSourceType::INTERNAL, 'upload_mode' => DocumentUploadMode::COMBINED_FORM, 'approval_mode' => ApprovalMode::NONE, 'is_required' => true, 'sort_order' => 1],
            ['transaction_type' => TransactionTypeCode::SPU, 'code' => DocumentCode::SPU_COMBINED_VENDOR, 'name' => 'Dokumen Vendor SPU', 'source_type' => DocumentSourceType::VENDOR, 'upload_mode' => DocumentUploadMode::COMBINED_FORM, 'approval_mode' => ApprovalMode::NONE, 'is_required' => false, 'sort_order' => 2],
            ['transaction_type' => TransactionTypeCode::SPUK, 'code' => DocumentCode::SPUK_COMBINED_INTERNAL, 'name' => 'Dokumen Internal SPUK', 'source_type' => DocumentSourceType::INTERNAL, 'upload_mode' => DocumentUploadMode::COMBINED_FORM, 'approval_mode' => ApprovalMode::NONE, 'is_required' => true, 'sort_order' => 1],
            ['transaction_type' => TransactionTypeCode::SPUK, 'code' => DocumentCode::SPUK_COMBINED_VENDOR, 'name' => 'Dokumen Vendor SPUK', 'source_type' => DocumentSourceType::VENDOR, 'upload_mode' => DocumentUploadMode::COMBINED_FORM, 'approval_mode' => ApprovalMode::NONE, 'is_required' => false, 'sort_order' => 2],
            ['transaction_type' => TransactionTypeCode::KAS_KECIL, 'code' => DocumentCode::KAS_KECIL_COMBINED_INTERNAL, 'name' => 'Dokumen Internal Kas Kecil', 'source_type' => DocumentSourceType::INTERNAL, 'upload_mode' => DocumentUploadMode::COMBINED_FORM, 'approval_mode' => ApprovalMode::NONE, 'is_required' => true, 'sort_order' => 1],
            ['transaction_type' => TransactionTypeCode::KAS_KECIL, 'code' => DocumentCode::KAS_KECIL_COMBINED_VENDOR, 'name' => 'Dokumen Pertanggungjawaban / Evidence Kas Kecil', 'source_type' => DocumentSourceType::VENDOR, 'upload_mode' => DocumentUploadMode::COMBINED_FORM, 'approval_mode' => ApprovalMode::NONE, 'is_required' => true, 'sort_order' => 2],
        ];

        foreach ($documentTypes as $documentType) {
            DocumentType::updateOrCreate(
                [
                    'code' => $documentType['code']->value,
                    'transaction_type_id' => $types[$documentType['transaction_type']->value]->id,
                ],
                [
                    'name' => $documentType['name'],
                    'source_type' => $documentType['source_type']->value,
                    'upload_mode' => $documentType['upload_mode']->value,
                    'approval_mode' => $documentType['approval_mode']->value,
                    'is_required' => $documentType['is_required'],
                    'sort_order' => $documentType['sort_order'],
                ],
            );
        }

        $admin = User::where('email', 'admin.divisi@demo.local')->first();
        $opsDivision = $admin?->division_id;
        $opsDepartment = $admin?->department_id;

        $masterRows = [
            [
                'vendor_code' => 'VND-0001',
                'memo_number' => 'MEMO-OPS-2026-001',
                'memo_subject' => 'Permohonan Penggunaan Anggaran Pekerjaan Jalur',
                'contract_number' => 'SPK-LRTJ-2026-001',
                'contract_title' => 'Pekerjaan Perawatan Fasilitas Stasiun',
                'contract_value' => 125000000,
            ],
            [
                'vendor_code' => 'VND-0002',
                'memo_number' => 'MEMO-OPS-2026-002',
                'memo_subject' => 'Permohonan Anggaran Pengadaan Perangkat Logistik',
                'contract_number' => 'SPK-LRTJ-2026-002',
                'contract_title' => 'Pekerjaan Layanan Logistik Operasional',
                'contract_value' => 98500000,
            ],
            [
                'vendor_code' => 'VND-0003',
                'memo_number' => 'MEMO-OPS-2026-003',
                'memo_subject' => 'Permohonan Anggaran Pemeliharaan Sistem Persinyalan',
                'contract_number' => 'SPK-LRTJ-2026-003',
                'contract_title' => 'Pekerjaan Pemeliharaan Sistem Persinyalan',
                'contract_value' => 210000000,
            ],
            [
                'vendor_code' => 'VND-0004',
                'memo_number' => 'MEMO-OPS-2026-004',
                'memo_subject' => 'Permohonan Anggaran Perbaikan Infrastruktur Rel',
                'contract_number' => 'SPK-LRTJ-2026-004',
                'contract_title' => 'Pekerjaan Perbaikan Infrastruktur Rel',
                'contract_value' => 175000000,
            ],
        ];

        foreach ($masterRows as $row) {
            $memoDisk = config('invoice_verification.storage.documents_disk', 'public');
            $memoFileName = str($row['memo_number'])->lower()->replace('/', '-')->toString().'.pdf';
            $memoPath = 'master-data/memo-requests/'.$memoFileName;
            $this->putDemoPdf($memoDisk, $memoPath, 'Memo Permohonan', [
                'Nomor: '.$row['memo_number'],
                'Perihal: '.$row['memo_subject'],
                'Vendor: '.$vendors[$row['vendor_code']]->name,
            ]);

            MemoRequest::updateOrCreate(
                ['memo_number' => $row['memo_number']],
                [
                    'memo_date' => now()->subDays(12)->toDateString(),
                    'subject' => $row['memo_subject'],
                    'description' => 'Memo acuan untuk transaksi demo PPA.',
                    'file_name' => $memoFileName,
                    'file_path' => $memoPath,
                    'file_disk' => $memoDisk,
                    'file_extension' => 'pdf',
                    'mime_type' => 'application/pdf',
                    'file_size' => Storage::disk($memoDisk)->size($memoPath),
                    'uploaded_at' => now()->subDays(12),
                    'division_id' => $opsDivision,
                    'department_id' => $opsDepartment,
                    'created_by' => $admin?->id,
                ],
            );

            $agreementDisk = config('invoice_verification.storage.documents_disk', 'public');
            $agreementFileName = str($row['contract_number'])->lower()->replace('/', '-')->toString().'.pdf';
            $agreementPath = 'master-data/agreement-references/'.$agreementFileName;
            $this->putDemoPdf($agreementDisk, $agreementPath, 'Agreement Reference', [
                'Nomor: '.$row['contract_number'],
                'Judul: '.$row['contract_title'],
                'Vendor: '.$vendors[$row['vendor_code']]->name,
            ]);

            AgreementReference::updateOrCreate(
                ['contract_number' => $row['contract_number']],
                [
                    'vendor_id' => $vendors[$row['vendor_code']]->id,
                    'division_id' => $opsDivision,
                    'department_id' => $opsDepartment,
                    'title' => $row['contract_title'],
                    'contract_value' => $row['contract_value'],
                    'effective_date' => now()->subMonth()->toDateString(),
                    'expired_at' => now()->addMonths(5)->toDateString(),
                    'file_name' => $agreementFileName,
                    'file_path' => $agreementPath,
                    'file_disk' => $agreementDisk,
                    'file_extension' => 'pdf',
                    'mime_type' => 'application/pdf',
                    'file_size' => Storage::disk($agreementDisk)->size($agreementPath),
                    'uploaded_at' => now()->subDays(11),
                    'created_by' => $admin?->id,
                ],
            );
        }

        $templates = [
            [
                'code' => 'TPL-PPA-LEMBAR-AWAL',
                'name' => 'Template Lembar Awal PPA',
                'template_type' => TemplateType::GENERATED_DOCUMENT,
                'transaction_type_id' => $types[TransactionTypeCode::PPA->value]->id,
                'document_code' => DocumentCode::PPA_LEMBAR_AWAL->value,
                'file_path' => 'templates/generated/ppa-lembar-awal.blade.php',
                'configuration_json' => ['engine' => 'blade-pdf'],
            ],
            [
                'code' => 'TPL-PPA-COMPILE-ORDER',
                'name' => 'Urutan Kompilasi Dokumen PPA',
                'template_type' => TemplateType::FINAL_COMPILATION_ORDER,
                'transaction_type_id' => $types[TransactionTypeCode::PPA->value]->id,
                'document_code' => null,
                'file_path' => null,
                'configuration_json' => ['order' => config('invoice_verification.document_compile_order.PPA')],
            ],
        ];

        foreach ($templates as $template) {
            TemplateReference::updateOrCreate(
                ['code' => $template['code']],
                [
                    'name' => $template['name'],
                    'template_type' => $template['template_type']->value,
                    'transaction_type_id' => $template['transaction_type_id'],
                    'document_code' => $template['document_code'],
                    'file_path' => $template['file_path'],
                    'configuration_json' => $template['configuration_json'],
                    'is_active' => true,
                ],
            );
        }
    }

    private function putDemoPdf(string $disk, string $path, string $title, array $lines): void
    {
        Storage::disk($disk)->put($path, $this->demoPdfContent($title, $lines));
    }

    private function demoPdfContent(string $title, array $lines): string
    {
        $text = collect([$title, ...$lines])
            ->map(fn (string $line, int $index) => sprintf('BT /F1 12 Tf 72 %d Td (%s) Tj ET', 760 - ($index * 22), $this->escapePdfText($line)))
            ->implode("\n");

        $objects = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n",
            "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
            "5 0 obj\n<< /Length ".strlen($text)." >>\nstream\n".$text."\nendstream\nendobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n".$xrefOffset."\n%%EOF\n";

        return $pdf;
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
