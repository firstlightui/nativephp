<?php

namespace FirstlightUI\Feedback;

use FirstlightUI\Haptics\HapticsManager;
use Illuminate\Container\Container;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class PendingFeedback
{
    private function __construct(
        private readonly FeedbackStore $store,
        private readonly string $message,
        private readonly FeedbackTone $tone,
        private readonly ?string $id,
        private readonly ?string $actionLabel,
        private readonly ?string $actionKey,
        private readonly bool $hold,
        private readonly bool $haptic = true,
    ) {}

    public static function create(FeedbackStore $store, string $message, FeedbackTone $tone): self
    {
        self::ensureNotBlank($message, 'message');

        return new self($store, $message, $tone, null, null, null, false);
    }

    public function id(string $id): self
    {
        self::ensureNotBlank($id, 'id');

        return new self($this->store, $this->message, $this->tone, $id, $this->actionLabel, $this->actionKey, $this->hold, $this->haptic);
    }

    public function action(string $label, string $key): self
    {
        self::ensureNotBlank($label, 'action label');
        self::ensureNotBlank($key, 'action key');

        return new self($this->store, $this->message, $this->tone, $this->id, $label, $key, $this->hold, $this->haptic);
    }

    public function hold(): self
    {
        return new self($this->store, $this->message, $this->tone, $this->id, $this->actionLabel, $this->actionKey, true, $this->haptic);
    }

    /** Keeps this Feedback silent even when a user interaction sent it. */
    public function withoutHaptic(): self
    {
        return new self($this->store, $this->message, $this->tone, $this->id, $this->actionLabel, $this->actionKey, $this->hold, false);
    }

    public function send(): string
    {
        $id = $this->id ?? Str::uuid()->toString();

        $id = $this->store->put(new FeedbackRecord(
            $id,
            $this->message,
            $this->tone,
            $this->hold,
            $this->actionLabel,
            $this->actionKey,
            $this->haptic,
        ));

        // Only Feedback a tap, change or submit caused plays; resume, sync
        // and connectivity notices stay silent.
        $haptic = $this->tone->haptic();
        $container = Container::getInstance();

        if ($this->haptic && $haptic !== null && $container->bound(HapticsManager::class)) {
            $container->make(HapticsManager::class)->playForInteraction($haptic);
        }

        return $id;
    }

    private static function ensureNotBlank(string $value, string $field): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException("Feedback requires a non-empty `{$field}`.");
        }
    }
}
