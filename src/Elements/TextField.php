<?php

namespace FirstlightUI\Elements;

use FirstlightUI\Validation\FieldErrorBinder;
use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;
use Native\Mobile\Icon\AndroidSymbol;
use Native\Mobile\Icon\IconResolver;
use Native\Mobile\Icon\IosSymbol;
use Throwable;

class TextField extends Element
{
    protected string $type = 'firstlight.text-field';

    /** @var array<string, mixed> */
    protected array $inputProps = [
        'value' => '',
        'label' => '',
        'placeholder' => '',
        'helper' => '',
        'error' => '',
        'required' => false,
        'disabled' => false,
        'read_only' => false,
        'secure' => false,
        'sync_mode' => 'live',
        'debounce_ms' => 300,
        'clearable' => false,
        'revealable' => false,
    ];

    protected ?string $changeCallback = null;

    protected ?string $submitCallback = null;

    public static function make(): static
    {
        return new static;
    }

    public function applyAttributes(array $attrs): void
    {
        foreach (['value', 'label', 'placeholder', 'helper', 'error'] as $prop) {
            if (array_key_exists($prop, $attrs)) {
                $this->{$this->methodName($prop)}($this->strictString($prop, $attrs[$prop]));
            }
        }

        foreach ([
            'required' => 'required',
            'disabled' => 'disabled',
            'read-only' => 'readOnly',
            'readOnly' => 'readOnly',
            'secure' => 'secure',
            'autocorrect' => 'autocorrect',
            'clearable' => 'clearable',
            'revealable' => 'revealable',
        ] as $attribute => $method) {
            if (array_key_exists($attribute, $attrs)) {
                $this->{$method}((bool) $attrs[$attribute]);
            }
        }

        foreach ([
            'keyboard' => 'keyboard',
            'content-type' => 'contentType',
            'contentType' => 'contentType',
            'autocapitalize' => 'autocapitalize',
            'submit-label' => 'submitLabel',
            'submitLabel' => 'submitLabel',
        ] as $attribute => $method) {
            if (array_key_exists($attribute, $attrs)) {
                $this->{$method}($this->strictString($attribute, $attrs[$attribute]));
            }
        }

        $this->applyIconAttributes($attrs, 'leading');
        $this->applyIconAttributes($attrs, 'trailing');

        foreach (['trailing-a11y-label', 'trailingA11yLabel'] as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                $this->trailingA11yLabel($this->strictString($attribute, $attrs[$attribute]));
                break;
            }
        }

        if (array_key_exists('sync-mode', $attrs) || array_key_exists('syncMode', $attrs)) {
            $this->syncMode($this->strictString(
                'sync-mode',
                $attrs['sync-mode'] ?? $attrs['syncMode'],
            ));
        }

