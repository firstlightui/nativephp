<?php

namespace FirstlightUI\Elements;

use FirstlightUI\Media\MediaValue;
use FirstlightUI\Support\Chrome;
use FirstlightUI\Validation\FieldErrorBinder;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;
use Throwable;

final class Media extends Element
{
    protected string $type = 'firstlight.media';

    private const EXCLUDED_ATTRIBUTES = [
        'multiple', 'accept', 'capture', 'video', 'gallery', 'url',
        'variant', 'size', 'tone', 'color', 'icon', 'icon-ios', 'iconIos',
        'icon-android', 'iconAndroid', 'placeholder', 'clearable',
        '_press', '_submit', '_longPress', '_doubleTap', '_pressDown', '_pressUp',
        '_navigate',
    ];

    /** @var array<string, mixed> */
    private array $fieldProps = [
        'mode' => '',
        'label' => '',
        'helper' => '',
        'error' => '',
        'required' => false,
        'disabled' => false,
        'disk' => 'mobile_public',
        'directory' => 'media',
        'has_value' => false,
        'path' => '',
        'mime' => '',
        'size' => 0,
        'preview_url' => '',
    ];

    private ?string $changeCallback = null;

    private ?string $clearCallback = null;

    public static function make(): static
    {
        return new static;
    }

    public function applyAttributes(array $attrs): void
    {
        foreach (self::EXCLUDED_ATTRIBUTES as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                throw new InvalidArgumentException(
                    "Media does not support the `{$attribute}` attribute."
                );
            }
        }

        if (array_key_exists('mode', $attrs)) {
            $this->mode($this->strictString('mode', $attrs['mode']));
        }

        if (array_key_exists('value', $attrs)) {
            $this->value($attrs['value']);
        }

