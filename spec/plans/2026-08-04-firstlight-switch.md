---
title: Firstlight Switch implementation plan
description: Test-first delivery plan for the public Switch contract, native renderers, showcase, documentation, and evidence.
status: historical
sources:
  - spec/designs/2026-08-04-firstlight-switch-design.md
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
---

# Firstlight Switch Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a development-proven `<firstlight:switch>` with strict boolean input, non-optimistic server-authoritative state, genuine SwiftUI and Material 3 expression, complete showcase coverage, and constitutional evidence.

**Architecture:** A `firstlight.switch` EDGE element publishes one accepted boolean and field metadata through the official Element Tree. Paired SwiftUI and Compose renderers display only the latest published value, emit one pending proposal through the standard toggle-change event, and clear pending state on every subsequent tree publication. The sibling showcase is the exhaustive consumer and screenshot source.

**Tech Stack:** PHP 8.4, Laravel 13 Blade, Pest 5, NativePHP Mobile 4.0.1, Mobile UI 0.3.0, Swift 6.2 and SwiftUI, Kotlin 2.3 and Material 3 Compose, XCTest/SnapshotTesting, JUnit/Paparazzi, iOS 18+, Android API 29+.

## Global Constraints

- Public package is `firstlightui/nativephp`; showcase is `firstlightui/showcase`; PHP namespace is `FirstlightUI`; Blade prefix is `firstlight`; plugin namespace is `Firstlight`.
- Public API is `<firstlight:switch>` and element type is `firstlight.switch`; internal PHP classes are `SwitchControl` because `switch` is reserved.
- iOS renderer is `SwitchRenderer`; Android renderer is `dev.firstlightui.plugins.firstlight_ui.ui.SwitchRenderer`; derive these from `nativephp.json`, not Composer names.
- Accepted state is strictly `bool`; omitted value is `false`; reject `null`, strings, integers, arrays, and objects. Recommend `:value="false"` when `value="false"` is authored.
- Supported props are `value`/`native:model`, `label`, `helper`, `error`, `disabled`, `a11y-label`, `a11y-hint`, and external `class`. Do not add `required`, loading, nullable state, placement, tone, variant, thumb icons, or platform escape props.
- Support plain `native:model` and `.live`; reject `.blur`, `.lazy`, and `.debounce.*` because Switch is discrete.
- Checked state is non-optimistic and server-authoritative. Preserve native pressed/focus feedback, emit at most one proposal per publication, and never echo programmatic updates.
- The row is fully tappable, the switch is logically trailing, and RTL mirrors automatically. Minimum target is 44 points on iOS and 48 dp on Android.
- Error replaces helper, uses semantic error/destructive colour, and is exposed to VoiceOver and TalkBack. A visible label or explicit `a11y-label` is mandatory in development.
- Use official SuperNative element, shared-memory, renderer, and toggle-change seams. Do not add JSON bridges, WebViews, generated-tree edits, or a parallel binding vocabulary.
- The local NativePHP publication fork may be used for development, but the unreleased identical-publication prerequisite remains an alpha blocker.
- Work locally only. Do not push, publish, tag, or open a PR. Preserve unrelated changes and stage only task-owned paths.
- Every production change follows RED, verified failure, minimal GREEN, verified pass, refactor, and focused commit.

---

### Task 1: Make component tooling understand reserved-word controls

**Files:**

- Modify: `tests/Feature/ComponentToolingTest.php`
- Modify: `bin/scaffold-component`
- Modify: `bin/check-component`

**Interfaces:**

- Consumes: internal class name `SwitchControl`.
- Produces: `$publicName = 'Switch'`, `$slug = 'switch'`, `$controlName = 'SwitchControl'`, and `$rendererName = 'SwitchRenderer'` for both scripts.

- [ ] **Step 1: Write the failing scaffold convention test**

Add a focused test that copies `bin/scaffold-component` to a temporary root, runs it with `SwitchControl`, and asserts these literal paths:

```php
it('strips an internal Control suffix from public component artifacts', function () {
    $sourceRoot = dirname(__DIR__, 2);
    $root = componentToolingRoot();
    copyComponentToolingPath($sourceRoot.'/bin/scaffold-component', $root.'/bin/scaffold-component');

    $process = new Process([$root.'/bin/scaffold-component', 'SwitchControl']);
    $process->run();

    expect($process->getExitCode())->toBe(0)
        ->and($root.'/resources/ios/SwitchControl.swift')->toBeFile()
        ->and($root.'/resources/ios/SwitchRenderer.swift')->toBeFile()
        ->and($root.'/resources/android/SwitchControl.kt')->toBeFile()
        ->and($root.'/resources/android/SwitchRenderer.kt')->toBeFile()
        ->and($root.'/docs/components/switch.md')->toBeFile()
        ->and($root.'/resources/ios/SwitchControlRenderer.swift')->not->toBeFile()
        ->and($root.'/docs/components/switch-control.md')->not->toBeFile();
});
```

