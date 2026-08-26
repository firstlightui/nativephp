<?php

namespace FirstlightUI\Elements;

use FirstlightUI\Support\CallbackExpression;
use FirstlightUI\Support\Chrome;
use FirstlightUI\Validation\FieldErrorBinder;
use FirstlightUI\Support\NormalizedOption;
use FirstlightUI\Support\NormalizedOptions;
use FirstlightUI\Support\OptionNormalizer;
use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;
use Throwable;

class Select extends Element
{
    protected string $type = 'firstlight.select';

    private const SEARCH_THRESHOLD = 13;

    /** @var list<string> */
    private const INCOMPATIBLE_ATTRIBUTES = [
        'mode',
        'style',
        'multiple',
        'clearable',
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

    protected string|int|null $rawValue = null;

    protected bool $searchable = false;

    /** @var array<string, string|bool> */
    protected array $fieldProps = [
        'label' => '',
        'placeholder' => '',
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

        if (array_key_exists('value', $attrs)) {
            $this->value($attrs['value']);
        }

        if (array_key_exists('searchable', $attrs)) {
            $this->searchable((bool) $attrs['searchable']);
        }

        foreach (['label', 'placeholder', 'helper', 'error'] as $prop) {
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
        if (! is_string($value) && ! is_int($value) && $value !== null) {
            throw new InvalidArgumentException(
                'Select value must be a string, integer, or null.'
            );
        }

        $this->rawValue = $value;

        return $this;
    }

    public function searchable(bool $value = true): static
    {
        $this->searchable = $value;

        return $this;
    }

    public function label(string $value): static
    {
        $this->fieldProps['label'] = $value;

        return $this;
    }

    public function placeholder(string $value): static
    {
        $this->fieldProps['placeholder'] = $value;

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
                "Select commits immediately, so the `{$mode}` sync mode has no effect. "
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
        $this->assertValueMatchesOptions($normalized);
        $this->warnWhenUnlabelled();

        $disabled = (bool) $this->fieldProps['disabled'] || $normalized->items === [];
        $props = array_merge($this->fieldProps, [
            'value_type' => $normalized->valueType,
            'selected_values' => $this->rawValue === null ? [] : [(string) $this->rawValue],
            'option_values' => $normalized->wireValues(),
            'option_labels' => $normalized->labels(),
            'option_enabled' => $normalized->enabledFlags(),
            'option_callbacks' => $this->optionCallbacks($registry, $normalized->items, $disabled),
            'search_enabled' => $this->searchable || count($normalized->items) >= self::SEARCH_THRESHOLD,
            'done_label' => Chrome::string('done'),
        ]);

        if ($normalized->items === []) {
            $props['disabled'] = true;
        }

        return $props;
    }

    /**
     * @param list<NormalizedOption> $options
     * @return list<string>
     */
    private function optionCallbacks(
        CallbackRegistry $registry,
        array $options,
        bool $disabled,
    ): array {
        return array_map(function (NormalizedOption $option) use ($registry, $disabled): string {
            if (
                $disabled
                || $option->disabled
                || $this->changeCallback === null
                || $option->value === $this->rawValue
            ) {
                return '0';
            }

            return (string) $registry->register(
                CallbackExpression::appendValue($this->changeCallback, $option->value)
            );
        }, $options);
    }

    private function assertValueMatchesOptions(NormalizedOptions $options): void
    {
        if ($this->rawValue === null) {
            return;
        }

        $matches = array_filter(
            $options->items,
            fn (NormalizedOption $option): bool => $option->value === $this->rawValue,
        );

        if ($matches === []) {
            throw new InvalidArgumentException(
                'Select value must match an option exactly by type and value.'
            );
        }
    }

    private function assertCompatibleAttributes(array $attrs): void
    {
        foreach (self::INCOMPATIBLE_ATTRIBUTES as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                throw new InvalidArgumentException(
                    "Firstlight Select `{$attribute}` is not supported."
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
            'Firstlight Select requires a visible label or a11y-label.',
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
