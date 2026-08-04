---
title: Firstlight Activity Indicator implementation plan
description: Test-first delivery plan for the strict Activity Indicator contract, paired renderers, showcase, documentation, and constitutional evidence.
status: current
sources:
  - spec/designs/2026-08-05-firstlight-activity-indicator-design.md
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
---

# Firstlight Activity Indicator Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a development-proven `<firstlight:activity-indicator>` with a strict eventless API, genuine circular native indicators, one polite announcement per appearance, complete documentation, and installed showcase evidence.

**Architecture:** A Firstlight-owned `firstlight.activity-indicator` EDGE element validates and publishes only semantic size and accessibility name. Paired SwiftUI and Material 3 renderers own animation and mount-scoped announcement behaviour without callbacks or bridge calls. The sibling showcase proves conditional presence, all sizes, accessibility, and deterministic capture composition.

**Tech Stack:** PHP 8.4, Laravel 13 Blade, Pest 5, NativePHP Mobile 4.0.1, Mobile UI 0.3.0, Swift 6.2 and SwiftUI, Kotlin 2.3 and Material 3 Compose, XCTest/SnapshotTesting, JUnit/Paparazzi, iOS 18+, Android API 29+.

## Global Constraints

- Public package is `firstlightui/nativephp`; showcase is `firstlightui/showcase`; PHP namespace is `FirstlightUI`; Blade prefix is `firstlight`; plugin namespace is `Firstlight`.
- Public API is the self-closing `<firstlight:activity-indicator>` and element type is `firstlight.activity-indicator`.
- Supported props are `size="sm|md|lg"` with `md` default, required non-empty `a11y-label` with accepted `a11yLabel` alias, and external `class` only.
- Presence means active. Do not add `wire:loading`, `loading`, `active`, `visible`, hidden-node state, or automatic request tracking.
- Reject visible label/slot content, `a11y-hint`, values, models, events, disabled state, colour/tone/variant, arbitrary style props, legacy integer sizes, and long-form size aliases.
- iOS uses SwiftUI `ProgressView`; Android uses Material 3 `CircularProgressIndicator`. Shared sizes remain semantic and do not promise identical geometry.
- Appearance politely announces the authored label once per mount without moving focus. Reconciliation of the same mounted node does not repeat it; a later remount announces again.
- Use the official SuperNative Element Tree and renderer seams. Do not add JSON bridges, WebViews, PHP timers, custom animation loops, generated-tree edits, or a Mobile UI adapter declaration.
- Keep `roadmap-v2.md` and `roadmap-v3.md` untracked and untouched unless the maintainer separately asks to commit them.
- Work locally only. Do not push, publish, tag, or open a PR. Preserve unrelated changes and stage only task-owned paths.
- Every production change follows RED, verified failure, minimal GREEN, verified pass, refactor, and a focused commit.
- Do not run a simulator, emulator, screenshot capture, or physical-device action without explicit permission for the exact target.

---

### Task 1: Publish the strict PHP and EDGE contract

**Files:**

- Create before scaffolding: `tests/Feature/ActivityIndicatorPublicContractTest.php`
- Create via scaffold and replace: `tests/Feature/ActivityIndicatorElementTest.php`
- Create via scaffold and implement: `src/Components/ActivityIndicator.php`
- Create via scaffold and implement: `src/Elements/ActivityIndicator.php`
- Modify: `src/FirstlightTagPrecompiler.php`
- Generated but left unstaged until later tasks: `resources/ios/ActivityIndicatorControl.swift`, `resources/ios/ActivityIndicatorRenderer.swift`, `resources/android/ActivityIndicatorControl.kt`, `resources/android/ActivityIndicatorRenderer.kt`, `tests/ios/ActivityIndicatorSnapshotTests.swift`, `tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/ActivityIndicatorTest.kt`, `docs/components/activity-indicator.md`

**Interfaces:**