- [ ] **Step 2: Run the test and verify RED**

Run:

```bash
vendor/bin/pest tests/Feature/ComponentToolingTest.php --filter='strips an internal Control suffix'
```

Expected: FAIL because the current script creates `SwitchControlRenderer.swift` and `switch-control.md`.

- [ ] **Step 3: Implement one shared naming convention in both scripts**

Keep each script's existing argument parsing and `$name` assignment. Immediately after `$name` is resolved, replace its current derived-name block with:

```php
$publicName = str_ends_with($name, 'Control')
    ? substr($name, 0, -strlen('Control'))
    : $name;
$slug = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $publicName));
$controlName = str_ends_with($name, 'Control') ? $name : $name.'Control';
$rendererName = $publicName.'Renderer';
```

Keep source class and PHP test names based on `$name`; use `$slug` for docs/evidence and `$rendererName` for both native renderer paths and manifest checks. Adjust the existing `ExampleControl` scaffold expectations to `ExampleRenderer.*` and `docs/components/example.md`.

- [ ] **Step 4: Verify GREEN and existing Segmented behavior**

Run:

```bash
vendor/bin/pest tests/Feature/ComponentToolingTest.php
bin/check-component Segmented --development
```

Expected: all tooling tests pass and Segmented still passes its development structural gate.

- [ ] **Step 5: Commit the tooling convention**

```bash
git add bin/scaffold-component bin/check-component tests/Feature/ComponentToolingTest.php
git commit -m "feat: support reserved component names"
```

---

### Task 2: Publish the strict PHP and EDGE Switch contract

**Files:**

- Create then remove after moving assertions: `tests/Feature/SwitchPublicContractTest.php`
- Create via scaffold and replace: `tests/Feature/SwitchControlElementTest.php`
- Create via scaffold and implement: `src/Components/SwitchControl.php`
- Create via scaffold and implement: `src/Elements/SwitchControl.php`
- Modify: `src/FirstlightTagPrecompiler.php`
- Modify: `nativephp.json`
- Modify: `tests/Feature/PluginManifestTest.php`
- Generated but intentionally left unstaged until Tasks 3, 4, and 6: `resources/ios/SwitchControl.swift`, `resources/ios/SwitchRenderer.swift`, `resources/android/SwitchControl.kt`, `resources/android/SwitchRenderer.kt`, `tests/ios/SwitchControlSnapshotTests.swift`, `tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/SwitchControlTest.kt`, `docs/components/switch.md`

**Interfaces:**

- Produces: `FirstlightUI\Components\SwitchControl::elementType(): 'firstlight.switch'`.
- Produces: `FirstlightUI\Elements\SwitchControl` with `value(bool)`, `label(string)`, `helper(string)`, `error(string)`, `disabled(bool)`, `syncMode(string)`, `onChange(string)`, and standard accessibility props.
- Produces Element Tree props: `value`, `label`, `helper`, `error`, `disabled`, optional `a11y_label`, optional `a11y_hint`, and optional registered `on_change`.

- [ ] **Step 1: Write a public-tag RED test before scaffolding**

Create `tests/Feature/SwitchPublicContractTest.php` using the real Blade compiler/collector harness from `SegmentedElementTest.php`. Register the future class string and assert:

```php
it('publishes the accepted boolean through the public Switch tag', function () {
    $result = compileFirstlightSwitchView(
        '<firstlight:switch native:model="notifications" label="Notifications" helper="Receive updates." />',
        ['notifications' => true],
    );

    expect($result['tree'])->not->toBeNull()
        ->and($result['tree']['type'])->toBe('firstlight.switch')
        ->and($result['tree']['props'])->toMatchArray([
            'value' => true,
            'label' => 'Notifications',
            'helper' => 'Receive updates.',
            'error' => '',
            'disabled' => false,
        ])
        ->and($result['registry']->resolve($result['tree']['props']['on_change']))->toBe([
            'method' => '__syncProperty',
            'args' => ['notifications'],
        ]);
});
```

- [ ] **Step 2: Verify the missing component produces RED**

```bash
vendor/bin/pest tests/Feature/SwitchPublicContractTest.php
```

