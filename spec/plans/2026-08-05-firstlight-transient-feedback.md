---
title: Firstlight Transient Feedback implementation plan
description: Test-first delivery plan for the app-level Feedback Center, paired native root hosts, public events, documentation, showcase, and constitutional evidence.
status: current
sources:
  - spec/designs/2026-08-05-firstlight-transient-feedback-design.md
  - Constitution.md
  - roadmap-v2.md
---

# Firstlight Transient Feedback Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver an app-level Transient Feedback system with FIFO presentation, semantic tones, one optional action, automatic or held lifetime, navigation-safe Laravel events, equal native accessibility, documentation, and installed showcase evidence.

**Architecture:** A process-scoped PHP store and `Feedback` facade publish the complete pending record set through a package-owned nested NativeComponent and chrome sentinel. Paired SwiftUI and Material 3 root hosts retain the visible item, queue position, timer, and completion tombstones across screen navigation while refreshed callback IDs route through the currently mounted package component into application-level Laravel events.

**Tech Stack:** PHP 8.4, Laravel 13 service container/events, Pest 5, NativePHP Mobile 4.0.1, Mobile UI 0.3.0, Swift 6.2 and SwiftUI, Kotlin 2.3 and Material 3 Compose, XCTest/SnapshotTesting, JUnit/Paparazzi, iOS 18+, Android API 29+.

## Global Constraints

- The approved design is `spec/designs/2026-08-05-firstlight-transient-feedback-design.md`; do not broaden or reinterpret it during implementation.
- Public package is `firstlightui/nativephp`; showcase is `firstlightui/showcase`; PHP namespace is `FirstlightUI`; plugin namespace is `Firstlight`.
- The public PHP surface is `FirstlightUI\Facades\Feedback`; there is no public `<firstlight:transient-feedback>` tag and no consumer-authored host.
- Factories are `message`, `success`, `warning`, and `danger`; tones are exactly `default`, `success`, `warning`, and `danger`.
- Each record has one stable ID, one non-empty message, automatic or held lifetime, and zero or one complete action label/key pair.
- FIFO means exactly one visible item. Reconciliation updates a pending ID in place without moving, restarting, or reannouncing it.
- User outcomes dispatch `FeedbackActionPressed` and/or `FeedbackDismissed`; programmatic dismissal dispatches neither.
- Dismiss reasons are exactly `timeout`, `manual`, and `action`. V1 has no swipe dismissal.
- Queue and timer survive native navigation and app backgrounding but not process termination.
- Use only official SuperNative element, nested-component, chrome contributor, callback, plugin-init, and root-host seams. Do not call `Dialog::toast()`, add bridge functions, edit generated hosts, or patch NativePHP.
- The standard component scaffold models in-tree manifest renderers and is not applicable to this app-level root-host service. Do not run `bin/scaffold-component`; create only the files named below.
- Do not add multiple actions, arbitrary seconds, placement, public icons, colours, motion, child content, history, persistence, notification APIs, or a global clear-all facade.
- Keep `roadmap-v2.md` and `roadmap-v3.md` untracked and untouched unless the maintainer separately authorizes them.
- Work locally only. Do not push, publish, tag, or open a PR. Preserve unrelated package and showcase work and stage only task-owned paths.
- Every production change follows RED, verified failure, minimal GREEN, verified pass, refactor, and a focused commit.
- Do not run a simulator, emulator, screenshot capture, accessibility service, or physical-device action without explicit permission for the exact target.

---

### Task 1: Build the PHP feedback domain and facade

**Files:**

- Create: `src/Feedback/FeedbackTone.php`
- Create: `src/Feedback/FeedbackDismissReason.php`
- Create: `src/Feedback/FeedbackRecord.php`
- Create: `src/Feedback/FeedbackStore.php`
- Create: `src/Feedback/PendingFeedback.php`
- Create: `src/Feedback/FeedbackManager.php`
- Create: `src/Facades/Feedback.php`
- Create: `src/Events/FeedbackActionPressed.php`
- Create: `src/Events/FeedbackDismissed.php`
- Create: `tests/Feature/TransientFeedbackApiTest.php`
- Modify: `src/FirstlightServiceProvider.php`

**Interfaces:**

- Produces: `FeedbackTone: string` cases `Default`, `Success`, `Warning`, and `Danger`.
- Produces: `FeedbackDismissReason: string` cases `Timeout`, `Manual`, and `Action`.
- Produces: immutable `FeedbackRecord(string $id, string $message, FeedbackTone $tone, bool $hold, ?string $actionLabel, ?string $actionKey)`.
- Produces: `FeedbackStore::put(FeedbackRecord): string`, `remove(string): ?FeedbackRecord`, `all(): array`, and internal test/lifecycle `reset(): void`.
- Produces: `FeedbackManager::message|success|warning|danger(string): PendingFeedback` and `dismiss(string): bool`.
- Produces: immutable builder methods `PendingFeedback::id(string): self`, `action(string,string): self`, `hold(): self`, and `send(): string`.
- Produces: events with public readonly payloads `FeedbackActionPressed::$id/$actionKey` and `FeedbackDismissed::$id/$reason`.

- [ ] **Step 1: Write the failing API and store tests**

Create `tests/Feature/TransientFeedbackApiTest.php` with direct manager/store coverage before any production classes exist:

```php
<?php

use FirstlightUI\Feedback\FeedbackDismissReason;
use FirstlightUI\Feedback\FeedbackManager;
use FirstlightUI\Feedback\FeedbackStore;
use FirstlightUI\Feedback\FeedbackTone;

it('queues semantic feedback and returns a generated stable id', function () {
    $store = new FeedbackStore;
    $id = (new FeedbackManager($store))->success('Appointment saved')->send();

    expect($id)->toBeString()->not->toBe('')
        ->and($store->all())->toHaveCount(1)
        ->and($store->all()[0]->id)->toBe($id)
        ->and($store->all()[0]->tone)->toBe(FeedbackTone::Success)
        ->and($store->all()[0]->hold)->toBeFalse();
});

it('updates a pending id in place without moving it', function () {
    $store = new FeedbackStore;
    $feedback = new FeedbackManager($store);
    $feedback->message('First')->id('first')->send();
    $feedback->message('Second')->id('second')->send();
    $feedback->warning('Updated')->id('first')->action('Retry', 'retry')->hold()->send();

    expect(array_map(fn ($item) => $item->id, $store->all()))->toBe(['first', 'second'])
        ->and($store->all()[0]->message)->toBe('Updated')
        ->and($store->all()[0]->actionKey)->toBe('retry')
        ->and($store->all()[0]->hold)->toBeTrue();
});

it('removes programmatic feedback without an application event', function () {
    $store = new FeedbackStore;
    $feedback = new FeedbackManager($store);
    $feedback->danger('Connection failed')->id('failure')->send();

    expect($feedback->dismiss('failure'))->toBeTrue()
        ->and($feedback->dismiss('failure'))->toBeFalse()
        ->and($store->all())->toBe([]);
});
```

Add datasets proving all tones, clone-on-write builder behaviour, generated ID uniqueness, exact enums, and rejection of blank messages/IDs/action labels/action keys. Assert action label and key cannot be authored independently by constructing only through the public builder.

- [ ] **Step 2: Run the focused test and verify RED**

```bash
vendor/bin/pest tests/Feature/TransientFeedbackApiTest.php
```

Expected: FAIL because `FirstlightUI\Feedback\FeedbackStore` and related classes do not exist. Bootstrap or syntax errors are not the expected RED.

- [ ] **Step 3: Implement the minimal immutable domain**

Create backed enums and a readonly record:

```php
enum FeedbackTone: string
{
    case Default = 'default';
    case Success = 'success';
    case Warning = 'warning';
    case Danger = 'danger';
}

enum FeedbackDismissReason: string
{
    case Timeout = 'timeout';
    case Manual = 'manual';
    case Action = 'action';
}

final readonly class FeedbackRecord
{
    public function __construct(
        public string $id,
        public string $message,
        public FeedbackTone $tone,
        public bool $hold,
        public ?string $actionLabel,
        public ?string $actionKey,
    ) {}
}
```

Implement `FeedbackStore` as an insertion-ordered `array<string, FeedbackRecord>`. Assignment to an existing key replaces the value without changing PHP array order; `all()` returns `array_values($records)`. `remove()` returns the removed record or `null`.

Implement `PendingFeedback` with private readonly constructor state and clone-on-write methods. Validate with `trim()` but preserve authored message/label/key text. Generate omitted IDs with `Illuminate\Support\Str::uuid()->toString()` only inside `send()`.

- [ ] **Step 4: Implement the manager, facade, events, and container bindings**

`FeedbackManager` creates builders and delegates dismissal to the store. The facade accessor is `FeedbackManager::class`:

```php
/**
 * @method static PendingFeedback message(string $message)
 * @method static PendingFeedback success(string $message)
 * @method static PendingFeedback warning(string $message)
 * @method static PendingFeedback danger(string $message)
 * @method static bool dismiss(string $id)
 */
final class Feedback extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FeedbackManager::class;
    }
}
```

Bind `FeedbackStore` and `FeedbackManager` as singletons in `FirstlightServiceProvider::register()`. Do not add session or disk persistence. Event constructors accept the exact enum/string payloads produced above.

- [ ] **Step 5: Verify GREEN and mutation-sensitive branches**

```bash
vendor/bin/pest tests/Feature/TransientFeedbackApiTest.php
php -l src/Feedback/FeedbackManager.php
php -l src/Feedback/PendingFeedback.php
php -l src/Facades/Feedback.php
git diff --check
```

Expected: focused tests pass. Mutating FIFO order, replacement semantics, any enum value, clone-on-write behaviour, validation branch, generated ID, or dismissal return value must fail a named test.

- [ ] **Step 6: Commit the PHP domain**

```bash
git add src/Feedback src/Facades/Feedback.php src/Events src/FirstlightServiceProvider.php tests/Feature/TransientFeedbackApiTest.php
git commit -m "feat: define Transient Feedback API"
```

---

### Task 2: Publish the app-level SuperNative Feedback Center

**Files:**

- Create: `src/NativeComponents/FeedbackCenter.php`
- Create: `src/Elements/FeedbackCenter.php`
- Create: `src/Elements/FeedbackItem.php`
- Create: `resources/views/native/feedback-center.blade.php`
- Create: `tests/Feature/FeedbackCenterTest.php`
- Modify: `src/FirstlightServiceProvider.php`

**Interfaces:**

- Consumes: `FeedbackStore`, `FeedbackRecord`, `FeedbackDismissReason`, `FeedbackActionPressed`, `FeedbackDismissed`, and `CallbackExpression::appendValue()`.
- Produces: internal sentinel type `firstlight.feedback-center`, observed natively as `firstlight_feedback_center`.
- Produces: internal child type `firstlight.feedback-item`, observed natively as `firstlight_feedback_item`.
- Produces item props: `feedback_id`, `message`, `tone`, `hold`, optional `action_label`, `on_action`, `on_timeout`, and `on_manual`.
- Produces: package child methods `action(string $id, string $key): void` and `dismiss(string $id, string $reason): void`.