        if (array_key_exists('debounce-ms', $attrs) || array_key_exists('debounceMs', $attrs)) {
            $value = $attrs['debounce-ms'] ?? $attrs['debounceMs'];
            if (! is_int($value) && ! (is_string($value) && ctype_digit($value))) {
                throw new InvalidArgumentException('Text Field debounce-ms must be an integer.');
            }
            $this->debounceMs((int) $value);
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

    public function secure(bool $value = true): static
    {
        $this->inputProps['secure'] = $value;

        return $this;
    }

    public function keyboard(string $value): static
    {
        return $this->enumProp('keyboard', $value, ['text', 'email', 'phone', 'url', 'number', 'decimal']);
    }

    public function contentType(string $value): static
    {
        return $this->enumProp('content-type', $value, ['name', 'username', 'email', 'password', 'new-password', 'one-time-code'], 'content_type');
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

    public function submitLabel(string $value): static
    {
        return $this->enumProp('submit-label', $value, ['done', 'go', 'next', 'search', 'send'], 'submit_label');
    }

    public function leadingIcon(
        ?string $name = null,
        IosSymbol|string|null $ios = null,
        AndroidSymbol|string|null $android = null,
    ): static {
        return $this->resolveIcon('leading', $name, $ios, $android);
    }

    public function trailingIcon(
        ?string $name = null,
        IosSymbol|string|null $ios = null,
        AndroidSymbol|string|null $android = null,
    ): static {
        return $this->resolveIcon('trailing', $name, $ios, $android);
    }

    public function trailingA11yLabel(string $value): static
    {
        $this->inputProps['trailing_a11y_label'] = $value;

        return $this;
    }

    public function clearable(bool $value = true): static
    {
        $this->inputProps['clearable'] = $value;

        return $this;
    }

    public function revealable(bool $value = true): static
    {
        $this->inputProps['revealable'] = $value;

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
            throw new InvalidArgumentException('Text Field debounce-ms must be at least 50 milliseconds.');
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
        $this->validateContract();
        $this->warnWhenUnlabelled();
        $props = $this->inputProps;

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

    private function methodName(string $prop): string
    {
        return $prop;
    }

    private function strictString(string $attribute, mixed $value): string
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException("Text Field {$attribute} must be a string.");
        }

        return $value;
    }

    /** @param list<string> $accepted */
    private function enumProp(string $name, string $value, array $accepted, ?string $wireName = null): static
    {
        if (! in_array($value, $accepted, true)) {
            throw new InvalidArgumentException(
                "Text Field {$name} must be one of [".implode(', ', $accepted)."]."
            );
        }

        $this->inputProps[$wireName ?? str_replace('-', '_', $name)] = $value;

        return $this;
    }

    private function applyIconAttributes(array $attrs, string $slot): void
    {
        $camel = $slot.'Icon';
        $base = $attrs[$slot.'-icon'] ?? $attrs[$camel] ?? null;
        $ios = $attrs[$slot.'-icon-ios'] ?? $attrs[$camel.'Ios'] ?? null;
        $android = $attrs[$slot.'-icon-android'] ?? $attrs[$camel.'Android'] ?? null;

        if ($base === null && $ios === null && $android === null) {
            return;
        }
        if ($base !== null && ! is_string($base)) {
            throw new InvalidArgumentException("Text Field {$slot}-icon must be a string.");
        }
        if ($ios !== null && ! is_string($ios) && ! $ios instanceof IosSymbol) {
            throw new InvalidArgumentException("Text Field {$slot}-icon-ios must be an IosSymbol or string.");
        }
        if ($android !== null && ! is_string($android) && ! $android instanceof AndroidSymbol) {
            throw new InvalidArgumentException("Text Field {$slot}-icon-android must be an AndroidSymbol or string.");
        }

        $this->{$camel}($base, $ios, $android);
    }

    private function resolveIcon(
        string $slot,
        ?string $name,
        IosSymbol|string|null $ios,
        AndroidSymbol|string|null $android,
    ): static {
        $resolved = IconResolver::resolve($name, $ios, $android);
        if ($resolved['icon'] !== null) {
            $this->inputProps[$slot.'_icon'] = $resolved['icon'];
        }
        if ($resolved['variant'] !== null) {
            $this->inputProps[$slot.'_icon_variant'] = $resolved['variant'];
        }

        return $this;
    }

    private function validateContract(): void
    {
        $clearable = $this->inputProps['clearable'];
        $revealable = $this->inputProps['revealable'];
        $hasTrailingIcon = isset($this->inputProps['trailing_icon']);
        $hasTrailingLabel = trim((string) ($this->inputProps['trailing_a11y_label'] ?? '')) !== '';
        $hasPress = $this->pressMethod !== null;

        if ($revealable && ! $this->inputProps['secure']) {
            throw new InvalidArgumentException('Text Field revealable requires secure.');
        }
        if ($clearable && $revealable) {
            throw new InvalidArgumentException('Text Field clearable and revealable are mutually exclusive.');
        }
        if (($clearable || $revealable) && ($hasTrailingIcon || $hasTrailingLabel || $hasPress)) {
            throw new InvalidArgumentException('Text Field semantic affordances and authored actions cannot share the trailing slot.');
        }
        if ($hasPress && ! $hasTrailingIcon) {
            throw new InvalidArgumentException('Text Field @press requires trailing-icon.');
        }
        if ($hasPress && ! $hasTrailingLabel) {
            throw new InvalidArgumentException('Text Field @press requires trailing-a11y-label.');
        }
        if ($hasTrailingLabel && ! $hasPress) {
            throw new InvalidArgumentException('Text Field trailing-a11y-label requires @press.');
        }
    }

    private function warnWhenUnlabelled(): void
    {
        $label = trim((string) $this->inputProps['label']);
        $a11yLabel = trim((string) ($this->extraProps['a11y_label'] ?? ''));

        if ($label !== '' || $a11yLabel !== '' || $this->applicationIsProduction()) {
            return;
        }

        trigger_error('Firstlight Text Field requires a visible label or a11y-label.', E_USER_WARNING);
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