Expected: FAIL because `<firstlight:switch>` remains uncompiled and publishes no node.

- [ ] **Step 3: Scaffold without overwriting authored work**

```bash
bin/scaffold-component SwitchControl
```

Expected: success with `SwitchControl` PHP/control/test names, `SwitchRenderer` native renderer names, and `docs/components/switch.md`. If any listed path exists, stop and inspect rather than overwrite.

- [ ] **Step 4: Move the RED contract into the required test path and expand it**

Move the compiler harness and first test to `tests/Feature/SwitchControlElementTest.php`, then delete `SwitchPublicContractTest.php`. Add literal tests for:

```php
it('defaults an omitted value to off');
it('rejects non-boolean authored values', function (mixed $candidate) {
    expect(fn () => compileFirstlightSwitchView(
        '<firstlight:switch :value="$candidate" label="Notifications" />',
        compact('candidate'),
    ))->toThrow(InvalidArgumentException::class, 'Switch value must be a boolean');
})->with([null, 0, 1, '0', '1', 'false', [], new stdClass]);
it('recommends a bound boolean when a string false is authored');
it('publishes helper error disabled and accessibility metadata');
it('accepts live sync mode without renderer timing props');
it('rejects deferred sync modes', function (string $attribute) {
    expect(fn () => compileFirstlightSwitchView(
        "<firstlight:switch {$attribute}=\"notifications\" label=\"Notifications\" />",
        ['notifications' => false],
    ))->toThrow(InvalidArgumentException::class, 'Switch supports only native:model or native:model.live');
})->with(['native:model.blur', 'native:model.lazy', 'native:model.debounce.250ms']);
it('warns in development when visible and accessibility labels are blank');
it('leaves the public tag untouched through web compilation');
```

Run the file again. Expected: RED from `FIRSTLIGHT_NOT_IMPLEMENTED` classes and absent registration, not a syntax error.

- [ ] **Step 5: Implement the minimal PHP contract**

Implement `SwitchControl` as an ordinary `Element`. The critical validation branch is:

```php
if (array_key_exists('value', $attrs) && ! is_bool($attrs['value'])) {
    $actual = get_debug_type($attrs['value']);
    $hint = $attrs['value'] === 'false'
        ? ' Use :value="false" or native:model so Blade supplies a boolean.'
        : '';

    throw new InvalidArgumentException(
        "Switch value must be a boolean; {$actual} given.{$hint}"
    );
}

$this->value($attrs['value'] ?? false);
```

Reject `required` and `placement` if present. `syncMode()` accepts only `live` and throws guidance for every other mode. `resolveProps()` registers one callback and always publishes primitive boolean/string props. Copy Segmented's development-only label warning behavior without sharing its selection state.

- [ ] **Step 6: Register the public tag and renderers**

Generalize `FirstlightTagPrecompiler` so its self-closing tag matcher includes `switch` and rewrites it to `<x-native-firstlight-switch>`. Add this manifest entry after Segmented:

```json
{
    "type": "firstlight.switch",
    "element": "FirstlightUI\\Elements\\SwitchControl",
    "blade": "FirstlightUI\\Components\\SwitchControl",
    "android_renderer": "dev.firstlightui.plugins.firstlight_ui.ui.SwitchRenderer",
    "ios_renderer": "SwitchRenderer",
    "self_closing": true
}
```

Update `PluginManifestTest.php` to assert the exact two-component array.

- [ ] **Step 7: Verify PHP GREEN and mutation-sensitive branches**

```bash
vendor/bin/pest tests/Feature/SwitchControlElementTest.php tests/Feature/PluginManifestTest.php tests/Feature/SegmentedElementTest.php
php -l src/Elements/SwitchControl.php
git diff --check
```

Expected: all focused tests pass. Mentally mutate default `false`, strict-type check, live-only branch, callback registration, and tag matcher; at least one named test must fail for each mutation.

- [ ] **Step 8: Commit the PHP contract only**

```bash
git add nativephp.json src/Components/SwitchControl.php src/Elements/SwitchControl.php src/FirstlightTagPrecompiler.php tests/Feature/SwitchControlElementTest.php tests/Feature/PluginManifestTest.php
git commit -m "feat: add Firstlight Switch contract"
```

Do not stage scaffolded native placeholders or public docs yet.

---

### Task 3: Implement the non-optimistic SwiftUI renderer

**Files:**

- Replace scaffold: `resources/ios/SwitchControl.swift`
- Replace scaffold: `resources/ios/SwitchRenderer.swift`
- Modify: `resources/ios/NativePHPTestShims.swift`
- Create: `tests/ios/SwitchControlRendererContractTests.swift`
- Replace scaffold: `tests/ios/SwitchControlSnapshotTests.swift`

