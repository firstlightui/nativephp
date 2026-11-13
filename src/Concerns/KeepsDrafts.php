<?php

namespace FirstlightUI\Concerns;

use FirstlightUI\Drafts\Draft;
use FirstlightUI\Drafts\DraftStore;
use FirstlightUI\Events\FeedbackActionPressed;
use FirstlightUI\Events\FeedbackDismissed;
use FirstlightUI\Facades\Feedback;
use FirstlightUI\Feedback\FeedbackDismissReason;
use FirstlightUI\Support\Chrome;
use InvalidArgumentException;
use Native\Mobile\Attributes\On;
use ReflectionProperty;

/**
 * Keeps a screen's unsaved changes when it closes and offers them again when
 * it reopens. iOS closes a screen on Back or an edge swipe before PHP hears
 * about it, so a screen cannot ask "Discard changes?"; keeping the draft is
 * what protects the edits instead.
 */
trait KeepsDrafts
{
    /** A draft edited from another version, offered with "Restore anyway". */
    private ?Draft $offeredDraft = null;

    /** @var array<string, mixed>|null Property values a restore replaced, for "Discard". */
    private ?array $valuesBeforeDraft = null;

    private ?string $draftFeedbackId = null;

    /**
     * What to keep when this screen closes, or null when nothing is unsaved.
     * Called from unmount(), on every way of leaving the screen.
     */
    abstract protected function draft(): ?Draft;

    /**
     * Restore the draft kept for $key, if any. Call it once the screen's data
     * has loaded, with the version just loaded. Returns true when the draft's
     * values were assigned. A draft edited from another version is not
     * assigned; it is offered with a "Restore anyway" Feedback action.
     */
    protected function restoreDraft(string $key, string|int|null $basedOn = null): bool
    {
        $draft = app(DraftStore::class)->take(static::class, $key);

        if ($draft === null) {
            return false;
        }

        $this->dismissDraftFeedback();
        $this->valuesBeforeDraft = null;

        if ($draft->basedOn !== ($basedOn === null ? null : (string) $basedOn)) {
            $this->offeredDraft = $draft;
            $this->draftFeedbackId = Feedback::warning(Chrome::string('draft_changed'))
                ->action(Chrome::string('restore_anyway'), 'firstlight-draft-restore')
                ->hold()
                ->send();

            return false;
        }

        $this->applyDraft($draft);

        return true;
    }

    #[On(FeedbackActionPressed::class)]
    public function answerDraftFeedback(string $id, string $actionKey): void
    {
        if ($id !== $this->draftFeedbackId) {
            return;
        }

        $this->draftFeedbackId = null;

        if ($actionKey === 'firstlight-draft-restore' && $this->offeredDraft !== null) {
            $this->applyDraft($this->offeredDraft);
        } elseif ($actionKey === 'firstlight-draft-discard' && $this->valuesBeforeDraft !== null) {
            foreach ($this->valuesBeforeDraft as $name => $value) {
                $this->{$name} = $value;
            }

            $this->valuesBeforeDraft = null;
        }
    }

    #[On(FeedbackDismissed::class)]
    public function draftFeedbackDismissed(string $id, FeedbackDismissReason $reason): void
    {
        if ($id !== $this->draftFeedbackId || $reason === FeedbackDismissReason::Action) {
            return;
        }

        // Swiping the "Restore anyway" warning away declines the old draft.
        $this->draftFeedbackId = null;
        $this->offeredDraft = null;
        $this->valuesBeforeDraft = null;
    }

    public function unmount(): void
    {
        $this->dismissDraftFeedback();

        // A draft the user neither restored nor declined is offered again.
        $draft = $this->draft() ?? $this->offeredDraft;

        if ($draft !== null) {
            app(DraftStore::class)->put(static::class, $draft);
        }

        parent::unmount();
    }

    private function applyDraft(Draft $draft): void
    {
        $previous = [];

        foreach ($draft->values as $name => $value) {
            if (! property_exists($this, $name)
                || ! ($property = new ReflectionProperty($this, $name))->isPublic()
                || $property->isStatic()) {
                throw new InvalidArgumentException(sprintf(
                    'Draft value [%s] is not a public property of [%s].',
                    $name,
                    static::class,
                ));
            }

            $previous[$name] = $this->{$name};
        }

        foreach ($draft->values as $name => $value) {
            $this->{$name} = $value;
        }

        $this->offeredDraft = null;
        $this->valuesBeforeDraft = $previous;
        // A generated ID per message: the native queue treats an ID whose
        // action was just pressed as finished, so "Restored" after "Restore
        // anyway" must not reuse the warning's ID.
        $this->draftFeedbackId = Feedback::message(Chrome::string('draft_restored'))
            ->action(Chrome::string('discard'), 'firstlight-draft-discard')
            ->send();
    }

    private function dismissDraftFeedback(): void
    {
        if ($this->draftFeedbackId !== null) {
            Feedback::dismiss($this->draftFeedbackId);
            $this->draftFeedbackId = null;
        }
    }
}
