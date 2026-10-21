<?php

namespace FirstlightUI\Elements;

use FirstlightUI\Support\WarningColors;
use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;
use Native\Mobile\Icon\AndroidSymbol;
use Native\Mobile\Icon\IconResolver;
use Native\Mobile\Icon\IosSymbol;

final class ListItem extends Element
{
    protected string $type = 'firstlight.list-item';

    /** @var list<string> */
    private const UNSUPPORTED_ATTRIBUTES = [
        'overline',
        'leading-image',
        'leadingImage',
        'leading-checkbox',
        'leadingCheckbox',
        'leading-radio',
        'leadingRadio',
        'leading-icon-bg-color',
        'leadingIconBgColor',
        'trailing-checkbox',
        'trailingCheckbox',
        'trailing-switch',
        'trailingSwitch',
        'trailing-icon-button',
        'trailingIconButton',
        'trailing-a11y-label',
        'trailingA11yLabel',
        'trailing-menu',
        'trailingMenu',
        'trailing-badges',
        'trailingBadges',
        'on-swipe-delete',
        'onSwipeDelete',
        'on-leading-change',
        'onLeadingChange',
        'on-trailing-change',
        'onTrailingChange',
        '_trailingPress',
        '_longPress',
        '_doubleTap',
        '_pressDown',
        '_pressUp',
        '_navigate',
        'value',
        'selected',
        'loading',
        'sync-mode',
        'syncMode',
        '_change',
        '_submit',
        'helper',
        'error',
        'required',
        'headline-color',
        'headlineColor',
        'supporting-color',
        'supportingColor',
        'container-color',
        'containerColor',
        'trailing-text-color',
        'trailingTextColor',
        'tonal-elevation',
        'tonalElevation',
        'shadow-elevation',
        'shadowElevation',
        'font',
        'line-height',
        'lineHeight',
        'line-height-px',
        'lineHeightPx',
        'color',
        'background',
        'bg',
    ];

    /** @var array<string, mixed> */
    private array $listItemProps = [
        'disabled' => false,
    ];

    /** @var array<string, list<array<string, mixed>>> */
    private array $swipeActions = [];

    public static function make(string $headline = ''): static
    {
        $item = new self;

        if ($headline !== '') {
            $item->headline($headline);
        }

        return $item;
    }

    public function applyAttributes(array $attrs): void
    {
        foreach (self::UNSUPPORTED_ATTRIBUTES as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                throw new InvalidArgumentException(
                    "Firstlight List Item does not support `{$attribute}`."
                );
            }
        }

        if (array_key_exists('headline', $attrs)) {
            if (! is_string($attrs['headline'])) {
                throw new InvalidArgumentException('Firstlight List Item `headline` must be a string.');
            }

            $this->headline($attrs['headline']);
        }

        if (array_key_exists('supporting', $attrs)) {
            if ($attrs['supporting'] !== null && ! is_string($attrs['supporting'])) {
                throw new InvalidArgumentException('Firstlight List Item `supporting` must be a string.');
            }

            $this->supporting($attrs['supporting']);
        }

        if (array_key_exists('disabled', $attrs)) {
            if (! is_bool($attrs['disabled'])) {
                throw new InvalidArgumentException('Firstlight List Item `disabled` must be a boolean.');
            }

            $this->disabled($attrs['disabled']);
        }

        $this->applyLeadingAttributes($attrs);
        $this->applyTrailingAttributes($attrs);
        $this->applyAccessibilityAttributes($attrs);

        foreach (['leading', 'trailing'] as $edge) {
            foreach (['tone', 'color'] as $kind) {
                $names = ["{$edge}-icon-{$kind}", $edge.'Icon'.ucfirst($kind)];
                if ($this->hasAny($attrs, $names)) {
                    $value = $this->firstValue($attrs, $names);
                    if (! is_string($value)) {
                        throw new InvalidArgumentException("Firstlight List Item `{$names[0]}` must be a string.");
                    }
                    $this->{$edge.'Icon'.ucfirst($kind)}($value);
                }
            }
        }