**Interfaces:**

- Produces: `SwitchRendererConfiguration`, `SwitchRendererEvent.toggleChange(callbackId:nodeId:value:)`, and `SwitchRendererState`.
- Produces: `SwitchRendererState.proposeChange() -> SwitchRendererEvent?` and `serverPublished(_ tree: NativeUITree) -> Bool`.
- Produces: `FirstlightSwitchControl` accepting accepted value, field strings, disabled state, accessibility strings, semantic tokens, and `onProposal: () -> Void`.

- [ ] **Step 1: Read and announce the required iOS skill**

Read `.agents/skills/firstlight-ios-component/SKILL.md`, recheck the approved design, `nativephp.json`, `Package.swift`, installed NativePHP source, and current Apple `Toggle` documentation. Record that this is a renderer path because upstream state is optimistic.

- [ ] **Step 2: Write failing state-machine tests**

In `SwitchControlRendererContractTests.swift`, build literal `NativeUINode` fixtures and assert:

```swift
func testProposalDoesNotMutateAcceptedValueAndDeduplicatesUntilPublication() {
    var state = SwitchRendererState(node: switchNode(value: false))

    XCTAssertEqual(state.proposeChange(), .toggleChange(callbackId: 41, nodeId: 7, value: true))
    XCTAssertFalse(state.configuration.value)
    XCTAssertNil(state.proposeChange())
}

func testRejectedIdenticalPublicationClearsPendingWithoutChangingValue() {
    var state = SwitchRendererState(node: switchNode(value: false))
    _ = state.proposeChange()

    XCTAssertFalse(state.serverPublished(tree(value: false)))
    XCTAssertFalse(state.configuration.value)
    XCTAssertEqual(state.proposeChange(), .toggleChange(callbackId: 41, nodeId: 7, value: true))
}

func testAcceptedAndProgrammaticPublicationsUpdateOnlyAcceptedState() {
    var state = SwitchRendererState(node: switchNode(value: false))

    XCTAssertTrue(state.serverPublished(tree(value: true)))
    XCTAssertTrue(state.configuration.value)
    XCTAssertEqual(state.proposeChange(), .toggleChange(callbackId: 41, nodeId: 7, value: false))
    XCTAssertTrue(state.configuration.value)
}

func testDisabledSwitchNeverProposes() {
    var state = SwitchRendererState(node: switchNode(value: false, disabled: true))

    XCTAssertNil(state.proposeChange())
    XCTAssertFalse(state.configuration.value)
}
```

- [ ] **Step 3: Verify Swift RED**

Use the fixed iPhone 17 Pro simulator:

```bash
xcodebuild -scheme FirstlightIOSControls -destination 'platform=iOS Simulator,id=7B3D2D67-FA32-4419-A020-C15B72509CCF' test
```

Expected: FAIL because the scaffold contains `FIRSTLIGHT_NOT_IMPLEMENTED` and the renderer state types do not exist.

- [ ] **Step 4: Implement state and official event dispatch**

Decode all props in `SwitchRendererConfiguration`. `proposeChange()` returns nil when disabled, callback ID is zero, or a proposal is pending; otherwise set only `pendingProposal = !configuration.value` and return the event. `serverPublished()` locates the same node ID in every received tree, replaces configuration, clears pending, and returns whether accepted value changed.

Dispatch with the official toggle seam:

```swift
NativeElementBridge.sendToggleChangeEvent(
    callbackId,
    nodeId: nodeId,
    value: value
)
```

Extend the `SWIFT_PACKAGE` shim with only this production signature.

- [ ] **Step 5: Implement the native field**

Use a genuine SwiftUI `Toggle` with a binding whose getter returns the accepted value and whose setter calls `onProposal()` without storing the proposed value:

```swift
Toggle(isOn: Binding(
    get: { value },
    set: { _ in onProposal() }
)) {
    VStack(alignment: .leading, spacing: 2) {
        if !label.isEmpty { Text(label).font(.body) }
        if !supportingText.isEmpty {
            Text(supportingText)
                .font(.footnote)
                .foregroundStyle(error.isEmpty ? tokens.onSurfaceVariant : tokens.destructive)
        }
    }
}
.tint(tokens.primary)
.disabled(disabled)
.frame(maxWidth: .infinity, minHeight: 44)
```

Apply explicit accessibility label/hint only when non-empty and expose error text accessibly. `SwitchRenderer` observes every `bridge.$currentTree` publication and calls `serverPublished()` even when the accepted boolean is unchanged.

