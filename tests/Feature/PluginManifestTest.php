<?php

it('declares the Firstlight package identity', function () {
    $composer = json_decode(file_get_contents(dirname(__DIR__, 2).'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    $manifest = json_decode(file_get_contents(dirname(__DIR__, 2).'/nativephp.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($composer['name'])->toBe('clinically/firstlight-ui')
        ->and($composer['type'])->toBe('nativephp-ui-plugin')
        ->and($composer['require']['nativephp/mobile'])->toBe('^4.0')
        ->and($composer['require']['nativephp/mobile-ui'])->toBe('^0.3')
        ->and($composer['autoload']['psr-4'])->toBe(['Clinically\\Firstlight\\' => 'src/'])
        ->and($manifest['name'])->toBe('clinically/firstlight-ui')
        ->and($manifest['version'])->toBe('0.1.0-alpha.1')
        ->and($manifest['namespace'])->toBe('Firstlight')
        ->and($manifest['platforms'])->toBe(['android', 'ios']);
});

it('starts without a generated demonstration component', function () {
    $manifest = json_decode(file_get_contents(dirname(__DIR__, 2).'/nativephp.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['components'])->toBe([]);
});