- Produces: `FirstlightUI\Components\ActivityIndicator::elementType(): 'firstlight.activity-indicator'`.
- Produces: `FirstlightUI\Elements\ActivityIndicator` with `size(string)` and `a11yLabel(string)` authoring methods.
- Produces Element Tree props exactly `['size' => 'sm|md|lg', 'a11y_label' => string]`; no callbacks, style props, or sync metadata.

- [ ] **Step 1: Write the public-tag RED test before scaffolding**

Create `tests/Feature/ActivityIndicatorPublicContractTest.php` with the collector setup used by `ProgressElementTest.php`. Register the future class string and assert the real public tag cannot yet publish:

```php
<?php

use FirstlightUI\Elements\ActivityIndicator;
use FirstlightUI\FirstlightTagPrecompiler;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

beforeEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    ElementRegistry::register('firstlight.activity-indicator', ActivityIndicator::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

it('publishes the default activity indicator contract', function () {
    NativeElementCollector::leaf('firstlight.activity-indicator', [
        'a11y-label' => 'Loading appointments',
        'margin-top' => 16,
    ]);

    $tree = NativeElementCollector::collect()->toArray(new CallbackRegistry);

    expect($tree['type'])->toBe('firstlight.activity-indicator')
        ->and($tree['props'])->toBe([
            'size' => 'md',
            'a11y_label' => 'Loading appointments',
        ])
        ->and($tree['layout']['marginTop'])->toBe(16)
        ->and($tree['style'] ?? [])->toBe([])
        ->and($tree['props'])->not->toHaveKeys(['on_change', 'on_press']);
});
```

- [ ] **Step 2: Run the focused test and verify RED**

```bash
vendor/bin/pest tests/Feature/ActivityIndicatorPublicContractTest.php
```

Expected: FAIL because `FirstlightUI\Elements\ActivityIndicator` does not exist. A syntax or bootstrap failure is not the expected RED.

- [ ] **Step 3: Scaffold without overwriting authored work**

```bash
bin/scaffold-component ActivityIndicator
```

Expected: success creating the nine component paths. If any target exists, stop and inspect rather than overwrite.

- [ ] **Step 4: Move and expand the failing contract**

Move the setup and first assertion into `tests/Feature/ActivityIndicatorElementTest.php`, delete `ActivityIndicatorPublicContractTest.php`, and add literal cases:

```php
it('publishes every semantic size', function (string $size) {
    expect(collectActivityIndicator([
        'size' => $size,
        'a11y-label' => 'Loading appointments',
    ])['props']['size'])->toBe($size);
})->with(['sm', 'md', 'lg']);

it('accepts the camel case accessibility alias', function () {
    expect(collectActivityIndicator([
        'a11yLabel' => 'Loading appointments',
    ])['props']['a11y_label'])->toBe('Loading appointments');
});

it('rejects missing blank and non-string accessibility labels', function (array $attrs) {
    collectActivityIndicator($attrs);
})->with([
    'missing' => [[]],
    'empty' => [['a11y-label' => '']],
    'whitespace' => [['a11y-label' => " \n\t "]],
    'null' => [['a11y-label' => null]],
    'integer' => [['a11y-label' => 42]],
])->throws(InvalidArgumentException::class, 'non-empty `a11y-label`');

it('rejects unsupported sizes', function (mixed $size) {
    collectActivityIndicator([
        'size' => $size,
        'a11y-label' => 'Loading appointments',
    ]);
})->with(['small', 'large', 'xs', 'xl', 1, 2, null, true, []])
    ->throws(InvalidArgumentException::class, '`size` must be one of: sm, md, lg');

it('rejects state event content and styling APIs', function (string $attribute, mixed $value) {
    collectActivityIndicator([
        'a11y-label' => 'Loading appointments',
        $attribute => $value,
    ]);
})->with([
    ['label', 'Loading'], ['a11y-hint', 'Please wait'], ['value', 0.5],
    ['loading', true], ['active', true], ['visible', true], ['disabled', true],
    ['color', '#ffffff'], ['tone', 'info'], ['variant', 'circular'],
    ['sync-mode', 'live'], ['_change', 'changed'], ['_submit', 'submitted'],
    ['_press', 'pressed'],
])->throws(InvalidArgumentException::class, 'does not support');
```

