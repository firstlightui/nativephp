<?php

namespace FirstlightUI\Elements;

use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;

final class Badge extends Element
{
    protected string $type = 'firstlight.badge';

    private const TONES = ['neutral', 'info', 'success', 'warning', 'danger'];
    private const INCOMPATIBLE = [
        'value', 'disabled', 'loading', 'helper', 'error', 'required',
        'sync-mode', 'syncMode', '_change', '_press', 'icon', 'color',
        'variant', 'background', 'bg',
    ];

    private ?int $count = null;
    private ?string $label = null;
    private string $tone = 'neutral';

    public static function make(): static
    {
        return new static;
    }

    public function applyAttributes(array $attrs): void
    {
        foreach (self::INCOMPATIBLE as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                throw new InvalidArgumentException("Firstlight Badge is display-only; `{$attribute}` is not supported.");
            }
        }

        if (array_key_exists('count', $attrs)) {
            if (! is_int($attrs['count']) || $attrs['count'] < 0) {
                throw new InvalidArgumentException('Firstlight Badge `count` must be a non-negative integer.');
            }
            $this->count = $attrs['count'];
        }

        if (array_key_exists('label', $attrs)) {
            if (! is_string($attrs['label'])) {
                throw new InvalidArgumentException('Firstlight Badge `label` must be a string.');
            }
            if (trim($attrs['label']) === '') {
                throw new InvalidArgumentException('Firstlight Badge requires a non-empty `label`.');
            }
            $this->label = $attrs['label'];
        }

        if (array_key_exists('tone', $attrs)) {
            if (! is_string($attrs['tone'])) {
                throw new InvalidArgumentException('Firstlight Badge `tone` must be a string.');
            }
            $this->tone($attrs['tone']);
        }

        foreach (['a11y-label', 'a11y-hint'] as $attribute) {
            if (array_key_exists($attribute, $attrs) && ! is_string($attrs[$attribute])) {
                throw new InvalidArgumentException("Firstlight Badge `{$attribute}` must be a string.");
            }
        }

        $this->applyA11yAttributes($attrs);
    }

    public function tone(string $tone): static
    {
        if (! in_array($tone, self::TONES, true)) {
            throw new InvalidArgumentException("Unsupported Badge tone [{$tone}]. Expected one of: ".implode(', ', self::TONES).'.');
        }
        $this->tone = $tone;
        return $this;
    }

    protected function resolveProps(CallbackRegistry $registry): array
    {
        if (($this->count !== null) === ($this->label !== null)) {
            throw new InvalidArgumentException('Firstlight Badge requires exactly one of `count` or `label`.');
        }

        if ($this->count !== null) {
            $a11yLabel = $this->extraProps['a11y_label'] ?? null;
            if (! is_string($a11yLabel) || trim($a11yLabel) === '') {
                throw new InvalidArgumentException('A count-based Firstlight Badge requires a non-empty `a11y-label`.');
            }
        }

        $label = $this->label ?? match (true) {
            $this->count === 0 => '',
            $this->count > 99 => '99+',
            default => (string) $this->count,
        };

        return ['label' => $label, 'tone' => $this->tone];
    }

    public function getStyle(): array
    {
        return [];
    }
}
