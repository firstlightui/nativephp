<?php

namespace FirstlightUI\NativeComponents;

use FirstlightUI\Elements\FeedbackCenter as FeedbackCenterElement;
use FirstlightUI\Elements\FeedbackItem;
use FirstlightUI\Events\FeedbackActionPressed;
use FirstlightUI\Events\FeedbackDismissed;
use FirstlightUI\Feedback\FeedbackDismissReason;
use FirstlightUI\Feedback\FeedbackStore;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NativeComponent;

final class FeedbackCenter extends NativeComponent
{
    public function render(): Element
    {
        $center = FeedbackCenterElement::make();

        $store = app(FeedbackStore::class);

        foreach ($store->all() as $record) {
            $center->addChild(FeedbackItem::fromRecord(
                $record,
                $store->publicationGeneration($record->id),
            ));
        }

        return $center;
    }

    public function action(string $id, string $key, int $publicationGeneration = 0): void
    {
        unset($publicationGeneration);

        $record = app(FeedbackStore::class)->remove($id);
        if ($record === null || $record->actionKey !== $key) {
            return;
        }

        try {
            event(new FeedbackActionPressed($id, $key));
        } finally {
            event(new FeedbackDismissed($id, FeedbackDismissReason::Action));
        }
    }

    public function dismiss(string $id, string $reason, int $publicationGeneration = 0): void
    {
        unset($publicationGeneration);

        $dismissReason = FeedbackDismissReason::tryFrom($reason);
        if ($dismissReason === null || $dismissReason === FeedbackDismissReason::Action) {
            return;
        }

        if (app(FeedbackStore::class)->remove($id) !== null) {
            event(new FeedbackDismissed($id, $dismissReason));
        }
    }
}
