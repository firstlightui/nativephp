<?php

namespace FirstlightUI\Elements;

use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;

final class ListSection extends \Native\Mobile\UI\Elements\ListSection
{
    /** @var list<string> */
    private const ALLOWED_CHILD_TYPES = [
        'firstlight.list-item',
    ];

    /** @var list<string> */
    private const UNSUPPORTED_ATTRIBUTES = [
        'separator',
        'plain',
        'horizontal',
        'shows-indicators',
        'showsIndicators',
        'on-refresh',
        'onRefresh',
        'on-end-reached',
        'onEndReached',
        'value',
        'sync-mode',
        'syncMode',
        '_change',
        '_press',
        '_submit',
        'label',
        'helper',
        'error',
        'required',
        'disabled',
        'loading',
        'options',
        'icon',
        'font',
        'line-height',
        'lineHeight',
        'line-height-px',
        'lineHeightPx',
        '_longPress',
        '_doubleTap',
        '_pressDown',
        '_pressUp',
        '_navigate',
    ];

    public function applyAttributes(array $attrs): void
    {
        foreach (self::UNSUPPORTED_ATTRIBUTES as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                throw new InvalidArgumentException(
                    "Firstlight List Section does not support `{$attribute}`."
                );
            }
        }

        if (array_key_exists('header', $attrs)) {
            if (! is_string($attrs['header']) || trim($attrs['header']) === '') {
                throw new InvalidArgumentException(
                    'Firstlight List Section requires a non-empty `header` when authored.'
                );
            }
        }

        if (array_key_exists('footer', $attrs)) {
            if (! is_string($attrs['footer']) || trim($attrs['footer']) === '') {
                throw new InvalidArgumentException(
                    'Firstlight List Section requires a non-empty `footer` when authored.'
                );
            }
        }

        parent::applyAttributes($attrs);
    }

    protected function resolveProps(CallbackRegistry $registry): array
    {
        $this->assertAllowedChildren();

        if ($this->children === []) {
            throw new InvalidArgumentException(
                'Firstlight List Section requires at least one List Item child.'
            );
        }

        return parent::resolveProps($registry);
    }

    private function assertAllowedChildren(): void
    {
        foreach ($this->children as $child) {
            if (! $child instanceof Element) {
                throw new InvalidArgumentException(
                    'Firstlight List Section accepts only List Item children.'
                );
            }

            if (! in_array($child->getType(), self::ALLOWED_CHILD_TYPES, true)) {
                throw new InvalidArgumentException(
                    'Firstlight List Section accepts only List Item children.'
                );
            }
        }
    }
}
