<?php

namespace FirstlightUI\Elements;

use FirstlightUI\Support\Chrome;
use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;

class SearchField extends Element
{
    protected string $type = 'firstlight.search-field';

    /** @var array<string, mixed> */
    protected array $inputProps = [
        'value' => '',
        'placeholder' => '',
        'disabled' => false,
        'sync_mode' => 'live',
        'debounce_ms' => 300,
    ];

    protected ?string $changeCallback = null;

    protected ?string $submitCallback = null;

    public static function make(): static
    {
        return new static;
    }

    public function applyAttributes(array $attrs): void
    {
        $this->rejectUnsupportedAttributes($attrs);

        foreach (['value', 'placeholder'] as $prop) {
            if (array_key_exists($prop, $attrs)) {
                $this->{$prop}($this->strictString($prop, $attrs[$prop]));
            }
        }

        if (array_key_exists('disabled', $attrs)) {
            $this->disabled((bool) $attrs['disabled']);
        }
        if (array_key_exists('autocapitalize', $attrs)) {
            $this->autocapitalize($this->strictString('autocapitalize', $attrs['autocapitalize']));
        }
        if (array_key_exists('autocorrect', $attrs)) {
            $this->autocorrect((bool) $attrs['autocorrect']);
        }
        if (array_key_exists('sync-mode', $attrs) || array_key_exists('syncMode', $attrs)) {
            $this->syncMode($this->strictString('sync-mode', $attrs['sync-mode'] ?? $attrs['syncMode']));
        }
        if (array_key_exists('debounce-ms', $attrs) || array_key_exists('debounceMs', $attrs)) {
            $value = $attrs['debounce-ms'] ?? $attrs['debounceMs'];
            if (! is_int($value) && ! (is_string($value) && ctype_digit($value))) {
                throw new InvalidArgumentException('Search Field debounce-ms must be an integer.');
            }
            $this->debounceMs((int) $value);
        }

        $this->applyA11yAttributes($attrs);
    }

    public function value(string $value): static
    {
        $this->inputProps['value'] = $value;

        return $this;
    }

    public function placeholder(string $value): static
    {
        $this->inputProps['placeholder'] = $value;

        return $this;
    }

    public function disabled(bool $value = true): static
    {
        $this->inputProps['disabled'] = $value;

        return $this;
    }

    public function autocapitalize(string $value): static
    {
        return $this->enumProp('autocapitalize', $value, ['none', 'sentences', 'words', 'characters']);
    }

    public function autocorrect(bool $value = true): static
    {
        $this->inputProps['autocorrect'] = $value;
        $this->inputProps['autocorrect_policy'] = $value ? 'enabled' : 'disabled';

        return $this;
    }

    public function syncMode(string $value): static
    {
        $normalized = $value === 'lazy' ? 'blur' : $value;

        return $this->enumProp('sync-mode', $normalized, ['live', 'blur', 'debounce'], 'sync_mode');
    }

    public function debounceMs(int $value): static
    {
        if ($value < 50) {
            throw new InvalidArgumentException('Search Field debounce-ms must be at least 50 milliseconds.');
        }

        $this->inputProps['debounce_ms'] = $value;

        return $this;
    }

    public function onChange(string $method): static
    {
        $this->changeCallback = $method;

        return $this;
    }

    public function onSubmit(string $method): static
    {
        $this->submitCallback = $method;

        return $this;
    }

    protected function resolveProps(CallbackRegistry $registry): array
    {
        $label = trim((string) ($this->extraProps['a11y_label'] ?? ''));
        if ($label === '') {
            throw new InvalidArgumentException('Search Field requires a non-empty a11y-label.');
        }

        $props = $this->inputProps;
        $props['clear_a11y_label'] = Chrome::string('clear_search');
        if ($this->changeCallback !== null) {
            $props['on_change'] = $registry->register($this->changeCallback);
        }
        if ($this->submitCallback !== null) {
            $props['on_submit'] = $registry->register($this->submitCallback);
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
            throw new InvalidArgumentException("Search Field {$attribute} must be a string.");
        }

        return $value;
    }

    /** @param list<string> $accepted */
    private function enumProp(string $name, string $value, array $accepted, ?string $wireName = null): static
    {
        if (! in_array($value, $accepted, true)) {
            throw new InvalidArgumentException(
                "Search Field {$name} must be one of [".implode(', ', $accepted).'].',
            );
        }

        $this->inputProps[$wireName ?? str_replace('-', '_', $name)] = $value;

        return $this;
    }

    /** @param array<string, mixed> $attrs */
    private function rejectUnsupportedAttributes(array $attrs): void
    {
        foreach (['label', 'helper', 'error', 'required', 'read-only', 'readOnly'] as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                $name = $attribute === 'readOnly' ? 'read-only' : $attribute;
                throw new InvalidArgumentException("Search Field does not support {$name}.");
            }
        }

        if (array_key_exists('clearable', $attrs)) {
            throw new InvalidArgumentException('Search Field owns its native clear action; clearable is not configurable.');
        }

        foreach (['secure', 'revealable', 'keyboard', 'content-type', 'contentType', 'submit-label', 'submitLabel', 'leading-icon', 'trailing-icon'] as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                throw new InvalidArgumentException("Search Field does not support {$attribute}.");
            }
        }
    }
}
