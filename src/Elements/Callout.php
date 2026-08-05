<?php

namespace FirstlightUI\Elements;

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
        return (new static)->message($message);
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

        return array_merge($this->calloutProps, $this->extraProps);
    }
}
