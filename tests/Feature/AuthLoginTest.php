<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Models\Department;
use App\Modules\InvoiceVerification\Domain\Models\Division;
use App\Modules\InvoiceVerification\Domain\Models\Vendor;
use Database\Seeders\InvoiceVerification\InvoiceVerificationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InvoiceVerificationSeeder::class);
    }

    public function test_login_page_uses_signal_title_and_password_toggle(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('<title>Login | SIGNAL</title>', false);
        $response->assertSee('id="toggle-password"', false);
    }

    public function test_internal_whitelisted_user_can_login_with_local_fallback_when_ldap_is_disabled(): void
    {
        config([
            'ldap.enabled' => false,
            'ldap.local_fallback' => true,
        ]);

        $response = $this->post(route('login'), [
            'email' => 'admin.divisi@demo.local',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('invoice-verification.dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'admin.divisi@demo.local')->firstOrFail());
    }

    public function test_non_whitelisted_email_cannot_login(): void
    {
        config([
            'ldap.enabled' => false,
            'ldap.local_fallback' => true,
        ]);

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => 'unknown@demo.local',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->followingRedirects()
            ->from(route('login'))
            ->post(route('login'), [
                'email' => 'unknown@demo.local',
                'password' => 'password',
            ])
            ->assertOk()
            ->assertSee('Login gagal')
            ->assertSee('Account is inactive or not whitelisted.');
    }

    public function test_vendor_uses_local_login_even_when_ldap_is_enabled(): void
    {
        config([
            'ldap.enabled' => true,
            'ldap.local_fallback' => false,
        ]);

        $response = $this->post(route('login'), [
            'email' => 'vendor@demo.local',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('invoice-verification.dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'vendor@demo.local')->firstOrFail());
    }

    public function test_register_post_is_disabled(): void
    {
        $response = $this->post('/register', [
            'name' => 'New User',
            'email' => 'new.user@demo.local',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertNotFound();
        $this->assertDatabaseMissing('users', [
            'email' => 'new.user@demo.local',
        ]);
    }

    public function test_admin_can_create_vendor_login_account_from_master_data(): void
    {
        $admin = User::where('email', 'admin.divisi@demo.local')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('invoice-verification.master-data.vendors.store'), [
            'name' => 'Vendor Baru',
            'npwp' => '01.234.567.8-999.000',
            'contact_name' => 'PIC Vendor Baru',
            'contact_email' => 'vendor.baru@demo.local',
            'contact_phone' => '081234567890',
            'vendor_password' => 'vendorpass',
            'bank_name' => 'Bank Demo',
            'default_account_number' => '1234567890',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('vendors', [
            'name' => 'Vendor Baru',
            'contact_email' => 'vendor.baru@demo.local',
        ]);

        $vendorUser = User::where('email', 'vendor.baru@demo.local')->firstOrFail();

        $this->assertTrue($vendorUser->hasRole(RoleCode::VENDOR));
        $this->assertTrue(Hash::check('vendorpass', $vendorUser->password));
        $this->assertNotNull(Vendor::where('contact_email', 'vendor.baru@demo.local')->first());

        $this->post(route('logout'));

        config([
            'ldap.enabled' => true,
            'ldap.local_fallback' => false,
        ]);

        $loginResponse = $this->post(route('login'), [
            'email' => 'vendor.baru@demo.local',
            'password' => 'vendorpass',
        ]);

        $loginResponse->assertRedirect(route('invoice-verification.dashboard'));
        $this->assertAuthenticatedAs($vendorUser);
    }

    public function test_admin_can_manage_internal_ldap_whitelist_from_master_data(): void
    {
        $admin = User::where('email', 'admin.divisi@demo.local')->firstOrFail();
        $division = Division::where('ldap_code', 'DIV-OPS')->firstOrFail();
        $department = Department::where('ldap_code', 'DEP-OPS-ADM')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('invoice-verification.master-data.ldap-whitelist.store'), [
            'name' => 'Internal LDAP User',
            'email' => 'internal.ldap@demo.local',
            'ldap_uid' => 'uid-internal-ldap',
            'employee_number' => 'EMP-0999',
            'role_code' => RoleCode::USER_DIVISI->value,
            'division_id' => $division->id,
            'department_id' => $department->id,
            'is_active' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'internal.ldap@demo.local',
            'role_code' => RoleCode::USER_DIVISI->value,
            'is_active' => true,
        ]);

        $whitelistedUser = User::where('email', 'internal.ldap@demo.local')->firstOrFail();

        $toggleResponse = $this->actingAs($admin)->patch(route('invoice-verification.master-data.ldap-whitelist.update', $whitelistedUser), [
            'is_active' => '0',
        ]);

        $toggleResponse->assertRedirect();
        $this->assertFalse($whitelistedUser->fresh()->is_active);
    }

    public function test_master_data_reference_page_uses_table_first_layout(): void
    {
        $admin = User::where('email', 'admin.divisi@demo.local')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('invoice-verification.master-data.index'));

        $response->assertOk();
        $response->assertSee('Reference Tables');
        $response->assertSee('Total Vendors');
        $response->assertSee('Tambah Vendor');
        $response->assertSee('LDAP Whitelist');
        $response->assertSee('vendorDrawer');
        $response->assertSee('data-reference-search', false);
    }

    public function test_admin_can_update_vendor_from_master_data(): void
    {
        $admin = User::where('email', 'admin.divisi@demo.local')->firstOrFail();
        $vendor = Vendor::where('contact_email', 'vendor@demo.local')->firstOrFail();

        $response = $this->actingAs($admin)->put(route('invoice-verification.master-data.vendors.update', $vendor), [
            'name' => 'Vendor Demo Updated',
            'npwp' => '09.999.999.9-999.000',
            'contact_name' => 'PIC Updated',
            'contact_email' => 'vendor.updated@demo.local',
            'contact_phone' => '081111111111',
            'bank_name' => 'Bank Update',
            'default_account_number' => '999000111',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'name' => 'Vendor Demo Updated',
            'contact_email' => 'vendor.updated@demo.local',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'vendor.updated@demo.local',
            'role_code' => RoleCode::VENDOR->value,
        ]);
    }
}
