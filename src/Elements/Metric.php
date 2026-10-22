<?php

namespace FirstlightUI\Elements;

use FirstlightUI\Support\WarningColors;
use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;

final class Metric extends Element
{
    protected string $type = 'firstlight.metric';

    private array $metricProps = ['label' => '', 'value' => '', 'detail' => '', 'tone' => 'neutral'];

    public static function make(string $label, string $value): static
    {
        $metric = new static;
        $metric->applyAttributes(compact('label', 'value'));

        return $metric;
    }

    public function applyAttributes(array $attrs): void
    {
        foreach (['disabled', 'selected', 'loading', 'error', 'helper', 'required', 'native:model', 'sync-mode', 'syncMode', '_change', '_press'] as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                throw new InvalidArgumentException("Firstlight Metric is display-only; `{$attribute}` is not supported.");
            }
        }

        foreach (array_keys($this->metricProps) as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                if (! is_string($attrs[$attribute])) {
                    throw new InvalidArgumentException("Firstlight Metric `{$attribute}` must be a string; format values in PHP.");
                }
                $this->metricProps[$attribute] = $attrs[$attribute];
            }
        }

        foreach (['a11y-label', 'a11yLabel', 'a11y-hint', 'a11yHint'] as $attribute) {
            if (array_key_exists($attribute, $attrs) && (! is_string($attrs[$attribute]) || trim($attrs[$attribute]) === '')) {
                throw new InvalidArgumentException("Firstlight Metric `{$attribute}` must be a non-empty string when supplied.");
            }
        }
        $this->applyA11yAttributes($attrs);
    }

    protected function resolveProps(CallbackRegistry $registry): array
    {
        foreach (['label', 'value'] as $attribute) {
            if (trim($this->metricProps[$attribute]) === '') {
                throw new InvalidArgumentException("Firstlight Metric requires a non-empty `{$attribute}`.");
            }
        }
        if (! in_array($this->metricProps['tone'], ['neutral', 'info', 'success', 'warning', 'danger'], true)) {
            throw new InvalidArgumentException('Unsupported Metric tone; expected neutral, info, success, warning, or danger.');
        }

        $description = $this->metricProps['label'].': '.$this->metricProps['value'];
        if (trim($this->metricProps['detail']) !== '') {
            $description .= ', '.$this->metricProps['detail'];
        }

        return array_merge($this->metricProps, [
            'a11y_label' => $this->extraProps['a11y_label'] ?? $description,
        ], $this->metricProps['tone'] === 'warning' ? WarningColors::props() : []);
    }
}
