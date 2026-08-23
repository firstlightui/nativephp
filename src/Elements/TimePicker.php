<?php

namespace FirstlightUI\Elements;

use DateTimeZone;
use FirstlightUI\Validation\FieldErrorBinder;
use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;
use Throwable;

final class TimePicker extends Element
{
    protected string $type = 'firstlight.time-picker';

    private const EXCLUDED_ATTRIBUTES = [
        'hour-format', 'hourFormat', 'min', 'max', 'range', 'step', 'mode',
        'picker-style', 'pickerStyle', 'display-style', 'displayStyle',
        'clearable', 'read-only', 'readOnly', 'title', 'confirm-label',
        'confirmLabel', 'cancel-label', 'cancelLabel', 'icon', 'icon-ios',
        'iconIos', 'icon-android', 'iconAndroid', 'loading', 'tone', 'variant',
        'size', 'date', 'datetime', '_press', '_submit', '_clear', '_longPress',
        '_doubleTap', '_pressDown', '_pressUp', '_navigate',
    ];

    /** @var array<string, bool|string> */
    private array $inputProps = [
        'has_value' => false,
        'value' => '',
        'label' => '',
        'placeholder' => '',
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
                throw new InvalidArgumentException("Time Picker does not support the `{$attribute}` attribute.");
            }
        }

        if (array_key_exists('value', $attrs)) {
            $this->value($attrs['value']);
        }

        foreach (['label', 'placeholder', 'helper', 'error'] as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                $this->{$attribute}($this->strictString($attribute, $attrs[$attribute]));
            }
        }

        foreach (['required', 'disabled'] as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                if (! is_bool($attrs[$attribute])) {
                    throw new InvalidArgumentException("Time Picker `{$attribute}` must be a boolean.");
                }

                $this->{$attribute}($attrs[$attribute]);
            }
        }

        if (array_key_exists('locale', $attrs)) {
            $this->locale($attrs['locale']);
        }
        if (array_key_exists('timezone', $attrs)) {
            $this->timezone($attrs['timezone']);
        }

        if (array_key_exists('sync-mode', $attrs) || array_key_exists('syncMode', $attrs)) {
            $this->syncMode($this->strictString('sync-mode', $attrs['sync-mode'] ?? $attrs['syncMode']));
        }

        foreach (['a11y-label', 'a11yLabel', 'a11y-hint', 'a11yHint'] as $attribute) {
            if (array_key_exists($attribute, $attrs) && ! is_string($attrs[$attribute])) {
                throw new InvalidArgumentException("Time Picker {$attribute} must be a string.");
            }
        }
        $this->applyA11yAttributes($attrs);
        FieldErrorBinder::apply($this, $attrs);
    }

    public function value(mixed $value): static
    {
        if ($value === null) {
            $this->inputProps['has_value'] = false;
            $this->inputProps['value'] = '';

            return $this;
        }

        $this->inputProps['has_value'] = true;
        $this->inputProps['value'] = $this->canonicalTime($value);

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

    public function locale(mixed $value): static
    {
        if (! is_string($value) || ! $this->isValidLanguageTag($value)) {
            throw new InvalidArgumentException('Time Picker `locale` must be a valid non-empty BCP-47 language tag.');
        }

        $this->inputProps['locale'] = $value;

        return $this;
    }

    public function timezone(mixed $value): static
    {
        if (! is_string($value) || $value === '' || ! in_array($value, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true)) {
            throw new InvalidArgumentException('Time Picker `timezone` must be a valid IANA timezone identifier.');
        }

        $this->inputProps['timezone'] = $value;

        return $this;
    }

    public function syncMode(string $value): static
    {
        if ($value !== 'live') {
            throw new InvalidArgumentException('Time Picker supports only native:model or native:model.live synchronization.');
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
            throw new InvalidArgumentException("Time Picker `{$attribute}` must be a string.");
        }

        return $value;
    }

    private function canonicalTime(mixed $value): string
    {
        if (! is_string($value) || preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $value) !== 1) {
            throw new InvalidArgumentException('Time Picker `value` must be a canonical `HH:mm` string or null.');
        }

        return $value;
    }

    private function isValidLanguageTag(string $value): bool
    {
        if ($value === '' || strlen($value) > 255) {
            return false;
        }

        $grandfathered = [
            'en-GB-oed', 'i-ami', 'i-bnn', 'i-default', 'i-enochian', 'i-hak',
            'i-klingon', 'i-lux', 'i-mingo', 'i-navajo', 'i-pwn', 'i-tao',
            'i-tay', 'i-tsu', 'sgn-BE-FR', 'sgn-BE-NL', 'sgn-CH-DE',
            'art-lojban', 'cel-gaulish', 'no-bok', 'no-nyn', 'zh-guoyu',
            'zh-hakka', 'zh-min', 'zh-min-nan', 'zh-xiang',
        ];

        if (in_array($value, $grandfathered, true)) {
            return true;
        }

        $language = '(?:[A-Za-z]{2,3}(?:-[A-Za-z]{3}){0,3}|[A-Za-z]{4}|[A-Za-z]{5,8})';
        $script = '(?:-[A-Za-z]{4})?';
        $region = '(?:-(?:[A-Za-z]{2}|\d{3}))?';
        $variant = '(?:-(?:[A-Za-z0-9]{5,8}|\d[A-Za-z0-9]{3}))*';
        $extension = '(?:-[0-9A-WY-Za-wy-z](?:-[A-Za-z0-9]{2,8})+)';
        $privateUse = '(?:-x(?:-[A-Za-z0-9]{1,8})+)?';

        return preg_match("/^(?:{$language}{$script}{$region}{$variant}(?:{$extension})*{$privateUse}|x(?:-[A-Za-z0-9]{1,8})+)$/D", $value) === 1;
    }

    private function warnWhenUnlabelled(): void
    {
        $label = trim((string) $this->inputProps['label']);
        $a11yLabel = trim((string) ($this->extraProps['a11y_label'] ?? ''));

        if ($label !== '' || $a11yLabel !== '' || $this->applicationIsProduction()) {
            return;
        }

        trigger_error('Firstlight Time Picker requires a visible `label` or `a11y-label`.', E_USER_WARNING);
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