- [ ] **Step 1: Write the failing element, callback, and lifecycle tests**

Create a fresh `Illuminate\Container\Container`, set it as the global container, bind `Illuminate\Events\Dispatcher` as both `events` and its contract, run `FirstlightServiceProvider::register()`, and reset `ComponentRegistry` and `ChromeContributorRegistry` around every case. Then build records, render `FeedbackCenter` as a child against a real `CallbackRegistry`, and assert exact tree data:

```php
it('publishes the full fifo queue with package-owned callbacks', function () {
    $store = app(FeedbackStore::class);
    app(FeedbackManager::class)->success('Saved')->id('saved')
        ->action('Undo', 'undo-save')->send();
    app(FeedbackManager::class)->warning('Offline')->id('offline')->hold()->send();

    $tree = renderFeedbackCenter($store);

    expect($tree['type'])->toBe('firstlight.feedback-center')
        ->and(array_column($tree['children'], 'props', 'type'))
        ->and($tree['children'])->toHaveCount(2)
        ->and($tree['children'][0]['props'])->toMatchArray([
            'feedback_id' => 'saved',
            'message' => 'Saved',
            'tone' => 'success',
            'hold' => false,
            'action_label' => 'Undo',
        ])
        ->and($tree['children'][0]['props']['on_action'])->toBeInt()->not->toBe(0)
        ->and($tree['children'][0]['props']['on_timeout'])->toBeInt()->not->toBe(0)
        ->and($tree['children'][1]['props']['on_manual'])->toBeInt()->not->toBe(0);
});
```

Add tests that resolve the callback expressions and prove action dispatch removes the record first, dispatches `FeedbackActionPressed` followed by `FeedbackDismissed(Action)`, and duplicate callbacks do nothing. Prove timeout/manual reasons, malformed reasons fail closed, an updated ID receives fresh callback IDs, and no callback belongs to the consumer screen registry.

- [ ] **Step 2: Run the focused test and verify RED**

```bash
vendor/bin/pest tests/Feature/FeedbackCenterTest.php
```

Expected: FAIL because the Feedback Center native component and internal elements do not exist.

- [ ] **Step 3: Implement internal center and item elements**

`FeedbackCenter` is a child-bearing sentinel with no public props. `FeedbackItem::fromRecord()` copies only the semantic record. Its callback expressions are built safely rather than interpolated:

```php
$action = CallbackExpression::appendValue('action', $record->id);
$action = CallbackExpression::appendValue($action, $record->actionKey);

$timeout = CallbackExpression::appendValue('dismiss', $record->id);
$timeout = CallbackExpression::appendValue($timeout, FeedbackDismissReason::Timeout->value);
```

Register `on_action` only for a complete action pair, `on_timeout` only for automatic items, and `on_manual` only for held items. Override style/layout output so these internal data nodes publish no visual in-tree layout.

- [ ] **Step 4: Implement the package-owned nested NativeComponent**

Register tag `firstlight-feedback-center` through `ComponentRegistry`. `FeedbackCenter::render()` builds the sentinel and appends one item per `FeedbackStore::all()` record. Because it returns a programmatic element from child scope, NativePHP pins every descendant callback to the child registry.

Implement callback methods in this order:

```php
public function action(string $id, string $key): void
{
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

public function dismiss(string $id, string $reason): void
{
    $dismissReason = FeedbackDismissReason::tryFrom($reason);
    if ($dismissReason === null || $dismissReason === FeedbackDismissReason::Action) {
        return;
    }

    if (app(FeedbackStore::class)->remove($id) !== null) {
        event(new FeedbackDismissed($id, $dismissReason));
    }
}
```

If the first event listener throws, the `finally` dispatch still runs and listener failures are not swallowed.

- [ ] **Step 5: Register the automatic chrome contributor**

Load package views under `firstlight`. Create `resources/views/native/feedback-center.blade.php` containing only:

```blade
<native:firstlight-feedback-center key="firstlight-feedback-center" />
```

In `boot()`, register a `ChromeContributorRegistry` callback that always renders this partial through `$renderPartial`. Always publishing the empty sentinel keeps the nested component mounted for a screen and gives native hosts an explicit empty-queue reconciliation frame. Do not require a layout trait or consumer view tag.

- [ ] **Step 6: Verify callbacks are navigation-refreshable and GREEN**

```bash
vendor/bin/pest tests/Feature/FeedbackCenterTest.php tests/Feature/TransientFeedbackApiTest.php
php -l src/NativeComponents/FeedbackCenter.php
php -l src/Elements/FeedbackCenter.php
php -l src/Elements/FeedbackItem.php
git diff --check
```

Expected: tests prove a second render of the same semantic IDs publishes new non-zero callback IDs while record order remains stable.

- [ ] **Step 7: Commit the SuperNative publication layer**

```bash
git add src/NativeComponents/FeedbackCenter.php src/Elements/FeedbackCenter.php src/Elements/FeedbackItem.php resources/views/native/feedback-center.blade.php src/FirstlightServiceProvider.php tests/Feature/FeedbackCenterTest.php
git commit -m "feat: publish app-level Feedback Center"
```

---

### Task 3: Implement the SwiftUI queue and root host

**Files:**

