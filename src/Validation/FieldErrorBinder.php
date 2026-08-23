<?php

namespace FirstlightUI\Validation;

final class FieldErrorBinder
{
    /**
     * Fill an element's error slot from the current MessageBag when the
     * author did not supply a non-empty `error` attribute.
     */
    public static function apply(object $element, array $attrs): void
    {
        $authored = $attrs['error'] ?? null;
        if (is_string($authored) && $authored !== '') {
            return;
        }

        if (! method_exists($element, 'error')) {
            return;
        }

        $name = self::resolveFieldName($attrs);
        if ($name === null) {
            return;
        }

        $bag = FieldErrorBag::current();
        if ($bag === null) {
            return;
        }

        $message = $bag->first($name);
        if ($message !== '') {
            $element->error($message);
        }
    }

    public static function resolveFieldName(array $attrs): ?string
    {
        foreach (['error-for', 'errorFor'] as $key) {
            if (isset($attrs[$key]) && is_string($attrs[$key]) && $attrs[$key] !== '') {
                return $attrs[$key];
            }
        }

        foreach ($attrs as $key => $value) {
            if (! is_string($key) || ! is_string($value) || $value === '') {
                continue;
            }

            if ($key === 'native:model' || str_starts_with($key, 'native:model.')) {
                return $value;
            }
        }

        $change = $attrs['_change'] ?? null;
        if (is_string($change) && preg_match("/^__syncProperty\\('([^']+)'\\)$/", $change, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