        foreach (['label', 'helper', 'error'] as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                $this->{$attribute}($this->strictString($attribute, $attrs[$attribute]));
            }
        }

        foreach (['required', 'disabled'] as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                if (! is_bool($attrs[$attribute])) {
                    throw new InvalidArgumentException(
                        "Media `{$attribute}` must be a boolean."
                    );
                }

                $this->{$attribute}($attrs[$attribute]);
            }
        }

        if (array_key_exists('disk', $attrs)) {
            $this->disk($this->strictString('disk', $attrs['disk']));
        }

        if (array_key_exists('directory', $attrs)) {
            $this->directory($this->strictString('directory', $attrs['directory']));
        }

        if (array_key_exists('aspect', $attrs)) {
            $this->aspect($this->strictString('aspect', $attrs['aspect']));
        }

        if (array_key_exists('crop', $attrs)) {
            $this->crop($this->strictString('crop', $attrs['crop']));
        }

        if (array_key_exists('sync-mode', $attrs) || array_key_exists('syncMode', $attrs)) {
            $this->syncMode($this->strictString(
                'sync-mode',
                $attrs['sync-mode'] ?? $attrs['syncMode'],
            ));
        }

        foreach (['a11y-label', 'a11yLabel', 'a11y-hint', 'a11yHint'] as $attribute) {
            if (array_key_exists($attribute, $attrs) && ! is_string($attrs[$attribute])) {
                throw new InvalidArgumentException(
                    "Media `{$attribute}` must be a string."
                );
            }
        }

        $this->applyA11yAttributes($attrs);
        FieldErrorBinder::apply($this, $attrs);

        if (array_key_exists('_clear', $attrs)) {
            $this->onClear($this->strictString('_clear', $attrs['_clear']));
        }

        $this->assertModeAndCropComposition();
    }

    public function mode(string $mode): static
    {
        if (! in_array($mode, ['image', 'document'], true)) {
            throw new InvalidArgumentException(
                'Media `mode` must be `image` or `document`.'
            );
        }

        $this->fieldProps['mode'] = $mode;

        return $this;
    }

    public function value(mixed $value): static
    {
        if ($value === null) {
            $this->fieldProps['has_value'] = false;
            $this->fieldProps['path'] = '';
            $this->fieldProps['mime'] = '';
            $this->fieldProps['size'] = 0;
            unset($this->fieldProps['width'], $this->fieldProps['height']);
            $this->fieldProps['preview_url'] = '';

            return $this;
        }

        if (! $value instanceof MediaValue) {
            throw new InvalidArgumentException(
                'Media `value` must be a MediaValue instance or null.'
            );
        }

        $this->fieldProps['has_value'] = true;
        $this->fieldProps['path'] = $value->path;
        $this->fieldProps['mime'] = $value->mime;
        $this->fieldProps['size'] = $value->size;
        $this->fieldProps['disk'] = $value->disk;

        if ($value->width !== null) {
            $this->fieldProps['width'] = $value->width;
        } else {
            unset($this->fieldProps['width']);
        }

        if ($value->height !== null) {
            $this->fieldProps['height'] = $value->height;
        } else {
            unset($this->fieldProps['height']);
        }

        $this->fieldProps['preview_url'] = $this->resolvePreviewUrl($value);

        return $this;
    }

    public function label(string $value): static
    {
        $this->fieldProps['label'] = $value;

        return $this;
    }

    public function helper(string $value): static
    {
        $this->fieldProps['helper'] = $value;

        return $this;
    }

    public function error(string $value): static
    {
        $this->fieldProps['error'] = $value;

        return $this;
    }

    public function required(bool $value = true): static
    {
        $this->fieldProps['required'] = $value;

        return $this;
    }

    public function disabled(bool $value = true): static
    {
        $this->fieldProps['disabled'] = $value;

        return $this;
    }

    public function disk(string $value): static
    {
        if ($value === '') {
            throw new InvalidArgumentException('Media `disk` must be a non-empty string.');
        }

        $this->fieldProps['disk'] = $value;

        return $this;
    }

    public function directory(string $value): static
    {
        $this->fieldProps['directory'] = trim($value, '/');

        return $this;
    }

    public function aspect(string $value): static
    {
        if ($value === '' || preg_match('/^\d+:\d+$/', $value) !== 1) {
            throw new InvalidArgumentException(
                'Media `aspect` must be a ratio like `1:1` or `4:3`.'
            );
        }

        $this->fieldProps['aspect'] = $value;

        return $this;
    }

    public function crop(string $value): static
    {
        if (! in_array($value, ['optional', 'required'], true)) {
            throw new InvalidArgumentException(
                'Media `crop` must be `optional` or `required`.'
            );
        }

        $this->fieldProps['crop'] = $value;

        return $this;
    }

    public function syncMode(string $mode): static
    {
        if ($mode !== 'live') {
            throw new InvalidArgumentException(
                'Media supports only native:model or native:model.live.'
            );
        }

        return $this;
    }

    public function onChange(string $method): static
    {
        $this->changeCallback = $method;

        return $this;
    }

    public function onClear(string $method): static
    {
        $this->clearCallback = $method;

        return $this;
    }

    protected function resolveProps(CallbackRegistry $registry): array
    {
        if (($this->fieldProps['mode'] ?? '') === '') {
            throw new InvalidArgumentException(
                'Media requires `mode` of `image` or `document`.'
            );
        }

        $this->assertModeAndCropComposition();
        $this->warnWhenUnlabelled();

        $props = $this->fieldProps;
        $props['confirm_label'] = Chrome::string('confirm');
        $props['cancel_label'] = Chrome::string('cancel');
        $props['clear_label'] = Chrome::string('clear');
        $props['skip_label'] = Chrome::string('skip');
        $props['crop_label'] = Chrome::string('crop');
        $props['zoom_in_label'] = Chrome::string('zoom_in');
        $props['zoom_out_label'] = Chrome::string('zoom_out');
        $props['choose_media_label'] = Chrome::string('choose_media');
        $props['photo_library_label'] = Chrome::string('photo_library');
        $props['camera_label'] = Chrome::string('camera');
        $props['browse_files_label'] = Chrome::string('browse_files');

        if (isset($props['aspect']) && ! isset($props['crop'])) {
            $props['crop'] = 'required';
        }

        if ($this->changeCallback !== null) {
            $props['on_change'] = $registry->register($this->changeCallback);
        }

        if ($this->clearCallback !== null) {
            $props['on_clear'] = $registry->register($this->clearCallback);
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

    private function assertModeAndCropComposition(): void
    {
        $mode = $this->fieldProps['mode'] ?? '';

        if ($mode === '') {
            return;
        }

        $hasAspect = array_key_exists('aspect', $this->fieldProps);
        $hasCrop = array_key_exists('crop', $this->fieldProps);

        if ($mode === 'document' && ($hasAspect || $hasCrop)) {
            throw new InvalidArgumentException(
                'Media document mode rejects `crop` and `aspect`.'
            );
        }
    }

    private function resolvePreviewUrl(MediaValue $value): string
    {
        try {
            return (string) Storage::disk($value->disk)->url($value->path);
        } catch (Throwable) {
            return '';
        }
    }

    private function strictString(string $attribute, mixed $value): string
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException(
                "Media `{$attribute}` must be a string."
            );
        }

        return $value;
    }

    private function warnWhenUnlabelled(): void
    {
        $label = trim((string) $this->fieldProps['label']);
        $a11yLabel = trim((string) ($this->extraProps['a11y_label'] ?? ''));

        if ($label !== '' || $a11yLabel !== '' || $this->applicationIsProduction()) {
            return;
        }

        trigger_error(
            'Firstlight Media requires a visible label or a11y-label.',
            E_USER_WARNING,
        );
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
