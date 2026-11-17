<?php

namespace FirstlightUI;

use FirstlightUI\Concerns\AuthorizesActions;
use FirstlightUI\Concerns\DestroysListItems;
use FirstlightUI\Concerns\PaginatesLists;
use FirstlightUI\Concerns\SubmitsForms;
use FirstlightUI\Concerns\ValidatesFields;
use FirstlightUI\Elements\Slider;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NavigationIntent;
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

    /** NativePHP's SHEET_DISMISS event type, which Bottom Sheet and Modal `@dismiss` use. */
    private const SHEET_DISMISS_EVENT = 14;

    private bool $leavingScreen = false;

    /** @var \WeakReference<self>|null The screen whose frame is being serialized, which overlays report to. */
    private static ?\WeakReference $recordingScreen = null;

    /**
     * Whether each Bottom Sheet and Modal, by its dismiss callback, was open
     * in this screen's last frame. After a deep link, one inside an unchanged
     * #[Memo] child is missing: its subtree isn't serialized again.
     *
     * @var array<int, bool>
     */
    private array $overlaysOpen = [];

    private bool $dismissingOverlays = false;

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
        if ($this->nativeParentComponent === null) {
            $this->overlaysOpen = [];
            self::$recordingScreen = \WeakReference::create($this);
        }

        return parent::wrapWithChrome($content)->setProp(
            'firstlight_response_revision',
            $this->leavingScreen ? self::$responseRevision : ++self::$responseRevision,
        );
    }

    /**
     * A screen that opens another stays under it on the stack, with the same
     * state. Its farewell frame closes its open Bottom Sheets and Modals, but
     * their bound visibility would still be `true`, so going back showed them
     * again. Running each one's `@dismiss` lets the app set it to `false`, as
     * Android's navigation does with dialogs. To show one again on return,
     * set its property in onResume().
     */
    public function navigate(string $uri, array $data = [], Transition|string|null $transition = null): static
    {
        parent::navigate($uri, $data, $transition);

        if ($this->nativeParentComponent === null) {
            $this->dismissOverlaysLeftOpen();
        }

        return $this;
    }

    /** Deep links and app shortcuts end the loop with a navigation too, without navigate(). */
    public function runLoop(): void
    {
        parent::runLoop();

        $this->dismissOverlaysLeftOpen();
    }

    /** @internal Bottom Sheet and Modal report whether they are open as they serialize. */
    public static function recordOverlay(int $dismissCallbackId, bool $open): void
    {
        $screen = self::$recordingScreen?->get();

        if ($dismissCallbackId !== 0 && $screen !== null) {
            $screen->overlaysOpen[$dismissCallbackId] = $open;
        }
    }

    private function dismissOverlaysLeftOpen(): void
    {
        $intent = $this->getNavigationIntent();

        // A dismiss handler that navigates would come back here for the same overlays.
        if ($this->dismissingOverlays || $intent?->type !== NavigationIntent::NAVIGATE) {
            return;
        }

        $dismissals = array_keys(array_filter($this->overlaysOpen));
        $this->overlaysOpen = [];
        $this->dismissingOverlays = true;

        try {
            foreach ($dismissals as $callbackId) {
                try {
                    $this->dispatchUiEvent(['type' => self::SHEET_DISMISS_EVENT, 'callback_id' => $callbackId]);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        } finally {
            $this->dismissingOverlays = false;
        }

        // The screen is already leaving for this destination.
        $this->setNavigationIntent($intent);
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
