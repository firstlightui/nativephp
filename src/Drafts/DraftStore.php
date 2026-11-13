<?php

namespace FirstlightUI\Drafts;

use Closure;

/**
 * Drafts of screens that are not open, kept in memory while the app runs.
 * Never written to disk: a draft may hold secrets or patient data.
 */
final class DraftStore
{
    /** @var array<string, Draft> */
    private array $drafts = [];

    private ?Closure $scope = null;

    /**
     * Keep each user's drafts apart, for example by account ID. When the
     * resolver returns null, such as while signed out, nothing is kept or
     * restored.
     *
     * @param  Closure(): (string|int|null)  $resolver
     */
    public function scopeUsing(Closure $resolver): void
    {
        $this->scope = $resolver;
    }

    public function put(string $screen, Draft $draft): void
    {
        $slot = $this->slot($screen, $draft->key);

        if ($slot !== null) {
            $this->drafts[$slot] = $draft;
        }
    }

    /** Remove and return the draft, so only one open screen holds it. */
    public function take(string $screen, string $key): ?Draft
    {
        $slot = $this->slot($screen, $key);

        if ($slot === null || ! isset($this->drafts[$slot])) {
            return null;
        }

        $draft = $this->drafts[$slot];
        unset($this->drafts[$slot]);

        return $draft;
    }

    public function has(string $screen, string $key): bool
    {
        $slot = $this->slot($screen, $key);

        return $slot !== null && isset($this->drafts[$slot]);
    }

    /** Forget every draft, for every scope. Call it on logout or account switch. */
    public function flush(): void
    {
        $this->drafts = [];
    }

    private function slot(string $screen, string $key): ?string
    {
        if ($this->scope === null) {
            return "{$screen}\0{$key}";
        }

        $scope = ($this->scope)();

        return $scope === null ? null : "{$scope}\0{$screen}\0{$key}";
    }
}
