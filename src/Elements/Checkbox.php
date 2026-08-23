<?php

namespace FirstlightUI\Elements;

use FirstlightUI\Validation\FieldErrorBinder;
use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;
use Throwable;

final class Checkbox extends Element
{
    protected string $type = 'firstlight.checkbox';

    private const EXCLUDED_ATTRIBUTES = [
        'indeterminate', 'placement', 'variant', 'tone', 'color', 'icon',
        'icon-ios', 'iconIos', 'icon-android', 'iconAndroid', '_press',
        '_submit', '_longPress', '_doubleTap', '_pressDown', '_pressUp',
        '_navigate',
    ];

    /** @var array<string, bool|string> */
    private array $fieldProps = [
        'value' => false,
        'label' => '',
        'helper' => '',
        'error' => '',
        'required' => false,
        'disabled' => false,
    ];

    private ?string $changeCallback = null;

    public static function make(): static
    {
        return new static;
    }

    public function applyAttributes(array $attrs): void
    {
        foreach (self::EXCLUDED_ATTRIBUTES as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                throw new InvalidArgumentException(
                    "Checkbox does not support the `{$attribute}` attribute."
                );
            }
        }

        if (array_key_exists('value', $attrs)) {
            if (! is_bool($attrs['value'])) {
                $actual = get_debug_type($attrs['value']);
                $hint = $attrs['value'] === 'false'
                    ? ' Use :value="false" or native:model so Blade supplies a boolean.'
                    : '';

                throw new InvalidArgumentException(
                    "Checkbox `value` must be a boolean; {$actual} given.{$hint}"
                );
            }

            $this->value($attrs['value']);
        }

        foreach (['label', 'helper', 'error'] as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                $this->{$attribute}($this->strictString($attribute, $attrs[$attribute]));
            }
        }

        foreach (['required', 'disabled'] as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                if (! is_bool($attrs[$attribute])) {
                    throw new InvalidArgumentException(
                        "Checkbox `{$attribute}` must be a boolean."
                    );
                }

                $this->{$attribute}($attrs[$attribute]);
            }
        }

        if (array_key_exists('sync-mode', $attrs) || array_key_exists('syncMode', $attrs)) {
            $this->syncMode($this->strictString(
                'sync-mode',
                $attrs['sync-mode'] ?? $attrs['syncMode'],
            ));
        }

        foreach (['a11y-label', 'a11yLabel', 'a11y-hint', 'a11yHint'] as $attribute) {
            if (array_key_exists($attribute, $attrs) && ! is_string($attrs[$attribute])) {
                throw new InvalidArgumentException(
                    "Checkbox `{$attribute}` must be a string."
                );
            }
        }

        $this->applyA11yAttributes($attrs);
        FieldErrorBinder::apply($this, $attrs);
    }

    public function value(bool $value): static
    {
        $this->fieldProps['value'] = $value;

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
                'Checkbox supports only native:model or native:model.live.'
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
        $this->warnWhenUnlabelled();
        $props = $this->fieldProps;

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
            throw new InvalidArgumentException(
                "Checkbox `{$attribute}` must be a string."
            );
        }

        return $value;
    }

    private function warnWhenUnlabelled(): void
    {
        $label = trim((string) $this->fieldProps['label']);
        $a11yLabel = trim((string) ($this->extraProps['a11y_label'] ?? ''));

        if ($label !== '' || $a11yLabel !== '' || $this->applicationIsProduction()) {
            return;
        }

        trigger_error(
            'Firstlight Checkbox requires a visible label or a11y-label.',
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
