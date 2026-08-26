<?php

namespace FirstlightUI\Elements;

use FirstlightUI\Support\Chrome;
use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;

final class ConfirmationDialog extends Element
{
    protected string $type = 'firstlight.confirmation-dialog';

    /** @var list<string> */
    private const TONES = ['default', 'destructive'];

    /** @var list<string> */
    private const UNSUPPORTED_ATTRIBUTES = [
        'value', 'native:model', 'disabled', 'loading', 'dismissible', 'dismissable',
        'a11y-label', 'a11yLabel', 'a11y-hint', 'a11yHint', 'variant', 'size', 'icon',
        'sync-mode', 'syncMode', '_change', '_submit', '_longPress', '_doubleTap',
        '_pressDown', '_pressUp', '_navigate',
    ];

    /** @var array{visible: bool, title: string, message: string, confirm_label: string, cancel_label: string, tone: string} */
    private array $dialogProps = [
        'visible' => false,
        'title' => '',
        'message' => '',
        'confirm_label' => 'Confirm',
        'cancel_label' => 'Cancel',
        'tone' => 'default',
    ];

    private ?string $dismissMethod = null;

    private bool $confirmLabelAuthored = false;

    private bool $cancelLabelAuthored = false;

    public static function make(string $title = '', string $message = ''): static
    {
        return (new static)->title($title)->message($message);
    }

    public function applyAttributes(array $attrs): void
    {
        foreach (self::UNSUPPORTED_ATTRIBUTES as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                throw new InvalidArgumentException(
                    "Firstlight Confirmation Dialog does not support `{$attribute}`."
                );
            }
        }

        if (array_key_exists('visible', $attrs)) {
            if (! is_bool($attrs['visible'])) {
                throw new InvalidArgumentException(
                    'Firstlight Confirmation Dialog `visible` must be a boolean.'
                );
            }

            $this->visible($attrs['visible']);
        }

        foreach ([
            'title' => 'title',
            'message' => 'message',
            'confirm-label' => 'confirmLabel',
            'confirmLabel' => 'confirmLabel',
            'cancel-label' => 'cancelLabel',
            'cancelLabel' => 'cancelLabel',
        ] as $attribute => $method) {
            if (! array_key_exists($attribute, $attrs)) {
                continue;
            }

            if (! is_string($attrs[$attribute])) {
                $publicName = str_replace('Label', '-label', $method);

                throw new InvalidArgumentException(
                    "Firstlight Confirmation Dialog `{$publicName}` must be a string."
                );
            }

            $this->{$method}($attrs[$attribute]);
        }

        if (array_key_exists('tone', $attrs)) {
            if (! is_string($attrs['tone'])) {
                throw new InvalidArgumentException(
                    'Firstlight Confirmation Dialog `tone` must be one of: default, destructive.'
                );
            }

            $this->tone($attrs['tone']);
        }
    }

    public function visible(bool $visible = true): static
    {
        $this->dialogProps['visible'] = $visible;

        return $this;
    }

    public function title(string $title): static
    {
        $this->dialogProps['title'] = $title;

        return $this;
    }

    public function message(string $message): static
    {
        $this->dialogProps['message'] = $message;

        return $this;
    }

    public function confirmLabel(string $label): static
    {
        $this->dialogProps['confirm_label'] = $label;
        $this->confirmLabelAuthored = true;

        return $this;
    }

    public function cancelLabel(string $label): static
    {
        $this->dialogProps['cancel_label'] = $label;
        $this->cancelLabelAuthored = true;

        return $this;
    }

    public function tone(string $tone): static
    {
        if (! in_array($tone, self::TONES, true)) {
            throw new InvalidArgumentException(
                "Unsupported Confirmation Dialog tone [{$tone}]. Expected one of: "
                .implode(', ', self::TONES).'.'
            );
        }

        $this->dialogProps['tone'] = $tone;

        return $this;
    }

    public function onDismiss(string $method): static
    {
        $this->dismissMethod = $method;

        return $this;
    }

    protected function resolveProps(CallbackRegistry $registry): array
    {
        $props = $this->dialogProps;
        if (! $this->confirmLabelAuthored) {
            $props['confirm_label'] = Chrome::string('confirm');
        }
        if (! $this->cancelLabelAuthored) {
            $props['cancel_label'] = Chrome::string('cancel');
        }

        foreach (['title', 'message', 'confirm_label', 'cancel_label'] as $prop) {
            if (trim($props[$prop]) === '') {
                $attribute = str_replace('_', '-', $prop);

                throw new InvalidArgumentException(
                    "Firstlight Confirmation Dialog requires a non-empty `{$attribute}`."
                );
            }
        }

        if ($this->pressMethod === null || trim($this->pressMethod) === '') {
            throw new InvalidArgumentException(
                'Firstlight Confirmation Dialog requires `@press` for confirmation.'
            );
        }

        if ($this->dismissMethod === null || trim($this->dismissMethod) === '') {
            throw new InvalidArgumentException(
                'Firstlight Confirmation Dialog requires `@dismiss` for cancellation and system dismissal.'
            );
        }

        return [
            ...$props,
            'on_dismiss' => $registry->register($this->dismissMethod),
        ];
    }

    public function getStyle(): array
    {
        return [];
    }
}
