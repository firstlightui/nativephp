<?php

namespace FirstlightUI\Elements;

use FirstlightUI\Support\FiniteNumber;
use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;
use Throwable;

final class Slider extends Element
{
    protected string $type = 'firstlight.slider';

    private const EXCLUDED_ATTRIBUTES = [
        '_input', '_press', '_submit', '_click', '_longPress', '_doubleTap',
        '_pressDown', '_pressUp', '_navigate', 'orientation', 'range', 'marks',
        'ticks', 'show-value', 'showValue', 'value-label', 'valueLabel',
        'formatter', 'min-label', 'minLabel', 'max-label', 'maxLabel', 'required',
        'color', 'track-color', 'trackColor', 'thumb-color', 'thumbColor',
        'style', 'variant', 'size',
    ];

    /** @var array<string, bool|float|int|string> */
    private array $inputProps = [
        'step' => 1.0,
        'label' => '',
        'helper' => '',
        'error' => '',
        'disabled' => false,
        'sync_mode' => 'live',
        'debounce_ms' => 300,
    ];

    private bool $valueProvided = false;

    private bool $minProvided = false;

    private bool $maxProvided = false;

    private bool $debounceProvided = false;

    private ?string $changeCallback = null;

    public static function make(): static
    {
        return new static;
    }

    public function applyAttributes(array $attrs): void
    {
        foreach (self::EXCLUDED_ATTRIBUTES as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                throw new InvalidArgumentException("Slider does not support the `{$attribute}` attribute.");
            }
        }

