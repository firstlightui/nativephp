<?php

namespace FirstlightUI\Validation;

use Native\Mobile\Edge\Element;

/**
 * One screen-reader alert for the frame published after a failed
 * interaction. The first error-bound element in render order carries the
 * announcement; the first invalid field carries the focus request. Each
 * alert is published once, and native renderers act once per revision.
 */
final class ValidationAlert
{
    private static int $revisions = 0;

    private bool $announcementClaimed = false;

    private bool $focusClaimed = false;

    private function __construct(
        public readonly int $revision,
        public readonly string $announcement,
        public readonly bool $movesFocus,
        private readonly ?array $keys,
    ) {}

    /** @param  list<string>|null  $keys  keys the alert is about; null for the whole bag */
    public static function next(string $announcement, bool $movesFocus, ?array $keys = null): self
    {
        return new self(++self::$revisions, $announcement, $movesFocus, $keys);
    }

    /** @param  string|null  $key  the element's bag key; null for a summary of every key */
    public function claim(Element $element, bool $field, ?string $key): void
    {
        $props = [];
        $concerned = $key === null || $this->keys === null || in_array($key, $this->keys, true);

        if (! $this->announcementClaimed && $this->announcement !== '' && $concerned) {
            $this->announcementClaimed = true;
            $props['validation_announcement'] = $this->announcement;
        }

        if ($field && $this->movesFocus && ! $this->focusClaimed) {
            $this->focusClaimed = true;
            $props['validation_focus'] = true;
        }

        if ($props === []) {
            return;
        }

        $element->setProp('validation_alert', $this->revision);

        foreach ($props as $key => $value) {
            $element->setProp($key, $value);
        }
    }
}
