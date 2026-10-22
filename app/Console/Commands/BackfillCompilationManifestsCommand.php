<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Enums\TransactionStatus;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Services\FinalizationService;
use Illuminate\Console\Command;

class BackfillCompilationManifestsCommand extends Command
{
    protected $signature = 'invoice-verification:backfill-compilation-manifests
        {--force : Susun ulang manifest yang sudah ada agar memakai daftar dokumen terbaru}';

    protected $description = 'Buat manifest Dokumen Kompilasi on-demand untuk transaksi Completed dan Archived yang lama';

    public function handle(FinalizationService $finalizationService): int
    {
        $fallbackActor = User::query()
            ->whereIn('role_code', [RoleCode::SUPERADMIN->value, RoleCode::ADMIN_DIVISI->value])
            ->where('is_active', true)
            ->orderByRaw("CASE WHEN role_code = 'SUPERADMIN' THEN 0 ELSE 1 END")
            ->first();

        $query = Transaction::query()
            ->whereIn('status', [
                TransactionStatus::COMPLETED->value,
                TransactionStatus::ARCHIVED->value,
            ])
            ->with([
                'transactionType',
                'owner',
                'creator',
                'generatedDocuments',
                'latestDocuments.documentType',
                'ppaVerificationSheet',
                'memoRequest',
                'agreementReference',
                'compiledDocument',
            ])
            ->orderBy('id');

        if (! $this->option('force')) {
            $query->whereDoesntHave('compiledDocument');
        }

        $created = 0;
        $refreshed = 0;
        $skipped = 0;
        $failed = 0;

        $query->chunkById(100, function ($transactions) use (
            $finalizationService,
            $fallbackActor,
            &$created,
            &$refreshed,
            &$skipped,
            &$failed,
        ): void {
            foreach ($transactions as $transaction) {
                $actor = $transaction->owner ?? $transaction->creator ?? $fallbackActor;

                if (! $actor) {
                    $this->warn("Lewati {$transaction->registration_number}: user pembuat/owner tidak ditemukan.");
                    $skipped++;

                    continue;
                }

                $hadManifest = $transaction->compiledDocument !== null;

                try {
                    $manifest = $finalizationService->registerCompilationManifest($transaction, $actor);
                    $hadManifest ? $refreshed++ : $created++;
                    $this->line(sprintf(
                        '[%s] %s — %d dokumen',
                        $hadManifest ? 'Perbarui' : 'Buat',
                        $transaction->registration_number,
                        $manifest->total_files,
                    ));
                } catch (\Throwable $exception) {
                    report($exception);
                    $this->error("Gagal {$transaction->registration_number}: {$exception->getMessage()}");
                    $failed++;
                }
            }
        });

        $this->newLine();
        $this->info("Selesai: {$created} dibuat, {$refreshed} diperbarui, {$skipped} dilewati, {$failed} gagal.");
        $this->comment('PDF belum disimpan di server; file gabungan baru dibuat sementara ketika user mengunduhnya.');

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
