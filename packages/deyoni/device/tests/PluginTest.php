<?php

test('Android document bridge declares no runtime permissions', function () {
    $manifest = json_decode(file_get_contents(dirname(__DIR__).'/nativephp.json'), true, flags: JSON_THROW_ON_ERROR);
    expect($manifest['platforms'])->toBe(['android']);
    expect($manifest['android']['permissions'])->toBe([]);
    expect($manifest['events'])->toContain(\Deyoni\Device\Events\DocumentResult::class);
});

test('off-device document export fails explicitly', function () {
    if (function_exists('nativephp_call')) { $this->markTestSkipped('Native runtime or test bridge is active.'); }
    expect((new \Deyoni\Device\DeyoniDevice)->documents('export'))->toBeFalse();
});

test('document results preserve outcome and local path', function () {
    $result = new \Deyoni\Device\Events\DocumentResult(true, '/private/import.deyoni', 'import');
    expect($result->success)->toBeTrue();
    expect($result->path)->toBe('/private/import.deyoni');
    expect($result->action)->toBe('import');
});

