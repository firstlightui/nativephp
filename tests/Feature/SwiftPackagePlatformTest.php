<?php

it('declares the NativePHP 4.7 iOS baseline for the Swift package', function () {
    $package = file_get_contents(dirname(__DIR__, 2).'/Package.swift');

    expect($package)->toContain('.iOS("18.2")')
        ->not->toContain('.iOS(.v16)');
});
