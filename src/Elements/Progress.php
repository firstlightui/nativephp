<?php

namespace FirstlightUI\Elements;

use InvalidArgumentException;

class Progress extends \Native\Mobile\UI\Elements\ProgressBar
{
    protected string $type = 'firstlight.progress';

    /** @var list<string> */
    private const UNSUPPORTED_ATTRIBUTES = [
        'label',
        'a11y-hint',
        'a11yHint',
        'color',
        'track-color',
        'trackColor',
        'tone',
        'variant',
        'size',
        'disabled',
        'loading',
        'helper',
        'error',
        'required',
        'sync-mode',
        'syncMode',
        '_change',
        '_press',
        'options',
        'icon',
    ];

    public function applyAttributes(array $attrs): void
    {
        foreach (self::UNSUPPORTED_ATTRIBUTES as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                throw new InvalidArgumentException(
                    "Firstlight Progress does not support `{$attribute}`."
                );
            }
        }

        $label = $attrs['a11y-label'] ?? $attrs['a11yLabel'] ?? null;

        if (! is_string($label) || trim($label) === '') {
            throw new InvalidArgumentException(
                'Firstlight Progress requires a non-empty `a11y-label` describing the work.'
            );
        }

        $hasMode = array_key_exists('indeterminate', $attrs);
        $indeterminate = $attrs['indeterminate'] ?? null;

        if ($hasMode && ! is_bool($indeterminate)) {
            throw new InvalidArgumentException(
                'Firstlight Progress `indeterminate` must be a boolean.'
            );
        }

        $hasValue = array_key_exists('value', $attrs) && $attrs['value'] !== null;

        if ($hasValue) {
            $value = $attrs['value'];

            if ((! is_int($value) && ! is_float($value))
                || ! is_finite((float) $value)
                || $value < 0
                || $value > 1) {
                throw new InvalidArgumentException(
                    'Firstlight Progress `value` must be a finite number from 0.0 through 1.0.'
                );
            }

            if ($hasMode && $indeterminate) {
                throw new InvalidArgumentException(
                    'Firstlight Progress cannot combine a non-null `value` with `indeterminate="true"`.'
                );
            }

            $this->value((float) $value);
        } else {
            if ($hasMode && ! $indeterminate) {
                throw new InvalidArgumentException(
                    'Determinate Firstlight Progress requires a non-null `value`.'
                );
            }

            $this->indeterminate();
        }

        $this->a11yLabel($label);
    }
}
