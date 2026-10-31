# Changelog

All notable changes to this project will be documented in this file.

## 1.0.0 - 2026-10-31

### Added

- Pill Group: options accept `icon`, `icon-ios`, and `icon-android`; `scrollable` shows the pills in one horizontally scrolling row; `collapse-labels` shows only the icon of unselected pills and reveals the selected pill's label; and `tone="primary|accent|success|warning|danger"` sets the selected pill's colour from the theme while unselected pills stay uniform. Existing groups render unchanged. Group-level `icon` is still rejected, now with guidance to put icons on options.

### Changed

- `nativephp/mobile-ui` 0.9.0 is now on Packagist, so apps no longer need the VCS repository workaround from RC12. Install with `composer require firstlightui/nativephp`; the `@RC` stability flag is no longer needed.
- Require iOS 18.2+. Android remains API 29+, with NativePHP Mobile `^4.7.0` and Mobile UI `^0.9.0` as the dependency floor. Regenerate native projects with `php artisan native:install --force` and rebuild the host applications after updating Composer dependencies.
- `ValidatesFields` now composes upstream `ValidatesInput` and shares the `FieldErrors` render scope with Mobile UI controls. Existing Form Request sources, Media conversion, `$errorBag`, summaries, and accessibility announcements remain available; views also receive upstream's `$errors`.

### Fixed

- Android Text Field, Text Area, Search Field, and Code Editor register Mobile's focused-input flush hook so pending blur/debounce text posts before a separate Button or Icon Button press, including accessibility activation, without a duplicate debounce event.
- Navigation farewell responses close Firstlight Bottom Sheets and Modals, including nested overlays, without changing their bound visibility properties or retained Element trees.
- Field-only validation through an interaction preserves other fields' errors. Deferred listeners and drag-end callbacks retain validation messages and announcements instead of opening the error overlay.

### Qualification

- Verified on physical iOS and Android devices, including VoiceOver on iOS. Spoken TalkBack output and soak/performance testing have not been recorded.
- Response revisions remain required: Mobile 4.7's frame-fingerprint optimisation does not resolve identical-response suppression.

## 1.0.0-RC12 - 2026-10-30

### Changed

- Firstlight now requires `nativephp/mobile` `^4.7` and `nativephp/mobile-ui` `^0.9`.

### Fixed

- Blade: on `nativephp/mobile` 4.7, a nested container such as a `list-section` inside a `list`, or an `<x-*>` Blade component inside a sheet, modal or list, no longer fails to compile with app.debug on. With debug off it no longer logs a false unknown-element report. Firstlight passed already-compiled component tags through NativePHP's new unknown-tag check.

### Known issues

- `nativephp/mobile-ui` 0.9.0 is tagged on GitHub but not yet on Packagist. Until it is, installing this release needs a VCS repository for `https://github.com/NativePHP/mobile-ui.git` in the app's `composer.json`.
- Android: on the Pixel 9 Pro emulator (Android 16 arm64), NativePHP 4.7's PHP binaries (OpenSSL 4.0.3) crash with SIGILL in `OPENSSL_cpuid_setup` before any PHP runs. This is upstream and has not been checked on physical devices. iOS is verified.

## 1.0.0-RC11 - 2026-10-29

### Fixed

