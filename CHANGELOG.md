# Changelog

All notable changes to this project will be documented in this file.

## Unreleased

### Changed

- iOS XCTest: snapshot references are now kept per iOS major version. iOS 26 keeps the existing `tests/ios/__Snapshots__` set unchanged, and a new `tests/ios/__Snapshots__iOS27` set was recorded on an iPhone 17 Pro / iOS 27.0 simulator. The suite passes on both runtimes. Any other runtime fails with a message to record a set. `TEST_RUNNER_FIRSTLIGHT_RECORD_SNAPSHOTS=1` records only the running runtime's set.

### Fixed

- Android Stepper: the decrease and increase actions are now Material 3 outlined icon buttons with Remove and Add icons, instead of bare `−` and `+` text with no container. The value is centred between them, and the label shares their row with the controls at the trailing end, as on iOS. The row mirrors in right-to-left layouts, icons stay 24 dp at large font scales, and a disabled Stepper dims its label and helper. The public API, accessibility semantics, and min/max/disabled behaviour are unchanged.
- iOS: going back from a pushed screen in a native-chrome layout no longer plays two transitions with a blank page between them. `back()` now republishes an unchanged screen with its current response revision, so NativePHP drops the frame instead of re-pushing the screen being left. This affects the toolbar back button and the edge swipe, in stack layouts and tab layouts. Android was not affected.
- iOS XCTest: rendered-accessibility tests for List Item context-menu actions and Switch semantics now enable system accessibility themselves, so they no longer see an empty accessibility tree on simulators where accessibility was never switched on. The Feedback Center hit-target snapshot pins dark appearance. Assertions and reference images are unchanged.

### Known limitations

- Correction to RC4: not all 62 iOS failures predated RC4. On the reference iPhone 17 Pro / iOS 26.5 simulator the suite had 4 failures, all environmental and now fixed; it passes with 0 failures. The other 59 are pixel-level runtime differences on iOS 27.0, where snapshot references had not been recorded. The iOS 27 set under Changed resolves them.
- Tabs snapshot references do not capture the selected-segment indicator on iOS 26.5. The iOS 27 references do.
- The iOS 27 Media crop-sheet reference does not show the "Crop" navigation title, which the iOS 26.5 reference draws in white. A host-app capture on iOS 27 has not yet confirmed whether the title is legible on device.

## 1.0.0-RC4 - 2026-10-24

### Added

- List Item `context-menu`: a native long-press menu (SwiftUI `.contextMenu` on iOS, Material 3 `DropdownMenu` on Android) with labelled items, shared/platform icons, destructive role, disabled items, and PHP callbacks. Enabled items are also VoiceOver and TalkBack actions, so no long press is required.
- Form-level validation errors on the existing Callout: `error-for="key"` shows that `ValidatesFields` key's first message, and `error-summary` lists every key's first message under a translatable heading (`firstlight::validation.summary`). Both default to `danger` and render nothing while there is nothing to show. No new catalogue tag.
- Screen-reader announcements for validation errors. After a failed press or keyboard submit, VoiceOver and TalkBack speak a summary of all messages once and screen-reader focus moves to the first invalid field; a failed field update (blur, live, change, `updated{Property}`) speaks only its own message without moving focus. iOS moves VoiceOver focus without opening the keyboard; Android moves input focus only to Text Field, Text Area, and Code Editor, and only while TalkBack is on.
- `FirstlightUI\Testing\ScreenAssertions::register()` adds Firstlight checks to NativePHP's `Native::test()` harness: `assertFieldError()`, `assertNoFieldError()`, `assertHasErrors()`, `assertHasNoErrors()`, `field()`, `choose()` by stable value, `listItem()`, `assertListItem()`, `assertMissingListItem()`, `assertListItemCount()`, `pressListItem()` including swipe actions, Confirmation Dialog `assertConfirmationDialog()`, `confirmDialog()` and `cancelDialog()`, `assertFeedbackSent()` and `assertNoFeedbackSent()`. It also adds the Pest expectations `toHaveFieldError()` and `toHaveListItem()`.
- `php artisan make:firstlight-screen` creates a `FirstlightUI\NativeComponent` screen and its Blade view. `--form`, `--list` and `--destroy` add `submit()`, list pagination and confirmed deletion. `--test` adds a Pest test.
- `NativeComponent::isRootScreen()` reports whether anything is below the screen on PHP's navigation stack, so a native-chrome layout can show a large title on the first screen and a back button on pushed ones.
- App recipes for a list and detail flow, a searchable list, a form screen, and a settings screen. Each runs unchanged in the showcase app with tests.

