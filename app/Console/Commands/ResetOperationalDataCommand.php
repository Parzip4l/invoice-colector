<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ResetOperationalDataCommand extends Command
{
    protected $signature = 'invoice-verification:reset-operational-data
        {--dry-run : Tampilkan data yang akan dihapus tanpa mengubah apa pun}
        {--confirm= : Token konfirmasi yang harus sama dengan konfigurasi production}';

    protected $description = 'Hapus seluruh data transaksi/testing dan file operasional tanpa menghapus akun maupun master data';

    public function handle(): int
    {
        if (! $this->allowed()) {
            $this->error('Reset data production dinonaktifkan. Aktifkan INVOICE_VERIFICATION_ALLOW_PRODUCTION_RESET terlebih dahulu.');

            return self::FAILURE;
        }

        $expectedToken = (string) config('invoice_verification.operations.production_reset_token');
        $providedToken = (string) $this->option('confirm');

        if ($expectedToken === '' || $providedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
            $this->error('Token konfirmasi reset tidak sesuai. Gunakan --confirm sesuai INVOICE_VERIFICATION_PRODUCTION_RESET_TOKEN.');

            return self::FAILURE;
        }

        $counts = $this->operationalCounts();
        $disks = $this->transactionFileDisks();
        $derivedFiles = $this->derivedCompilationFiles();

        $this->table(
            ['Data', 'Jumlah'],
            collect($counts)->map(fn (int $count, string $label) => [$label, $count])->values()->all(),
        );
        $this->line('Folder file transaksi yang diproses: '.($disks === [] ? 'tidak ada' : implode(', ', $disks)));

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN: tidak ada database maupun file yang dihapus.');

            return self::SUCCESS;
        }

        DB::transaction(function (): void {
            // Audit logs use nullOnDelete, therefore clear them before deleting transactions
            // so test history does not survive as orphan records.
            DB::table('audit_logs')->delete();
            DB::table('transactions')->delete();
            DB::table('transaction_number_sequences')->delete();
            DB::table('jobs')->delete();
            DB::table('failed_jobs')->delete();
            DB::table('job_batches')->delete();
        });

        $fileFailures = 0;

        foreach ($disks as $disk) {
            try {
                if (Storage::disk($disk)->exists('transactions')) {
                    Storage::disk($disk)->deleteDirectory('transactions');
                }
            } catch (Throwable $exception) {
                report($exception);
                $this->error("Database sudah dibersihkan, tetapi folder transactions pada disk {$disk} gagal dihapus: {$exception->getMessage()}");
                $fileFailures++;
            }
        }

        foreach ($derivedFiles as [$disk, $path]) {
            try {
                if (Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->delete($path);
                }
            } catch (Throwable $exception) {
                report($exception);
                $this->error("Database sudah dibersihkan, tetapi file {$disk}:{$path} gagal dihapus: {$exception->getMessage()}");
                $fileFailures++;
            }
        }

        $this->info('Data transaksi, approval, verifikasi, numbering, kompilasi, audit, antrean, dan sequence berhasil di-reset.');
        $this->comment('Akun SSO, user, divisi, departemen, vendor, bank, tipe transaksi/dokumen, template, memo, kontrak, dan approval flow tetap dipertahankan.');

        return $fileFailures === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function allowed(): bool
    {
        if (! app()->environment('production')) {
            return true;
        }

        return (bool) config('invoice_verification.operations.allow_production_reset', false);
    }

    /** @return array<string, int> */
    private function operationalCounts(): array
    {
        return [
            'Transaksi' => DB::table('transactions')->count(),
            'Dokumen upload' => DB::table('transaction_documents')->count(),
            'Dokumen generated' => DB::table('generated_documents')->count(),
            'Approval transaksi' => DB::table('approval_transactions')->count(),
            'Verifikasi accounting' => DB::table('accounting_verifications')->count(),
            'Numbering register' => DB::table('numbering_register')->count(),
            'Dokumen kompilasi' => DB::table('compiled_documents')->count(),
            'Audit log' => DB::table('audit_logs')->count(),
            'Queue aktif/gagal' => DB::table('jobs')->count() + DB::table('failed_jobs')->count(),
        ];
    }

    /** @return list<string> */
    private function transactionFileDisks(): array
    {
        return collect([
            config('invoice_verification.storage.documents_disk'),
            config('invoice_verification.storage.compiled_disk'),
            config('invoice_verification.storage.archive_disk'),
        ])->merge(DB::table('transaction_documents')->distinct()->pluck('file_disk'))
            ->merge(DB::table('generated_documents')->distinct()->pluck('file_disk'))
            ->merge(DB::table('ppa_verification_sheets')->distinct()->pluck('file_disk'))
            ->merge(DB::table('transactions')->distinct()->pluck('payment_proof_file_disk'))
            ->filter(fn ($disk) => is_string($disk) && $disk !== '')
            ->unique()
            ->values()
            ->all();
    }

    /** @return list<array{0: string, 1: string}> */
    private function derivedCompilationFiles(): array
    {
        return DB::table('compiled_documents')
            ->get(['compiled_file_disk', 'compiled_file_path', 'archive_disk', 'archive_path'])
            ->flatMap(fn ($document) => [
                [$document->compiled_file_disk, $document->compiled_file_path],
                [$document->archive_disk, $document->archive_path],
            ])
            ->filter(fn (array $file) => filled($file[0]) && filled($file[1]))
            ->unique(fn (array $file) => $file[0].'|'.$file[1])
            ->values()
            ->all();
    }
}
