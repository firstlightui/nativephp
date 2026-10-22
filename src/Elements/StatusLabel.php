<?php

namespace FirstlightUI\Elements;

use FirstlightUI\Support\WarningColors;
use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;
use Native\Mobile\Icon\AndroidSymbol;
use Native\Mobile\Icon\IconResolver;
use Native\Mobile\Icon\IosSymbol;

class StatusLabel extends Element
{
    protected string $type = 'firstlight.status-label';

    /** @var list<string> */
    private const TONES = ['neutral', 'info', 'success', 'warning', 'danger'];

    /** @var list<string> */
    private const INCOMPATIBLE_ATTRIBUTES = [
        'value',
        'disabled',
        'loading',
        'error',
        'required',
        'helper',
        'sync-mode',
        'syncMode',
        '_change',
        '_press',
    ];

    /** @var array<string, string> */
    protected array $statusProps = [
        'label' => '',
        'tone' => 'neutral',
    ];

    public static function make(string $label = ''): static
    {
        return (new static)->label($label);
    }

    public function applyAttributes(array $attrs): void
    {
        $this->assertDisplayOnlyAttributes($attrs);

        if (array_key_exists('label', $attrs)) {
            $this->label((string) ($attrs['label'] ?? ''));
        }

        if (array_key_exists('tone', $attrs)) {
            $this->tone((string) ($attrs['tone'] ?? ''));
        }

        $this->applyA11yAttributes($attrs);

        $name = $attrs['icon'] ?? null;
        $ios = $attrs['icon-ios'] ?? $attrs['iconIos'] ?? null;
        $android = $attrs['icon-android'] ?? $attrs['iconAndroid'] ?? null;
        if ($name !== null && ! is_string($name)) {
            throw new InvalidArgumentException('Firstlight Status Label `icon` must be a string.');
        }
        if ($ios !== null && ! is_string($ios) && ! $ios instanceof IosSymbol) {
            throw new InvalidArgumentException('Firstlight Status Label `icon-ios` must be an IosSymbol or string.');
        }
        if ($android !== null && ! is_string($android) && ! $android instanceof AndroidSymbol) {
            throw new InvalidArgumentException('Firstlight Status Label `icon-android` must be an AndroidSymbol or string.');
        }
        if (array_intersect(array_keys($attrs), ['icon', 'icon-ios', 'iconIos', 'icon-android', 'iconAndroid'])) {
            $this->icon($name, $ios, $android);
        }
    }

    public function icon(
        ?string $name = null,
        IosSymbol|string|null $ios = null,
        AndroidSymbol|string|null $android = null,
    ): static {
        $resolved = IconResolver::resolve($name, $ios, $android);
        unset($this->statusProps['icon'], $this->statusProps['icon_variant']);
        if ($resolved['icon'] !== null) {
            $this->statusProps['icon'] = $resolved['icon'];
        }
        if ($resolved['variant'] !== null) {
            $this->statusProps['icon_variant'] = $resolved['variant'];
        }

        return $this;
    }

    public function label(string $label): static
    {
        $this->statusProps['label'] = $label;

        return $this;
    }

    public function tone(string $tone): static
    {
        if (! in_array($tone, self::TONES, true)) {
            throw new InvalidArgumentException(
                "Unsupported Status Label tone [{$tone}]. Expected one of: "
                .implode(', ', self::TONES).'.'
            );
        }

        $this->statusProps['tone'] = $tone;

        return $this;
    }

    protected function resolveProps(CallbackRegistry $registry): array
    {
        if (trim($this->statusProps['label']) === '') {
            throw new InvalidArgumentException(
                'Firstlight Status Label requires a non-empty `label`.'
            );
        }

        return array_merge($this->statusProps, $this->extraProps,
            $this->statusProps['tone'] === 'warning' ? WarningColors::props() : []);
    }

    private function assertDisplayOnlyAttributes(array $attrs): void
    {
        foreach (self::INCOMPATIBLE_ATTRIBUTES as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                throw new InvalidArgumentException(
                    "Firstlight Status Label is display-only; `{$attribute}` is not supported."
                );
            }
        }
    }
}