### Changed

- `submit()` now keeps the messages of a `ValidationException` thrown by its action (for example `ValidationException::withMessages(['form' => ...])`), so actions can fail the whole form. Previously those messages were discarded.
- The package now requires `illuminate/console` to register the generator.

### Fixed

- iOS: pressing a Button while a Text Field, Text Area, Code Editor or Search Field is focused now publishes the field's pending `.blur` or `.debounce` change before the press, so the handler reads the typed value instead of the previous one. The fields now register with NativePHP's keyboard focus policy, as Mobile UI's own inputs do. Android is unchanged until NativePHP ships the equivalent hook.

### Known limitations

- TalkBack focus on the first invalid field was observed on the Android emulator. VoiceOver cannot run in the iOS Simulator, and spoken output was not captured on either platform; those parts are covered by PHP, XCTest and Robolectric tests. Physical-device VoiceOver and TalkBack checks remain outstanding.
- The error summary lists messages in rule order, not screen order; declare rules in the order fields appear.
- Android does not yet publish a focused field's pending `.blur` or `.debounce` change before a Button press; that needs the hook from NativePHP/mobile-air#565, which is merged but not released. Use plain `native:model` or `.live` for Button-submitted forms that must work on both platforms. On iOS, Icon Button and List Item presses also do not flush the focused field.
- The app recipes were verified on an iOS 27 simulator and an API 37 Android emulator, not on physical devices, in dark mode, or with VoiceOver or TalkBack.
- The RC3 qualification limits still apply: physical-device, spoken VoiceOver/TalkBack traversal, soak/performance and wider-application qualification remain outstanding.

## 1.0.0-RC3 - 2026-10-23

### Fixed

- Android Media now opens the system photo picker, camera and document picker, copies the pick to the cache directory and emits its real path. It previously emitted fixed placeholder paths that the PHP side could not store.
- Media crop Confirm now stores the region the crop sheet shows (centred at the locked or image aspect, narrowed by zoom) on iOS and Android, instead of the uncropped original.
- The plugin manifest declares a default iOS `NSCameraUsageDescription`, so Media's Camera option no longer terminates apps on physical iPhones that omit one. Application `permissions` entries still override it.
- Document the installable Composer constraint (`^1.0@RC`) and describe the package as a release candidate.
- Android Callout tone icons are no longer clipped. The icon matches the message's first line height and scales with font size, as on iOS. It is now hidden from TalkBack, as the contract requires.

### Changed

- Bottom Sheets open at the height of their content on iOS and Android instead of a fixed half-height stop. Short sheets stay short and tall sheets open fully, so controls at the bottom are visible without dragging; the height follows content and text-size changes, up to a full-height sheet. Both platforms now use Firstlight renderers instead of Mobile UI's `bottom_sheet` renderers. The public API is unchanged and `detents` is still rejected.

### Known limitations

- The iOS Bottom Sheet renderer and its XCTest cases were written without an iOS build: neither has been compiled nor run on a simulator or device. Content-height presentation, the full-height cap for very tall content, and the first-presentation transition still need simulator verification.
- Android Bottom Sheet content taller than the screen is clipped rather than scrolled. The Callout and Bottom Sheet fixes have no emulator or device evidence yet, and the Bottom Sheet documentation screenshots predate the content-height change.
- The RC2 qualification limits still apply: physical-device, spoken VoiceOver/TalkBack traversal, soak/performance and wider-application qualification remain outstanding, and the upstream theme-ownership race remains deferred.
- Requirements remain PHP `^8.4`, NativePHP Mobile `^4.6`, Mobile UI `^0.8`, iOS 18+, Android API 29+, and Swift tools 6.2.

