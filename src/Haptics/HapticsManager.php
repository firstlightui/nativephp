<?php

namespace FirstlightUI\Haptics;

use InvalidArgumentException;
use Throwable;

/**
 * Plays haptics called from PHP through the `Firstlight.Haptics.*` bridge
 * functions. Controls play their own touch haptics natively; PHP plays the
 * outcomes (success, warning, error) and app-specific moments.
 *
 * While PHP handles a user interaction, outcomes wait until it ends and only
 * the strongest one plays, so a failed save that also shows an error
 * Feedback buzzes once.
 */
class HapticsManager
{
    public const PLAY = 'Firstlight.Haptics.Play';

    public const SET_ENABLED = 'Firstlight.Haptics.SetEnabled';

    /** @var list<string> */
    public const IMPACT_WEIGHTS = ['light', 'medium', 'heavy', 'soft', 'rigid'];

    private int $interactionDepth = 0;

    private ?Haptic $pendingOutcome = null;

    public function play(Haptic|string $haptic): void
    {
        $haptic = Haptic::parse($haptic);

        if ($haptic === Haptic::None) {
            return;
        }

        if ($haptic->isOutcome() && $this->interacting()) {
            if ($haptic->outcomeRank() > ($this->pendingOutcome?->outcomeRank() ?? 0)) {
                $this->pendingOutcome = $haptic;
            }

            return;
        }

        $this->call(self::PLAY, ['haptic' => $haptic->value]);
    }

    public function success(): void
    {
        $this->play(Haptic::Success);
    }

    public function warning(): void
    {
        $this->play(Haptic::Warning);
    }

    public function error(): void
    {
        $this->play(Haptic::Error);
    }

    public function selection(): void
    {
        $this->play(Haptic::Selection);
    }

    /**
     * A physical impact: `light`, `medium`, `heavy`, `soft` or `rigid`, at an
     * intensity from 0 to 1.
     */
    public function impact(string $weight = 'medium', float $intensity = 1.0): void
    {
        if (! in_array($weight, self::IMPACT_WEIGHTS, true)) {
            throw new InvalidArgumentException(
                "Unknown impact weight [{$weight}]. Expected one of: ".implode(', ', self::IMPACT_WEIGHTS).'.'
            );
        }

        if (! is_finite($intensity) || $intensity < 0.0 || $intensity > 1.0) {
            throw new InvalidArgumentException('Impact intensity must be between 0 and 1.');
        }

        $this->call(self::PLAY, ['impact' => $weight, 'intensity' => $intensity]);
    }

    /**
     * Turns every Firstlight haptic on or off for this install, including the
     * ones controls play natively. The device remembers the choice; use it
     * for an in-app "Haptics" setting. The system haptics setting always
     * applies as well.
     */
    public function setEnabled(bool $enabled): void
    {
        $this->call(self::SET_ENABLED, ['enabled' => $enabled]);
    }

    /** @internal Firstlight brackets each user interaction it dispatches. */
    public function beginInteraction(): void
    {
        $this->interactionDepth++;
    }

    /** @internal */
    public function endInteraction(): void
    {
        if ($this->interactionDepth === 0 || --$this->interactionDepth > 0) {
            return;
        }

        $outcome = $this->pendingOutcome;
        $this->pendingOutcome = null;

        if ($outcome !== null) {
            $this->call(self::PLAY, ['haptic' => $outcome->value]);
        }
    }

    /** @internal Whether PHP is handling a tap, change or submit right now. */
    public function interacting(): bool
    {
        return $this->interactionDepth > 0;
    }

    /** @internal Plays only when a user interaction caused it, such as a Feedback's tone. */
    public function playForInteraction(Haptic $haptic): void
    {
        if ($this->interacting()) {
            $this->play($haptic);
        }
    }

    /** @param  array<string, mixed>  $payload */
    protected function call(string $function, array $payload): void
    {
        if (! function_exists('nativephp_call')) {
            return;
        }

        // A haptic is fire and forget: a device without the plugin or without
        // a haptic engine must never break the interaction that played it.
        try {
            nativephp_call($function, json_encode($payload, JSON_THROW_ON_ERROR));
        } catch (Throwable) {
        }
    }
}
