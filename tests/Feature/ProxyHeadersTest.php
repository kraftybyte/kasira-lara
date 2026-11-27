<?php

it('ignores a forged X-Forwarded-For header when reading the client ip', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '9.9.9.9'])
        ->withHeaders(['X-Forwarded-For' => '1.2.3.4'])
        ->get('/admin/login')
        ->assertOk();

    expect(request()->ip())->toBe('9.9.9.9');
});

it('still detects https from X-Forwarded-Proto', function () {
    $this->withHeaders(['X-Forwarded-Proto' => 'https'])->get('/admin/login')->assertOk();

    expect(request()->isSecure())->toBeTrue();
});