- Create: `resources/ios/FeedbackCenterState.swift`
- Create: `resources/ios/FeedbackCenterControl.swift`
- Create: `resources/ios/FeedbackCenterHost.swift`
- Create: `resources/ios/FirstlightUIInit.swift`
- Create: `tests/ios/FeedbackCenterTests.swift`
- Create: `tests/ios/FeedbackCenterSnapshotTests.swift`
- Modify: `nativephp.json`

**Interfaces:**

- Consumes wire item props and standard callback IDs from Task 2.
- Produces: `FeedbackCenterItemConfiguration`, `FeedbackCenterQueueState`, and `FeedbackCenterWireEvent.press(callbackID:nodeID:)`.
- Produces: deterministic `reconcile(_:)`, `action()`, `timeout()`, `manualDismiss()`, `advance(by:)`, `pause(at:)`, and `resume(at:)` state transitions.
- Produces: `FirstlightFeedbackCenterHost` and init function `registerFirstlightUI()` consuming `firstlight_feedback_center`.

- [ ] **Step 1: Invoke the required Apple component skill**

Read and follow `.agents/skills/firstlight-ios-component/SKILL.md`. Recheck the approved design, `Package.swift`, current NativePHP `NativeRootHostRegistry`, `NativeUIBridge.sendPressEvent`, SwiftUI Material/Button/accessibility APIs, scene phase, Reduce Motion, and current iOS availability. Record any contract blocker before editing production Swift.

- [ ] **Step 2: Write failing pure-state XCTest cases**

Create tests using fixture nodes with explicit semantic IDs and callbacks:

```swift
func testReconcilePreservesFIFOAndRefreshesCallbacksWithoutRestarting() {
    var state = FeedbackCenterQueueState(now: 100)
    state.reconcile([item(id: "one", action: 11), item(id: "two", timeout: 22)], now: 100)
    state.advance(by: 1)

    state.reconcile([item(id: "one", action: 101), item(id: "two", timeout: 202)], now: 101)

    XCTAssertEqual(state.visible?.feedbackID, "one")
    XCTAssertEqual(state.visible?.actionCallback, 101)
    XCTAssertEqual(state.pendingIDs, ["two"])
    XCTAssertEqual(state.elapsed, 1)
}

func testCompletionTombstoneBlocksStaleFramesUntilAbsence() {
    var state = FeedbackCenterQueueState(now: 0)
    state.reconcile([item(id: "one"), item(id: "two")], now: 0)
    XCTAssertEqual(state.timeout(), .press(callbackID: 12, nodeID: 1))
    XCTAssertEqual(state.visible?.feedbackID, "two")

    state.reconcile([item(id: "one"), item(id: "two")], now: 1)
    XCTAssertEqual(state.pendingIDs, [])
    state.reconcile([item(id: "two")], now: 2)
    state.reconcile([item(id: "one"), item(id: "two")], now: 3)
    XCTAssertEqual(state.pendingIDs, ["one"])
}
```

Add cases for in-place copy/tone update, programmatic absence, duplicate action presses, hold/manual dismissal, automatic timeout, background pause/resume, missing callbacks fail closed, and queue reset on an empty center.

- [ ] **Step 3: Run XCTest compilation and verify RED**

```bash
swift build --build-tests --triple arm64-apple-ios18.0-simulator --sdk /Applications/Xcode.app/Contents/Developer/Platforms/iPhoneSimulator.platform/Developer/SDKs/iPhoneSimulator26.5.sdk
```

Expected RED is compilation failure for the missing Feedback Center types, not an unrelated destination or bootstrap error.

- [ ] **Step 4: Implement pure configuration, timing, queue, and tombstones**

Decode `feedback_id`, `message`, `tone`, `hold`, `action_label`, and callback IDs defensively. An automatic item is eligible only with `on_timeout != 0`; a held item only with `on_manual != 0`; an action is visible only when both label and callback exist.

Use semantic IDs as reconciliation identity. Maintain ordered configurations, one current ID, elapsed/remaining time, a paused flag, and tombstones. Completion returns one standard press event, advances immediately, and ignores repeats. When a tombstoned ID is absent from a publication, release its tombstone so a later absent-then-present epoch can enqueue it again.

Define automatic base time as a renderer policy helper with a 4-second minimum, 10-second message cap, and an action extension. Accept `assistiveTechnologyActive` in the helper so tests can prove the accessible extension without reading global UIKit state.

- [ ] **Step 5: Write failing control and accessibility snapshots**

Add light/dark snapshots for default, success with action, warning hold, danger, long copy, and accessibility text size. Add construction assertions that decorative symbols are hidden, action/dismiss labels are authored, held controls expose dismiss, and automatic message-only feedback has no inert button. Keep `FIRSTLIGHT_RECORD_SNAPSHOTS` disabled during RED.

- [ ] **Step 6: Implement the SwiftUI control and host**

Build a bottom-safe-area material notice with native `Text`, `Button`, and a held-item dismiss `Button`. Use system semantic symbols internally and hide them from accessibility. Reflow actions below text at constrained or accessibility sizes. Use theme/system colours, not public styling props.

The host owns one queue state for the lifetime of the root wrapper, reconciles on every center-node publication, schedules only the current automatic item, cancels timing on background, and resumes remaining time on foreground. Pause while the feedback action/dismiss control is accessibility-focused. Post one polite `AccessibilityNotification.Announcement` only when the visible semantic ID changes. Reduced Motion selects opacity-only presentation.

Wire action, timeout, and manual completion through `NativeUIBridge.sendPressEvent(callbackID,nodeId:)`.

- [ ] **Step 7: Register the iOS root host and manifest init**

