<?php

namespace FirstlightUI\Elements;

use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;

class StatusLabel extends Element
{
    protected string $type = 'firstlight.status-label';

    /** @var list<string> */
    private const TONES = ['neutral', 'info', 'success', 'warning', 'danger'];

    /** @var list<string> */
    private const INCOMPATIBLE_ATTRIBUTES = [
        'value',
        'disabled',
        'loading',
        'error',
        'required',
        'helper',
        'sync-mode',
        'syncMode',
        '_change',
        '_press',
    ];

    /** @var array{label: string, tone: string} */
    protected array $statusProps = [
        'label' => '',
        'tone' => 'neutral',
    ];

    public static function make(string $label = ''): static
    {
        return (new static)->label($label);
    }

    public function applyAttributes(array $attrs): void
    {
        $this->assertDisplayOnlyAttributes($attrs);

        if (array_key_exists('label', $attrs)) {
            $this->label((string) ($attrs['label'] ?? ''));
        }

        if (array_key_exists('tone', $attrs)) {
            $this->tone((string) ($attrs['tone'] ?? ''));
        }

        $this->applyA11yAttributes($attrs);
    }

    public function label(string $label): static
    {
        $this->statusProps['label'] = $label;

        return $this;
    }

    public function tone(string $tone): static
    {
        if (! in_array($tone, self::TONES, true)) {
            throw new InvalidArgumentException(
                "Unsupported Status Label tone [{$tone}]. Expected one of: "
                .implode(', ', self::TONES).'.'
            );
        }

        $this->statusProps['tone'] = $tone;

        return $this;
    }

    protected function resolveProps(CallbackRegistry $registry): array
    {
        if (trim($this->statusProps['label']) === '') {
            throw new InvalidArgumentException(
                'Firstlight Status Label requires a non-empty `label`.'
            );
        }

        return array_merge($this->statusProps, $this->extraProps);
    }

    private function assertDisplayOnlyAttributes(array $attrs): void
    {
        foreach (self::INCOMPATIBLE_ATTRIBUTES as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                throw new InvalidArgumentException(
                    "Firstlight Status Label is display-only; `{$attribute}` is not supported."
                );
            }
        }
    }
}