- [ ] **Step 6: Add failing-then-passing snapshots**

Snapshot light, dark, disabled-on, error, long-label, and `accessibilityExtraExtraExtraLarge` states in `SwitchControlSnapshotTests.swift`. Use the existing `FIRSTLIGHT_RECORD_SNAPSHOTS=1` convention. First run the ordinary command with one deliberately missing reference and confirm a snapshot-missing failure. Inspect the newly rendered failure artifact, then record and verify with:

```bash
FIRSTLIGHT_RECORD_SNAPSHOTS=1 xcodebuild -scheme FirstlightIOSControls -destination 'platform=iOS Simulator,id=7B3D2D67-FA32-4419-A020-C15B72509CCF' test
xcodebuild -scheme FirstlightIOSControls -destination 'platform=iOS Simulator,id=7B3D2D67-FA32-4419-A020-C15B72509CCF' test
```

- [ ] **Step 7: Verify iOS GREEN**

```bash
xcodebuild -scheme FirstlightIOSControls -destination 'platform=iOS Simulator,id=7B3D2D67-FA32-4419-A020-C15B72509CCF' test
bin/check-component SwitchControl --development
```

The structural check may still fail only on Android scaffold markers; iOS tests must be fully green with no warnings.

- [ ] **Step 8: Commit iOS implementation and snapshots**

```bash
git add resources/ios/SwitchControl.swift resources/ios/SwitchRenderer.swift resources/ios/NativePHPTestShims.swift tests/ios/SwitchControlRendererContractTests.swift tests/ios/SwitchControlSnapshotTests.swift tests/ios/__Snapshots__
git commit -m "feat: add native iOS Switch renderer"
```

---

### Task 4: Implement the non-optimistic Material 3 renderer

**Files:**

- Replace scaffold: `resources/android/SwitchControl.kt`
- Replace scaffold: `resources/android/SwitchRenderer.kt`
- Modify as required by exact production signatures: `tests/android/src/main/kotlin/com/nativephp/mobile/ui/nativerender/NativePHPTestShims.kt`
- Create: `tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/SwitchRendererContractTest.kt`
- Replace scaffold: `tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/SwitchControlTest.kt`
- Create/update Paparazzi references under: `tests/android/src/test/snapshots/images/`

**Interfaces:**

- Produces Kotlin equivalents `SwitchRendererConfiguration`, `SwitchRendererEvent.ToggleChange`, `SwitchRendererState.proposeChange()`, and `serverPublished(tree)`.
- Produces `FirstlightSwitchControl(...)` as the Material settings-row composable.

- [ ] **Step 1: Read and announce the required Android skill**

Read `.agents/skills/firstlight-android-component/SKILL.md`, current Material 3 `Switch` docs, installed NativePHP bridge source, and the design. Confirm Android min API 29 and JDK 21.

- [ ] **Step 2: Write failing Kotlin state tests**

Mirror the Swift literals without mocks:

```kotlin
@Test
fun proposalKeepsAcceptedValueAndDeduplicatesUntilPublication() {
    val state = SwitchRendererState(node(value = false))

    assertEquals(SwitchRendererEvent.ToggleChange(41, 7, true), state.proposeChange())
    assertFalse(state.configuration.value)
    assertNull(state.proposeChange())
}

@Test
fun identicalRejectedPublicationClearsPending() {
    val state = SwitchRendererState(node(value = false))
    state.proposeChange()

    assertFalse(state.serverPublished(tree(value = false)))
    assertFalse(state.configuration.value)
    assertEquals(SwitchRendererEvent.ToggleChange(41, 7, true), state.proposeChange())
}

@Test
fun acceptedAndProgrammaticPublicationsUpdateOnlyAcceptedState() {
    val state = SwitchRendererState(node(value = false))

    assertTrue(state.serverPublished(tree(value = true)))
    assertTrue(state.configuration.value)
    assertEquals(SwitchRendererEvent.ToggleChange(41, 7, false), state.proposeChange())
    assertTrue(state.configuration.value)
}

@Test
fun disabledSwitchDoesNotPropose() {
    val state = SwitchRendererState(node(value = false, disabled = true))

    assertNull(state.proposeChange())
    assertFalse(state.configuration.value)
}
```

- [ ] **Step 3: Verify Android RED**

```bash
JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home tests/android/gradlew -p tests/android testDebugUnitTest
```

Expected: FAIL because the scaffold markers and state types are unimplemented.

- [ ] **Step 4: Implement state and official event dispatch**

