---
title: Form-level errors and validation announcements development review
description: Development evidence for error-bound Callouts, error summaries, and one-time VoiceOver/TalkBack validation alerts.
status: current
audience: maintainer
sources:
  - spec/reference/field-validation.md
  - spec/reference/form-submit.md
  - spec/components/callout.md
  - tests/Feature/ValidatesFieldsTest.php
  - tests/Feature/SubmitsFormsTest.php
  - tests/Feature/BladeAttributeDecodingTest.php
  - tests/Feature/CalloutElementTest.php
  - tests/ios/ValidationAlertTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/ValidationAlertTest.kt
---

# Form-level Errors and Validation Announcements: Development Review

Date: 2026-10-24. Development mode; not release qualification.

## Catalogue decision

No new tag. Form-level and summary errors reuse Callout (`error-for`, `error-summary`); announcements and focus are a `ValidatesFields` extension that publishes `validation_alert`, `validation_announcement`, and `validation_focus` props on existing fields and Callout.

## Automated evidence

- PHP: `composer test` — 1439 passed, 5 skipped (pre-existing skips and warnings unchanged).
- iOS: `xcodebuild -scheme FirstlightIOSControls` on iPhone 17 Pro (iOS 26.5) — 294 tests, 0 failures, 6 skipped; existing snapshots unchanged.
- Android: `tests/android/gradlew testDebugUnitTest` (JDK 21) — 301 tests in 85 suites, 0 failures. The Robolectric focus test fails when the Text Field `focusRequester` wiring is removed.
- Showcase: `php artisan test` — 176 passed, including the new `/validation` alert and form-level cases and `/captures/form-errors`.
- `bin/check-docs --development` passed.

## Native evidence

- iOS Simulator (iPhone 17 Pro, iOS 26.5) and Android emulator (`Pixel_9_Pro`): the showcase built against this branch, and `/captures/form-errors` rendered the summary Callout above failed fields in light and dark (`docs/screenshots/form-errors/`).
- iOS `/validation`: a failed Continue showed the summary and field errors; the accessibility tree exposed `Error: There are 3 errors. …`. `taken@example.com` showed the form-level Callout with no success Feedback.
- Android `/validation` with TalkBack enabled: after a failed Continue, TalkBack focus and input focus were on the Name field (first invalid field) and the keyboard opened.

## Outstanding

- VoiceOver cannot run in the iOS Simulator, so iOS VoiceOver focus movement and the spoken announcement were not observed live; they are covered by XCTest state tests only.
- TalkBack's spoken text was not captured (TalkBack does not log speech by default); the announce call is covered by the Android runtime test.
- Physical-device VoiceOver and TalkBack traversal remain release gates.
