<?php

it('declares the NativePHP v4 iOS baseline for the Swift package', function () {
    $package = file_get_contents(dirname(__DIR__, 2).'/Package.swift');

    expect($package)->toContain('.iOS(.v18)')
        ->not->toContain('.iOS(.v16)');
});
