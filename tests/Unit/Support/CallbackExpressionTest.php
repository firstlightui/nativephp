<?php

use Clinically\Firstlight\Support\CallbackExpression;
use Native\Mobile\Edge\CallbackRegistry;

it('appends an integer without changing existing callback arguments', function () {
    $parsed = CallbackRegistry::parse(
        CallbackExpression::appendInteger("selectQueue('documents')", 20)
    );

    expect($parsed)->toBe([
        'method' => 'selectQueue',
        'args' => ['documents', 20],
    ]);
});

it('appends an integer to a callback without existing arguments', function (string $expression) {
    $parsed = CallbackRegistry::parse(CallbackExpression::appendInteger($expression, -10));

    expect($parsed)->toBe([
        'method' => 'selectPriority',
        'args' => [-10],
    ]);
})->with(['selectPriority', 'selectPriority()']);