Implement:

```swift
func registerFirstlightUI() {
    NativeRootHostRegistry.shared.register(
        "firstlight.feedback-center",
        consumes: "firstlight_feedback_center"
    ) { root, content in
        let center = root.children.first { $0.type == "firstlight_feedback_center" }
        return AnyView(FirstlightFeedbackCenterHost(centerNode: center) { content })
    }
}
```

Set `nativephp.json` `ios.init_function` to `registerFirstlightUI`. Add a manifest test proving the exact function and absence of `bridge_functions` owned by this feature.

- [ ] **Step 8: Verify iOS GREEN without a simulator**

```bash
swift build --build-tests --triple arm64-apple-ios18.0-simulator --sdk /Applications/Xcode.app/Contents/Developer/Platforms/iPhoneSimulator.platform/Developer/SDKs/iPhoneSimulator26.5.sdk
vendor/bin/pest tests/Feature/PluginManifestTest.php
git diff --check
```

Expected: production and test sources compile. Do not execute XCTest or record snapshots without an authorized simulator.

- [ ] **Step 9: Commit the iOS implementation**

```bash
git add resources/ios/FeedbackCenterState.swift resources/ios/FeedbackCenterControl.swift resources/ios/FeedbackCenterHost.swift resources/ios/FirstlightUIInit.swift tests/ios/FeedbackCenterTests.swift tests/ios/FeedbackCenterSnapshotTests.swift nativephp.json tests/Feature/PluginManifestTest.php
git commit -m "feat: render Transient Feedback on iOS"
```

---

### Task 4: Implement the Material 3 queue and root host

**Files:**

- Create: `resources/android/FeedbackCenterState.kt`
- Create: `resources/android/FeedbackCenterControl.kt`
- Create: `resources/android/FeedbackCenterHost.kt`
- Create: `resources/android/FirstlightUIInit.kt`
- Create: `tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/FeedbackCenterTest.kt`
- Modify: `nativephp.json`
- Modify only if the production import requires it: `tests/android/build.gradle.kts`

**Interfaces:**

- Consumes the Task 2 wire contract and Task 3 semantic state contract.
- Produces: `FeedbackCenterItemConfiguration`, `FeedbackCenterQueueState`, `FeedbackCenterWireEvent.Press`, and `FirstlightFeedbackCenterHost`.
- Produces init function `dev.firstlightui.plugins.firstlight_ui.registerFirstlightUI(Context)` consuming `firstlight_feedback_center`.

- [ ] **Step 1: Invoke the required Android component skill**

Read and follow `.agents/skills/firstlight-android-component/SKILL.md`. Recheck the approved design, production/test source sync, NativePHP root-host registry, Material 3 Snackbar APIs, `AccessibilityManager.calculateRecommendedTimeoutMillis`, lifecycle observers, focus, live-region semantics, and API 29 compatibility.

- [ ] **Step 2: Write failing Kotlin queue tests**

Mirror the Swift contract with literal Kotlin assertions:

```kotlin
@Test
fun `reconciliation preserves fifo and refreshes callbacks`() {
    val state = FeedbackCenterQueueState(nowMillis = 100_000)
    state.reconcile(listOf(item("one", action = 11), item("two", timeout = 22)), 100_000)
    state.advanceBy(1_000)

    state.reconcile(listOf(item("one", action = 101), item("two", timeout = 202)), 101_000)

    assertEquals("one", state.visible?.feedbackId)
    assertEquals(101, state.visible?.actionCallback)
    assertEquals(listOf("two"), state.pendingIds)
    assertEquals(1_000, state.elapsedMillis)
}
```

Add the same tombstone, absent epoch, programmatic cancellation, hold, timeout, pause/resume, malformed callback, copy-only reconciliation, and exactly-once cases as iOS.

- [ ] **Step 3: Run Android tests and verify RED**

```bash
JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home tests/android/gradlew -p tests/android testDebugUnitTest --tests '*FeedbackCenter*'
```

Expected: FAIL because Feedback Center production types do not exist.

- [ ] **Step 4: Implement the pure Kotlin state contract**

Use the same wire names, eligibility rules, FIFO identity, callback refresh, elapsed-time preservation, tombstone release, and standard press event as Swift. Keep time injected as `Long` milliseconds so no unit test sleeps. The production host converts the automatic base to the Android accessibility manager's recommended timeout with text/icon/control flags.

- [ ] **Step 5: Write failing semantics and Paparazzi cases**

Add tests proving one polite live-region pane, full message, one optional action, held dismiss action, semantic tone, no decorative icon description, 48-dp controls, and no inert callbacks. Add light/dark/default/success/warning/danger, action, hold, long-copy, font-scale-two, and RTL Paparazzi cases. Do not record goldens during RED.

- [ ] **Step 6: Implement the Material 3 control and lifecycle host**

Render one Material 3 `Snackbar` in a root `SnackbarHost` layer above content and system insets. Supply a single optional action and a held dismiss action. Use semantic theme colours and internal tone icons without exposing a public icon API.

Use `AccessibilityManager.calculateRecommendedTimeoutMillis()` for automatic timing. Pause the state while the host activity lifecycle is below `RESUMED`; resume remaining time when it returns. Pause while action or dismiss owns Compose focus, and use the recommended accessible timeout for message-only TalkBack announcements. Mark only the newly visible item as a polite live region and key announcement state by semantic ID.

- [ ] **Step 7: Register the Android root host and manifest init**

Implement:

```kotlin
fun registerFirstlightUI(context: Context) {
    NativeRootHostRegistry.register(
        "firstlight.feedback-center",
        consumes = "firstlight_feedback_center",
    ) { root, content ->
        val center = root.children.firstOrNull { it.type == "firstlight_feedback_center" }
        FirstlightFeedbackCenterHost(centerNode = center, content = content)
    }
}
```

The `context` argument is accepted because NativePHP's generated plugin init call supplies it. Set `nativephp.json` `android.init_function` to `dev.firstlightui.plugins.firstlight_ui.registerFirstlightUI`.

- [ ] **Step 8: Verify Android GREEN and record test goldens only after behaviour passes**

```bash
JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home tests/android/gradlew -p tests/android compileDebugKotlin
JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home tests/android/gradlew -p tests/android testDebugUnitTest --tests '*FeedbackCenter*'
JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home tests/android/gradlew -p tests/android recordPaparazziDebug
JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home tests/android/gradlew -p tests/android verifyPaparazziDebug
vendor/bin/pest tests/Feature/PluginManifestTest.php
git diff --check
```

Inspect every newly recorded golden before accepting it. Re-run the focused tests after recording.

- [ ] **Step 9: Commit the Android implementation**

```bash
git add resources/android/FeedbackCenterState.kt resources/android/FeedbackCenterControl.kt resources/android/FeedbackCenterHost.kt resources/android/FirstlightUIInit.kt tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/FeedbackCenterTest.kt tests/android/src/test/snapshots nativephp.json tests/Feature/PluginManifestTest.php tests/android/build.gradle.kts
git commit -m "feat: render Transient Feedback on Android"
```

---

### Task 5: Publish the maintained contract and public guide

**Files:**

- Create: `spec/components/transient-feedback.md`
- Create: `docs/components/transient-feedback.md`
- Create: `bin/check-transient-feedback`
- Modify: `spec/index.md`
- Modify: `docs/index.md`
- Modify: `spec/screenshots.json`
- Regenerate: `llms.txt`
- Regenerate: `llms-full.txt`
- Create: `tests/Feature/TransientFeedbackDocumentationTest.php`

**Interfaces:**

- Consumes the complete public PHP, event, wire, platform, accessibility, and evidence contracts from Tasks 1-4.
- Produces screenshot key `transient-feedback`, route `/captures/transient-feedback`, and four standard output paths.
- Produces dedicated structural gate `bin/check-transient-feedback [--development]` for this service-backed feature.

- [ ] **Step 1: Invoke the documentation skills and write failing structure tests**

Read and follow `.agents/skills/firstlight-docs-write/SKILL.md` and `.agents/skills/firstlight-docs-update/SKILL.md`. Before writing docs, add a Pest test asserting both pages are indexed, the screenshot registration exists, the facade/events are named literally, and no public tag is documented.

```php
it('indexes the service-backed Transient Feedback contract', function () {
    $public = file_get_contents(__DIR__.'/../../docs/components/transient-feedback.md');
    $spec = file_get_contents(__DIR__.'/../../spec/components/transient-feedback.md');

    expect($public)->toContain('FirstlightUI\\Facades\\Feedback')
        ->toContain('FeedbackActionPressed')
        ->toContain('FeedbackDismissed')
        ->not->toContain('<firstlight:transient-feedback')
        ->and($spec)->toContain('firstlight.feedback-center');
});
```

- [ ] **Step 2: Run documentation tests and verify RED**

```bash
vendor/bin/pest tests/Feature/TransientFeedbackDocumentationTest.php
```

Expected: FAIL because the guide, maintained component contract, and screenshot registration do not exist.

- [ ] **Step 3: Write the maintained component contract and consumer guide**

The spec records ownership, primitive audit, exact facade/builder/event APIs, callback wire props, navigation reconciliation, tombstones, timing, accessibility, platform expression, failures, exclusions, and evidence boundary.

The public guide starts with `Feedback::success(...)->action(...)->send()`, explains application event listeners, all tones, hold, explicit/programmatic dismissal, FIFO/update semantics, navigation/background/process lifecycle, accessibility, and exclusions. It must explicitly say there is no Blade tag or host installation step.

- [ ] **Step 4: Add the dedicated structural gate**

`bin/check-transient-feedback` must verify the PHP domain/facade/events, nested component/elements, both native state/control/host/init triples, platform tests, exact manifest init functions, no `bridge_functions` addition, indexed docs/spec pages, screenshot manifest entry, generated docs freshness, and absence of `Dialog::toast`/`nativephp_call` in feature sources. In non-development mode it also requires four screenshots and `spec/reviews/transient-feedback-alpha.md` with no open checklist rows.

- [ ] **Step 5: Register screenshots and regenerate artifacts**

Add:

```json
"transient-feedback": {
    "route": "/captures/transient-feedback",
    "test": "php artisan test tests/Feature/TransientFeedbackCaptureTest.php",
    "outputs": {
        "ios-light": "docs/screenshots/transient-feedback/ios-light.png",
        "ios-dark": "docs/screenshots/transient-feedback/ios-dark.png",
        "android-light": "docs/screenshots/transient-feedback/android-light.png",
        "android-dark": "docs/screenshots/transient-feedback/android-dark.png"
    }
}
```

Run `bin/build-docs-artifacts`; do not hand-edit `llms.txt` or `llms-full.txt`.

- [ ] **Step 6: Verify documentation GREEN**

```bash
vendor/bin/pest tests/Feature/TransientFeedbackDocumentationTest.php
bin/check-docs --development
bin/check-transient-feedback --development
bin/build-docs-artifacts
git diff --check
```