Also assert the self-closing tag precompiles to `<x-native-firstlight-activity-indicator>` and the paired form remains unsupported. Run the file again; expected RED from scaffold markers and missing precompiler registration.

- [ ] **Step 5: Implement the minimal PHP contract**

Implement the Blade component as:

```php
<?php

namespace FirstlightUI\Components;

use Native\Mobile\Edge\Components\Native\NativeBladeComponent;

final class ActivityIndicator extends NativeBladeComponent
{
    protected bool $isSelfClosing = true;

    protected function elementType(): string
    {
        return 'firstlight.activity-indicator';
    }
}
```

Implement the element as an ordinary `Element`. Validate unsupported attributes before applying defaults, require a string label whose `trim()` is non-empty, preserve the authored label, and publish only primitive props:

```php
private const SIZES = ['sm', 'md', 'lg'];

public function applyAttributes(array $attrs): void
{
    foreach (self::UNSUPPORTED_ATTRIBUTES as $attribute) {
        if (array_key_exists($attribute, $attrs)) {
            throw new InvalidArgumentException(
                "Firstlight Activity Indicator does not support `{$attribute}`."
            );
        }
    }

    $size = $attrs['size'] ?? 'md';
    if (! is_string($size) || ! in_array($size, self::SIZES, true)) {
        throw new InvalidArgumentException(
            'Firstlight Activity Indicator `size` must be one of: sm, md, lg.'
        );
    }

    $label = $attrs['a11y-label'] ?? $attrs['a11yLabel'] ?? null;
    if (! is_string($label) || trim($label) === '') {
        throw new InvalidArgumentException(
            'Firstlight Activity Indicator requires a non-empty `a11y-label`.'
        );
    }

    $this->indicatorProps = ['size' => $size, 'a11y_label' => $label];
}

protected function resolveProps(CallbackRegistry $registry): array
{
    return $this->indicatorProps;
}
```

Override `getStyle()` to return `[]`; preserve `parent::getLayout()` unchanged so external layout classes remain available.

- [ ] **Step 6: Register only the public Blade syntax**

Add `activity-indicator` to `FIRSTLIGHT_SELF_CLOSING_TAG` in `src/FirstlightTagPrecompiler.php`. Do not add it to the paired tag matcher and do not register the manifest until both native renderers exist.

- [ ] **Step 7: Verify PHP GREEN and mutation-sensitive branches**

```bash
vendor/bin/pest tests/Feature/ActivityIndicatorElementTest.php tests/Feature/ProgressElementTest.php
php -l src/Components/ActivityIndicator.php
php -l src/Elements/ActivityIndicator.php
git diff --check
```

Expected: focused tests pass. Mutating default `md`, any allowed size, required-label validation, unsupported-prop validation, or tag matching must fail a named test.

- [ ] **Step 8: Commit only the PHP contract**

```bash
git add src/Components/ActivityIndicator.php src/Elements/ActivityIndicator.php src/FirstlightTagPrecompiler.php tests/Feature/ActivityIndicatorElementTest.php
git commit -m "feat: define Activity Indicator contract"
```

---

### Task 2: Implement the SwiftUI renderer and announcement guard

**Files:**

- Replace scaffold: `resources/ios/ActivityIndicatorControl.swift`
- Replace scaffold: `resources/ios/ActivityIndicatorRenderer.swift`
- Replace scaffold: `tests/ios/ActivityIndicatorSnapshotTests.swift`

**Interfaces:**

- Produces: `ActivityIndicatorSize: String, CaseIterable` with native `ControlSize` mapping.
- Produces: `ActivityIndicatorRendererConfiguration: Equatable` decoding `size` and `accessibilityLabel`.
- Produces: `ActivityIndicatorAnnouncementState.consume(label:) -> String?`, returning the label once per state lifetime.
- Produces: `ActivityIndicatorRendererState.serverPublished(_:) -> Bool`, updating configuration without resetting announcement state.
- Produces: `FirstlightActivityIndicatorControl(size:accessibilityLabel:tint:)`.

