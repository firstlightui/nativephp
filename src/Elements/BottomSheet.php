<?php

namespace FirstlightUI\Elements;

use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;

class BottomSheet extends \Native\Mobile\UI\Elements\BottomSheet
{
    protected string $type = 'firstlight.bottom-sheet';

    /** @var list<string> */
    private const UNSUPPORTED_ATTRIBUTES = [
        'value',
        'native:model',
        'detents',
        'dismissible',
        'dismissable',
        'horizontal',
        'label',
        'helper',
        'error',
        'required',
        'disabled',
        'loading',
        'options',
        'icon',
        'sync-mode',
        'syncMode',
        '_change',
        '_press',
        '_submit',
        '_longPress',
        '_doubleTap',
        '_pressDown',
        '_pressUp',
        '_navigate',
        '_refresh',
        'on-refresh',
        'onRefresh',
    ];

    public static function make(Element ...$children): static
    {
        $sheet = new static;
        $sheet->children = $children;

        return $sheet;
    }

    public function applyAttributes(array $attrs): void
    {
        foreach (self::UNSUPPORTED_ATTRIBUTES as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                throw new InvalidArgumentException(
                    "Firstlight Bottom Sheet does not support `{$attribute}`."
                );
            }
        }

        if (array_key_exists('visible', $attrs) && ! is_bool($attrs['visible'])) {
            throw new InvalidArgumentException(
                'Firstlight Bottom Sheet `visible` must be a boolean.'
            );
        }

        foreach (['a11y-label', 'a11yLabel', 'a11y-hint', 'a11yHint'] as $attribute) {
            if (! array_key_exists($attribute, $attrs)) {
                continue;
            }

            if (! is_string($attrs[$attribute]) || trim($attrs[$attribute]) === '') {
                throw new InvalidArgumentException(
                    "Firstlight Bottom Sheet `{$attribute}` must be a non-empty string."
                );
            }
        }

        parent::applyAttributes($attrs);
    }

    protected function resolveProps(CallbackRegistry $registry): array
    {
        if ($this->dismissCallback === null || trim($this->dismissCallback) === '') {
            throw new InvalidArgumentException(
                'Firstlight Bottom Sheet requires `@dismiss`.'
            );
        }

        return parent::resolveProps($registry);
    }
}