Expected: all commands pass and a second artifact build produces no diff.

- [ ] **Step 7: Commit documentation and structural gates**

```bash
git add spec/components/transient-feedback.md docs/components/transient-feedback.md bin/check-transient-feedback spec/index.md docs/index.md spec/screenshots.json llms.txt llms-full.txt tests/Feature/TransientFeedbackDocumentationTest.php
git commit -m "docs: add Transient Feedback guide"
```

---

### Task 6: Dogfood the installed package in the sibling showcase

**Files in `../firstlight-showcase`:**

- Create: `app/NativeComponents/TransientFeedbackShowcase.php`
- Create: `app/NativeComponents/Captures/TransientFeedbackCapture.php`
- Create: `app/NativeComponents/TransientFeedbackDestination.php`
- Create: `app/Support/ShowcaseFeedbackLog.php`
- Create: `resources/views/native/transient-feedback-showcase.blade.php`
- Create: `resources/views/native/transient-feedback-destination.blade.php`
- Create: `resources/views/native/captures/transient-feedback.blade.php`
- Create: `tests/Feature/TransientFeedbackShowcaseTest.php`
- Create: `tests/Feature/TransientFeedbackCaptureTest.php`
- Modify: `app/NativeComponents/ShowcaseHome.php`
- Modify: `app/NativeComponents/ShowcaseScreen.php`
- Modify: `app/Providers/NativeServiceProvider.php`
- Modify: `routes/native.php`

**Interfaces:**

- Consumes: installed `FirstlightUI\Facades\Feedback`, both application events, and `FeedbackStore` for test isolation only.
- Produces: interactive `/transient-feedback`, navigation target `/transient-feedback/destination`, and deterministic `/captures/transient-feedback`.

- [ ] **Step 1: Inspect status and invoke showcase/documentation screenshot guidance**

Run `git -C ../firstlight-showcase status --short --branch` and preserve adjacent work. Read `.agents/skills/firstlight-docs-screenshots/SKILL.md` for fixture rules, but do not run a device or capture command.

- [ ] **Step 2: Write failing showcase and capture tests**

Test that the home registration uses exact tag `Transient Feedback`, the interactive methods publish every tone, one action, hold, three-item FIFO, duplicate-ID update, programmatic removal, and a held item before navigation. Test that the event log receives action and dismissal event payloads. Test that the capture route publishes one deterministic held success item with action so it cannot expire during capture.

```php
it('queues the fifo demonstration in authored order', function () {
    native(TransientFeedbackShowcase::class)
        ->call('queueThree');

    expect(array_map(
        fn ($item) => $item->message,
        app(FeedbackStore::class)->all(),
    ))->toBe(['First queued', 'Second queued', 'Third queued']);
});
```

- [ ] **Step 3: Run focused tests and verify RED**

```bash
php artisan test tests/Feature/TransientFeedbackShowcaseTest.php tests/Feature/TransientFeedbackCaptureTest.php
```

Expected: FAIL because the routes and showcase components do not exist.

- [ ] **Step 4: Implement the interactive gallery and event log**

Add buttons for all approved states and lifecycle operations. `ShowcaseFeedbackLog` stores only the most recent event summary for the current process. Register synchronous listeners in `NativeServiceProvider` for `FeedbackActionPressed` and `FeedbackDismissed`; the parent screen's next render reads the log and shows the latest outcome.

The navigation demonstration sends a held feedback ID, then navigates to the dedicated destination. The destination can programmatically dismiss that ID and shows the shared event log. Do not change `NATIVEPHP_START_URL=/` or shared appearance/navigation ownership.

- [ ] **Step 5: Implement the deterministic capture fixture**

`TransientFeedbackCapture::mount()` sends one held `success` item with message `Appointment saved`, action label `Undo`, fixed ID `transient-feedback-capture`, and action key `undo-save`. The fixture body supplies stable neutral content behind the app-level feedback surface. Its tests reset the package store before and after each case.

- [ ] **Step 6: Install the exact package revision and verify without devices**

After the package commits exist, refresh the path dependency so `composer.lock` points at the exact latest package revision. Then run:

```bash
composer validate --strict
php artisan test tests/Feature/TransientFeedbackShowcaseTest.php tests/Feature/TransientFeedbackCaptureTest.php tests/Feature/ShowcaseNavigationTest.php
php artisan test
php artisan native:plugin:validate /Users/wojt/Code/clinically-au/firstlight-ui
git diff --check
```

Expected: focused and full showcase tests pass. Plugin validation may report the established UI-only warning but must recognize both init functions and current package sources.

- [ ] **Step 7: Commit only showcase-owned paths**

```bash
git add app/NativeComponents/TransientFeedbackShowcase.php app/NativeComponents/Captures/TransientFeedbackCapture.php app/NativeComponents/TransientFeedbackDestination.php app/Support/ShowcaseFeedbackLog.php resources/views/native/transient-feedback-showcase.blade.php resources/views/native/transient-feedback-destination.blade.php resources/views/native/captures/transient-feedback.blade.php tests/Feature/TransientFeedbackShowcaseTest.php tests/Feature/TransientFeedbackCaptureTest.php app/NativeComponents/ShowcaseHome.php app/NativeComponents/ShowcaseScreen.php app/Providers/NativeServiceProvider.php routes/native.php composer.lock
git commit -m "feat: showcase Transient Feedback"
```

---

### Task 7: Run complete off-device verification and constitutional review

**Files:**

- Create: `spec/reviews/transient-feedback-development.md`
- Modify only if evidence changes: `spec/components/transient-feedback.md`