- [ ] **Step 1: Invoke the required Apple implementation skill**

Read and follow `.agents/skills/firstlight-ios-component/SKILL.md`. Recheck the approved design, `Package.swift`, NativePHP test shims, current Apple `ProgressView`, `controlSize`, and `AccessibilityNotification.Announcement` documentation. Record any availability constraint before editing production Swift.

- [ ] **Step 2: Write failing Swift contract tests**

Replace the scaffold test with production-facing assertions:

```swift
@MainActor
final class ActivityIndicatorSnapshotTests: XCTestCase {
    func testConfigurationDecodesSemanticSizeAndRequiredName() {
        let configuration = ActivityIndicatorRendererConfiguration(
            node: makeNode(size: "lg", label: "Loading appointments")
        )

        XCTAssertEqual(configuration.size, .large)
        XCTAssertEqual(configuration.accessibilityLabel, "Loading appointments")
    }

    func testAnnouncementIsConsumedOnlyOncePerMountedState() {
        var state = ActivityIndicatorAnnouncementState()

        XCTAssertEqual(state.consume(label: "Loading appointments"), "Loading appointments")
        XCTAssertNil(state.consume(label: "Loading appointments"))
        XCTAssertNil(state.consume(label: "Loading updated appointments"))
    }

    func testReconciliationUpdatesPresentationWithoutResettingAnnouncement() {
        var state = ActivityIndicatorRendererState(node: makeNode(size: "sm"))
        XCTAssertNotNil(state.consumeAnnouncement())

        XCTAssertTrue(state.serverPublished(tree(node: makeNode(size: "lg"))))
        XCTAssertEqual(state.configuration.size, .large)
        XCTAssertNil(state.consumeAnnouncement())
    }
}
```

Add a control construction test and light/dark snapshots containing all sizes. Use the existing `FIRSTLIGHT_RECORD_SNAPSHOTS` guard; never record during the RED run.

- [ ] **Step 3: Run the Swift test and verify RED**

```bash
swift test --filter ActivityIndicatorSnapshotTests
```

Expected: FAIL because the scaffold has no size, configuration, announcement, or renderer state types.

- [ ] **Step 4: Implement the native control and renderer**

Implement semantic size mapping and a genuine system indicator:

```swift
enum ActivityIndicatorSize: String, CaseIterable {
    case small = "sm"
    case medium = "md"
    case large = "lg"

    var controlSize: ControlSize {
        switch self {
        case .small: .small
        case .medium: .regular
        case .large: .large
        }
    }
}

struct FirstlightActivityIndicatorControl: View {
    let size: ActivityIndicatorSize
    let accessibilityLabel: String
    let tint: Color

    var body: some View {
        ProgressView()
            .progressViewStyle(.circular)
            .controlSize(size.controlSize)
            .tint(tint)
            .accessibilityLabel(Text(accessibilityLabel))
    }
}
```

`ActivityIndicatorRendererState` stores stable `nodeID`, configuration, and one announcement guard. `serverPublished(_:)` finds the stable node recursively and updates only changed configuration. The renderer observes `NativeUIBridge.shared.$currentTree`, resolves `theme.primary`, and posts only a string returned by `consumeAnnouncement()`:

```swift
.onAppear {
    if let label = state.consumeAnnouncement() {
        AccessibilityNotification.Announcement(label).post()
    }
}
```

Do not post on `serverPublished`, size changes, or body recomputation.

- [ ] **Step 5: Verify Swift GREEN and the complete Swift package**

```bash
FIRSTLIGHT_RECORD_SNAPSHOTS=1 swift test --filter ActivityIndicatorSnapshotTests
swift test --filter ActivityIndicatorSnapshotTests
swift test
git diff --check
```

Expected: the first command records only the new Activity Indicator light and
dark reference images through SnapshotTesting's off-device host; the second
proves them stable without record mode; the complete Swift suite passes with
no simulator.

