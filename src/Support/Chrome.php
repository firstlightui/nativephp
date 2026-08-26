<?php

namespace FirstlightUI\Support;

use DateTimeZone;
use Illuminate\Container\Container;
use Illuminate\Contracts\Translation\Translator;
use InvalidArgumentException;
use Throwable;

final class Chrome
{
    /** @var array<string, string> */
    public const STRINGS = [
        'confirm' => 'Confirm',
        'cancel' => 'Cancel',
        'ok' => 'OK',
        'dismiss' => 'Dismiss',
        'dismiss_feedback' => 'Dismiss feedback',
        'clear' => 'Clear',
        'clear_search' => 'Clear search',
        'clear_text' => 'Clear text',
        'skip' => 'Skip',
        'done' => 'Done',
        'crop' => 'Crop',
        'zoom_in' => 'Zoom in',
        'zoom_out' => 'Zoom out',
        'choose_media' => 'Choose media',
        'photo_library' => 'Photo Library',
        'camera' => 'Camera',
        'browse_files' => 'Browse Files',
        'show_password' => 'Show password',
        'hide_password' => 'Hide password',
    ];

    public static function string(string $key): string
    {
        if (! array_key_exists($key, self::STRINGS)) {
            throw new InvalidArgumentException("Unknown Firstlight chrome string [{$key}].");
        }

        $translator = self::translator();
        if ($translator !== null) {
            $translated = $translator->get('firstlight::chrome.'.$key);
            if (is_string($translated)
                && $translated !== ''
                && $translated !== 'firstlight::chrome.'.$key) {
                return $translated;
            }
        }

        return self::STRINGS[$key];
    }

    public static function applicationLocale(): ?string
    {
        $translator = self::translator();
        if ($translator === null || ! method_exists($translator, 'getLocale')) {
            return null;
        }

        try {
            $locale = $translator->getLocale();
        } catch (Throwable) {
            return null;
        }

        if (! is_string($locale) || $locale === '') {
            return null;
        }

        $mapped = str_replace('_', '-', explode('.', $locale)[0]);

        return self::isValidLanguageTag($mapped) ? $mapped : null;
    }

    public static function applicationTimezone(): ?string
    {
        $timezone = self::configValue('app.timezone');
        if (! is_string($timezone) || $timezone === '') {
            return null;
        }

        if (! in_array($timezone, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true)) {
            return null;
        }

        return $timezone;
    }

    /**
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    public static function withPickerChrome(array $props): array
    {
        $props['confirm_label'] = self::string('confirm');
        $props['cancel_label'] = self::string('cancel');

        if (! array_key_exists('locale', $props)) {
            $locale = self::applicationLocale();
            if ($locale !== null) {
                $props['locale'] = $locale;
            }
        }

        if (! array_key_exists('timezone', $props)) {
            $timezone = self::applicationTimezone();
            if ($timezone !== null) {
                $props['timezone'] = $timezone;
            }
        }

        return $props;
    }

    private static function translator(): ?Translator
    {
        $container = self::container();
        if ($container === null || ! $container->bound('translator')) {
            return null;
        }

        try {
            $translator = $container->make('translator');
        } catch (Throwable) {
            return null;
        }

        return $translator instanceof Translator ? $translator : null;
    }

    private static function configValue(string $key): mixed
    {
        $container = self::container();
        if ($container === null || ! $container->bound('config')) {
            return null;
        }

        try {
            $config = $container->make('config');
        } catch (Throwable) {
            return null;
        }

        if (is_object($config) && method_exists($config, 'get')) {
            return $config->get($key);
        }

        return null;
    }

    private static function container(): ?Container
    {
        try {
            $container = Container::getInstance();
        } catch (Throwable) {
            return null;
        }

        return $container instanceof Container ? $container : null;
    }

    private static function isValidLanguageTag(string $value): bool
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
}
