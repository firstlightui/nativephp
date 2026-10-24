<?php

namespace FirstlightUI\Elements;

use FirstlightUI\Support\WarningColors;
use FirstlightUI\Validation\ErrorSummary;
use FirstlightUI\Validation\FieldErrorBag;
use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;

final class Callout extends Element
{
    protected string $type = 'firstlight.callout';

    /** @var list<string> */
    private const TONES = ['neutral', 'info', 'success', 'warning', 'danger'];

    /** @var list<string> */
    private const UNSUPPORTED_ATTRIBUTES = [
        'value', 'native:model', 'disabled', 'loading', 'dismissible', 'dismissable',
        'title', 'label', 'variant', 'size', 'icon', 'icon-ios', 'iconIos',
        'icon-android', 'iconAndroid', 'helper', 'error', 'required',
        'sync-mode', 'syncMode', '_change', '_submit', '_longPress', '_doubleTap',
        '_pressDown', '_pressUp', '_navigate',
    ];

    /** @var array{message: string, tone: string, action_label: string} */
    private array $calloutProps = [
        'message' => '',
        'tone' => 'info',
        'action_label' => '',
    ];

    public static function make(string $message = ''): static
    {
        return (new self)->message($message);
    }

    /**
     * The MessageBag text an error-bound Callout shows, or null when the
     * attributes do not bind one. An empty string means there is nothing
     * to show, so the Blade tag publishes no node.
     */
    public static function boundErrorMessage(array $attrs): ?string
    {
        $key = $attrs['error-for'] ?? $attrs['errorFor'] ?? null;
        $summary = $attrs['error-summary'] ?? $attrs['errorSummary'] ?? null;

        if ($key === null && ($summary === null || $summary === false)) {
            return null;
        }

        if ($key !== null && $summary !== null && $summary !== false) {
            throw new InvalidArgumentException(
                'Firstlight Callout `error-for` and `error-summary` are mutually exclusive.'
            );
        }

        if ($key !== null && (! is_string($key) || trim($key) === '')) {
            throw new InvalidArgumentException(
                'Firstlight Callout `error-for` must be a non-empty MessageBag key.'
            );
        }

        if (array_key_exists('message', $attrs)) {
            throw new InvalidArgumentException(
                'Firstlight Callout takes its message from the error bag when `error-for` or `error-summary` is set; remove `message`.'
            );
        }

        $bag = FieldErrorBag::current();

        if ($bag === null) {
            return '';
        }

        return $key !== null ? $bag->first($key) : ErrorSummary::text($bag);
    }

    public function applyAttributes(array $attrs): void
    {
        foreach (self::UNSUPPORTED_ATTRIBUTES as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                throw new InvalidArgumentException(
                    "Firstlight Callout does not support `{$attribute}`."
                );
            }
        }

        $boundMessage = self::boundErrorMessage($attrs);

        if ($boundMessage !== null) {
            $this->message($boundMessage)->tone('danger');
        }

        foreach ([
            'message' => 'message',
            'action-label' => 'actionLabel',
            'actionLabel' => 'actionLabel',
        ] as $attribute => $method) {
            if (! array_key_exists($attribute, $attrs)) {
                continue;
            }

            if (! is_string($attrs[$attribute])) {
                $publicName = $method === 'actionLabel' ? 'action-label' : $method;

                throw new InvalidArgumentException(
                    "Firstlight Callout `{$publicName}` must be a string."
                );
            }

            $this->{$method}($attrs[$attribute]);
        }

        if (array_key_exists('tone', $attrs)) {
            if (! is_string($attrs['tone'])) {
                throw new InvalidArgumentException(
                    'Firstlight Callout `tone` must be one of: '.implode(', ', self::TONES).'.'
                );
            }

            $this->tone($attrs['tone']);
        }

        $this->applyA11yAttributes($attrs);

        if ($boundMessage !== null && $boundMessage !== '') {
            $key = $attrs['error-for'] ?? $attrs['errorFor'] ?? null;
            FieldErrorBag::alert()?->claim($this, field: false, key: $key);
        }
    }

    public function message(string $message): static
    {
        $this->calloutProps['message'] = $message;

        return $this;
    }

    public function tone(string $tone): static
    {
        if (! in_array($tone, self::TONES, true)) {
            throw new InvalidArgumentException(
                "Unsupported Callout tone [{$tone}]. Expected one of: "
                .implode(', ', self::TONES).'.'
            );
        }

        $this->calloutProps['tone'] = $tone;

        return $this;
    }

    public function actionLabel(string $label): static
    {
        $this->calloutProps['action_label'] = $label;

        return $this;
    }

    protected function resolveProps(CallbackRegistry $registry): array
    {
        if (trim($this->calloutProps['message']) === '') {
            throw new InvalidArgumentException(
                'Firstlight Callout requires a non-empty `message`.'
            );
        }

        $hasActionLabel = trim($this->calloutProps['action_label']) !== '';
        $hasPress = $this->pressMethod !== null && trim($this->pressMethod) !== '';

        if ($hasActionLabel !== $hasPress) {
            throw new InvalidArgumentException(
                'Firstlight Callout requires `action-label` and `@press` together.'
            );
        }

        return array_merge($this->calloutProps, $this->extraProps,
            $this->calloutProps['tone'] === 'warning' ? WarningColors::props() : []);
    }
}