- [ ] **Step 6: Commit the iOS implementation**

```bash
git add resources/ios/ActivityIndicatorControl.swift resources/ios/ActivityIndicatorRenderer.swift tests/ios/ActivityIndicatorSnapshotTests.swift tests/ios/__Snapshots__/ActivityIndicatorSnapshotTests
git commit -m "feat: render Activity Indicator on iOS"
```

Omit the snapshot directory from `git add` when no new snapshots were intentionally recorded.

---

### Task 3: Implement the Material renderer and register the component

**Files:**

- Replace scaffold: `resources/android/ActivityIndicatorControl.kt`
- Replace scaffold: `resources/android/ActivityIndicatorRenderer.kt`
- Replace scaffold: `tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/ActivityIndicatorTest.kt`
- Modify: `nativephp.json`
- Modify: `tests/Feature/PluginManifestTest.php`

**Interfaces:**

- Produces: `ActivityIndicatorSize` with `wireName` and `dimension: Dp` for `20.dp`, `32.dp`, and `48.dp`.
- Produces: `ActivityIndicatorRendererConfiguration` and `ActivityIndicatorRendererState.serverPublished(tree): Boolean`.
- Produces: `FirstlightActivityIndicatorControl(size, accessibilityLabel, color, modifier)` with content-description and polite-live-region semantics.
- Registers exact paired renderers with no `adapter` key.

- [ ] **Step 1: Invoke the required Material implementation skill**

Read and follow `.agents/skills/firstlight-android-component/SKILL.md`. Recheck the approved design, Gradle test source layout, current Material 3 progress and Compose semantics documentation, and installed NativePHP node APIs before editing production Kotlin.

- [ ] **Step 2: Write failing Kotlin contract and semantics tests**

Replace the scaffold with tests for decoding, stable publication, semantics, and all sizes:

```kotlin
class ActivityIndicatorContractTest {
    @Test
    fun `configuration decodes semantic size and required name`() {
        val configuration = ActivityIndicatorRendererConfiguration(
            node(size = "lg", label = "Loading appointments"),
        )

        assertEquals(ActivityIndicatorSize.Large, configuration.size)
        assertEquals("Loading appointments", configuration.accessibilityLabel)
    }

    @Test
    fun `identical publication does not change mounted configuration`() {
        val state = ActivityIndicatorRendererState(node())

        assertFalse(state.serverPublished(NativeUITree(node())))
        assertTrue(state.serverPublished(NativeUITree(node(size = "lg"))))
        assertEquals(ActivityIndicatorSize.Large, state.configuration.size)
    }

    @Test
    fun `semantic sizes map to bounded material dimensions`() {
        assertEquals(listOf(20.dp, 32.dp, 48.dp), ActivityIndicatorSize.entries.map { it.dimension })
    }
}
```

Add a Compose semantics assertion that finds the node by content description and verifies `LiveRegionMode.Polite` with no click action. Add Paparazzi light and dark renders containing all three sizes.

- [ ] **Step 3: Run the focused Android test and verify RED**

```bash
tests/android/gradlew -p tests/android testDebugUnitTest --tests '*ActivityIndicator*'
```

Expected: FAIL because scaffold types and Material semantics are absent.

- [ ] **Step 4: Implement the Material control and renderer**

Implement the control with theme colour and explicit display-only semantics:

```kotlin
@Composable
fun FirstlightActivityIndicatorControl(
    size: ActivityIndicatorSize,
    accessibilityLabel: String,
    color: Color,
    modifier: Modifier = Modifier,
) {
    CircularProgressIndicator(
        modifier = modifier
            .size(size.dimension)
            .semantics {
                contentDescription = accessibilityLabel
                liveRegion = LiveRegionMode.Polite
            },
        color = color,
    )
}
```

Mirror the simple stable-node renderer-state pattern from Status Label. Use `remember(node.id)`, observe `NativeUIBridge.currentTree.value` through `LaunchedEffect`, and resolve `NativeUITheme.light.primary` or `.dark.primary`. Do not add callback, click, role, timer, or custom animation state.

