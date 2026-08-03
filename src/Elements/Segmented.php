<?php

namespace Clinically\Firstlight\Elements;

use Clinically\Firstlight\Support\CallbackExpression;
use Clinically\Firstlight\Support\NormalizedOption;
use Clinically\Firstlight\Support\OptionNormalizer;
use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;
use Throwable;

class Segmented extends Element
{
    protected string $type = 'firstlight.segmented';

    /** @var array<int|string, mixed> */
    protected array $rawOptions = [];

    protected string|int|null $rawValue = null;

    /** @var array<string, string|bool> */
    protected array $fieldProps = [
        'label' => '',
        'helper' => '',
        'error' => '',
        'required' => false,
        'disabled' => false,
    ];

    protected ?string $changeCallback = null;

    public static function make(): static
    {
        return new static;
    }

    public function applyAttributes(array $attrs): void
    {
        if (array_key_exists('options', $attrs)) {
            $this->options((array) $attrs['options']);
        }

        if (array_key_exists('value', $attrs)) {
            $this->value($attrs['value']);
        }

        foreach (['label', 'helper', 'error'] as $prop) {
            if (array_key_exists($prop, $attrs)) {
                $this->{$prop}((string) ($attrs[$prop] ?? ''));
            }
        }

        if (array_key_exists('required', $attrs)) {
            $this->required((bool) $attrs['required']);
        }

        if (array_key_exists('disabled', $attrs)) {
            $this->disabled((bool) $attrs['disabled']);
        }

        if (isset($attrs['sync-mode']) || isset($attrs['syncMode'])) {
            $this->syncMode((string) ($attrs['sync-mode'] ?? $attrs['syncMode']));
        }

        $this->applyA11yAttributes($attrs);
    }

    /** @param array<int|string, mixed> $options */
    public function options(array $options): static
    {
        $this->rawOptions = $options;

        return $this;
    }

    public function value(mixed $value): static
    {
        if (! is_string($value) && ! is_int($value) && $value !== null) {
            throw new InvalidArgumentException(
                'Segmented selected value must be a string, integer, or null.'
            );
        }

        $this->rawValue = $value;

        return $this;
    }

    public function label(string $value): static
    {
        $this->fieldProps['label'] = $value;

        return $this;
    }

    public function helper(string $value): static
    {
        $this->fieldProps['helper'] = $value;

        return $this;
    }

    public function error(string $value): static
    {
        $this->fieldProps['error'] = $value;

        return $this;
    }

    public function required(bool $value = true): static
    {
        $this->fieldProps['required'] = $value;

        return $this;
    }

    public function disabled(bool $value = true): static
    {
        $this->fieldProps['disabled'] = $value;

        return $this;
    }

    public function syncMode(string $mode): static
    {
        if ($mode !== 'live') {
            throw new InvalidArgumentException(
                "Segmented commits immediately, so the `{$mode}` sync mode has no effect. "
                .'Use plain `native:model` (or `native:model.live`) and defer in your component if needed.'
            );
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
        $normalized = OptionNormalizer::normalize($this->rawOptions);
        $this->assertSelectedValueType($normalized->valueType);
        $this->warnWhenUnlabelled();

        $props = array_merge($this->fieldProps, [
            'value_type' => $normalized->valueType,
            'selected_value' => $this->rawValue === null ? '' : (string) $this->rawValue,
            'option_values' => $normalized->wireValues(),
            'option_labels' => $normalized->labels(),
            'option_enabled' => $normalized->enabledFlags(),
        ]);

        if ($normalized->items === []) {
            $props['disabled'] = true;
        }

        if ($normalized->valueType === 'string' && $this->changeCallback !== null) {
            $props['on_change'] = $registry->register($this->changeCallback);
        }

        if ($normalized->valueType === 'integer' && $this->changeCallback !== null) {
            $props['option_callbacks'] = array_map(
                fn (NormalizedOption $option): string => (string) $registry->register(
                    CallbackExpression::appendInteger($this->changeCallback, $option->value)
                ),
                $normalized->items,
            );
        }

        return $props;
    }

    private function assertSelectedValueType(string $valueType): void
    {
        if ($this->rawValue === null) {
            return;
        }

        $selectedType = is_string($this->rawValue) ? 'string' : 'integer';

        if ($selectedType !== $valueType) {
            throw new InvalidArgumentException(
                "Segmented selected value type [{$selectedType}] must match option value type [{$valueType}]."
            );
        }
    }

    private function warnWhenUnlabelled(): void
    {
        $label = trim((string) $this->fieldProps['label']);
        $a11yLabel = trim((string) ($this->extraProps['a11y_label'] ?? ''));

        if ($label !== '' || $a11yLabel !== '' || $this->applicationIsProduction()) {
            return;
        }

        trigger_error(
            'Firstlight Segmented requires a visible label or a11y-label.',
            E_USER_WARNING,
        );
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
