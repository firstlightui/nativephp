<?php

namespace FirstlightUI\Elements;

use FirstlightUI\Elements\Concerns\AcceptsHaptic;
use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;
use Native\Mobile\Icon\AndroidSymbol;
use Native\Mobile\Icon\IconResolver;
use Native\Mobile\Icon\IosSymbol;

/**
 * One icon action in a Toolbar. The Toolbar renders its items; an item never
 * renders on its own.
 */
final class ToolbarItem extends Element
{
    use AcceptsHaptic;

    protected string $type = 'firstlight.toolbar-item';

    /** @var list<string> */
    private const MENU_KEYS = ['method', 'label', 'icon', 'ios', 'android', 'role', 'disabled'];

    /** @var list<string> */
    private const UNSUPPORTED_ATTRIBUTES = [
        'label', 'variant', 'size', 'loading', 'glass', 'value', 'sync-mode', 'syncMode',
        'context-menu', 'contextMenu', '_change', '_submit', '_longPress', '_doubleTap',
        '_pressDown', '_pressUp', '_navigate', 'url',
        // iOS bar buttons take no hint or value, so neither platform does.
        'a11y-hint', 'a11yHint', 'badge-a11y-label', 'badgeA11yLabel',
    ];

    /** @var array<string, mixed> */
    private array $itemProps = ['primary' => false, 'disabled' => false];

    /** @var list<array<string, mixed>> */
    private array $menu = [];

    protected function hapticPlaysOnPress(): bool
    {
        return true;
    }

    public static function make(): static
    {
        return new static;
    }

    public function applyAttributes(array $attrs): void
    {
        $attrs = $this->applyHapticAttribute($attrs);

        foreach (self::UNSUPPORTED_ATTRIBUTES as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                throw new InvalidArgumentException("Firstlight Toolbar Item does not support `{$attribute}`.");
            }
        }

