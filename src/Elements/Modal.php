<?php

namespace FirstlightUI\Elements;

use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;

class Modal extends \Native\Mobile\UI\Elements\Modal
{
    protected string $type = 'firstlight.modal';

    /** @var list<string> */
    private const UNSUPPORTED_ATTRIBUTES = [
        'value',
        'native:model',
        'detents',
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
        $modal = new static;
        $modal->children = $children;

        return $modal;
    }

    public function applyAttributes(array $attrs): void
    {
        foreach (self::UNSUPPORTED_ATTRIBUTES as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                throw new InvalidArgumentException(
                    "Firstlight Modal does not support `{$attribute}`."
                );
            }
        }

        foreach (['visible', 'dismissible', 'dismissable'] as $attribute) {
            if (! array_key_exists($attribute, $attrs)) {
                continue;
            }

            if (! is_bool($attrs[$attribute])) {
                throw new InvalidArgumentException(
                    "Firstlight Modal `{$attribute}` must be a boolean."
                );
            }
        }

        foreach (['a11y-label', 'a11yLabel', 'a11y-hint', 'a11yHint'] as $attribute) {
            if (! array_key_exists($attribute, $attrs)) {
                continue;
            }

            if (! is_string($attrs[$attribute]) || trim($attrs[$attribute]) === '') {
                throw new InvalidArgumentException(
                    "Firstlight Modal `{$attribute}` must be a non-empty string."
                );
            }
        }

        parent::applyAttributes($attrs);
    }

    protected function resolveProps(CallbackRegistry $registry): array
    {
        if ($this->dismissCallback === null || trim($this->dismissCallback) === '') {
            throw new InvalidArgumentException(
                'Firstlight Modal requires `@dismiss`.'
            );
        }

        return parent::resolveProps($registry);
    }
}