- [ ] **Step 5: Register the paired public component**

Add this entry immediately after Progress in `nativephp.json` and in the exact array asserted by `PluginManifestTest.php`:

```json
{
    "type": "firstlight.activity-indicator",
    "element": "FirstlightUI\\Elements\\ActivityIndicator",
    "blade": "FirstlightUI\\Components\\ActivityIndicator",
    "android_renderer": "dev.firstlightui.plugins.firstlight_ui.ui.ActivityIndicatorRenderer",
    "ios_renderer": "ActivityIndicatorRenderer",
    "self_closing": true
}
```

Assert explicitly that the entry has no `adapter` key.

- [ ] **Step 6: Verify Android and manifest GREEN**

```bash
tests/android/gradlew -p tests/android testDebugUnitTest --tests '*ActivityIndicator*'
tests/android/gradlew -p tests/android testDebugUnitTest
vendor/bin/pest tests/Feature/ActivityIndicatorElementTest.php tests/Feature/PluginManifestTest.php
bin/check-component ActivityIndicator --development
git diff --check
```

Expected: focused and full Android tests, PHP registration tests, and structural component checks pass without an emulator.

- [ ] **Step 7: Commit Android and paired registration**

```bash
git add nativephp.json resources/android/ActivityIndicatorControl.kt resources/android/ActivityIndicatorRenderer.kt tests/Feature/PluginManifestTest.php tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/ActivityIndicatorTest.kt tests/android/src/test/snapshots
git commit -m "feat: render Activity Indicator on Android"
```

Stage only newly generated Activity Indicator snapshots under the shared snapshot directory.

---

### Task 4: Publish component documentation and generated artefacts

**Files:**

- Create: `spec/components/activity-indicator.md`
- Replace scaffold: `docs/components/activity-indicator.md`
- Modify: `spec/screenshots.json`
- Regenerate: `spec/index.md`
- Regenerate: `docs/index.md`
- Regenerate: `llms.txt`
- Regenerate: `llms-full.txt`

**Interfaces:**

- Publishes the exact approved API and conditional-presence recipe.
- Registers capture route `/captures/activity-indicator` and four eventual screenshot outputs.
- Makes the component discoverable in generated maintainer and public indexes.

- [ ] **Step 1: Write the maintainer contract and public guide**

The maintainer contract must cover purpose/state class, complete API table, presence/empty behaviour, validation, no-event data flow, primitive audit and paired-renderer decision, accessibility announcement contract, platform expression, and evidence boundary. The public guide begins with:

```blade
@if ($loading)
    <firstlight:activity-indicator
        size="md"
        a11y-label="Loading appointments"
    />
@endif
```

It documents `sm|md|lg`, required context naming, theme-only colour, the distinction from Progress, absence of `wire:loading`, and separately composed visible text. It must not promise release completion or show `<native:activity-indicator>` as the Firstlight API.

- [ ] **Step 2: Register documentation screenshot metadata**

Add `activity-indicator` to `spec/screenshots.json` with route `/captures/activity-indicator`, test command `php artisan test tests/Feature/ActivityIndicatorCaptureTest.php`, and outputs:

```json
{
    "ios-light": "docs/screenshots/activity-indicator/ios-light.png",
    "ios-dark": "docs/screenshots/activity-indicator/ios-dark.png",
    "android-light": "docs/screenshots/activity-indicator/android-light.png",
    "android-dark": "docs/screenshots/activity-indicator/android-dark.png"
}
```

Do not create placeholder PNG files.

- [ ] **Step 3: Build and verify documentation artefacts**

```bash
bin/build-docs-artifacts
bin/check-docs --development
bin/check-component ActivityIndicator --development
git diff --check
```

Expected: generated indexes and LLM artefacts include Activity Indicator; development docs checks pass while release-only screenshots remain an explicit later gate.

- [ ] **Step 4: Commit package documentation**

