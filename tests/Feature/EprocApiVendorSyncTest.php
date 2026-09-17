<?php

namespace Tests\Feature;

use App\Modules\InvoiceVerification\Domain\Models\AgreementReference;
use App\Modules\InvoiceVerification\Domain\Models\Vendor;
use App\Modules\InvoiceVerification\Services\Eproc\EprocApiVendorSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EprocApiVendorSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_vendors_and_purchase_orders_from_eproc_api(): void
    {
        config([
            'invoice_verification.eproc.base_url' => 'https://eproc.test',
            'invoice_verification.eproc.vendor_endpoint' => 'detail_purchase_order_vendor',
            'invoice_verification.eproc.token' => 'CTU2000',
        ]);

        Http::fake([
            'eproc.test/detail_purchase_order_vendor' => Http::response([
                'response' => [
                    'status' => true,
                    'message' => 'Successfully Get Detail Vendor',
                ],
                'results' => [
                    'vendor' => [
                        [
                            'erp_vendor_code' => 'S-004021',
                            'vendor_name' => 'Leap Networks Indonesia',
                            'vendor_address' => 'Jl. Kesehatan Raya No.12',
                            'phone_number' => '081371442551',
                            'email' => 'saleslni@leap-networks.id',
                            'npwp_company' => '317752475036000',
                            'contact_person_name' => 'Aqila Nurussakinah Siregar',
                            'contact_person_mobile_phone' => '081371442551',
                            'contact_person_email' => 'aqila.siregar@leap-networks.id',
                            'purchase_order' => [
                                [
                                    'purchase_order_number' => '82/SCM/114/VIII/2025',
                                    'purchase_order_date' => '2025-08-07',
                                    'delivery_date' => '2025-08-04',
                                    'purchase_order_item' => [
                                        [
                                            'item_name' => 'Jasa Perawatan & Instalasi CCTV',
                                            'description' => 'Jasa Perawatan & Instalasi CCTV',
                                            'line_amount' => '466200000',
                                        ],
                                        [
                                            'item_name' => 'Perangkat Security Command Center',
                                            'description' => 'Perangkat Security Command Center',
                                            'line_amount' => '244200000',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $stats = app(EprocApiVendorSyncService::class)->sync();

        $this->assertSame(1, $stats['vendors_created']);
        $this->assertSame(1, $stats['agreements_created']);

        $vendor = Vendor::query()->where('vendor_code', 'S-004021')->firstOrFail();
        $this->assertSame('Leap Networks Indonesia', $vendor->name);
        $this->assertSame('317752475036000', $vendor->npwp);
        $this->assertSame('aqila.siregar@leap-networks.id', $vendor->contact_email);

        $agreement = AgreementReference::query()->where('contract_number', '82/SCM/114/VIII/2025')->firstOrFail();
        $this->assertSame($vendor->id, $agreement->vendor_id);
        $this->assertSame('710400000.00', $agreement->contract_value);
        $this->assertSame('2025-08-07', $agreement->effective_date?->format('Y-m-d'));
    }
}
