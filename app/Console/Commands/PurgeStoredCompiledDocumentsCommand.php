<?php

namespace App\Console\Commands;

use App\Modules\InvoiceVerification\Domain\Models\CompiledDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeStoredCompiledDocumentsCommand extends Command
{
    protected $signature = 'invoice-verification:purge-stored-compilations
        {--dry-run : Tampilkan file yang dapat dibersihkan tanpa menghapusnya}';

    protected $description = 'Hapus PDF kompilasi lama yang dapat dibuat ulang secara on-demand tanpa menghapus file sumber';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $deletedFiles = 0;
        $releasedBytes = 0;

        CompiledDocument::query()
            ->where(function ($query) {
                $query->whereNotNull('compiled_file_path')
                    ->orWhereNotNull('archive_path');
            })
            ->orderBy('id')
            ->each(function (CompiledDocument $compiledDocument) use ($dryRun, &$deletedFiles, &$releasedBytes): void {
                $seen = [];

                foreach ([
                    [$compiledDocument->compiled_file_disk, $compiledDocument->compiled_file_path],
                    [$compiledDocument->archive_disk, $compiledDocument->archive_path],
                ] as [$disk, $path]) {
                    if (blank($disk) || blank($path) || isset($seen[$disk.'|'.$path])) {
                        continue;
                    }

                    $seen[$disk.'|'.$path] = true;
                    $storage = Storage::disk($disk);

                    if (! $storage->exists($path)) {
                        continue;
                    }

                    $size = (int) $storage->size($path);
                    $this->line(sprintf('%s %s:%s (%s)', $dryRun ? '[Simulasi]' : '[Hapus]', $disk, $path, $this->formatBytes($size)));

                    if (! $dryRun) {
                        $storage->delete($path);
                    }

                    $deletedFiles++;
                    $releasedBytes += $size;
                }

                if (! $dryRun) {
                    $compiledDocument->forceFill([
                        'compiled_file_disk' => null,
                        'compiled_file_path' => null,
                        'archive_disk' => null,
                        'archive_path' => null,
                    ])->save();
                }
            });

        $verb = $dryRun ? 'dapat dibersihkan' : 'berhasil dibersihkan';
        $this->info(sprintf('%d file %s. Ruang yang dapat dibebaskan: %s.', $deletedFiles, $verb, $this->formatBytes($releasedBytes)));
        $this->comment('File sumber upload dan dokumen hasil generate tidak dihapus.');

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        if ($bytes < 1024 * 1024 * 1024) {
            return number_format($bytes / (1024 * 1024), 1).' MB';
        }

        return number_format($bytes / (1024 * 1024 * 1024), 2).' GB';
    }
}