```bash
git add docs/components/activity-indicator.md docs/index.md llms-full.txt llms.txt spec/components/activity-indicator.md spec/index.md spec/screenshots.json
git commit -m "docs: add Activity Indicator guide"
```

---

### Task 5: Install and dogfood Activity Indicator in the showcase

**Files in `/Users/wojt/Code/clinically-au/firstlight-showcase`:**

- Create: `app/NativeComponents/ActivityIndicatorShowcase.php`
- Create: `app/NativeComponents/Captures/ActivityIndicatorCapture.php`
- Create: `resources/views/native/activity-indicator-showcase.blade.php`
- Create: `resources/views/native/captures/activity-indicator.blade.php`
- Create: `tests/Feature/ActivityIndicatorShowcaseTest.php`
- Create: `tests/Feature/ActivityIndicatorCaptureTest.php`
- Modify: `app/NativeComponents/ShowcaseHome.php`
- Modify: `routes/native.php`
- Modify: `tests/Feature/ShowcaseNavigationTest.php`
- Modify: `composer.lock`

**Interfaces:**

- Adds routes `/activity-indicator` and `/captures/activity-indicator` with `ShowcaseLayout`.
- `ActivityIndicatorShowcase` exposes `public bool $loading = true`, `showIndicator(): void`, and `hideIndicator(): void`.
- Capture fixture always publishes one `sm`, one default `md`, and one `lg` indicator with deterministic labels.

- [ ] **Step 1: Inspect and preserve the sibling working tree**

```bash
git -C /Users/wojt/Code/clinically-au/firstlight-showcase status --short --branch
```

Expected: understand every pre-existing change before editing. Do not overwrite adjacent fixtures, shared layout, start route, or appearance state.

- [ ] **Step 2: Refresh the exact local package revision**

After Tasks 1–4 are committed, run from the showcase:

```bash
composer update firstlightui/nativephp --with-dependencies
```

Expected: `composer.lock` records the current package commit and no unrelated dependency drift. If more packages change, stop and inspect before continuing.

- [ ] **Step 3: Write failing showcase and capture tests**

Create a recursive helper for `firstlight.activity-indicator` nodes. The gallery test asserts sizes and labels, then toggles conditional presence:

```php
it('publishes all Activity Indicator sizes and conditional presence', function () {
    $screen = Native::visit('/activity-indicator');
    $nodes = activityIndicatorNodes($screen->tree());

    expect(array_column(array_column($nodes, 'props'), 'size'))->toBe(['sm', 'md', 'lg', 'md'])
        ->and(array_column(array_column($nodes, 'props'), 'a11y_label'))->toContain(
            'Loading appointments'
        );

    $screen->tap('Hide activity indicator')->assertSet('loading', false);
    expect(activityIndicatorNodes($screen->tree()))->toHaveCount(3);

    $screen->tap('Show activity indicator')->assertSet('loading', true);
    expect(activityIndicatorNodes($screen->tree()))->toHaveCount(4);
    $screen->assertAccessible();
});
```

The capture test asserts exactly three stable size/name pairs at `/captures/activity-indicator`. Update navigation expectations to require the exact tag and route. Run:

```bash
php artisan test tests/Feature/ActivityIndicatorShowcaseTest.php tests/Feature/ActivityIndicatorCaptureTest.php tests/Feature/ShowcaseNavigationTest.php
```

Expected: FAIL because routes, classes, views, and catalogue entry do not exist.

- [ ] **Step 4: Implement the gallery, capture fixture, routes, and catalogue entry**

The gallery renders separately labelled `sm`, default `md`, and `lg` examples, a long-context example, explanatory Progress distinction, and:

```blade
@if ($loading)
    <firstlight:activity-indicator a11y-label="Loading appointments" />

    <firstlight:button
        label="Hide activity indicator"
        @press="hideIndicator"
    />
@else
    <firstlight:button
        label="Show activity indicator"
        @press="showIndicator"
    />
@endif
```

