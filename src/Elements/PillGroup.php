<?php

namespace FirstlightUI\Elements;

use FirstlightUI\Support\CallbackExpression;
use FirstlightUI\Validation\FieldErrorBinder;
use FirstlightUI\Support\NormalizedOption;
use FirstlightUI\Support\NormalizedOptions;
use FirstlightUI\Support\OptionNormalizer;
use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;
use Throwable;

class PillGroup extends Element
{
    protected string $type = 'firstlight.pill-group';

    /** @var list<string> */
    private const INCOMPATIBLE_ATTRIBUTES = [
        'selected',
        'loading',
        'tone',
        'variant',
        'icon',
        'icon-ios',
        'icon-android',
        '_press',
        '_submit',
    ];

    /** @var array<int|string, mixed> */
    protected array $rawOptions = [];

    protected mixed $rawValue = null;

    protected bool $multiple = false;

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
        $this->assertCompatibleAttributes($attrs);

        if (array_key_exists('options', $attrs)) {
            $this->options((array) $attrs['options']);
        }

        if (array_key_exists('multiple', $attrs)) {
            $this->multiple((bool) $attrs['multiple']);
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
        FieldErrorBinder::apply($this, $attrs);
    }

    /** @param array<int|string, mixed> $options */
    public function options(array $options): static
    {
        $this->rawOptions = $options;

        return $this;
    }

    public function value(mixed $value): static
    {
        if (! is_string($value) && ! is_int($value) && ! is_array($value) && $value !== null) {
            throw new InvalidArgumentException(
                'Pill Group value must be a string, integer, array, or null.'
            );
        }

        $this->rawValue = $value;

        return $this;
    }

    public function multiple(bool $value = true): static
    {
        $this->multiple = $value;

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
                "Pill Group commits immediately, so the `{$mode}` sync mode has no effect. "
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
        $selected = $this->normalizeSelectedValues($normalized);
        $this->warnWhenUnlabelled();

        $disabled = (bool) $this->fieldProps['disabled'] || $normalized->items === [];
        $valueType = $normalized->items === [] && $selected !== []
            ? (is_string($selected[0]) ? 'string' : 'integer')
            : $normalized->valueType;

        $props = array_merge($this->fieldProps, [
            'multiple' => $this->multiple,
            'value_type' => $valueType,
            'selected_values' => array_map('strval', $selected),
            'option_values' => $normalized->wireValues(),
            'option_labels' => $normalized->labels(),
            'option_enabled' => $normalized->enabledFlags(),
            'option_callbacks' => $this->optionCallbacks(
                $registry,
                $normalized->items,
                $selected,
                $disabled,
            ),
        ]);

        if ($normalized->items === []) {
            $props['disabled'] = true;
        }

        return $props;
    }

    /**
     * @param list<NormalizedOption> $options
     * @param list<string|int> $selected
     * @return list<string>
     */
    private function optionCallbacks(
        CallbackRegistry $registry,
        array $options,
        array $selected,
        bool $disabled,
    ): array {
        return array_map(function (NormalizedOption $option) use ($registry, $selected, $disabled): string {
            if ($disabled || $option->disabled || $this->changeCallback === null) {
                return '0';
            }

            $nextValue = $this->multiple
                ? $this->toggleMultipleValue($selected, $option->value)
                : ($this->contains($selected, $option->value) ? null : $option->value);

            return (string) $registry->register(
                CallbackExpression::appendValue($this->changeCallback, $nextValue)
            );
        }, $options);
    }

    /** @return list<string|int> */
    private function normalizeSelectedValues(NormalizedOptions $options): array
    {
        if (! $this->multiple) {
            if (is_array($this->rawValue)) {
                throw new InvalidArgumentException(
                    'Pill Group single-selection value must be a string, integer, or null.'
                );
            }

            if ($this->rawValue === null) {
                return [];
            }

            $selected = [$this->rawValue];
            $this->assertSelectedTypeMatchesOptions($selected, $options);

            return $selected;
        }

        if ($this->rawValue === null) {
            return [];
        }

        if (! is_array($this->rawValue)) {
            throw new InvalidArgumentException(
                'Pill Group multiple-selection value must be an array or null.'
            );
        }

        if (! array_is_list($this->rawValue)) {
            throw new InvalidArgumentException(
                'Pill Group multiple-selection value must be a list.'
            );
        }

        $selected = [];
        $seen = [];
        $valueType = null;

        foreach ($this->rawValue as $index => $value) {
            if (! is_string($value) && ! is_int($value)) {
                throw new InvalidArgumentException(
                    "Pill Group selected value at index [{$index}] must be a string or integer."
                );
            }

            $type = is_string($value) ? 'string' : 'integer';
            if ($valueType !== null && $valueType !== $type) {
                throw new InvalidArgumentException(
                    'Pill Group selected values cannot mix string and integer values.'
                );
            }

            $valueType = $type;
            $key = $type.':'.$value;
            if (isset($seen[$key])) {
                throw new InvalidArgumentException(
                    "Pill Group has a duplicate selected value [{$key}]."
                );
            }

            $seen[$key] = true;
            $selected[] = $value;
        }

        $this->assertSelectedTypeMatchesOptions($selected, $options);

        return $selected;
    }

    /**
     * @param list<string|int> $selected
     */
    private function assertSelectedTypeMatchesOptions(array $selected, NormalizedOptions $options): void
    {
        if ($selected === [] || $options->items === []) {
            return;
        }

        $selectedType = is_string($selected[0]) ? 'string' : 'integer';
        if ($selectedType !== $options->valueType) {
            throw new InvalidArgumentException(
                "Pill Group selected value type [{$selectedType}] must match option value type [{$options->valueType}]."
            );
        }
    }

    /**
     * @param list<string|int> $selected
     * @return list<string|int>
     */
    private function toggleMultipleValue(array $selected, string|int $value): array
    {
        if ($this->contains($selected, $value)) {
            return array_values(array_filter(
                $selected,
                fn (string|int $selectedValue): bool => $selectedValue !== $value,
            ));
        }

        return [...$selected, $value];
    }

    /** @param list<string|int> $values */
    private function contains(array $values, string|int $candidate): bool
    {
        return in_array($candidate, $values, true);
    }

    private function assertCompatibleAttributes(array $attrs): void
    {
        foreach (self::INCOMPATIBLE_ATTRIBUTES as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                throw new InvalidArgumentException(
                    "Firstlight Pill Group `{$attribute}` is not supported."
                );
            }
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
            'Firstlight Pill Group requires a visible label or a11y-label.',
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
