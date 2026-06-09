<?php

namespace App\Modules\InvoiceVerification\Domain\Enums;

enum DocumentCode: string
{
    case PPA_LEMBAR_AWAL = 'PPA_LEMBAR_AWAL';
    case PPA_LEMBAR_VERIFIKASI = 'PPA_LEMBAR_VERIFIKASI';
    case PPA_INVOICE = 'PPA_INVOICE';
    case PPA_KWITANSI = 'PPA_KWITANSI';
    case PPA_FAKTUR_PAJAK = 'PPA_FAKTUR_PAJAK';
    case PPA_BAPP = 'PPA_BAPP';
    case PPA_BAST = 'PPA_BAST';
    case PPA_MEMO_PERMOHONAN = 'PPA_MEMO_PERMOHONAN';
    case PPA_PERJANJIAN = 'PPA_PERJANJIAN';
    case PPA_LAMPIRAN_PEKERJAAN = 'PPA_LAMPIRAN_PEKERJAAN';
    case PPA_LAPORAN_PEKERJAAN = 'PPA_LAPORAN_PEKERJAAN';
    case SPU_INITIAL_FORM = 'SPU_INITIAL_FORM';
    case SPUK_INITIAL_FORM = 'SPUK_INITIAL_FORM';
    case KAS_KECIL_INITIAL_FORM = 'KAS_KECIL_INITIAL_FORM';
    case SPU_COMBINED_INTERNAL = 'SPU_COMBINED_INTERNAL';
    case SPU_COMBINED_VENDOR = 'SPU_COMBINED_VENDOR';
    case SPUK_COMBINED_INTERNAL = 'SPUK_COMBINED_INTERNAL';
    case SPUK_COMBINED_VENDOR = 'SPUK_COMBINED_VENDOR';
    case KAS_KECIL_COMBINED_INTERNAL = 'KAS_KECIL_COMBINED_INTERNAL';
    case KAS_KECIL_COMBINED_VENDOR = 'KAS_KECIL_COMBINED_VENDOR';

    public function label(): string
    {
        return match ($this) {
            self::PPA_LEMBAR_AWAL => 'Lembar Awal PPA',
            self::PPA_LEMBAR_VERIFIKASI => 'Lembar Verifikasi',
            self::PPA_INVOICE => 'Invoice',
            self::PPA_KWITANSI => 'Kwitansi',
            self::PPA_FAKTUR_PAJAK => 'Faktur Pajak',
            self::PPA_BAPP => 'BAPP',
            self::PPA_BAST => 'BAST',
            self::PPA_MEMO_PERMOHONAN => 'Memo Permohonan',
            self::PPA_PERJANJIAN => 'Perjanjian (PKS / SPK / PO)',
            self::PPA_LAMPIRAN_PEKERJAAN => 'Lampiran Pekerjaan',
            self::PPA_LAPORAN_PEKERJAAN => 'Laporan Pekerjaan',
            self::SPU_INITIAL_FORM => 'Dokumen Awal SPU',
            self::SPUK_INITIAL_FORM => 'Dokumen Awal SPUK',
            self::KAS_KECIL_INITIAL_FORM => 'Dokumen Awal Kas Kecil',
            self::SPU_COMBINED_INTERNAL => 'Dokumen Internal SPU',
            self::SPU_COMBINED_VENDOR => 'Dokumen Vendor SPU',
            self::SPUK_COMBINED_INTERNAL => 'Dokumen Internal SPUK',
            self::SPUK_COMBINED_VENDOR => 'Dokumen Vendor SPUK',
            self::KAS_KECIL_COMBINED_INTERNAL => 'Dokumen Internal Kas Kecil',
            self::KAS_KECIL_COMBINED_VENDOR => 'Dokumen Vendor Kas Kecil',
        };
    }
}
