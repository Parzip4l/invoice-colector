<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use App\Modules\InvoiceVerification\Domain\Models\Department;
use App\Modules\InvoiceVerification\Domain\Models\Division;
use Database\Seeders\InvoiceVerification\InvoiceVerificationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PortalOrganizationSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_transaction_syncs_read_only_division_and_department_from_portal(): void
    {
        $this->seed(InvoiceVerificationSeeder::class);
        $user = User::factory()->create([
            'name' => 'Pemohon Portal',
            'email' => 'pemohon.portal@lrtjakarta.co.id',
            'role_code' => RoleCode::USER_DIVISI,
            'division_id' => null,
            'department_id' => null,
        ]);

        config([
            'invoice_verification.approval_resolver.base_url' => 'https://portal.test',
            'invoice_verification.approval_resolver.shared_secret' => 'portal-test-secret',
            'invoice_verification.approval_resolver.client_code' => 'invoice_collector',
        ]);

        Http::fake([
            'https://portal.test/api/v1/approval/resolve' => Http::response([
                'success' => true,
                'data' => [
                    'requester' => [
                        'name' => $user->name,
                        'email' => $user->email,
                        'employee_number' => 'EMP-PORTAL-001',
                        'organization' => [
                            'division' => ['id' => 'portal-div', 'code' => 'DIV-PORTAL', 'name' => 'Divisi Portal', 'type' => 'division'],
                            'department' => ['id' => 'portal-dept', 'code' => 'DEP-PORTAL', 'name' => 'Departemen Portal', 'type' => 'department'],
                        ],
                    ],
                    'steps' => [],
                ],
            ]),
        ]);

        $response = $this->actingAs($user)->get(route('invoice-verification.transactions.create'));

        $response->assertOk()
            ->assertSee('Divisi Portal')
            ->assertSee('Departemen Portal')
            ->assertDontSee('Pilih departemen');

        $user->refresh();
        $this->assertSame('DIV-PORTAL', Division::findOrFail($user->division_id)->ldap_code);
        $this->assertSame('DEP-PORTAL', Department::findOrFail($user->department_id)->ldap_code);
        $this->assertSame('EMP-PORTAL-001', $user->employee_number);
    }
}