**Interfaces:**

- Consumes all package and showcase commits and current generated-host source checksums.
- Produces an article-by-article review with separate implementation, development, component-release, and catalogue verdicts.

- [ ] **Step 1: Invoke the required review and verification skills**

Read and follow `.agents/skills/firstlight-review-component/SKILL.md` and `superpowers:verification-before-completion`. Re-read the design, this plan, Constitution, current status in both repositories, and exact diffs. Do not infer evidence from earlier task output.

- [ ] **Step 2: Run fresh package gates**

```bash
composer validate --strict
vendor/bin/pest tests/Feature/TransientFeedbackApiTest.php tests/Feature/FeedbackCenterTest.php tests/Feature/TransientFeedbackDocumentationTest.php tests/Feature/PluginManifestTest.php
composer test
bin/check-transient-feedback --development
bin/check-docs --development
bin/build-docs-artifacts
swift build --build-tests --triple arm64-apple-ios18.0-simulator --sdk /Applications/Xcode.app/Contents/Developer/Platforms/iPhoneSimulator.platform/Developer/SDKs/iPhoneSimulator26.5.sdk
JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home tests/android/gradlew -p tests/android compileDebugKotlin
JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home tests/android/gradlew -p tests/android testDebugUnitTest
JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home tests/android/gradlew -p tests/android verifyPaparazziDebug
git diff --check
```

Record exact counts, exit codes, warnings, and skipped evals.

- [ ] **Step 3: Run fresh consumer and host-build gates without launch**

In `../firstlight-showcase`:

```bash
composer validate --strict
php artisan test
php artisan native:plugin:validate /Users/wojt/Code/clinically-au/firstlight-ui
php artisan native:run android --build=bundle --no-tty
php artisan native:build --simulated --no-tty
git diff --check
```

Compare generated iOS and Android Feedback Center source checksums to package sources. Generated trees remain read-only evidence.

- [ ] **Step 4: Write the honest development review**

Write `spec/reviews/transient-feedback-development.md` with primitive audit, RED evidence, exact commands, generated-host checksums, queue/action/dismiss/navigation/background evidence, Article I-XI verdicts, warnings, and remaining runtime rows. Mark simulator execution, screenshots, VoiceOver, TalkBack, physical-device, and any inaccessible host behaviour BLOCKED unless explicitly authorized and observed.

- [ ] **Step 5: Verify the review and commit it**

```bash
bin/check-transient-feedback --development
bin/check-docs --development
git diff --check
git add spec/reviews/transient-feedback-development.md spec/components/transient-feedback.md
git commit -m "docs: review Transient Feedback development"
```

Do not stage either roadmap file and do not claim component or catalogue release readiness.

---

### Task 8: Collect authorized runtime, screenshot, and accessibility evidence

**Files:**

- Create only after capture approval: `docs/screenshots/transient-feedback/ios-light.png`
- Create only after capture approval: `docs/screenshots/transient-feedback/ios-dark.png`
- Create only after capture approval: `docs/screenshots/transient-feedback/android-light.png`
- Create only after capture approval: `docs/screenshots/transient-feedback/android-dark.png`
- Modify: `spec/reviews/transient-feedback-development.md`

**Interfaces:**

- Consumes an explicitly authorized fixed iOS target and Android target.
- Produces dated execution, interaction, screenshot, and accessibility evidence; it does not by itself satisfy physical-device release gates.

- [ ] **Step 1: Stop and request exact target authorization**

List available simulators/emulators read-only if permitted, then ask for the exact iOS simulator/device and Android emulator/device. Do not boot, install, launch, change appearance, run XCTest, or capture before approval.

- [ ] **Step 2: Execute focused native tests on authorized targets**

Run only the Feedback Center XCTest suite on the approved iOS target. Build, install, launch, and readiness-check both generated showcase hosts. Preserve and restore appearance, Reduced Motion/animator scales, app foreground state, and configured start route through the guarded workflow.

- [ ] **Step 3: Exercise the complete runtime contract**

Observe FIFO order, message-only timeout, action plus action-dismiss event order, held explicit dismissal, duplicate suppression, update-in-place, programmatic removal, navigation survival, background pause/resume, process restart discard, rapid queueing, long copy, scaling, RTL, dark mode, contrast, and Reduced Motion on both platforms.

- [ ] **Step 4: Capture and inspect the four-image matrix**

Use `bin/capture-doc-screenshots TransientFeedback` with `--showcase=../firstlight-showcase` and the exact approved UDID and Android serial. Record the literal executed command in the evidence report; never leave symbolic target values in reported evidence.

Inspect every image for full viewport, correct appearance, native platform expression, message/action/dismiss hierarchy, clipping, accidental data, and capture stability. Obtain explicit maintainer approval before treating images as evidence.

- [ ] **Step 5: Record accessibility and remaining blockers**

Manually verify VoiceOver and TalkBack announcement order, non-repetition, action/dismiss labels and focus, timer pause or accessible extension, 44-point/48-dp targets, scaling, contrast, RTL, and Reduced Motion. Keep physical-device rows blocked unless performed on physical devices.

- [ ] **Step 6: Re-run final gates and commit evidence**

Run the complete package and showcase gates again from the committed implementations, then:

```bash
git add docs/screenshots/transient-feedback spec/reviews/transient-feedback-development.md
git commit -m "docs: record Transient Feedback runtime evidence"
```

Update `roadmap-v2.md` only under separate maintainer authorization and only if its strict delivery rule is truthfully satisfied.