## 1.0.0-RC2 - 2026-10-23

### Added

- Optional decorative Status Label icons using the shared `icon`, `icon-ios`, and `icon-android` contract, with tone-owned foregrounds in both appearances.
- Static Metric display with a label, formatted string value, optional detail and semantic tone, one accessible description, tabular figures and large-text wrapping.
- Catalogue decision notes for the core-owned accessible tappable surface and a design-only status-history display.

### Fixed

- Deliver Feedback action and dismissal events to the active screen's `#[On]` handlers as well as Laravel listeners, preserving exactly-once consumption and action-before-dismiss ordering. Screen-local Undo now participates in the normal interaction repaint.

### Known limitations

- Physical-device, spoken VoiceOver/TalkBack traversal, soak/performance and wider-application qualification remain outstanding; simulator/emulator evidence is not stable-release qualification.
- Arbitrary tappable surfaces remain a NativePHP core accessibility handoff; Metric is display-only, and no status-history control is implemented.
- NativePHP's Android Material icon helper draws the resolved icon name but does not accept a separate variant-font argument.
- Opposite-system appearance previews in the showcase can lose contrast when upstream worker bootstraps overwrite the shared native theme. This existing theme-ownership race is explicitly deferred, not patched in Firstlight.
- Requirements remain PHP `^8.4`, NativePHP Mobile `^4.6`, Mobile UI `^0.8`, iOS 18+, Android API 29+, and Swift tools 6.2.

## 1.0.0-RC1 - 2026-10-21

### Added

- Theme-backed List Item leading/trailing icon tones and explicit hex icon colours on iOS and Android.
- `Feedback::fake()` with sent-record queries and assertions, including action keys.

### Fixed

- Document List pagination with NativePHP's supported `@endReached` event spelling.
- Share warning/on-warning theme tokens and accessible amber fallbacks across Status Label, Badge, Callout, and Feedback, including Android Badge's dark appearance.
- Apply the resolved field name and hint to iOS Text Field's editable and secure inputs.
- Treat null or empty List Item supporting/trailing text as absent.
- Keep wrapped iOS Status Label text inside its contrasting fill at maximum Dynamic Type while preserving compact single-line pills.
- Pin iOS snapshot appearance and fit Media, Pill Group, and Callout fixtures to their test canvases without relaxing snapshot equality.
- Preserve stderr diagnostics when a focused release-gate test floods stdout, without increasing the output or timeout limits.
- Align release-gate expectations with the existing Media catalogue and Feedback publication-generation contract; shorten component guidance without weakening its checks.

### Known limitations

- This release candidate is not stable-release qualification: physical-device, VoiceOver/TalkBack traversal, soak/performance, and wider-application evidence remain outstanding.
- Requirements: PHP `^8.4`, NativePHP Mobile `^4.6`, Mobile UI `^0.8`, iOS 18+, Android API 29+, and Swift tools 6.2.
- Blur-bound inputs do not guarantee publication before a separate button press; use live binding for button-submitted forms.
- Packagist `dev-main` was stale during investigation; a package maintainer must inspect its auto-update/app settings outside this repository.

## 0.1.0-alpha.1 - 2026-08-03

### Added

- Initial Firstlight UI package skeleton.
- Paired iOS and Android Segmented renderers using native platform controls.
- String, integer, null, disabled-option, validation, field metadata, and accessibility contracts for Segmented.
- Dynamic Type, Android font scaling, dark-mode, contrast, and representative snapshot coverage.
- Server-authoritative selection timing with repeated-attempt support.
- A separate showcase application for installation and real-device verification.

### Fixed

- Swift 6 actor isolation for the native iOS event transport.