Use immutable accepted configuration plus a private pending proposal. `proposeChange()` never assigns accepted value. `serverPublished()` finds the node by ID on every `NativeUIBridge.currentTree` publication, replaces configuration, and clears pending.

Dispatch only through:

```kotlin
NativeUIBridge.sendToggleChangeEvent(
    event.callbackId,
    event.nodeId,
    event.value,
)
```

- [ ] **Step 5: Implement the Material settings row**

Compose a full-width `Row` with `Modifier.defaultMinSize(minHeight = 48.dp).toggleable(...)`, a weighted text `Column`, and trailing Material 3 `Switch`. The row owns `Role.Switch`; set the inner switch `onCheckedChange = null` and clear its duplicate semantics. Use `error ?: helper` as supporting text, semantic theme tokens, `MaterialTheme.typography.bodyLarge/bodySmall`, and `semanticsError(error)` when present. Do not store checked state in Compose.

- [ ] **Step 6: Add failing-then-passing Paparazzi coverage**

Cover light, dark, disabled-on, error, long-label, and font scale `2.0`. Run `testDebugUnitTest` first and confirm at least one missing-snapshot failure. Inspect Paparazzi's failure delta, then record and verify with:

```bash
JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home tests/android/gradlew -p tests/android recordPaparazziDebug
JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home tests/android/gradlew -p tests/android testDebugUnitTest
```

- [ ] **Step 7: Verify Android GREEN and both production source layouts**

```bash
JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home tests/android/gradlew -p tests/android testDebugUnitTest
bin/check-component SwitchControl --development
```

Expected: Android tests and the complete development structural gate pass with no `FIRSTLIGHT_NOT_IMPLEMENTED` markers.

- [ ] **Step 8: Commit Android implementation and snapshots**

```bash
git add resources/android/SwitchControl.kt resources/android/SwitchRenderer.kt tests/android/src/main/kotlin/com/nativephp/mobile/ui/nativerender/NativePHPTestShims.kt tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/SwitchRendererContractTest.kt tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/SwitchControlTest.kt tests/android/src/test/snapshots/images
git commit -m "feat: add native Android Switch renderer"
```

---

### Task 5: Dogfood every Switch state in the showcase

**Files in `/Users/wojt/Code/clinically-au/firstlight-showcase`:**

- Create: `app/NativeComponents/SwitchShowcase.php`
- Create: `resources/views/native/switch-showcase.blade.php`
- Create: `tests/Feature/SwitchShowcaseTest.php`
- Modify: `routes/native.php`
- Modify: `composer.lock`

**Interfaces:**

- Produces route `/switch` named `switch` using `ShowcaseLayout`.
- Produces public properties for off/on, disabled-off/on, helper, error, long-label, rejected, and programmatic states.
- Produces `rejectSwitch(bool $value): void`, `enableProgrammaticSwitch(): void`, and `resetProgrammaticSwitch(): void`.

- [ ] **Step 1: Refresh the exact local package revision**

From the showcase:

```bash
composer update firstlightui/nativephp --with-dependencies
php artisan native:plugin:validate /Users/wojt/Code/clinically-au/firstlight-ui
```

Expected: Composer lock points to the current local package commit and plugin validation recognizes `firstlight.switch` plus both renderers.

- [ ] **Step 2: Write the failing showcase contract**

Create `SwitchShowcaseTest.php` and first assert `Native::visit('/switch')` publishes `firstlight.switch` nodes labelled exactly:

```php
[
    'Notifications off',
    'Notifications on',
    'Disabled off',
    'Disabled on',
    'Helper text',
    'Validation error',
    'Rejected setting',
    'Programmatic setting',
    'A considerably longer setting label that wraps naturally',
]
```

Assert each node's primitive props and call `$screen->assertAccessible()`.

- [ ] **Step 3: Verify showcase RED**

```bash
php artisan test tests/Feature/SwitchShowcaseTest.php
```

Expected: FAIL because `/switch` is not registered.

- [ ] **Step 4: Implement exhaustive fixtures**

Build `SwitchShowcase` with strict boolean properties. Give every interactive example a stable `ref`, including `notificationsOff`, `disabledOff`, `disabledOn`, `rejectedSwitch`, and `programmaticSwitch`. For rejection, bind `:value="$rejected" @change="rejectSwitch"`; increment an attempts counter without changing `$rejected`. For programmatic state, render buttons that set the property true and false. The Blade screen uses only `<firstlight:switch>` examples plus native headings/buttons.

- [ ] **Step 5: Prove event, rejection, rapid-input, and programmatic behavior**

Add tests using NativePHP's real interaction harness:

