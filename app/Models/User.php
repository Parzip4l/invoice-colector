<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Models\AccountingVerification;
use App\Modules\InvoiceVerification\Domain\Models\ApprovalTransaction;
use App\Modules\InvoiceVerification\Domain\Models\Department;
use App\Modules\InvoiceVerification\Domain\Models\Division;
use App\Modules\InvoiceVerification\Domain\Models\GeneratedDocument;
use App\Modules\InvoiceVerification\Domain\Models\PpaVerificationSheet;
use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use App\Modules\InvoiceVerification\Domain\Models\Vendor;
use App\Modules\InvoiceVerification\Domain\Models\VendorDocumentReview;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ldap_uid',
        'employee_number',
        'name',
        'email',
        'department_id',
        'division_id',
        'role_code',
        'is_active',
        'last_synced_at',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'is_active' => 'boolean',
            'role_code' => RoleCode::class,
            'password' => 'hashed',
        ];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function createdTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'created_by');
    }

    public function pendingApprovals(): HasMany
    {
        return $this->hasMany(ApprovalTransaction::class, 'approver_user_id');
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class, 'generated_by');
    }

    public function vendorDocumentReviews(): HasMany
    {
        return $this->hasMany(VendorDocumentReview::class, 'reviewed_by');
    }

    public function accountingVerifications(): HasMany
    {
        return $this->hasMany(AccountingVerification::class, 'verifier_user_id');
    }

    public function ppaVerificationSheets(): HasMany
    {
        return $this->hasMany(PpaVerificationSheet::class, 'filled_by_user_id');
    }

    public function vendorProfile(): HasOne
    {
        return $this->hasOne(Vendor::class, 'contact_email', 'email');
    }

    public function linkedVendor(): ?Vendor
    {
        return $this->relationLoaded('vendorProfile')
            ? $this->vendorProfile
            : $this->vendorProfile()->first();
    }

    public function hasRole(RoleCode|string ...$roles): bool
    {
        $expected = array_map(
            static fn (RoleCode|string $role) => $role instanceof RoleCode ? $role->value : $role,
            $roles,
        );

        return in_array($this->role_code?->value ?? $this->role_code, $expected, true);
    }
}
