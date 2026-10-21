# Changelog

All notable changes to this project will be documented in this file.

## 1.0.0-RC1 - 2026-10-21

### Added

- Theme-backed List Item leading/trailing icon tones and explicit hex icon colours on iOS and Android.
- `Feedback::fake()` with sent-record queries and assertions, including action keys.

### Fixed

- Document List pagination with NativePHP's supported `@endReached` event spelling.
- Share warning/on-warning theme tokens and accessible amber fallbacks across Status Label, Badge, Callout, and Feedback, including Android Badge's dark appearance.
- Apply the resolved field name and hint to iOS Text Field's editable and secure inputs.
- Treat null or empty List Item supporting/trailing text as absent.
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
