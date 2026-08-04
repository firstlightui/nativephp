<?php

namespace FirstlightUI\Elements;

use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;
use Throwable;

final class TextArea extends Element
{
    protected string $type = 'firstlight.text-area';

    private const EXCLUDED_ATTRIBUTES = [
        'secure', 'keyboard', 'content-type', 'contentType', 'submit-label',
        'submitLabel', 'clearable', 'revealable', 'leading-icon', 'leadingIcon',
        'trailing-icon', 'trailingIcon', 'prefix', 'suffix', 'loading',
        'single-line', 'singleLine', 'multiline', '_submit', '_press',
        '_longPress', '_doubleTap', '_pressDown', '_pressUp', '_navigate',
        'color', 'background', 'bg', 'font', 'size', 'variant', 'style',
    ];

    /** @var array<string, bool|int|string> */
    private array $inputProps = [
        'value' => '',
        'label' => '',
        'placeholder' => '',
        'helper' => '',
        'error' => '',
        'required' => false,
        'disabled' => false,
        'read_only' => false,
        'min_lines' => 3,
        'max_lines' => 8,
        'autocapitalize' => '',
        'autocorrect_policy' => 'default',
        'sync_mode' => 'live',
        'debounce_ms' => 300,
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
                throw new InvalidArgumentException("Text Area does not support the `{$attribute}` attribute.");
            }
        }

        foreach (['value', 'label', 'placeholder', 'helper', 'error'] as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                $this->{$attribute}($this->strictString($attribute, $attrs[$attribute]));
            }
        }

        foreach ([
            'required' => 'required',
            'disabled' => 'disabled',
            'read-only' => 'readOnly',
            'readOnly' => 'readOnly',
            'autocorrect' => 'autocorrect',
        ] as $attribute => $method) {
            if (array_key_exists($attribute, $attrs)) {
                if (! is_bool($attrs[$attribute])) {
                    throw new InvalidArgumentException("Text Area {$attribute} must be a boolean.");
                }
                $this->{$method}($attrs[$attribute]);
            }
        }

        foreach (['min-lines' => 'minLines', 'minLines' => 'minLines', 'max-lines' => 'maxLines', 'maxLines' => 'maxLines'] as $attribute => $method) {
            if (array_key_exists($attribute, $attrs)) {
                $this->{$method}($this->strictPositiveInteger($attribute, $attrs[$attribute]));
            }
        }

        if (array_key_exists('autocapitalize', $attrs)) {
            $this->autocapitalize($this->strictString('autocapitalize', $attrs['autocapitalize']));
        }

        if (array_key_exists('sync-mode', $attrs) || array_key_exists('syncMode', $attrs)) {
            $this->syncMode($this->strictString('sync-mode', $attrs['sync-mode'] ?? $attrs['syncMode']));
        }

        if (array_key_exists('debounce-ms', $attrs) || array_key_exists('debounceMs', $attrs)) {
            $this->debounceMs($this->strictPositiveInteger('debounce-ms', $attrs['debounce-ms'] ?? $attrs['debounceMs']));
        }

        foreach (['a11y-label', 'a11yLabel', 'a11y-hint', 'a11yHint'] as $attribute) {
            if (array_key_exists($attribute, $attrs) && ! is_string($attrs[$attribute])) {
                throw new InvalidArgumentException("Text Area {$attribute} must be a string.");
            }
        }
        $this->applyA11yAttributes($attrs);
    }

    public function value(string $value): static
    {
        $this->inputProps['value'] = $value;
        return $this;
    }

    public function label(string $value): static
    {
        $this->inputProps['label'] = $value;
        return $this;
    }

    public function placeholder(string $value): static
    {
        $this->inputProps['placeholder'] = $value;
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

    public function required(bool $value = true): static
    {
        $this->inputProps['required'] = $value;
        return $this;
    }

    public function disabled(bool $value = true): static
    {
        $this->inputProps['disabled'] = $value;
        return $this;
    }

    public function readOnly(bool $value = true): static
    {
        $this->inputProps['read_only'] = $value;
        return $this;
    }

    public function minLines(int $value): static
    {
        if ($value < 1) {
            throw new InvalidArgumentException('Text Area min-lines must be a positive integer.');
        }
        $this->inputProps['min_lines'] = $value;
        return $this;
    }

    public function maxLines(int $value): static
    {
        if ($value < 1) {
            throw new InvalidArgumentException('Text Area max-lines must be a positive integer.');
        }
        $this->inputProps['max_lines'] = $value;
        return $this;
    }

    public function autocapitalize(string $value): static
    {
        if (! in_array($value, ['none', 'sentences', 'words', 'characters'], true)) {
            throw new InvalidArgumentException('Text Area autocapitalize must be one of [none, sentences, words, characters].');
        }
        $this->inputProps['autocapitalize'] = $value;
        return $this;
    }

    public function autocorrect(bool $value = true): static
    {
        $this->inputProps['autocorrect_policy'] = $value ? 'enabled' : 'disabled';
        return $this;
    }

    public function syncMode(string $value): static
    {
        $normalized = $value === 'lazy' ? 'blur' : $value;
        if (! in_array($normalized, ['live', 'blur', 'debounce'], true)) {
            throw new InvalidArgumentException('Text Area sync-mode must be one of [live, blur, debounce].');
        }
        $this->inputProps['sync_mode'] = $normalized;
        return $this;
    }

    public function debounceMs(int $value): static
    {
        if ($value < 50) {
            throw new InvalidArgumentException('Text Area debounce-ms must be at least 50 milliseconds.');
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
        if ($this->inputProps['min_lines'] > $this->inputProps['max_lines']) {
            throw new InvalidArgumentException('Text Area min-lines must be less than or equal to max-lines.');
        }

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
            throw new InvalidArgumentException("Text Area {$attribute} must be a string.");
        }
        return $value;
    }

    private function strictPositiveInteger(string $attribute, mixed $value): int
    {
        if (! is_int($value) && ! (is_string($value) && ctype_digit($value))) {
            throw new InvalidArgumentException("Text Area {$attribute} must be a positive integer.");
        }
        $value = (int) $value;
        if ($value < 1) {
            throw new InvalidArgumentException("Text Area {$attribute} must be a positive integer.");
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
        trigger_error('Firstlight Text Area requires a visible label or a11y-label.', E_USER_WARNING);
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