```php
$screen->toggle('notificationsOff')->assertSet('notificationsOff', true);
$screen->toggle('rejectedSwitch')->assertSet('rejected', false)->assertSet('rejectedAttempts', 1);
$screen->call('enableProgrammaticSwitch')->assertSet('programmatic', true);
```

Fire the rejected callback repeatedly only after obtaining each newly published tree and assert one increment per publication. Assert disabled refs do not change. Avoid renderer mocks.

- [ ] **Step 6: Verify and commit the showcase screen**

```bash
php artisan test tests/Feature/SwitchShowcaseTest.php
php artisan test
git diff --check
git add app/NativeComponents/SwitchShowcase.php resources/views/native/switch-showcase.blade.php tests/Feature/SwitchShowcaseTest.php routes/native.php composer.lock
git commit -m "feat: showcase Firstlight Switch"
```

---

### Task 6: Publish Switch docs and deterministic capture evidence

**Files in package:**

- Replace scaffold: `docs/components/switch.md`
- Modify: `docs/index.md`
- Modify: `spec/screenshots.json`
- Create through capture: `docs/screenshots/switch/ios-light.png`
- Create through capture: `docs/screenshots/switch/ios-dark.png`
- Create through capture: `docs/screenshots/switch/android-light.png`
- Create through capture: `docs/screenshots/switch/android-dark.png`

**Files in showcase:**

- Create: `app/NativeComponents/Captures/SwitchCapture.php`
- Create: `resources/views/native/captures/switch.blade.php`
- Create: `tests/Feature/SwitchCaptureTest.php`
- Modify: `routes/native.php`

**Interfaces:**

- Produces route `/captures/switch`, a stable off/on/error three-row fixture, and the four-image documentation matrix.
- Adds `switch` to `spec/screenshots.json` with the focused capture test command.

- [ ] **Step 1: Use the Firstlight documentation skills**

Read and announce `.agents/skills/firstlight-docs-write/SKILL.md` for the new component reference and `.agents/skills/firstlight-docs-screenshots/SKILL.md` for capture. Follow their source and appearance-restoration requirements.

- [ ] **Step 2: Write the capture RED test**

Assert `/captures/switch` publishes exactly three `firstlight.switch` nodes labelled `Notifications`, `Automatic updates`, and `Setting with error`, with values `false`, `true`, and `false`; assert the error text and accessibility.

Run:

```bash
php artisan test tests/Feature/SwitchCaptureTest.php
```

Expected: FAIL because the route and fixture do not exist.

- [ ] **Step 3: Implement and verify the stable capture route**

Add `SwitchCapture`, its Blade view, and route using `ShowcaseLayout`. Keep fixture copy deterministic and free of timestamps or production references.

```bash
php artisan test tests/Feature/SwitchCaptureTest.php
```

Expected: PASS.

- [ ] **Step 4: Write the public component reference**

Replace the scaffold marker with current frontmatter and sections matching `docs/components/segmented.md`: complete example, props, events, strict booleans, state timing, disabled behavior, accessibility, validation/failure behavior, platform behavior, compatibility, and screenshots. State that `value="false"` is invalid and show `:value="false"`. Add Switch to `docs/index.md`.

- [ ] **Step 5: Register the screenshot matrix**

Add this literal entry to `spec/screenshots.json`:

```json
"switch": {
    "route": "/captures/switch",
    "test": "php artisan test tests/Feature/SwitchCaptureTest.php",
    "outputs": {
        "ios-light": "docs/screenshots/switch/ios-light.png",
        "ios-dark": "docs/screenshots/switch/ios-dark.png",
        "android-light": "docs/screenshots/switch/android-light.png",
        "android-dark": "docs/screenshots/switch/android-dark.png"
    }
}
```

- [ ] **Step 6: Build docs and verify source-backed content before capture**

```bash
composer run docs:build
composer run docs:check
git diff --check -- docs spec
```

Expected: generated LLM artifacts include Switch and documentation validation reports only missing Switch images before capture.

- [ ] **Step 7: Launch explicit simulator and emulator targets**

Use iPhone 17 Pro `7B3D2D67-FA32-4419-A020-C15B72509CCF`. Launch Android `Pixel_9_Pro` on fixed port 5554 so its serial is `emulator-5554`:

```bash
xcrun simctl boot 7B3D2D67-FA32-4419-A020-C15B72509CCF
/Users/wojt/Library/Android/sdk/emulator/emulator -avd Pixel_9_Pro -port 5554 -no-snapshot-save
adb -s emulator-5554 wait-for-device
```

