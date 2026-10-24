<?php

namespace FirstlightUI\Validation;

use Illuminate\Container\Container;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\MessageBag;
use Throwable;

/**
 * Package-owned wording for form-level summaries and screen-reader
 * announcements. Each MessageBag key contributes its first message, in
 * bag order, matching what the bound field shows.
 */
final class ErrorSummary
{
    /** @return list<string> */
    public static function messages(MessageBag $bag, ?array $keys = null): array
    {
        $messages = [];

        foreach ($keys ?? $bag->keys() as $key) {
            $message = $bag->first($key);

            if ($message !== '') {
                $messages[] = $message;
            }
        }

        return $messages;
    }

    /** Visible Callout summary: one message alone, or a heading and one message per line. */
    public static function text(MessageBag $bag): string
    {
        return self::compose(self::messages($bag), "\n");
    }

    /** Spoken summary: the same content on one line. */
    public static function announcement(MessageBag $bag, ?array $keys = null): string
    {
        return self::compose(self::messages($bag, $keys), ' ');
    }

    /** @param  list<string>  $messages */
    private static function compose(array $messages, string $separator): string
    {
        $count = count($messages);

        if ($count <= 1) {
            return $messages[0] ?? '';
        }

        return implode($separator, [self::heading($count), ...$messages]);
    }

    private static function heading(int $count): string
    {
        $translator = self::translator();

        if ($translator !== null) {
            try {
                $translated = $translator->choice('firstlight::validation.summary', $count, ['count' => $count]);

                if (is_string($translated) && $translated !== '' && $translated !== 'firstlight::validation.summary') {
                    return $translated;
                }
            } catch (Throwable) {
            }
        }

        return str_replace(':count', (string) $count, $count === 1
            ? 'There is :count error.'
            : 'There are :count errors.');
    }

    private static function translator(): ?Translator
    {
        try {
            $container = Container::getInstance();

            if (! $container->bound('translator')) {
                return null;
            }

            $translator = $container->make('translator');
        } catch (Throwable) {
            return null;
        }

        return $translator instanceof Translator ? $translator : null;
    }
}
