<?php

namespace Tests\Feature;

use App\Modules\InvoiceVerification\Domain\Models\Transaction;
use Database\Seeders\InvoiceVerification\InvoiceVerificationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileInvoiceCollectorApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'invoice_verification.mobile_sso.shared_secret' => 'mobile-secret',
            'invoice_verification.mobile_sso.signature_tolerance_seconds' => 300,
        ]);
        $this->seed(InvoiceVerificationSeeder::class);
    }

    public function test_portal_user_can_exchange_token_and_read_own_invoice_with_timeline(): void
    {
        $token = $this->exchangeToken('user.divisi@demo.local');

        $list = $this->withToken($token)
            ->getJson('/api/mobile/v1/invoice-collector/transactions?search=PNK-00001')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.data.0.registration_number', 'PNK-00001')
            ->assertJsonStructure(['data' => ['data', 'current_page', 'last_page', 'per_page', 'total']]);

        $transactionId = $list->json('data.data.0.id');

        $this->withToken($token)
            ->getJson("/api/mobile/v1/invoice-collector/transactions/{$transactionId}")
            ->assertOk()
            ->assertJsonPath('data.registration_number', 'PNK-00001')
            ->assertJsonStructure(['data' => ['status' => ['code', 'name'], 'documents', 'history', 'approvals']]);

        $this->withToken($token)
            ->getJson("/api/mobile/v1/invoice-collector/transactions/{$transactionId}/history")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => [['id', 'action', 'to_status', 'created_at']]]);

        $foreignTransaction = Transaction::where('registration_number', 'PPA-00001')->firstOrFail();
        $this->withToken($token)
            ->getJson("/api/mobile/v1/invoice-collector/transactions/{$foreignTransaction->id}")
            ->assertForbidden();
    }

    public function test_sso_exchange_rejects_invalid_signature(): void
    {
        $body = json_encode([
            'email' => 'user.divisi@demo.local',
            'name' => 'User Divisi',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $this->call('POST', '/api/mobile/v1/invoice-collector/auth/sso-exchange', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_INVOICE_TIMESTAMP' => (string) now()->timestamp,
            'HTTP_X_INVOICE_SIGNATURE' => 'invalid',
        ], $body)->assertUnauthorized();
    }

    private function exchangeToken(string $email): string
    {
        $body = json_encode([
            'email' => $email,
            'name' => 'Mobile User',
            'device_id' => 'device-test',
            'device_name' => 'Test Phone',
            'platform' => 'android',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->timestamp;

        $response = $this->call('POST', '/api/mobile/v1/invoice-collector/auth/sso-exchange', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_INVOICE_TIMESTAMP' => $timestamp,
            'HTTP_X_INVOICE_SIGNATURE' => hash_hmac('sha256', $timestamp.'.'.$body, 'mobile-secret'),
        ], $body)->assertOk();

        return (string) $response->json('data.access_token');
    }
}
