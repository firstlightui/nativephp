<?php

namespace FirstlightUI\Elements;

use FirstlightUI\Support\Chrome;
use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;

final class AlertDialog extends Element
{
    protected string $type = 'firstlight.alert-dialog';

    /** @var list<string> */
    private const UNSUPPORTED_ATTRIBUTES = [
        'value', 'native:model', 'disabled', 'loading', 'dismissible', 'dismissable',
        'a11y-label', 'a11yLabel', 'a11y-hint', 'a11yHint', 'variant', 'size', 'icon',
        'tone', 'confirm-label', 'confirmLabel', 'cancel-label', 'cancelLabel',
        'sync-mode', 'syncMode', '_press', '_change', '_submit', '_longPress',
        '_doubleTap', '_pressDown', '_pressUp', '_navigate',
    ];

    /** @var array{visible: bool, title: string, message: string, action_label: string} */
    private array $dialogProps = [
        'visible' => false,
        'title' => '',
        'message' => '',
        'action_label' => 'OK',
    ];

    private ?string $dismissMethod = null;

    private bool $actionLabelAuthored = false;

    public static function make(string $title = '', string $message = ''): static
    {
        return (new static)->title($title)->message($message);
    }

    public function applyAttributes(array $attrs): void
    {
        foreach (self::UNSUPPORTED_ATTRIBUTES as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                throw new InvalidArgumentException(
                    "Firstlight Alert Dialog does not support `{$attribute}`."
                );
            }
        }

        if (array_key_exists('visible', $attrs)) {
            if (! is_bool($attrs['visible'])) {
                throw new InvalidArgumentException(
                    'Firstlight Alert Dialog `visible` must be a boolean.'
                );
            }

            $this->visible($attrs['visible']);
        }

        foreach ([
            'title' => 'title',
            'message' => 'message',
            'action-label' => 'actionLabel',
            'actionLabel' => 'actionLabel',
        ] as $attribute => $method) {
            if (! array_key_exists($attribute, $attrs)) {
                continue;
            }

            if (! is_string($attrs[$attribute])) {
                $publicName = str_replace('Label', '-label', $method);

                throw new InvalidArgumentException(
                    "Firstlight Alert Dialog `{$publicName}` must be a string."
                );
            }

            $this->{$method}($attrs[$attribute]);
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

    public function actionLabel(string $label): static
    {
        $this->dialogProps['action_label'] = $label;
        $this->actionLabelAuthored = true;

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
        if (! $this->actionLabelAuthored) {
            $props['action_label'] = Chrome::string('ok');
        }

        foreach (['title', 'message', 'action_label'] as $prop) {
            if (trim($props[$prop]) === '') {
                $attribute = str_replace('_', '-', $prop);

                throw new InvalidArgumentException(
                    "Firstlight Alert Dialog requires a non-empty `{$attribute}`."
                );
            }
        }

        if ($this->pressMethod !== null && trim($this->pressMethod) !== '') {
            throw new InvalidArgumentException(
                'Firstlight Alert Dialog does not support `@press`.'
            );
        }

        if ($this->dismissMethod === null || trim($this->dismissMethod) === '') {
            throw new InvalidArgumentException(
                'Firstlight Alert Dialog requires `@dismiss` for acknowledgement and system dismissal.'
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
