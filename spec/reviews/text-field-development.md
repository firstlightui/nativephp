---
title: Text Field development review
description: Constitution review and simulator evidence for the Firstlight Text Field build.
status: complete
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-text-field-design.md
  - docs/components/text-field.md
  - spec/screenshots.json
---

# Text Field Development Review

**Development verdict:** PASS

**Release verdict:** BLOCKED

Reviewed on 2026-08-04 against package commit `cc81c14` and showcase commit
`535ed6a`. The accepted runtime screenshots were produced from package commit
`46fd342` plus the subsequently committed screenshot files; the showcase source
was the dirty working tree later committed without content changes as `535ed6a`.
This is traceable development evidence, not a clean release capture.

## Requirement review

| Requirement | Verdict | Evidence |
| --- | --- | --- |
| Public EDGE contract and official callbacks | PASS | Strict PHP builder, real Blade native/web compilation, `@change`, `@submit`, standard `@press`, and manifest tests. |
| Focused native editing and reconciliation | PASS | Swift and Kotlin state tests cover selection/composition-safe local drafts, live/blur/debounce timing, server reconciliation, clear, reveal, and submit ordering. |
| Genuine Apple presentation | PASS | SwiftUI `TextField`/`SecureField`, native keyboard/content hints, read-only selection, Dynamic Type snapshot, and iPhone 17 Pro runtime captures. |
| Genuine Material presentation | PASS | Material 3 `OutlinedTextField`, `TextFieldValue`, IME/autofill policy, font-scale Paparazzi baseline, and Android emulator runtime captures. |
| Icon naming and accessibility | PASS | Shared, iOS, and Android leading/trailing names follow `spec/reference/icons.md`; authored actions require an accessibility label; semantic actions own localized native icons. |
| Android icon variant metadata | PASS | Typed `AndroidSymbol` variants are retained on the wire and in renderer configuration. The current NativePHP `MaterialIcon` helper still renders by name only, so visual filled/outlined selection remains an upstream capability limit. |
| Accessibility structure | PASS | Development labels, error/helper semantics, decorative-icon hiding, separate 44-point/48-dp actions, showcase `assertAccessible()`, and large-text snapshots pass. |
| Public docs and installed showcase | PASS | Reference page, LLM artefacts, capture manifest, exact-package focused showcase tests, plugin validation, both host builds, and the visually approved four-image matrix pass. |
| Physical-device interaction | BLOCKED | VoiceOver, TalkBack, keyboard/IME, focus, copy/selection, rapid input, offline behavior, and performance have not been reviewed on physical devices. |
| Clean release capture | BLOCKED | Accepted images came from a development capture while the showcase fixture was uncommitted; release mode must be rerun from clean package and showcase commits. |

## Verification

- `vendor/bin/pest --compact`: 76 passed, 357 assertions; five model evals skipped by default.
- `xcodebuild -quiet -scheme FirstlightIOSControls -destination 'platform=iOS Simulator,id=EB44C64E-1579-4C13-A1F9-C44FBD496763' ... test`: passed.
- `JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home tests/android/gradlew -p tests/android testDebugUnitTest`: passed.
- `bin/check-component TextField --development`: passed.
- `bin/build-docs-artifacts && bin/check-docs --development`: passed.
- `php artisan test tests/Feature/TextFieldShowcaseTest.php tests/Feature/TextFieldCaptureTest.php`: 5 passed, 95 assertions.
- `php artisan native:plugin:validate vendor/firstlightui/nativephp`: passed with the expected no-bridge-functions warning for this UI-only plugin.
- `php artisan native:run ios ...` and `php artisan native:run android ...`: both host builds and launches passed through the guarded capture workflow.

## Accepted simulator evidence

- iOS: iPhone 17 Pro, iOS 26.5, light and dark.
- Android: `emulator-5554`, ARM64 emulator, light and dark.
- Images: `docs/screenshots/text-field/{ios,android}-{light,dark}.png`.
- Visual review: correct Text Field route, native platform treatment, stable
  label/helper/error states, clear and reveal actions, no clipping, no stale or
  loading state, and differentiated light/dark appearances.

Do not describe Text Field as release-ready until both blocked rows are closed.

## Development screenshot evidence update — 2026-08-05

Text Field's current four-image development matrix is present and visually
approved in the [alpha catalogue screenshot evidence](2026-08-05-alpha-screenshot-evidence.md).
This supersedes only earlier pending or absent screenshot statements. Clean
release capture, physical-device, assistive-technology, and component-specific
blocked rows remain unchanged.
