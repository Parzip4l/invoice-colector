<?php

namespace App\Modules\InvoiceVerification\Jobs;

use App\Modules\InvoiceVerification\Services\Contracts\LdapDirectorySynchronizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncLdapDirectoryJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(LdapDirectorySynchronizer $synchronizer): void
    {
        $synchronizer->syncAll();
    }
}
