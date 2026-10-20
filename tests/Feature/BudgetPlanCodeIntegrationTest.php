<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\InvoiceVerification\Domain\Enums\RoleCode;
use Database\Seeders\InvoiceVerification\InvoiceVerificationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BudgetPlanCodeIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InvoiceVerificationSeeder::class);

        $this->user = User::factory()->create([
            'email' => 'user.divisi@lrtjakarta.co.id',
            'name' => 'User Divisi',
            'role_code' => RoleCode::USER_DIVISI,
        ]);
    }

    public function test_guest_cannot_access_budget_plan_codes_search(): void
    {
        $response = $this->getJson(route('invoice-verification.budget-plan-codes.search'));
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_search_budget_plan_codes(): void
    {
        Http::fake([
            'https://versus.lrtjakarta.co.id/api/v1/budget-plan-codes*' => Http::response([
                'success' => true,
                'message' => 'Data Master Budget Plan Codes berhasil diambil.',
                'data' => [
                    [
                        'id' => 'b2f760e8-a896-4e48-acb0-32e2ab632d28',
                        'rab_code' => '01.S02.001',
                        'code_name' => 'Jasa Alih Contact Center',
                        'department_code' => 'PEL',
                        'department_name' => 'Services Department',
                        'kode_subsidi' => 'SRN1360',
                        'category' => 'SRN',
                        'status' => 'Active',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('invoice-verification.budget-plan-codes.search', ['q' => 'Contact']));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.rab_code', '01.S02.001')
            ->assertJsonPath('data.0.code_name', 'Jasa Alih Contact Center')
            ->assertJsonPath('data.0.department_code', 'PEL')
            ->assertJsonPath('data.0.label', '01.S02.001 - Jasa Alih Contact Center (PEL)');
    }

    public function test_authenticated_user_can_fetch_budget_plan_code_detail(): void
    {
        Http::fake([
            'https://versus.lrtjakarta.co.id/api/v1/budget-plan-codes/01.S02.001' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 'b2f760e8-a896-4e48-acb0-32e2ab632d28',
                    'rab_code' => '01.S02.001',
                    'code_name' => 'Jasa Alih Contact Center',
                    'department_code' => 'PEL',
                    'department_name' => 'Services Department',
                    'kode_subsidi' => 'SRN1360',
                    'category' => 'SRN',
                    'status' => 'Active',
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('invoice-verification.budget-plan-codes.show', '01.S02.001'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.rab_code', '01.S02.001')
            ->assertJsonPath('data.code_name', 'Jasa Alih Contact Center');
    }

    public function test_budget_plan_code_detail_returns_404_when_not_found(): void
    {
        Http::fake([
            'https://versus.lrtjakarta.co.id/api/v1/budget-plan-codes/UNKNOWN' => Http::response([
                'success' => false,
                'message' => 'Not found',
            ], 404),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('invoice-verification.budget-plan-codes.show', 'UNKNOWN'));

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }
}
