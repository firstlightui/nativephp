<?php

namespace FirstlightUI\Elements;

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
        'leading-actions',
        'leadingActions',
        'trailing-actions',
        'trailingActions',
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
        'leading-icon-color',
        'leadingIconColor',
        'trailing-icon-color',
        'trailingIconColor',
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

    public static function make(string $headline = ''): static
    {
        $item = new static;

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
            if (! is_string($attrs['supporting'])) {
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
    }

    public function headline(string $value): static
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException('Firstlight List Item requires a non-empty `headline`.');
        }

        $this->listItemProps['headline'] = $value;

        return $this;
    }

    public function supporting(string $value): static
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException('Firstlight List Item requires a non-empty `supporting` when authored.');
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

    public function trailingText(string $value): static
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException(
                'Firstlight List Item requires a non-empty `trailing-text` when authored.'
            );
        }

        $this->listItemProps['trailing_type'] = 'text';
        $this->listItemProps['trailing_value'] = $value;

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

        return $this->listItemProps;
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
        $textAuthored = $this->hasAny($attrs, ['trailing-text', 'trailingText']);

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

        if ($textAuthored) {
            $text = $this->firstValue($attrs, ['trailing-text', 'trailingText']);
            if (! is_string($text)) {
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