        foreach (['value', 'min', 'max', 'step'] as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                $this->{$attribute}($attrs[$attribute]);
            }
        }

        foreach (['label', 'helper', 'error'] as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                $this->{$attribute}($this->strictString($attribute, $attrs[$attribute]));
            }
        }

        if (array_key_exists('disabled', $attrs)) {
            if (! is_bool($attrs['disabled'])) {
                throw new InvalidArgumentException('Slider `disabled` must be a boolean.');
            }
            $this->disabled($attrs['disabled']);
        }

        if (array_key_exists('sync-mode', $attrs) || array_key_exists('syncMode', $attrs)) {
            $this->syncMode($this->strictString('sync-mode', $attrs['sync-mode'] ?? $attrs['syncMode']));
        }

        if (array_key_exists('debounce-ms', $attrs) || array_key_exists('debounceMs', $attrs)) {
            $this->debounceProvided = true;
            $this->debounceMs($this->strictPositiveInteger('debounce-ms', $attrs['debounce-ms'] ?? $attrs['debounceMs']));
        }

        foreach (['a11y-label', 'a11yLabel', 'a11y-hint', 'a11yHint', 'a11y-value', 'a11yValue'] as $attribute) {
            if (array_key_exists($attribute, $attrs) && ! is_string($attrs[$attribute])) {
                throw new InvalidArgumentException("Slider `{$attribute}` must be a string.");
            }
        }

        $this->applyA11yAttributes($attrs);
        if (array_key_exists('a11y-value', $attrs) || array_key_exists('a11yValue', $attrs)) {
            $this->extraProps['a11y_value'] = $attrs['a11y-value'] ?? $attrs['a11yValue'];
        }
    }

    public function value(mixed $value): static
    {
        $this->inputProps['value'] = FiniteNumber::normalize($value, 'Slider `value`');
        $this->valueProvided = true;

        return $this;
    }

    public function min(mixed $value): static
    {
        $this->inputProps['min'] = FiniteNumber::normalize($value, 'Slider `min`');
        $this->minProvided = true;

        return $this;
    }

    public function max(mixed $value): static
    {
        $this->inputProps['max'] = FiniteNumber::normalize($value, 'Slider `max`');
        $this->maxProvided = true;

        return $this;
    }

    public function step(mixed $value): static
    {
        $this->inputProps['step'] = FiniteNumber::normalize($value, 'Slider `step`');

        return $this;
    }

    public function label(string $value): static
    {
        $this->inputProps['label'] = $value;

        return $this;
    }

    public function helper(string $value): static
    {
        $this->inputProps['helper'] = $value;

        return $this;
    }

    public function error(string $value): static
    {
        $this->inputProps['error'] = $value;

        return $this;
    }

    public function disabled(bool $value = true): static
    {
        $this->inputProps['disabled'] = $value;

        return $this;
    }

    public function syncMode(string $value): static
    {
        if (! in_array($value, ['live', 'blur', 'debounce'], true)) {
            throw new InvalidArgumentException('Slider sync-mode must be one of [live, blur, debounce].');
        }
        $this->inputProps['sync_mode'] = $value;

        return $this;
    }

    public function debounceMs(int $value): static
    {
        if ($value < 50) {
            throw new InvalidArgumentException('Slider debounce-ms must be at least 50 milliseconds.');
        }
        $this->inputProps['debounce_ms'] = $value;

        return $this;
    }

    public function onChange(string $method): static
    {
        $this->changeCallback = $method;

        return $this;
    }

    protected function resolveProps(CallbackRegistry $registry): array
    {
        foreach (['value' => $this->valueProvided, 'min' => $this->minProvided, 'max' => $this->maxProvided] as $attribute => $provided) {
            if (! $provided) {
                throw new InvalidArgumentException("Slider requires `{$attribute}`.");
            }
        }

        $value = $this->inputProps['value'];
        $min = $this->inputProps['min'];
        $max = $this->inputProps['max'];
        $step = $this->inputProps['step'];

        if ($min >= $max) {
            throw new InvalidArgumentException('Slider `min` must be less than `max`.');
        }
        if ($step <= 0) {
            throw new InvalidArgumentException('Slider `step` must be greater than zero.');
        }
        if ($value < $min) {
            throw new InvalidArgumentException('Slider `value` must not be below `min`.');
        }
        if ($value > $max) {
            throw new InvalidArgumentException('Slider `value` must not be above `max`.');
        }
        if ($this->debounceProvided && $this->inputProps['sync_mode'] !== 'debounce') {
            throw new InvalidArgumentException('Slider debounce-ms requires sync-mode debounce.');
        }

        $this->inputProps['interval_count'] = FiniteNumber::gridIndex($max, $min, $step, 'Slider range');
        FiniteNumber::gridIndex($value, $min, $step, 'Slider `value`');
        $this->warnWhenUnlabelled();

        $props = $this->inputProps;
        if ($this->changeCallback !== null) {
            $props['on_change'] = $registry->register($this->changeCallback);
        }

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

    private function strictString(string $attribute, mixed $value): string
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException("Slider `{$attribute}` must be a string.");
        }

        return $value;
    }

    private function strictPositiveInteger(string $attribute, mixed $value): int
    {
        if (! is_int($value) && ! (is_string($value) && ctype_digit($value))) {
            throw new InvalidArgumentException("Slider {$attribute} must be a positive integer.");
        }

        $number = (int) $value;
        if ($number < 1) {
            throw new InvalidArgumentException("Slider {$attribute} must be a positive integer.");
        }

        return $number;
    }

    private function warnWhenUnlabelled(): void
    {
        $label = trim((string) $this->inputProps['label']);
        $a11yLabel = trim((string) ($this->extraProps['a11y_label'] ?? ''));
        if ($label !== '' || $a11yLabel !== '' || $this->applicationIsProduction()) {
            return;
        }

        trigger_error('Firstlight Slider requires a visible `label` or `a11y-label`.', E_USER_WARNING);
    }

    private function applicationIsProduction(): bool
    {
        if (! function_exists('app')) {
            return false;
        }

        try {
            $application = app();

            return is_object($application)
                && method_exists($application, 'isProduction')
                && $application->isProduction();
        } catch (Throwable) {
            return false;
        }
    }
}
