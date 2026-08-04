<?php

namespace FirstlightUI\Elements;

use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;
use Throwable;

class SwitchControl extends Element
{
    protected string $type = 'firstlight.switch';

    /** @var array<string, bool|string> */
    protected array $fieldProps = [
        'value' => false,
        'label' => '',
        'helper' => '',
        'error' => '',
        'disabled' => false,
    ];

    protected ?string $changeCallback = null;

    public static function make(): static
    {
        return new static;
    }

    public function applyAttributes(array $attrs): void
    {
        foreach (['required', 'placement'] as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                throw new InvalidArgumentException(
                    "Switch does not support the `{$attribute}` attribute."
                );
            }
        }

        if (array_key_exists('value', $attrs) && ! is_bool($attrs['value'])) {
            $actual = get_debug_type($attrs['value']);
            $hint = $attrs['value'] === 'false'
                ? ' Use :value="false" or native:model so Blade supplies a boolean.'
                : '';

            throw new InvalidArgumentException(
                "Switch value must be a boolean; {$actual} given.{$hint}"
            );
        }

        $this->value($attrs['value'] ?? false);

        foreach (['label', 'helper', 'error'] as $prop) {
            if (array_key_exists($prop, $attrs)) {
                $this->{$prop}((string) ($attrs[$prop] ?? ''));
            }
        }

        if (array_key_exists('disabled', $attrs)) {
            $this->disabled((bool) $attrs['disabled']);
        }

        if (isset($attrs['sync-mode']) || isset($attrs['syncMode'])) {
            $this->syncMode((string) ($attrs['sync-mode'] ?? $attrs['syncMode']));
        }

        $this->applyA11yAttributes($attrs);
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

    public function disabled(bool $value = true): static
    {
        $this->fieldProps['disabled'] = $value;

        return $this;
    }

    public function syncMode(string $mode): static
    {
        if ($mode !== 'live') {
            throw new InvalidArgumentException(
                'Switch supports only native:model or native:model.live.'
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

    private function warnWhenUnlabelled(): void
    {
        $label = trim((string) $this->fieldProps['label']);
        $a11yLabel = trim((string) ($this->extraProps['a11y_label'] ?? ''));

        if ($label !== '' || $a11yLabel !== '' || $this->applicationIsProduction()) {
            return;
        }

        trigger_error(
            'Firstlight Switch requires a visible label or a11y-label.',
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
