<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_user_can_be_promoted_to_superadmin_from_cli(): void
    {
        $user = User::factory()->create([
            'email' => 'muhamad.sobirin@lrtjakarta.co.id',
            'role_code' => RoleCode::USER_DIVISI,
            'is_active' => false,
        ]);

        $this->artisan('invoice:grant-superadmin', [
            'email' => 'MUHAMAD.SOBIRIN@LRTJAKARTA.CO.ID',
        ])
            ->expectsOutput('Akun muhamad.sobirin@lrtjakarta.co.id sekarang berstatus Super Admin dan dapat membuka seluruh menu internal.')
            ->assertSuccessful();

        $user->refresh();

        $this->assertSame(RoleCode::SUPERADMIN, $user->role_code);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->hasRole(RoleCode::SUPERADMIN));
        $this->assertTrue($user->hasRole(RoleCode::ADMIN_DIVISI));
        $this->assertTrue($user->hasRole(RoleCode::AKUNTANSI));
        $this->assertTrue($user->hasRole(RoleCode::FINANCE));
        $this->assertFalse($user->hasRole(RoleCode::VENDOR));
        $this->assertFalse($user->hasRole(RoleCode::USER_DIVISI));
        $this->assertFalse($user->hasRole(RoleCode::KEPALA_DEPARTEMEN));
        $this->assertFalse($user->hasRole(RoleCode::KEPALA_DIVISI));

        $this->actingAs($user)
            ->get(route('invoice-verification.dashboard'))
            ->assertOk()
            ->assertSee('Buat Transaksi')
            ->assertSee('Inbox Approval')
            ->assertSee('Verifikasi Accounting')
            ->assertSee('Numbering Register')
            ->assertSee('Dokumen Kompilasi')
            ->assertSee('Arsip')
            ->assertSee('Master Data')
            ->assertSee('Log Audit');
    }

    public function test_command_does_not_create_an_unknown_account(): void
    {
        $this->artisan('invoice:grant-superadmin', [
            'email' => 'missing@lrtjakarta.co.id',
        ])
            ->expectsOutput('Akun missing@lrtjakarta.co.id tidak ditemukan. Login melalui Microsoft 365 terlebih dahulu agar akun dibuat.')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'missing@lrtjakarta.co.id']);
    }
}
