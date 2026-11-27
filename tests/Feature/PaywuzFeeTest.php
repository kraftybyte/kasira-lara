<?php

use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Services\PaywuzService;

it('grosses up the QRIS fee so the store receives the full bill when the customer pays the fee', function () {
    $tenant = Tenant::factory()->create();
    TenantSetting::create(['tenant_id' => $tenant->id, 'paywuz_api_key' => 'k', 'paywuz_fee_by_merchant' => false]);

    $fee = (new PaywuzService($tenant->id))->calculateQrisFee(100000);

    expect($fee['fee'])->toBe(997.0)
        ->and($fee['customer_pays'])->toBe(100997.0)
        ->and($fee['merchant_receives'])->toBe(100000.0)
        ->and(290 + ceil(100997 * 0.007))->toBe(997.0);
});

it('charges the plain fee when the store bears it', function () {
    $tenant = Tenant::factory()->create();
    TenantSetting::create(['tenant_id' => $tenant->id, 'paywuz_api_key' => 'k', 'paywuz_fee_by_merchant' => true]);

    $fee = (new PaywuzService($tenant->id))->calculateQrisFee(100000);

    expect($fee['fee'])->toBe(990.0)->and($fee['customer_pays'])->toBe(100000.0);
});