Use the two static callbacks exactly as shown so callback grammar stays
deterministic. Do not change `ShowcaseLayout` or `NATIVEPHP_START_URL=/`.

- [ ] **Step 5: Verify focused and complete consumer GREEN**

```bash
php artisan test tests/Feature/ActivityIndicatorShowcaseTest.php tests/Feature/ActivityIndicatorCaptureTest.php tests/Feature/ShowcaseNavigationTest.php
composer test
git diff --check
```

Expected: focused tests and all showcase tests pass with no device.

- [ ] **Step 6: Commit only showcase-owned work**

```bash
git add app/NativeComponents/ActivityIndicatorShowcase.php app/NativeComponents/Captures/ActivityIndicatorCapture.php app/NativeComponents/ShowcaseHome.php composer.lock resources/views/native/activity-indicator-showcase.blade.php resources/views/native/captures/activity-indicator.blade.php routes/native.php tests/Feature/ActivityIndicatorShowcaseTest.php tests/Feature/ActivityIndicatorCaptureTest.php tests/Feature/ShowcaseNavigationTest.php
git commit -m "feat: showcase Activity Indicator"
```

---

### Task 6: Run complete off-device gates and constitutional review

**Files:**

- Create: `spec/reviews/activity-indicator-development.md`
- Modify only after evidence passes: `roadmap-v2.md`

**Interfaces:**

- Records package/showcase commits, renderer path decision, focused/full tests, consumer lock, platform suites, build evidence, accessibility gaps, device rows, and prerequisites.
- Marks Activity Indicator delivered only if the review skill permits it; otherwise records the exact blocked status and leaves Checkbox unpromoted.

- [ ] **Step 1: Run complete package and documentation verification**

```bash
composer test
bin/check-component ActivityIndicator --development
bin/build-docs-artifacts
bin/check-docs --development
composer validate --strict
php artisan native:plugin:validate /Users/wojt/Code/clinically-au/firstlight-ui
git diff --check
```

Expected: 0 failures. Five model evals may remain intentionally skipped unless separately requested with `--evals`.

- [ ] **Step 2: Run both complete off-device platform suites**

```bash
swift test
tests/android/gradlew -p tests/android testDebugUnitTest
```

Expected: all XCTest/SnapshotTesting and JVM/Paparazzi tests pass without starting a simulator or emulator.

- [ ] **Step 3: Re-verify the installed consumer**

From `/Users/wojt/Code/clinically-au/firstlight-showcase`:

```bash
composer test
php artisan native:plugin:validate /Users/wojt/Code/clinically-au/firstlight-ui
git diff --check
```

Expected: the full showcase passes against the exact committed package revision and plugin validation reports no component registration error.

- [ ] **Step 4: Invoke the required constitutional review skill**

Read and follow `.agents/skills/firstlight-review-component/SKILL.md`. Review the exact package and showcase commits against every constitutional article. Create `spec/reviews/activity-indicator-development.md` with dated factual rows. Do not infer native feel, VoiceOver, TalkBack, or device behaviour from source or screenshots.

- [ ] **Step 5: Stop for exact device permissions before visual evidence**

Request explicit permission naming the intended iOS simulator and Android emulator before any host build, launch, or capture. Request physical-device permission separately. If permission is declined or evidence fails, record the blocked rows and do not mark the component complete.

- [ ] **Step 6: Update the hit list only to the proven state**

If every required component-development row passes, change Activity Indicator from **Now** to delivered, increment V2 delivery, and promote Checkbox to **Now**. If device or accessibility evidence remains open, keep Activity Indicator **Now** and record its specific blockers without promoting Checkbox.

- [ ] **Step 7: Commit the review evidence and any factual roadmap update**

```bash
git add spec/reviews/activity-indicator-development.md roadmap-v2.md
git commit -m "docs: review Activity Indicator development"
```

Stage `roadmap-v2.md` only if it was intentionally updated from verified evidence and the maintainer confirms the previously untracked roadmap should enter version control. Otherwise commit only the review record and report the roadmap as untouched.
