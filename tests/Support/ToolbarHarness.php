<?php

use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\NativeElementCollector;

/** @param list<array<string, mixed>> $items */
function collectToolbar(array $items, array $attributes = [], ?CallbackRegistry $registry = null): array
{
    NativeElementCollector::open('firstlight.toolbar', $attributes);
    foreach ($items as $item) {
        NativeElementCollector::leaf($item['type'] ?? 'firstlight.toolbar-item', array_diff_key($item, ['type' => true]));
    }
    NativeElementCollector::close();

    return NativeElementCollector::collect()->toArray($registry ?? new CallbackRegistry);
}

function toolbarItem(string $label, array $attributes = []): array
{
    return ['icon' => 'star', 'a11y-label' => $label, '_press' => 'run'.str_replace(' ', '', $label), ...$attributes];
}
