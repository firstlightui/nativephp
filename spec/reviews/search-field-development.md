---
title: Search Field development review
description: Constitutional review of the off-device Firstlight Search Field implementation and its remaining controller-owned evidence.
status: blocked
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - spec/designs/2026-08-04-firstlight-search-field-design.md
  - docs/components/search-field.md
  - spec/screenshots.json
---

# Search Field Development Review

Reviewed on 2026-08-04 against the candidate package and installed showcase
worktrees. The implementation is complete off-device. Simulator/emulator access,
runtime screenshots, and physical-device checks remain controller-owned.

**Off-device implementation verdict:** PASS

**Development verdict:** BLOCKED

**Component-release verdict:** BLOCKED

## Constitutional review

| Article | Verdict | Evidence |
| --- | --- | --- |
| I. SuperNative first | PASS | `SearchField` is an ordinary EDGE element registered in `nativephp.json`; paired renderers consume `NativeUINode`, publish standard text/submit wire events, and use no WebView, JSON bridge, generated-tree edit, or alternate state system. |
| II. Familiar APIs | PASS | Strict `value`/`native:model`, `placeholder`, `disabled`, `autocapitalize`, `autocorrect`, `a11y-*`, `class`, `@change`, and `@submit`; no invented search event or renderer terminology. |
| III. Predictable state | PASS | Swift and Kotlin state contracts cover focused draft preservation, acknowledgement, deferred server correction, live/blur/debounce, immediate clear, disabled state, programmatic publication, and empty submission. |
| IV. Equal platform quality | BLOCKED | Production UIKit and Compose implementations exist, and Android production compilation passes. The controller still needs to run the permitted iOS simulator suite; equality cannot be closed from source inspection alone. |
| V. Native expression | BLOCKED | iOS embeds genuine `UISearchTextField`; Android composes Material 3 `OutlinedTextField`, `TextFieldValue`, search IME action, and Material icon buttons. Runtime visual review is pending controller evidence. |
| VI. Accessibility correctness | BLOCKED | Required explicit name, optional hint, editable semantics, Dynamic Type/font scaling support, disabled state, decorative search icon, and 44-point/48-dp clear targets are implemented and tested structurally. VoiceOver/TalkBack runtime review and screenshot evidence are pending. |
| VII. System-first theming | PASS | UIKit owns Apple search chrome; Android uses Material theme defaults. `class` remains external EDGE layout and no style bag or geometry escape prop exists. |
| VIII. Small proven expansion | PASS | The approved contract and Mobile UI primitive audit precede the renderer. Search Field remains narrower than Text Field and owns only semantic search/clear behavior. |
| IX. Evidence quality | BLOCKED | PHP, structural docs, installed showcase, plugin validation, Swift parsing, and consumer tests pass. iOS simulator execution and controller-owned runtime captures remain pending; physical-device evidence blocks release. |
| X. Alpha stewardship | PASS | The public API is additive, docs describe its exact compatibility boundary, and no package publication or tag is part of this change. |
| XI. Skills enforce the constitution | PASS | The create, iOS, Android, and review skills were applied; `bin/scaffold-component SearchField` ran once after the failing Pest contract, and `bin/check-component SearchField --development` passes. |

## Implementation path and official primitive audit

Search Field is a paired renderer. Mobile UI's generic filled, outlined, and
bare text inputs provide standard binding modifiers, but do not expose a native
UIKit search-field expression or make search/clear affordances an invariant,
narrow public contract. The Apple renderer therefore embeds `UISearchTextField`.
The Android renderer uses Material 3 text/search primitives while retaining
`TextFieldValue` selection and composition.

## Off-device verification

- `vendor/bin/pest --compact tests/Feature/SearchFieldElementTest.php tests/Feature/PluginManifestTest.php`: 19 passed, 87 assertions.
- `composer test -- --compact`: 387 passed, 1,131 assertions; five model evals skipped by default.
- `bin/check-component SearchField --development`: passed.
- `bin/build-docs-artifacts && bin/check-docs --development`: passed.
- `swiftc -parse` over Search Field production and test sources: passed.
- Focused Search Field Android JVM/Paparazzi task: passed; the controller-owned screenshot case was skipped by its explicit evidence guard.
- Complete Android `testDebugUnitTest`: passed.
- `composer validate --strict`: passed in package and showcase.
- Showcase focused tests: 15 passed, 185 assertions.
- Showcase full test suite: 58 passed, 878 assertions.
- `php artisan native:plugin:validate vendor/firstlightui/nativephp`: passed with the expected no-bridge-functions warning for a UI-only plugin.
- `git diff --check`: passed in both repositories.

Adding Search Field's earlier-alphabetic real-Blade contract exposed an existing
order dependency in `SegmentedElementTest`: after `Component::flushCache()`, its
temporary view factory lacked the `__components` namespace. Registering the same
temporary namespace used by newer component tests makes the full suite
order-independent; that focused test-harness repair belongs to this component.

## Pending controller evidence

- Run the Search Field iOS XCTest contract and snapshot case on the exact
  permitted simulator target.
- Attempt the stable `/captures/search-field` light/dark runtime matrix on each
  permitted target. Screenshot failure may be waived per the roadmap controller,
  but the attempt and reason must be recorded.
- Complete VoiceOver, TalkBack, keyboard/IME, focus retention after clear,
  marked-text composition, rapid input, reconciliation, font scaling, contrast,
  RTL, reduced-motion, and offline checks on physical devices before release.

Search Field must not be described as development-complete or release-ready
until the applicable blocked rows are closed or the controller records the
explicit screenshot waiver.
