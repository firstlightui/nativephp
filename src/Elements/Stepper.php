<?php

namespace FirstlightUI\Elements;

use FirstlightUI\Support\FiniteNumber;
use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;
use Throwable;

final class Stepper extends Element
{
    protected string $type = 'firstlight.stepper';

    private const EXCLUDED_ATTRIBUTES = [
        '_input', '_press', '_submit', '_click', '_longPress', '_doubleTap',
        '_pressDown', '_pressUp', '_navigate', 'icons', 'increment-icon',
        'incrementIcon', 'decrement-icon', 'decrementIcon', 'formatter',
        'value-label', 'valueLabel', 'show-min', 'showMin', 'show-max',
        'showMax', 'min-label', 'minLabel', 'max-label', 'maxLabel',
        'wraparound', 'wrap-around', 'wrapAround', 'orientation',
        'acceleration', 'long-press', 'longPress', 'required', 'placeholder',
        'color', 'background', 'style', 'variant', 'size', 'tone',
        'a11y-value', 'a11yValue',
    ];

    /** @var array<string, bool|string|float|int> */
    private array $inputProps = [
        'label' => '',
        'helper' => '',
        'error' => '',
        'disabled' => false,
    ];

    /** @var array{value?: int|float, min?: int|float, max?: int|float, step?: int|float} */
    private array $authoredNumbers = [];

    private ?string $changeCallback = null;

    public static function make(): static
    {
        return new static;
    }

    public function applyAttributes(array $attrs): void
    {
        foreach (self::EXCLUDED_ATTRIBUTES as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                throw new InvalidArgumentException("Stepper does not support the `{$attribute}` attribute.");
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
                throw new InvalidArgumentException('Stepper `disabled` must be a boolean.');
            }
            $this->disabled($attrs['disabled']);
        }

        if (array_key_exists('sync-mode', $attrs) || array_key_exists('syncMode', $attrs)) {
            $this->syncMode($this->strictString('sync-mode', $attrs['sync-mode'] ?? $attrs['syncMode']));
        }

