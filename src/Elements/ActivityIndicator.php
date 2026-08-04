<?php

namespace FirstlightUI\Elements;

use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;

final class ActivityIndicator extends Element
{
    protected string $type = 'firstlight.activity-indicator';

    /** @var list<string> */
    private const SIZES = ['sm', 'md', 'lg'];

    /** @var list<string> */
    private const UNSUPPORTED_ATTRIBUTES = [
        'label',
        'a11y-hint',
        'a11yHint',
        'value',
        'loading',
        'active',
        'visible',
        'disabled',
        'color',
        'tone',
        'variant',
        'sync-mode',
        'syncMode',
        '_change',
        '_submit',
        '_press',
    ];

    /** @var array{size: string} */
    private array $indicatorProps = [
        'size' => 'md',
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
                    "Firstlight Activity Indicator does not support `{$attribute}`."
                );
            }
        }

        if (array_key_exists('size', $attrs)) {
            if (! is_string($attrs['size'])) {
                throw new InvalidArgumentException(
                    'Firstlight Activity Indicator `size` must be one of: sm, md, lg.'
                );
            }

            $this->size($attrs['size']);
        }

        $label = $attrs['a11y-label'] ?? $attrs['a11yLabel'] ?? null;

        if (! is_string($label)) {
            throw new InvalidArgumentException(
                'Firstlight Activity Indicator requires a non-empty `a11y-label` describing the work.'
            );
        }

        $this->a11yLabel($label);
    }

    public function size(string $size): static
    {
        if (! in_array($size, self::SIZES, true)) {
            throw new InvalidArgumentException(
                'Firstlight Activity Indicator `size` must be one of: sm, md, lg.'
            );
        }

        $this->indicatorProps['size'] = $size;

        return $this;
    }

    public function a11yLabel(string $value): static
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException(
                'Firstlight Activity Indicator requires a non-empty `a11y-label` describing the work.'
            );
        }

        return parent::a11yLabel($value);
    }

    protected function resolveProps(CallbackRegistry $registry): array
    {
        $label = $this->extraProps['a11y_label'] ?? null;

        if (! is_string($label) || trim($label) === '') {
            throw new InvalidArgumentException(
                'Firstlight Activity Indicator requires a non-empty `a11y-label` describing the work.'
            );
        }

        return $this->indicatorProps;
    }

    public function getStyle(): array
    {
        return [];
    }
}