Keep the emulator process in its own terminal session; do not block the agent with a long foreground command.

- [ ] **Step 8: Capture all four screenshots through the repository tool**

```bash
bin/capture-doc-screenshots Switch \
  --showcase=/Users/wojt/Code/clinically-au/firstlight-showcase \
  --ios=7B3D2D67-FA32-4419-A020-C15B72509CCF \
  --android=emulator-5554
```

Expected: four output paths under `docs/screenshots/switch/`, with both apps restored to their original appearance after capture. Do not use `--release` while the upstream publication dependency is unreleased.

- [ ] **Step 9: Inspect images and commit both repositories separately**

Open all four images with the local image viewer. Verify label wrapping, switch placement, checked/disabled state, error contrast, light/dark appearance, and no clipping. Then:

```bash
# showcase
git add app/NativeComponents/Captures/SwitchCapture.php resources/views/native/captures/switch.blade.php tests/Feature/SwitchCaptureTest.php routes/native.php
git commit -m "test: add Switch capture fixture"

# package
git add docs/components/switch.md docs/index.md docs/screenshots/switch spec/screenshots.json llms.txt llms-full.txt
git commit -m "docs: publish Firstlight Switch"
```

---

### Task 7: Run constitutional development review and close the component milestone

**Files:**

- Create: `spec/reviews/2026-08-04-firstlight-switch-development.md`
- Modify only if evidence finds a real defect: Switch-owned source/test/docs paths from prior tasks.

**Interfaces:**

- Produces a requirement-by-requirement `PASS`, `FAIL`, or `BLOCKED` verdict with exact commands, commits, screenshots, targets, and upstream assumptions.

- [ ] **Step 1: Read and announce the required review skill**

Read `.agents/skills/firstlight-review-component/SKILL.md`, the constitution, alpha design, approved Switch design, public docs, manifest, implementations, tests, screenshots, and both repository statuses. Use `development` review mode.

- [ ] **Step 2: Run package and structural gates**

```bash
bin/check-component SwitchControl --development
composer test
xcodebuild -scheme FirstlightIOSControls -destination 'platform=iOS Simulator,id=7B3D2D67-FA32-4419-A020-C15B72509CCF' test
JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home tests/android/gradlew -p tests/android testDebugUnitTest
composer validate --strict
git diff --check
```

Expected: all development gates pass without warnings.

- [ ] **Step 3: Run exact-commit consumer and plugin gates**

From the showcase:

```bash
composer update firstlightui/nativephp --with-dependencies
php artisan test tests/Feature/SwitchShowcaseTest.php tests/Feature/SwitchCaptureTest.php
php artisan test
php artisan native:plugin:validate /Users/wojt/Code/clinically-au/firstlight-ui
php artisan native:run ios 7B3D2D67-FA32-4419-A020-C15B72509CCF --no-tty --start-url=/captures/switch
php artisan native:run android emulator-5554 --no-tty --start-url=/captures/switch
```

Expected: consumer tests, plugin validation, both host builds, installs, and launches succeed without generated-tree edits.

- [ ] **Step 4: Perform development accessibility and interaction inspection**

On both targets verify: accessible name and on/off value, switch role, hint, disabled state, error announcement, single focus stop, 44/48 target, dark mode, increased contrast, large text/font scale, Reduced Motion, RTL mirroring, long label, native pressed feedback, rapid taps, rejected proposal, accepted proposal, and programmatic change. Record exact target/runtime and result for each row.

- [ ] **Step 5: Write the constitutional verdict**

In `spec/reviews/2026-08-04-firstlight-switch-development.md`, list Articles I-XI individually as `PASS`, `FAIL`, or `BLOCKED` with exact evidence. Mark development readiness `PASS` only if all development checks pass. Separately mark component-release and catalogue readiness `BLOCKED` by the unreleased identical-publication dependency and missing dated physical-device rows; do not soften those blockers.

- [ ] **Step 6: Re-run focused gates after any review repair**

If review reveals a defect, add a failing test first, make the minimum fix, rerun its focused suite, then rerun Steps 2-4. Do not edit the constitution to make a failure pass.

- [ ] **Step 7: Commit the evidence report**

```bash
git add spec/reviews/2026-08-04-firstlight-switch-development.md
git commit -m "docs: record Switch development review"
```

- [ ] **Step 8: Report the honest milestone**

Report exact package and showcase commits, all passing commands, screenshot paths, simulator/emulator targets, accessibility results, and unresolved blockers. State “Switch development-proven” rather than “alpha ready.” Do not push, publish, tag, or open a PR.
