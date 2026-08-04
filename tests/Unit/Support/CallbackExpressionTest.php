<?php

use FirstlightUI\Support\CallbackExpression;
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

it('appends null and list values as literal callback arguments', function (mixed $value) {
    $parsed = CallbackRegistry::parse(
        CallbackExpression::appendValue("selectQueues('documents')", $value)
    );

    expect($parsed)->toBe([
        'method' => 'selectQueues',
        'args' => ['documents', $value],
    ]);
})->with([
    null,
    ['mine', 'all'],
    [10, 20],
    ["O'Connor, 東京"],
]);