        foreach (['primary', 'disabled'] as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                if (! is_bool($attrs[$attribute])) {
                    throw new InvalidArgumentException("Firstlight Toolbar Item `{$attribute}` must be a boolean.");
                }

                $this->{$attribute}($attrs[$attribute]);
            }
        }

        $name = $attrs['icon'] ?? null;
        $ios = $attrs['icon-ios'] ?? $attrs['iconIos'] ?? null;
        $android = $attrs['icon-android'] ?? $attrs['iconAndroid'] ?? null;
        if ($name !== null && ! is_string($name)) {
            throw new InvalidArgumentException('Firstlight Toolbar Item `icon` must be a string.');
        }
        if ($ios !== null && ! is_string($ios) && ! $ios instanceof IosSymbol) {
            throw new InvalidArgumentException('Firstlight Toolbar Item `icon-ios` must be an IosSymbol or string.');
        }
        if ($android !== null && ! is_string($android) && ! $android instanceof AndroidSymbol) {
            throw new InvalidArgumentException('Firstlight Toolbar Item `icon-android` must be an AndroidSymbol or string.');
        }
        if ($name !== null || $ios !== null || $android !== null) {
            $this->icon($name, $ios, $android);
        }

        $label = $attrs['a11y-label'] ?? $attrs['a11yLabel'] ?? null;
        if ($label !== null && ! is_string($label)) {
            throw new InvalidArgumentException('Firstlight Toolbar Item `a11y-label` must be a string.');
        }
        if (is_string($label)) {
            $this->a11yLabel($label);
        }

        if (array_key_exists('badge', $attrs)) {
            $this->badge($attrs['badge']);
        }

        if (array_key_exists('menu', $attrs)) {
            if (! is_array($attrs['menu'])) {
                throw new InvalidArgumentException('Firstlight Toolbar Item `menu` must be an array.');
            }
            $this->menu($attrs['menu']);
        }
    }

    public function icon(
        ?string $name = null,
        IosSymbol|string|null $ios = null,
        AndroidSymbol|string|null $android = null,
    ): static {
        if ($name === null || trim($name) === '') {
            throw new InvalidArgumentException('Firstlight Toolbar Item requires a non-empty `icon` shared fallback.');
        }

        $resolved = IconResolver::resolve($name, $ios, $android);
        $this->itemProps['icon'] = $resolved['icon'];
        $this->itemProps['icon_variant'] = $resolved['variant'] ?? '';

        return $this;
    }

    public function primary(bool $value = true): static
    {
        $this->itemProps['primary'] = $value;

        return $this;
    }

    public function isPrimary(): bool
    {
        return $this->itemProps['primary'];
    }

    public function disabled(bool $value = true): static
    {
        $this->itemProps['disabled'] = $value;

        return $this;
    }

    /** A count or short text, such as 3 or 'New'. Null and 0 show no badge; counts above 99 show 99+. */
    public function badge(int|string|null $value): static
    {
        $this->itemProps['badge'] = match (true) {
            $value === null, $value === 0, $value === '' => '',
            is_int($value) && $value < 0 => throw new InvalidArgumentException('Firstlight Toolbar Item `badge` must not be negative.'),
            is_int($value) && $value > 99 => '99+',
            default => (string) $value,
        };

        return $this;
    }

    /**
     * Items the press opens instead of running `@press`. Each takes `method`
     * and `label`, and optionally `icon` with `ios` and `android` overrides,
     * `role` (`destructive`) and `disabled`.
     *
     * @param  list<array<string, mixed>>  $items
     */
    public function menu(array $items): static
    {
        foreach ($items as $item) {
            if (! is_array($item) || array_diff(array_keys($item), self::MENU_KEYS) !== []) {
                throw new InvalidArgumentException('Firstlight Toolbar Item `menu` accepts arrays with '.implode(', ', self::MENU_KEYS).' only.');
            }
            foreach (['method', 'label'] as $key) {
                if (! is_string($item[$key] ?? null) || trim($item[$key]) === '') {
                    throw new InvalidArgumentException("Firstlight Toolbar Item menu items require a non-empty `{$key}`.");
                }
            }
            if (! in_array($item['role'] ?? '', ['', 'destructive'], true)) {
                throw new InvalidArgumentException('Firstlight Toolbar Item menu item `role` must be empty or destructive.');
            }
            if (array_key_exists('disabled', $item) && ! is_bool($item['disabled'])) {
                throw new InvalidArgumentException('Firstlight Toolbar Item menu item `disabled` must be a boolean.');
            }
            if (array_key_exists('icon', $item) && (! is_string($item['icon']) || trim($item['icon']) === '')) {
                throw new InvalidArgumentException('Firstlight Toolbar Item menu item `icon` must be a non-empty string.');
            }
            foreach (['ios', 'android'] as $key) {
                if (array_key_exists($key, $item) && ! isset($item['icon'])) {
                    throw new InvalidArgumentException("Firstlight Toolbar Item menu item `{$key}` requires a shared `icon`.");
                }
            }
        }

        $this->menu = array_values($items);

        return $this;
    }

    protected function resolveProps(CallbackRegistry $registry): array
    {
        if (! isset($this->itemProps['icon'])) {
            throw new InvalidArgumentException('Firstlight Toolbar Item requires a non-empty `icon` shared fallback.');
        }

        $label = $this->extraProps['a11y_label'] ?? null;
        if (! is_string($label) || trim($label) === '') {
            throw new InvalidArgumentException('Firstlight Toolbar Item requires a non-empty `a11y-label`.');
        }

        $hasPress = $this->pressMethod !== null && trim($this->pressMethod) !== '';
        if ($this->menu !== [] && $hasPress) {
            throw new InvalidArgumentException('Firstlight Toolbar Item takes `@press` or `menu`, not both.');
        }
        if ($this->menu === [] && ! $hasPress) {
            throw new InvalidArgumentException('Firstlight Toolbar Item requires `@press` or a `menu`.');
        }

        $props = $this->itemProps;

        if (($props['badge'] ?? '') === '') {
            unset($props['badge']);
        }

        if ($this->menu !== []) {
            $resolved = [];
            foreach ($this->menu as $item) {
                $icon = IconResolver::resolve($item['icon'] ?? null, $item['ios'] ?? null, $item['android'] ?? null);
                $disabled = $item['disabled'] ?? false;
                $resolved[] = [
                    // Disabled items register no callback, so no renderer can dispatch them.
                    'cb' => $disabled ? 0 : $registry->register($item['method']),
                    'label' => $item['label'],
                    'icon' => $icon['icon'] ?? '',
                    'icon_variant' => $icon['variant'] ?? '',
                    'role' => $item['role'] ?? '',
                    'disabled' => $disabled,
                ];
            }
            $props['menu_json'] = json_encode($resolved, JSON_THROW_ON_ERROR);
        }

        return $props;
    }

    public function getLayout(): array
    {
        return [];
    }

    public function getStyle(): array
    {
        return [];
    }
}
