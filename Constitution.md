# Firstlight UI Constitution

Ratified: 2026-08-03

Last amended: 2026-08-04 (Articles III.3 and IX.6)

Status: approved founding constitution

## Preamble

Firstlight UI exists to make NativePHP applications feel at home on every supported platform. It provides a familiar Laravel and EDGE authoring experience backed by genuine SwiftUI and Jetpack Compose controls.

Firstlight values clear APIs, native interaction, equal platform quality, accessibility, and evidence over component count or superficial visual consistency. This constitution governs component design, implementation, review, and release.

## Article I: SuperNative first

1. Firstlight is a NativePHP SuperNative UI Component plugin.
2. Components participate in the official EDGE, Element Tree, shared-memory frame, renderer, and wire-event lifecycle.
3. Firstlight does not introduce a parallel renderer, HTML-to-native layer, or WebView-backed control system.
4. Ordinary control state and animation stay on the native UI thread and do not use JSON bridge calls.
5. PHP receives semantic events. Continuous future interactions use SharedValues when appropriate.
6. Components must support normal SuperNative reconciliation, identity, and hot reload.

## Article II: Familiar and coherent APIs

1. Firstlight follows NativePHP conventions whenever they express the same concept.
2. Shared props and events use familiar names including `value`, `label`, `disabled`, `placeholder`, `native:model`, `@change`, and `@press`.
3. Equivalent migrations should normally require changing the Blade namespace rather than relearning the component.
4. Compatibility preserves the NativePHP mental model, not incidental limitations or ambiguous behaviour.
5. Shared APIs never expose SwiftUI or Compose implementation terminology.
6. New props must express durable user-facing semantics rather than one renderer's current implementation.

## Article III: Stable values and predictable state

1. Selection controls bind stable public values, never display labels or renderer indexes.
2. `null` represents no selection.
3. User interaction emits immediately; visible control state remains server-authoritative until PHP publishes the accepted value.
4. Server reconciliation must not echo events, replay animations, or produce feedback loops.
5. Programmatic state changes do not masquerade as user changes.
6. Disabled choices cannot emit changes.
7. Missing selections remain unselected; components never silently choose the first option.
8. Malformed input, duplicate values, and unsupported value types fail loudly with actionable diagnostics.

## Article IV: Equal platform quality

1. iOS and Android have equal status.
2. A component is incomplete until both production renderers meet the shared contract.
3. No public release may contain a finished implementation on one platform and a placeholder, degraded substitute, or knowingly inferior implementation on the other.
4. Every documented example must work unchanged on both platforms.
5. Platform-specific limitations must be resolved before release or the shared capability must be deferred.

## Article V: Native expression over pixel parity

1. Firstlight guarantees behavioural parity, not pixel parity.
2. iOS renderers use genuine SwiftUI controls, presentation, motion, materials, and interaction patterns.
3. Android renderers use genuine Material 3 controls, presentation, motion, state layers, and interaction patterns.
4. Platform-specific visual features are not imitated artificially on the other platform.
5. Shared hierarchy, capability, intent, and quality matter more than identical geometry.

## Article VI: Accessibility is correctness

1. Accessibility is part of the component contract, not optional polish.
2. Components support VoiceOver, TalkBack, Dynamic Type or font scaling, dark mode, increased contrast, and Reduced Motion where supported.
3. Controls expose correct labels, values, hints, selected state, disabled state, and roles.
4. Interaction targets meet the platform baseline: at least 44 points on iOS and 48 dp on Android.
5. A control without a visible or explicit accessibility label must fail development review.
6. Accessibility regressions block release.

## Article VII: System-first theming

1. Firstlight inherits the existing `native-ui` semantic theme rather than creating a competing theme system.
2. Theme tokens communicate brand colours and typography.
3. Platforms retain control of native geometry, motion, and interaction.
4. `class` controls external layout; semantic props control intent and variants.
5. Firstlight does not expose an untyped style bag or shared SwiftUI and Compose escape hatches.
6. Arbitrary per-control radius, shadow, and animation customization is excluded when it compromises native behaviour or parity.

## Article VIII: Small, proven expansion

1. Firstlight optimizes for deeply polished components rather than catalogue size.
2. New primitives begin with a clear use case and public contract.
3. Components are implemented one at a time and dogfooded before public release.
4. Firstlight does not rebuild adequate `nativephp/mobile-ui` primitives merely to claim completeness.
5. Documentation and showcase workflows are extracted into dedicated skills only after repetition reveals stable boundaries.

## Article IX: Evidence-based quality

1. Shared PHP contract tests and platform tests must prove the documented behaviour.
2. Both platform builds run for every component change.
3. Accessibility semantics and representative screenshots are tested.
4. Physical-device release checks cover interaction, motion, presentation, accessibility, offline behaviour, reconciliation, and rapid input.
5. The separate Firstlight showcase proves real Composer installation and plugin registration.
6. The separate Firstlight showcase dogfoods every public component and documented state before release.
7. Native feel requires structured human or agent review and cannot be inferred solely from file existence or screenshots.

## Article X: Public alpha stewardship

1. Rapid iteration applies to implementation and component growth, not casual foundational API churn.
2. During `0.x`, patch releases do not knowingly break public APIs.
3. Intentional breaking changes occur in minor releases and include migration guidance.
4. Release notes separate additions, fixes, deprecations, and breaking changes.
5. Supported NativePHP, `mobile-ui`, iOS, and Android versions are documented for every release.

## Article XI: Skills enforce the constitution

1. Repository-owned skills guide component creation, iOS implementation, Android implementation, and constitutional review.
2. Versioned scripts perform deterministic scaffolding and structural checks.
3. Skills use official SuperNative extension seams and reject WebView or bridge-based UI substitutes.
4. The review skill reports compliance with evidence and blocks incomplete renderer pairs, tests, documentation, or showcase coverage.
5. Automation assists judgment but does not waive any constitutional requirement.

## Article XII: Amendment

1. This constitution changes deliberately, not incidentally during component implementation.
2. Amendments require a written rationale, affected principles, migration impact, and explicit maintainer approval.
3. An amendment and the code that relies on it must not be hidden in the same unremarked change.
4. Component documentation and implementation plans may evolve without constitutional amendment when they remain within these principles.
