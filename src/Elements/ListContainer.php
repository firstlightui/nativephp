<?php

namespace FirstlightUI\Elements;

use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;

class ListContainer extends \Native\Mobile\UI\Elements\NativeList
{
    protected string $type = 'firstlight.list';

    /** @var list<string> */
    private const ALLOWED_CHILD_TYPES = [
        'firstlight.list-item',
        'list_section',
    ];

    /** @var list<string> */
    private const UNSUPPORTED_ATTRIBUTES = [
        'horizontal',
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
                    "Firstlight List does not support `{$attribute}`."
                );
            }
        }

        foreach (['separator', 'plain', 'shows-indicators', 'showsIndicators'] as $attribute) {
            if (! array_key_exists($attribute, $attrs)) {
                continue;
            }

            if (! is_bool($attrs[$attribute])) {
                throw new InvalidArgumentException(
                    "Firstlight List `{$attribute}` must be a boolean."
                );
            }
        }

        parent::applyAttributes($attrs);
    }

    protected function resolveProps(CallbackRegistry $registry): array
    {
        $this->assertAllowedChildren();

        return parent::resolveProps($registry);
    }

    private function assertAllowedChildren(): void
    {
        foreach ($this->children as $child) {
            if (! $child instanceof Element) {
                throw new InvalidArgumentException(
                    'Firstlight List accepts only List Item and List Section children.'
                );
            }

            if (! in_array($child->getType(), self::ALLOWED_CHILD_TYPES, true)) {
                throw new InvalidArgumentException(
                    'Firstlight List accepts only List Item and List Section children.'
                );
            }
        }
    }
}