        foreach (['leading', 'trailing'] as $edge) {
            if ($this->hasAny($attrs, ["{$edge}-actions", "{$edge}Actions"])) {
                $actions = $this->firstValue($attrs, ["{$edge}-actions", "{$edge}Actions"]);
                if (! is_array($actions)) {
                    throw new InvalidArgumentException("Firstlight List Item `{$edge}-actions` must be an array.");
                }

                $this->{"{$edge}Actions"}($actions);
            }
        }
    }

    /** @param list<array<string, mixed>> $actions */
    public function leadingActions(array $actions): static
    {
        return $this->setSwipeActions('leading', $actions);
    }

    /** @param list<array<string, mixed>> $actions */
    public function trailingActions(array $actions): static
    {
        return $this->setSwipeActions('trailing', $actions);
    }

    /** @param array<array-key, mixed> $actions */
    private function setSwipeActions(string $edge, array $actions): static
    {
        foreach ($actions as $action) {
            if (! is_array($action)
                || array_diff(array_keys($action), ['method', 'label', 'icon', 'ios', 'android', 'role']) !== []) {
                throw new InvalidArgumentException("Firstlight List Item `{$edge}-actions` accepts action arrays with method, label, icon, ios, android, and role only.");
            }

            foreach (['method', 'label'] as $key) {
                if (! is_string($action[$key] ?? null) || trim($action[$key]) === '') {
                    throw new InvalidArgumentException("Firstlight List Item swipe actions require a non-empty `{$key}`.");
                }
            }

            if (! in_array($action['role'] ?? '', ['', 'destructive'], true)) {
                throw new InvalidArgumentException('Firstlight List Item swipe action `role` must be empty or destructive.');
            }

            if (array_key_exists('icon', $action)
                && (! is_string($action['icon']) || trim($action['icon']) === '')) {
                throw new InvalidArgumentException('Firstlight List Item swipe action `icon` must be a non-empty string.');
            }

            foreach (['ios' => IosSymbol::class, 'android' => AndroidSymbol::class] as $key => $symbol) {
                if (! array_key_exists($key, $action)) {
                    continue;
                }
                if (! isset($action['icon'])
                    || (! $action[$key] instanceof $symbol && (! is_string($action[$key]) || trim($action[$key]) === ''))) {
                    throw new InvalidArgumentException("Firstlight List Item swipe action `{$key}` requires a shared icon and a platform symbol or non-empty string.");
                }
            }
        }

        $this->swipeActions[$edge] = array_values($actions);

        return $this;
    }

    public function headline(string $value): static
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException('Firstlight List Item requires a non-empty `headline`.');
        }

        $this->listItemProps['headline'] = $value;

        return $this;
    }

    public function supporting(?string $value): static
    {
        if ($value === null || trim($value) === '') {
            unset($this->listItemProps['supporting']);

            return $this;
        }

        $this->listItemProps['supporting'] = $value;

        return $this;
    }

    public function leadingIcon(
        ?string $name = null,
        IosSymbol|string|null $ios = null,
        AndroidSymbol|string|null $android = null,
    ): static {
        if ($name === null || trim($name) === '') {
            throw new InvalidArgumentException(
                'Firstlight List Item requires a non-empty `leading-icon` shared fallback.'
            );
        }

        $resolved = IconResolver::resolve($name, $ios, $android);
        $this->listItemProps['leading_type'] = 'icon';
        $this->listItemProps['leading_value'] = $resolved['icon'];

        if ($resolved['variant'] !== null) {
            $this->listItemProps['leading_icon_variant'] = $resolved['variant'];
        } else {
            unset($this->listItemProps['leading_icon_variant']);
        }

        return $this;
    }

    public function leadingAvatar(string $value): static
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException(
                'Firstlight List Item requires a non-empty `leading-avatar` when authored.'
            );
        }

        $this->listItemProps['leading_type'] = 'avatar';
        $this->listItemProps['leading_value'] = $value;

        return $this;
    }

    public function leadingMonogram(string $value): static
    {
        if (preg_match('/^(?:\\X){1,2}$/u', trim($value)) !== 1) {
            throw new InvalidArgumentException(
                'Firstlight List Item `leading-monogram` must contain one or two characters.'
            );
        }

        $this->listItemProps['leading_type'] = 'monogram';
        $this->listItemProps['leading_value'] = trim($value);

        return $this;
    }

    public function trailingIcon(
        ?string $name = null,
        IosSymbol|string|null $ios = null,
        AndroidSymbol|string|null $android = null,
    ): static {
        if ($name === null || trim($name) === '') {
            throw new InvalidArgumentException(
                'Firstlight List Item requires a non-empty `trailing-icon` shared fallback.'
            );
        }

        $resolved = IconResolver::resolve($name, $ios, $android);
        $this->listItemProps['trailing_type'] = 'icon';
        $this->listItemProps['trailing_value'] = $resolved['icon'];

        if ($resolved['variant'] !== null) {
            $this->listItemProps['trailing_icon_variant'] = $resolved['variant'];
        } else {
            unset($this->listItemProps['trailing_icon_variant']);
        }

        return $this;
    }

    public function trailingText(?string $value): static
    {
        if ($value === null || trim($value) === '') {
            if (($this->listItemProps['trailing_type'] ?? null) === 'text') {
                unset($this->listItemProps['trailing_type'], $this->listItemProps['trailing_value']);
            }

            return $this;
        }

        $this->listItemProps['trailing_type'] = 'text';
        $this->listItemProps['trailing_value'] = $value;

        return $this;
    }

    public function leadingIconTone(string $tone): static
    {
        return $this->iconTone('leading', $tone);
    }

    public function trailingIconTone(string $tone): static
    {
        return $this->iconTone('trailing', $tone);
    }

    private function iconTone(string $edge, string $tone): static
    {
        if (! in_array($tone, ['neutral', 'info', 'success', 'warning', 'danger'], true)) {
            throw new InvalidArgumentException("Firstlight List Item `{$edge}-icon-tone` must be neutral, info, success, warning, or danger.");
        }

        $this->listItemProps["{$edge}_icon_tone"] = $tone;

        return $this;
    }

    public function leadingIconColor(string $color): static
    {
        return $this->iconColor('leading', $color);
    }

    public function trailingIconColor(string $color): static
    {
        return $this->iconColor('trailing', $color);
    }

    private function iconColor(string $edge, string $color): static
    {
        if (! preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/D', $color)) {
            throw new InvalidArgumentException("Firstlight List Item `{$edge}-icon-color` must be a hex colour (#RGB, #RRGGBB, or #AARRGGBB).");
        }

        $this->listItemProps["{$edge}_icon_color"] = $color;

        return $this;
    }

    public function disabled(bool $value = true): static
    {
        $this->listItemProps['disabled'] = $value;

        return $this;
    }

    public function a11yLabel(string $value): static
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException(
                'Firstlight List Item requires a non-empty `a11y-label` when authored.'
            );
        }

        return parent::a11yLabel($value);
    }

    public function a11yHint(string $value): static
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException(
                'Firstlight List Item requires a non-empty `a11y-hint` when authored.'
            );
        }

        return parent::a11yHint($value);
    }

    protected function resolveProps(CallbackRegistry $registry): array
    {
        $headline = $this->listItemProps['headline'] ?? null;
        if (! is_string($headline) || trim($headline) === '') {
            throw new InvalidArgumentException('Firstlight List Item requires a non-empty `headline`.');
        }

        if ($this->pressMethod === null || trim($this->pressMethod) === '') {
            throw new InvalidArgumentException('Firstlight List Item requires `@press`.');
        }

        $props = $this->listItemProps;
        if (in_array('warning', [$props['leading_icon_tone'] ?? null, $props['trailing_icon_tone'] ?? null], true)) {
            $props = array_merge($props, WarningColors::props());
        }
        foreach ($this->swipeActions as $edge => $actions) {
            if ($actions === []) {
                continue;
            }

            $resolved = [];
            foreach ($actions as $action) {
                $icon = IconResolver::resolve($action['icon'] ?? null, $action['ios'] ?? null, $action['android'] ?? null);
                $resolved[] = [
                    'cb' => $registry->register($action['method']),
                    'label' => $action['label'],
                    'icon' => $icon['icon'] ?? '',
                    'icon_variant' => $icon['variant'] ?? '',
                    'role' => $action['role'] ?? '',
                ];
            }

            // Firstlight owns the swipe layer. Upstream action props would add
            // a second drawer and enable iOS full-swipe/optimistic deletion.
            $props["firstlight_{$edge}_actions_json"] = json_encode($resolved, JSON_THROW_ON_ERROR);
        }

        return $props;
    }

    public function getStyle(): array
    {
        return [];
    }

    /** @param array<string, mixed> $attrs */
    private function applyLeadingAttributes(array $attrs): void
    {
        $iconAuthored = $this->hasAny($attrs, [
            'leading-icon', 'leadingIcon',
            'leading-icon-ios', 'leadingIconIos',
            'leading-icon-android', 'leadingIconAndroid',
        ]);
        $avatarAuthored = $this->hasAny($attrs, ['leading-avatar', 'leadingAvatar']);
        $monogramAuthored = $this->hasAny($attrs, ['leading-monogram', 'leadingMonogram']);

        if (count(array_filter([$iconAuthored, $avatarAuthored, $monogramAuthored])) > 1) {
            throw new InvalidArgumentException(
                'Firstlight List Item accepts exactly one leading icon, avatar, or monogram.'
            );
        }

        if ($iconAuthored) {
            $name = $this->firstValue($attrs, ['leading-icon', 'leadingIcon']);
            $ios = $this->firstValue($attrs, ['leading-icon-ios', 'leadingIconIos']);
            $android = $this->firstValue($attrs, ['leading-icon-android', 'leadingIconAndroid']);

            if ($name !== null && ! is_string($name)) {
                throw new InvalidArgumentException('Firstlight List Item `leading-icon` must be a string.');
            }
            if ($ios !== null && ! is_string($ios) && ! $ios instanceof IosSymbol) {
                throw new InvalidArgumentException(
                    'Firstlight List Item `leading-icon-ios` must be an IosSymbol or string.'
                );
            }
            if ($android !== null && ! is_string($android) && ! $android instanceof AndroidSymbol) {
                throw new InvalidArgumentException(
                    'Firstlight List Item `leading-icon-android` must be an AndroidSymbol or string.'
                );
            }

            $this->leadingIcon($name, $ios, $android);
        }

        if ($avatarAuthored) {
            $avatar = $this->firstValue($attrs, ['leading-avatar', 'leadingAvatar']);
            if (! is_string($avatar)) {
                throw new InvalidArgumentException('Firstlight List Item `leading-avatar` must be a string.');
            }

            $this->leadingAvatar($avatar);
        }

        if ($monogramAuthored) {
            $monogram = $this->firstValue($attrs, ['leading-monogram', 'leadingMonogram']);
            if (! is_string($monogram)) {
                throw new InvalidArgumentException('Firstlight List Item `leading-monogram` must be a string.');
            }

            $this->leadingMonogram($monogram);
        }
    }

    /** @param array<string, mixed> $attrs */
    private function applyTrailingAttributes(array $attrs): void
    {
        $iconAuthored = $this->hasAny($attrs, [
            'trailing-icon', 'trailingIcon',
            'trailing-icon-ios', 'trailingIconIos',
            'trailing-icon-android', 'trailingIconAndroid',
        ]);
        $text = $this->firstValue($attrs, ['trailing-text', 'trailingText']);
        $textAuthored = $text !== null && (! is_string($text) || trim($text) !== '');

        if ($iconAuthored && $textAuthored) {
            throw new InvalidArgumentException(
                'Firstlight List Item accepts exactly one trailing icon or text affordance.'
            );
        }

        if ($iconAuthored) {
            $name = $this->firstValue($attrs, ['trailing-icon', 'trailingIcon']);
            $ios = $this->firstValue($attrs, ['trailing-icon-ios', 'trailingIconIos']);
            $android = $this->firstValue($attrs, ['trailing-icon-android', 'trailingIconAndroid']);

            if ($name !== null && ! is_string($name)) {
                throw new InvalidArgumentException('Firstlight List Item `trailing-icon` must be a string.');
            }
            if ($ios !== null && ! is_string($ios) && ! $ios instanceof IosSymbol) {
                throw new InvalidArgumentException(
                    'Firstlight List Item `trailing-icon-ios` must be an IosSymbol or string.'
                );
            }
            if ($android !== null && ! is_string($android) && ! $android instanceof AndroidSymbol) {
                throw new InvalidArgumentException(
                    'Firstlight List Item `trailing-icon-android` must be an AndroidSymbol or string.'
                );
            }

            $this->trailingIcon($name, $ios, $android);
        }

        if ($this->hasAny($attrs, ['trailing-text', 'trailingText'])) {
            if ($text !== null && ! is_string($text)) {
                throw new InvalidArgumentException('Firstlight List Item `trailing-text` must be a string.');
            }

            $this->trailingText($text);
        }
    }

    /** @param array<string, mixed> $attrs */
    private function applyAccessibilityAttributes(array $attrs): void
    {
        if ($this->hasAny($attrs, ['a11y-label', 'a11yLabel'])) {
            $label = $this->firstValue($attrs, ['a11y-label', 'a11yLabel']);
            if (! is_string($label)) {
                throw new InvalidArgumentException('Firstlight List Item `a11y-label` must be a string.');
            }

            $this->a11yLabel($label);
        }

        if ($this->hasAny($attrs, ['a11y-hint', 'a11yHint'])) {
            $hint = $this->firstValue($attrs, ['a11y-hint', 'a11yHint']);
            if (! is_string($hint)) {
                throw new InvalidArgumentException('Firstlight List Item `a11y-hint` must be a string.');
            }

            $this->a11yHint($hint);
        }
    }

    /** @param array<string, mixed> $attrs */
    private function hasAny(array $attrs, array $names): bool
    {
        foreach ($names as $name) {
            if (array_key_exists($name, $attrs)) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, mixed> $attrs */
    private function firstValue(array $attrs, array $names): mixed
    {
        foreach ($names as $name) {
            if (array_key_exists($name, $attrs)) {
                return $attrs[$name];
            }
        }

        return null;
    }
}
