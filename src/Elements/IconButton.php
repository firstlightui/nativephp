<?php

namespace FirstlightUI\Elements;

use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;
use Native\Mobile\Icon\AndroidSymbol;
use Native\Mobile\Icon\IconResolver;
use Native\Mobile\Icon\IosSymbol;

final class IconButton extends Element
{
    protected string $type = 'firstlight.icon-button';

    /** @var list<string> */
    private const VARIANTS = ['primary', 'secondary', 'destructive', 'success', 'ghost'];

    /** @var list<string> */
    private const SIZES = ['sm', 'md', 'lg'];

    /** @var list<string> */
    private const UNSUPPORTED_ATTRIBUTES = [
        'label',
        'icon-trailing',
        'iconTrailing',
        'menu',
        'value',
        'sync-mode',
        'syncMode',
        '_change',
        '_submit',
        '_longPress',
        '_doubleTap',
        '_pressDown',
        '_pressUp',
        '_navigate',
        'color',
        'dark-color',
        'darkColor',
        'background',
        'bg',
        'font',
        'line-height',
        'lineHeight',
        'line-height-px',
        'lineHeightPx',
        'helper',
        'error',
        'required',
        'options',
    ];

    /** @var array<string, mixed> */
    private array $iconButtonProps = [
        'variant' => 'primary',
        'size' => 'md',
        'disabled' => false,
        'loading' => false,
    ];

    public static function make(): static
    {
        return new static;
    }

    public function applyAttributes(array $attrs): void
    {
        foreach (self::UNSUPPORTED_ATTRIBUTES as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                throw new InvalidArgumentException(
                    "Firstlight Icon Button does not support `{$attribute}`."
                );
            }
        }

        foreach (['disabled', 'loading'] as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                if (! is_bool($attrs[$attribute])) {
                    throw new InvalidArgumentException(
                        "Firstlight Icon Button `{$attribute}` must be a boolean."
                    );
                }

                $this->{$attribute}($attrs[$attribute]);
            }
        }

        if (array_key_exists('variant', $attrs)) {
            if (! is_string($attrs['variant'])) {
                throw new InvalidArgumentException('Firstlight Icon Button `variant` must be a string.');
            }

            $this->variant($attrs['variant']);
        }

        if (array_key_exists('size', $attrs)) {
            if (! is_string($attrs['size'])) {
                throw new InvalidArgumentException('Firstlight Icon Button `size` must be a string.');
            }

            $this->size($attrs['size']);
        }

        $name = $attrs['icon'] ?? null;
        $ios = $attrs['icon-ios'] ?? $attrs['iconIos'] ?? null;
        $android = $attrs['icon-android'] ?? $attrs['iconAndroid'] ?? null;

        if ($name !== null && ! is_string($name)) {
            throw new InvalidArgumentException('Firstlight Icon Button `icon` must be a string.');
        }
        if ($ios !== null && ! is_string($ios) && ! $ios instanceof IosSymbol) {
            throw new InvalidArgumentException(
                'Firstlight Icon Button `icon-ios` must be an IosSymbol or string.'
            );
        }
        if ($android !== null && ! is_string($android) && ! $android instanceof AndroidSymbol) {
            throw new InvalidArgumentException(
                'Firstlight Icon Button `icon-android` must be an AndroidSymbol or string.'
            );
        }

        $this->icon($name, $ios, $android);

        $label = $attrs['a11y-label'] ?? $attrs['a11yLabel'] ?? null;
        if ($label !== null && ! is_string($label)) {
            throw new InvalidArgumentException('Firstlight Icon Button `a11y-label` must be a string.');
        }
        if (is_string($label)) {
            $this->a11yLabel($label);
        }

        $hint = $attrs['a11y-hint'] ?? $attrs['a11yHint'] ?? null;
        if ($hint !== null && ! is_string($hint)) {
            throw new InvalidArgumentException('Firstlight Icon Button `a11y-hint` must be a string.');
        }
        if (is_string($hint)) {
            $this->a11yHint($hint);
        }
    }

    public function icon(
        ?string $name = null,
        IosSymbol|string|null $ios = null,
        AndroidSymbol|string|null $android = null,
    ): static {
        if ($name !== null) {
            $this->iconButtonProps['shared_icon'] = $name;
        }

        $resolved = IconResolver::resolve($name, $ios, $android);
        if ($resolved['icon'] !== null) {
            $this->iconButtonProps['icon'] = $resolved['icon'];
        }
        if ($resolved['variant'] !== null) {
            $this->iconButtonProps['icon_variant'] = $resolved['variant'];
        }

        return $this;
    }

    public function variant(string $value): static
    {
        if (! in_array($value, self::VARIANTS, true)) {
            throw new InvalidArgumentException(
                "Unsupported Icon Button variant [{$value}]. Expected one of: ".implode(', ', self::VARIANTS).'.'
            );
        }

        $this->iconButtonProps['variant'] = $value;

        return $this;
    }

    public function size(string $value): static
    {
        if (! in_array($value, self::SIZES, true)) {
            throw new InvalidArgumentException(
                "Unsupported Icon Button size [{$value}]. Expected one of: ".implode(', ', self::SIZES).'.'
            );
        }

        $this->iconButtonProps['size'] = $value;

        return $this;
    }

    public function disabled(bool $value = true): static
    {
        $this->iconButtonProps['disabled'] = $value;

        return $this;
    }

    public function loading(bool $value = true): static
    {
        $this->iconButtonProps['loading'] = $value;

        return $this;
    }

    protected function resolveProps(CallbackRegistry $registry): array
    {
        $sharedIcon = $this->iconButtonProps['shared_icon'] ?? null;
        if (! is_string($sharedIcon) || trim($sharedIcon) === '') {
            throw new InvalidArgumentException(
                'Firstlight Icon Button requires a non-empty `icon` shared fallback.'
            );
        }

        $label = $this->extraProps['a11y_label'] ?? null;
        if (! is_string($label) || trim($label) === '') {
            throw new InvalidArgumentException(
                'Firstlight Icon Button requires a non-empty `a11y-label`.'
            );
        }

        if ($this->pressMethod === null || trim($this->pressMethod) === '') {
            throw new InvalidArgumentException(
                'Firstlight Icon Button requires `@press`; use an Icon for display-only glyphs.'
            );
        }

        $props = $this->iconButtonProps;
        unset($props['shared_icon']);

        return $props;
    }

    public function getStyle(): array
    {
        return [];
    }

    public function getLayout(): array
    {
        $layout = parent::getLayout();
        unset($layout['padding']);

        return $layout;
    }
}