        foreach (['a11y-label', 'a11yLabel', 'a11y-hint', 'a11yHint'] as $attribute) {
            if (array_key_exists($attribute, $attrs) && ! is_string($attrs[$attribute])) {
                throw new InvalidArgumentException("Stepper `{$attribute}` must be a string.");
            }
        }
        $this->applyA11yAttributes($attrs);
    }

    public function value(mixed $value): static
    {
        $this->authoredNumbers['value'] = $this->finiteNumber('value', $value);

        return $this;
    }

    public function min(mixed $value): static
    {
        $this->authoredNumbers['min'] = $this->finiteNumber('min', $value);

        return $this;
    }

    public function max(mixed $value): static
    {
        $this->authoredNumbers['max'] = $this->finiteNumber('max', $value);

        return $this;
    }

    public function step(mixed $value): static
    {
        $this->authoredNumbers['step'] = $this->finiteNumber('step', $value);

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
        if ($value !== 'live') {
            throw new InvalidArgumentException('Stepper supports only native:model or native:model.live synchronization.');
        }

        return $this;
    }

    public function onChange(string $method): static
    {
        $this->changeCallback = $method;

        return $this;
    }

    protected function resolveProps(CallbackRegistry $registry): array
    {
        foreach (['value', 'min', 'max'] as $attribute) {
            if (! array_key_exists($attribute, $this->authoredNumbers)) {
                throw new InvalidArgumentException("Stepper requires `{$attribute}`.");
            }
        }

        $integerMode = $this->usesIntegerSemantics();
        $value = $integerMode
            ? $this->authoredNumbers['value']
            : (float) $this->authoredNumbers['value'];
        $min = $integerMode
            ? $this->authoredNumbers['min']
            : (float) $this->authoredNumbers['min'];
        $max = $integerMode
            ? $this->authoredNumbers['max']
            : (float) $this->authoredNumbers['max'];
        $step = $integerMode
            ? ($this->authoredNumbers['step'] ?? 1)
            : (float) ($this->authoredNumbers['step'] ?? 1);

        if ($min >= $max) {
            throw new InvalidArgumentException('Stepper `min` must be less than `max`.');
        }
        if ($step <= 0) {
            throw new InvalidArgumentException('Stepper `step` must be greater than zero.');
        }
        if ($value < $min) {
            throw new InvalidArgumentException('Stepper `value` must not be below `min`.');
        }
        if ($value > $max) {
            throw new InvalidArgumentException('Stepper `value` must not be above `max`.');
        }

        $intervalCount = $integerMode
            ? $this->integerGridIndex($max, $min, $step, 'Stepper range')
            : FiniteNumber::gridIndex($max, $min, $step, 'Stepper range');
        $valueIndex = $integerMode
            ? $this->integerGridIndex($value, $min, $step, 'Stepper `value`')
            : FiniteNumber::gridIndex($value, $min, $step, 'Stepper `value`');
        $canDecrement = $valueIndex > 0;
        $canIncrement = $valueIndex < $intervalCount;
        $decrement = $integerMode
            ? ($canDecrement ? $value - $step : $value)
            : $min + (max(0, $valueIndex - 1) * $step);
        $increment = $integerMode
            ? ($canIncrement ? $value + $step : $value)
            : $min + (min($intervalCount, $valueIndex + 1) * $step);

        $props = [
            ...$this->inputProps,
            'value' => $value,
            'min' => $min,
            'max' => $max,
            'step' => $step,
            'display_value' => $this->displayNumber($value),
            'can_decrement' => $canDecrement,
            'can_increment' => $canIncrement,
            'decrement_value' => $decrement,
            'increment_value' => $increment,
            'number_kind' => $integerMode ? 'integer' : 'float',
        ];

        if ($this->changeCallback !== null) {
            $props['on_decrement'] = $registry->register(
                $this->callbackExpression($props['decrement_value'])
            );
            $props['on_increment'] = $registry->register(
                $this->callbackExpression($props['increment_value'])
            );
        }

        $this->warnWhenUnlabelled();

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

    private function finiteNumber(string $attribute, mixed $value): int|float
    {
        FiniteNumber::normalize($value, "Stepper `{$attribute}`");

        /** @var int|float $value */
        return $value;
    }

    private function usesIntegerSemantics(): bool
    {
        foreach (['value', 'min', 'max'] as $attribute) {
            if (! isset($this->authoredNumbers[$attribute]) || ! is_int($this->authoredNumbers[$attribute])) {
                return false;
            }
        }

        return ! isset($this->authoredNumbers['step']) || is_int($this->authoredNumbers['step']);
    }

    private function displayNumber(int|float $value): string
    {
        return (string) $value;
    }

    private function integerGridIndex(int $value, int $origin, int $step, string $context): int
    {
        if ($this->normalizedRemainder($value, $step) !== $this->normalizedRemainder($origin, $step)) {
            throw new InvalidArgumentException("{$context} must lie on the step grid.");
        }

        $valueQuotient = $this->floorDiv($value, $step);
        $originQuotient = $this->floorDiv($origin, $step);
        if (
            $originQuotient <= PHP_INT_MAX - FiniteNumber::MAX_NATIVE_INTERVALS
            && $valueQuotient > $originQuotient + FiniteNumber::MAX_NATIVE_INTERVALS
        ) {
            throw new InvalidArgumentException("{$context} exceeds the native interval limit.");
        }

        return $valueQuotient - $originQuotient;
    }

    private function normalizedRemainder(int $value, int $divisor): int
    {
        $remainder = $value % $divisor;

        return $remainder < 0 ? $remainder + $divisor : $remainder;
    }

    private function floorDiv(int $value, int $divisor): int
    {
        $quotient = intdiv($value, $divisor);

        return $value < 0 && $value % $divisor !== 0 ? $quotient - 1 : $quotient;
    }

    private function callbackExpression(int|float $value): string
    {
        $parsed = CallbackRegistry::parse((string) $this->changeCallback);
        $arguments = [...$parsed['args'], $value];
        $encoded = array_map(
            fn (mixed $argument): string => json_encode(
                $argument,
                JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION
            ),
            $arguments,
        );

        return $parsed['method'].'('.implode(',', $encoded).')';
    }

    private function strictString(string $attribute, mixed $value): string
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException("Stepper `{$attribute}` must be a string.");
        }

        return $value;
    }

    private function warnWhenUnlabelled(): void
    {
        $label = trim((string) $this->inputProps['label']);
        $a11yLabel = trim((string) ($this->extraProps['a11y_label'] ?? ''));
        if ($label !== '' || $a11yLabel !== '' || $this->applicationIsProduction()) {
            return;
        }

        trigger_error('Firstlight Stepper requires a visible `label` or `a11y-label`.', E_USER_WARNING);
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
