<?php

namespace App\Modules\InvoiceVerification\Services;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Models\AuditLog;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;

class AuditLogService
{
    public function log(
        string $module,
        string $action,
        User $actor,
        ?Transaction $transaction = null,
        ?string $referenceType = null,
        string|int|null $referenceId = null,
        array $oldValue = [],
        array $newValue = [],
    ): AuditLog {
        return AuditLog::create([
            'transaction_id' => $transaction?->id,
            'actor_type' => User::class,
            'actor_id' => (string) $actor->getKey(),
            'module' => $module,
            'action' => $action,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId !== null ? (string) $referenceId : null,
            'old_value_json' => $oldValue ?: null,
            'new_value_json' => $newValue ?: null,
        ]);
    }
}
