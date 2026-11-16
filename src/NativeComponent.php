<?php

namespace FirstlightUI;

use FirstlightUI\Concerns\AuthorizesActions;
use FirstlightUI\Concerns\DestroysListItems;
use FirstlightUI\Concerns\PaginatesLists;
use FirstlightUI\Concerns\SubmitsForms;
use FirstlightUI\Concerns\ValidatesFields;
use FirstlightUI\Elements\Slider;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NativeComponent as EdgeNativeComponent;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\Transition;

class NativeComponent extends EdgeNativeComponent
{
    use AuthorizesActions;
    use DestroysListItems;
    use PaginatesLists;
    use SubmitsForms;
    use ValidatesFields {
        ValidatesFields::dispatchUiEvent as dispatchValidatedUiEvent;
    }

    // Shared across screen instances: navigating/remounting must not reuse
    // the previous frame's revision within the same PHP runtime.
    private static int $responseRevision = 0;

    /** NativePHP's SLIDER_CHANGE event type. */
    private const SLIDER_CHANGE_EVENT = 9;

    private bool $leavingScreen = false;

    /**
     * True when nothing is below this screen on PHP's navigation stack.
     * Layouts use it to decide whether the native bar shows a back button.
     */
    public function isRootScreen(): bool
    {
        return $this->nativeRouter?->isRootScreen() ?? true;
    }

    /**
     * Complete a hand-built screen tree, just as view()/fromView() do for
     * Blade. Nested components contribute content, not separate responses.
     */
    protected function response(Element $content): Element
    {
        return NativeElementCollector::inComponentScope()
            ? $content
            : $this->wrapWithChrome($content);
    }

    /**
     * back() republishes this screen once before it is popped. Keeping that
     * frame's revision leaves an unchanged screen byte-identical, so NativePHP
     * drops it: a changed frame reaching iOS mid-pop re-pushes the screen the
     * user just left (a second back transition over a blank page).
     */
    public function back(Transition|string|null $transition = null): static
    {
        $this->leavingScreen = true;

        try {
            return parent::back($transition);
        } finally {
            $this->leavingScreen = false;
        }
    }

    protected function wrapWithChrome(Element $content): Element
    {
        return parent::wrapWithChrome($content)->setProp(
            'firstlight_response_revision',
            $this->leavingScreen ? self::$responseRevision : ++self::$responseRevision,
        );
    }

    /** Rounds a Firstlight Slider's 32-bit native value to its step's precision, then validates as usual. */
    protected function dispatchUiEvent(array $event): void
    {
        if (($event['type'] ?? null) === self::SLIDER_CHANGE_EVENT && isset($this->nativeCallbacks)) {
            $decimals = Slider::changeDecimals($this->nativeCallbacks->kind((int) ($event['callback_id'] ?? 0)));
            if ($decimals !== null) {
                $event['value'] = round((float) ($event['value'] ?? 0.0), $decimals);
            }
        }

        $this->dispatchValidatedUiEvent($event);
    }

    /** Streaming bypasses the revision-bearing Element response. */
    final protected function renderStreaming(): bool
    {
        return false;
    }
}