- iOS Bottom Sheet: with `pinned-footer`, the footer now keeps its own height pinned at the bottom of the sheet. Before, the Mobile UI column inside the footer took the full proposed height, squeezing the body's scroll view to a sliver so only the footer showed, at the top of the sheet. The detent still covers both body and footer.
- iOS Select and Date Picker: `variant="chip"` draws its own filled capsule, so select chips no longer render without their fill (the `Menu` trigger did not reliably adopt the bordered style, most visibly on a sheet's first open). Chips also keep their intrinsic width instead of wrapping letter by letter when a row is too narrow.
- Android Choice Group: selected `variant="tiles"` tiles, and the radio and checkbox marks in both variants, use the theme's `primary` colour, as on iOS, instead of Material's default lavender `secondaryContainer`.
- Callout: the `action-label` action is drawn as an outlined, tone-tinted button on iOS and Android instead of bold text that did not look tappable.
- Android Select: the primary focus outline shows only while the menu or search dialog is open, instead of staying on the field after it closes.

### Known issues

- Mobile UI iOS ignores `flex-wrap` on `native:row`, so a row of chips stays on one line and overflows rather than wrapping. This is upstream: `nativephp/mobile` 4.6.1 `FlexContainer.swift` stores `wrap` but never reads it (Android wraps). Firstlight chips now refuse to shrink below their intrinsic width.

## 1.0.0-RC10 - 2026-10-29

### Added

- List Item: `leading-label` for identifiers such as bed numbers, `trailing-status` with semantic tones, and `provisional` / `pending-removal` states. Nonnormal states require an authored `state-label` for assistive technology and do not disable row actions.
- Choice Group: `variant="tiles"` with optional option `prefix` and `supporting` text for rich single- or multi-select grids. Stable values and server-authoritative selection are unchanged.
- Select and Date Picker: `variant="chip"` for compact native triggers. Date Picker also accepts `display-text` for an accepted chip date without replacing its canonical value or spoken date.
- Bottom Sheet: `pinned-footer` keeps the final direct child visible while the preceding content scrolls. At least two direct children are required.
- Text Field: `helper-tone="success|warning|neutral"` for supporting guidance; validation errors still take precedence.
- Documentation and showcase examples for the new variants and for comparison/read-only summaries composed from existing native text and Status Label controls.

### Fixed

- Android Bottom Sheet: content taller than the screen scrolls instead of being clipped, including sheets without a pinned footer. Existing authored scrolling containers retain a bounded viewport instead of nesting inside another vertical scroller.
- List Item: trailing statuses stack below the headline and supporting text at accessibility text sizes instead of squeezing the row text.
- Package tests: the manifest version assertion matches the release, the iOS test theme supplies its success colour, and iOS 27 Date Picker references include RC8's intentional theme-primary picker tint.

### Qualification

- This remains a prerelease, not a completed catalogue/alpha qualification. Full NativePHP consumer-host builds, new-state native documentation capture matrices, touch/swipe and spoken screen-reader checks, physical-device checks, and wider soak/performance evidence remain outstanding. Existing test-host diagnostics and the largest-text Date Picker Confirm wrapping remain unchanged.

## 1.0.0-RC9 - 2026-10-26

### Changed

- Transient Feedback: timed feedback with an action, such as `Feedback::success('Resolved')->action('Undo', 'undo')->send()`, now shows the same dismiss control as held feedback (× with the package chrome labels `Dismiss` / `Dismiss feedback`) beside its action on iOS and Android. Before, such a notice covered the bottom action bar and tab bar until it timed out. The timeout still applies. Pressing the control dispatches `FeedbackDismissed` with `FeedbackDismissReason::Manual`, as held feedback already does. Timed feedback without an action is unchanged, and there is still no swipe-to-dismiss or duration option.

## 1.0.0-RC8 - 2026-10-26

### Security

- Firstlight now requires `nativephp/mobile` `^4.6.1`, the release that fixes NativePHP's October 2026 advisories (async task bridge code injection, object instantiation through `/_native/api/events`, unconfined `File.Move`/`File.Copy`, unauthenticated async payloads, `/_assets` path traversal, and Android cookie logging) and ships PHP runtimes patched for CVE-2026-109618 and CVE-2026-109619. Firstlight itself calls none of the affected bridge functions, and its Feedback events are dispatched in PHP, not through the events endpoint. Apps must still run `php artisan native:install --force`, rebuild and ship a new store release; see [NativePHP's post](https://nativephp.com/blog/our-first-security-advisories-and-this-weeks-php-cves).

### Added

- Packaged translations of the chrome and validation-summary strings for German, Spanish, French, Italian, Japanese, Dutch, Polish, Brazilian Portuguese and Simplified Chinese. Apps whose locale matches one of these get translated Confirm/Cancel, clear, password-reveal, Media and Code Editor labels and error summaries without publishing anything. The translations were machine-drafted and still need native-speaker review.
- Recipes for a large list screen (`native:virtual-list` with Firstlight List Item rows and one query per visible window) and a photo upload screen (a cropped Media field validated with Laravel rules and saved to a model on Save). Each runs unchanged in the showcase app with tests. The paginate-lists and List pages now recommend a virtual list for thousands of local records and pagination for remote data.
- Text Field `mask`: a display-only input pattern such as `mask="#### #### #### ####"`. `#` is a digit, `A` a letter and `*` either; other characters are literals inserted as the person types. The bound value, `@change` and `@submit` receive only the slot characters, for example `4111111111111111`. Characters that fit no slot are dropped, input stops when the pattern is full, and paste accepts raw or formatted text. The mask never changes the keyboard, and it cannot be combined with `secure`. See the [input masks addendum](spec/designs/2026-10-25-firstlight-text-field-masks-design.md).
- Media: the crop sheet can pan. Drag the photo to choose which part to keep, pinch or use the zoom buttons to zoom, and the region stops at the photo's edges at any zoom. VoiceOver and TalkBack get Move crop left, right, up and down actions, with the labels in package chrome for every shipped locale. Confirm stores exactly the region the sheet shows; it was always the centred region before.

### Fixed

- Android Text Field: external width and flex utilities apply to the complete field, so a `flex-1` field shares a row with a trailing Icon Button instead of consuming its space. Text Field on both platforms also applies a pending PHP value on blur without echoing it, while a newer local edit supersedes the pending value; a PHP-cleared draft no longer reappears after send.
- Icon Button: presses dismiss the focused input through NativePHP's keyboard policy. On iOS this flushes pending Firstlight `.blur` and `.debounce` edits before dispatching the press; Android applications should continue using live models for separately submitted fields.
- iOS Text Field and Text Area: padding participates in the input's focus target. Text Field number, decimal and phone keyboards include a localized Done accessory that commits, submits and dismisses, including for masked input.
- iOS Text Field: focus uses the enclosing Mobile UI scroll proxy after keyboard presentation. Masked numeric input retires its Done accessory before resignation, preventing the next field's keyboard inset from losing the accessory height.
- iOS Button: flex growth and fixed or percentage widths expand the native button label into the host's allocation without changing the outer layout constraints.
- iOS Date Picker and Time Picker: the presented native picker uses the theme primary tint instead of the system blue.
- Android List Item: the combined accessibility description preserves existing sentence-ending punctuation instead of adding a second full stop between the headline, supporting text and hint. Explicit labels follow the same rule; iOS already separates its label and hint without this duplication.
- iOS Text Field, Text Area, Code Editor, Media, Date Picker and Time Picker fill with the theme's `surface-variant` instead of the system `secondarySystemBackground`. In dark mode the system grey sat darker than a navy theme background and did not match Android, which already uses theme colours.
- Docs: every recipe gives its screen root and its lists `bg-theme-background`, and the theming page explains why. On iOS a root column, scroll view or list without a background class paints the system background, pure black in dark mode, so recipe screens did not match the theme.
- Media: image mode now shows a thumbnail of the chosen photo instead of a generic photo icon. The renderers were given `Storage::url()`, which on `mobile_public` is an `_assets` URL that only the WebView serves, and never loaded it. The field now publishes the file's local path, and iOS (ImageIO) and Android (`BitmapFactory`) decode a subsampled thumbnail off the main thread. Disks without a local driver keep the icon.
- Media: a validation error for the field's `native:model` property now shows under the field. Media requires `@change`, whose callback replaced the model-sync callback that the error binder read the field name from, so errors appeared only with an explicit `error-for`. Any Firstlight field with both `native:model` and `@change` is fixed the same way.
- iOS Button: a `ghost` button now has a 44-point minimum touch target. Mobile UI's plain ghost style only responded to taps on its text, about 19 points tall. Other variants and Android are unchanged.
- Android Button: TalkBack now announces every Button as a button with its label and hint. NativePHP's press handler wrapped the Material 3 button in a role-less clickable, so TalkBack focused a plain view. Drawing and touch handling are unchanged.

## 1.0.0-RC7 - 2026-10-24

### Added

- Button accepts the shared icon contract overrides `icon-ios`, `icon-android`, `icon-trailing-ios` and `icon-trailing-android`, plus their camelCase aliases. Before, they were silently ignored, so a shared name that NativePHP's iOS icon helper cannot resolve, such as `chevron-right`, left the iOS button without a glyph.

### Changed

- Documentation now presents Firstlight as the app-shell layer for NativePHP. The four recipes lead the documentation index, a new "Build with a coding agent" page covers the Boost guideline, skills, and `llms.txt`, the concept page states what Firstlight is not, and the accessibility page leads with what users get and its verification status.
- `llms.txt` opens with the documentation index's description and lead text and groups pages under the index's sections. `llms-full.txt` opens with the same description.

### Fixed

- iOS Confirmation Dialog: on iOS 26 the dialog showed as a popover at the top leading edge over the navigation title, without its cancel action. It is now a UIKit action sheet that always shows both actions. It is anchored only at regular width (iPad). Callbacks, copy updates and programmatic closure are unchanged.
- iOS Media: the Choose/Replace button and the crop sheet actions use the theme primary instead of the system blue.
- Android Checkbox: a disabled checkbox now dims its label and helper with the 0.38 disabled-content alpha, as Stepper does.
- iOS Search Field: a disabled field now dims its text and search glyph. Before, only Android dimmed them.
- Docs: the Modal and Bottom Sheet examples use a real heading (`native:text` at title weight) instead of a Status Label, which read as a badge. The Progress examples set `class="w-full"`, because Android otherwise keeps the Material default track width.
- `bin/check-component` takes the documentation, screenshot and review slug from the manifest type, so List (`ListContainer`) is checked against `list`, not `list-container`.
- `spec/screenshots.json` captures List Section from its own showcase route instead of reusing `/captures/list`.
- The Boost guideline, the `firstlight-init` and `building-firstlight-screens` skills, and `llms.txt` linked to `https://firstlightui.dev/how-to/...` and similar paths, which return 404. They now link to `https://firstlightui.dev/docs/...`.

## 1.0.0-RC6 - 2026-10-24

### Fixed

- Slider: a fractional step such as `0.1` no longer breaks the screen after a drag. The renderer sends its value back as a 32-bit Float (0.3 arrives as 0.30000001192…), which the strict step-grid and bounds checks rejected on the next render, so PHP showed the error screen. The iOS slider could then take props from an unrelated error-screen node with the same id, which is the likely cause of a reported TestFlight crash. Values from the renderer now get Float round-trip tolerance, authored off-grid values are still rejected, and on both platforms a slider takes new props only from a `firstlight.slider` node.
- iOS Date Picker and Time Picker: on iPhone the calendar or time wheel now opens in a sheet sized to its content instead of a full-height sheet. The sheet's detent is the measured height of the picker and its Cancel and Confirm actions, so it grows with Dynamic Type and falls back to the large detent only when the content needs it. iPad keeps its popover. A compact popover was tried and rejected: UIKit shrinks it to the space on one side of a mid-screen trigger and clips the month header and actions. Cancel and Confirm, dismiss-to-cancel, min/max bounds, accessibility and validation focus are unchanged. Android is unchanged.
- iOS Segmented: inside a native list (for example a `native:list-section` row), the selected segment now slides to the new option instead of jumping to it. UIKit runs the selection animation in the control's next layout pass, and the list ran that pass while it re-sized the row with animations disabled. The control now finishes that layout pass as soon as the selection changes. Segmented controls outside lists, and Android, were not affected.

## 1.0.0-RC5 - 2026-10-24

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
