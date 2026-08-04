<?php

namespace FirstlightUI\Elements;

use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;

class Button extends \Native\Mobile\UI\Elements\Button
{
    protected string $type = 'firstlight.button';

    /** @var list<string> */
    private const VARIANTS = ['primary', 'secondary', 'destructive', 'success', 'ghost'];

    /** @var list<string> */
    private const SIZES = ['sm', 'md', 'lg'];

    /** @var list<string> */
    private const UNSUPPORTED_ATTRIBUTES = [
        'font',
        'line-height',
        'lineHeight',
        'line-height-px',
        'lineHeightPx',
        'menu',
        'glass',
        '_longPress',
        '_doubleTap',
        '_pressDown',
        '_pressUp',
        '_navigate',
        'value',
        'sync-mode',
        'syncMode',
        '_change',
        'options',
        'helper',
        'error',
        'required',
    ];

    /** @var array<string, mixed> */
    protected array $buttonProps = [
        'variant' => 'primary',
        'size' => 'md',
        'disabled' => false,
        'loading' => false,
    ];

    public function applyAttributes(array $attrs): void
    {
        foreach (self::UNSUPPORTED_ATTRIBUTES as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                throw new InvalidArgumentException(
                    "Firstlight Button does not support `{$attribute}`."
                );
            }
        }

        foreach (['disabled', 'loading'] as $attribute) {
            if (array_key_exists($attribute, $attrs) && ! is_bool($attrs[$attribute])) {
                throw new InvalidArgumentException(
                    "Firstlight Button `{$attribute}` must be a boolean."
                );
            }
        }

        parent::applyAttributes($attrs);
    }

    public function variant(string $value): static
    {
        if (! in_array($value, self::VARIANTS, true)) {
            throw new InvalidArgumentException(
                "Unsupported Button variant [{$value}]. Expected one of: "
                .implode(', ', self::VARIANTS).'.'
            );
        }

        return parent::variant($value);
    }

    public function size(string $value): static
    {
        if (! in_array($value, self::SIZES, true)) {
            throw new InvalidArgumentException(
                "Unsupported Button size [{$value}]. Expected one of: "
                .implode(', ', self::SIZES).'.'
            );
        }

        return parent::size($value);
    }

    protected function resolveProps(CallbackRegistry $registry): array
    {
        $props = parent::resolveProps($registry);

        if (trim((string) ($props['label'] ?? '')) === '') {
            throw new InvalidArgumentException(
                'Firstlight Button requires a non-empty `label`; use Icon Button for icon-only actions.'
            );
        }

        return $props;
    }
}
