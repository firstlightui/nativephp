# Changelog

All notable changes to this project will be documented in this file.

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
