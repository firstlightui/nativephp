<?php

namespace FirstlightUI\Elements;

use FirstlightUI\Validation\FieldErrorBinder;
use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;
use Throwable;

final class CodeEditor extends Element
{
    protected string $type = 'firstlight.code-editor';

    private const LANGUAGES = ['plain', 'json', 'yaml', 'env'];

    private const EXCLUDED_ATTRIBUTES = [
        'placeholder', 'autocorrect', 'autocapitalize', 'keyboard', 'content-type',
        'contentType', 'secure', 'sensitive', 'highlight', 'submit-label', 'submitLabel',
        'clearable', 'revealable', 'leading-icon', 'leadingIcon', 'trailing-icon',
        'trailingIcon', 'prefix', 'suffix', 'loading', 'single-line', 'singleLine',
        'multiline', '_submit', '_press', '_longPress', '_doubleTap', '_pressDown',
        '_pressUp', '_navigate', 'color', 'background', 'bg', 'font', 'size',
        'variant', 'style', 'theme',
    ];

    /** @var array<string, bool|int|string> */
    private array $inputProps = [
        'value' => '',
        'label' => '',
        'helper' => '',
        'error' => '',
        'required' => false,
        'disabled' => false,
        'read_only' => false,
        'language' => 'plain',
        'line_numbers' => true,
        'wrap' => true,
        'indent' => 2,
        'follow' => false,
        'min_lines' => 6,
        'max_lines' => 20,
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
                throw new InvalidArgumentException("Code Editor does not support the `{$attribute}` attribute.");
            }
        }

        foreach (['value', 'label', 'helper', 'error', 'language'] as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                $this->{$attribute}($this->strictString($attribute, $attrs[$attribute]));
            }
        }

        foreach ([
            'required' => 'required',
            'disabled' => 'disabled',
            'read-only' => 'readOnly',
            'readOnly' => 'readOnly',
            'line-numbers' => 'lineNumbers',
            'lineNumbers' => 'lineNumbers',
            'wrap' => 'wrap',
            'follow' => 'follow',
        ] as $attribute => $method) {
            if (array_key_exists($attribute, $attrs)) {
                if (! is_bool($attrs[$attribute])) {
                    throw new InvalidArgumentException("Code Editor {$attribute} must be a boolean.");
                }
                $this->{$method}($attrs[$attribute]);
            }
        }

        if (array_key_exists('indent', $attrs)) {
            $indent = $attrs['indent'];
            if (! is_int($indent) && ! (is_string($indent) && ctype_digit($indent))) {
                throw new InvalidArgumentException('Code Editor indent must be 2 or 4.');
            }
            $this->indent((int) $indent);
        }

        foreach (['min-lines' => 'minLines', 'minLines' => 'minLines', 'max-lines' => 'maxLines', 'maxLines' => 'maxLines'] as $attribute => $method) {
            if (array_key_exists($attribute, $attrs)) {
                $this->{$method}($this->strictPositiveInteger($attribute, $attrs[$attribute]));
            }
        }

        if (array_key_exists('sync-mode', $attrs) || array_key_exists('syncMode', $attrs)) {
            $this->syncMode($this->strictString('sync-mode', $attrs['sync-mode'] ?? $attrs['syncMode']));
        }

        if (array_key_exists('debounce-ms', $attrs) || array_key_exists('debounceMs', $attrs)) {
            $this->debounceMs($this->strictPositiveInteger('debounce-ms', $attrs['debounce-ms'] ?? $attrs['debounceMs']));
        }

        foreach (['a11y-label', 'a11yLabel', 'a11y-hint', 'a11yHint'] as $attribute) {
            if (array_key_exists($attribute, $attrs) && ! is_string($attrs[$attribute])) {
                throw new InvalidArgumentException("Code Editor {$attribute} must be a string.");
            }
        }
        $this->applyA11yAttributes($attrs);
        FieldErrorBinder::apply($this, $attrs);
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

    public function language(string $value): static
    {
        if (! in_array($value, self::LANGUAGES, true)) {
            throw new InvalidArgumentException('Code Editor language must be one of ['.implode(', ', self::LANGUAGES).'].');
        }
        $this->inputProps['language'] = $value;
        return $this;
    }

    public function lineNumbers(bool $value = true): static
    {
        $this->inputProps['line_numbers'] = $value;
        return $this;
    }

    public function wrap(bool $value = true): static
    {
        $this->inputProps['wrap'] = $value;
        return $this;
    }

    public function indent(int $value): static
    {
        if (! in_array($value, [2, 4], true)) {
            throw new InvalidArgumentException('Code Editor indent must be 2 or 4.');
        }
        $this->inputProps['indent'] = $value;
        return $this;
    }

    public function follow(bool $value = true): static
    {
        $this->inputProps['follow'] = $value;
        return $this;
    }

    public function minLines(int $value): static
    {
        if ($value < 1) {
            throw new InvalidArgumentException('Code Editor min-lines must be a positive integer.');
        }
        $this->inputProps['min_lines'] = $value;
        return $this;
    }

    public function maxLines(int $value): static
    {
        if ($value < 1) {
            throw new InvalidArgumentException('Code Editor max-lines must be a positive integer.');
        }
        $this->inputProps['max_lines'] = $value;
        return $this;
    }

    public function syncMode(string $value): static
    {
        $normalized = $value === 'lazy' ? 'blur' : $value;
        if (! in_array($normalized, ['live', 'blur', 'debounce'], true)) {
            throw new InvalidArgumentException('Code Editor sync-mode must be one of [live, blur, debounce].');
        }
        $this->inputProps['sync_mode'] = $normalized;
        return $this;
    }

    public function debounceMs(int $value): static
    {
        if ($value < 50) {
            throw new InvalidArgumentException('Code Editor debounce-ms must be at least 50 milliseconds.');
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
            throw new InvalidArgumentException('Code Editor min-lines must be less than or equal to max-lines.');
        }

        if ($this->inputProps['follow'] && ! $this->inputProps['read_only']) {
            throw new InvalidArgumentException('Code Editor follow requires read-only; following only applies to programmatic replacements of read-only content.');
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
            throw new InvalidArgumentException("Code Editor {$attribute} must be a string.");
        }
        return $value;
    }

    private function strictPositiveInteger(string $attribute, mixed $value): int
    {
        if (! is_int($value) && ! (is_string($value) && ctype_digit($value))) {
            throw new InvalidArgumentException("Code Editor {$attribute} must be a positive integer.");
        }
        $value = (int) $value;
        if ($value < 1) {
            throw new InvalidArgumentException("Code Editor {$attribute} must be a positive integer.");
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
        trigger_error('Firstlight Code Editor requires a visible label or a11y-label.', E_USER_WARNING);
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
