# Tipoff gaps: catalogue decisions

Date: 2026-10-22

## Accessible tappable surface — upstream core, not a new catalogue tag

An arbitrary-content action wrapper is the existing `native:pressable` primitive,
not a new purpose-specific control. Adding another general container would duplicate
core's layout, gestures, menu handling and press feedback. Firstlight's Button and
List Item remain the supported semantic actions, but neither is a substitute for
an arbitrary status banner. Do not claim that case is solved.

Installed source audit (Mobile 4.6 / UI 0.8):

- `vendor/nativephp/mobile/src/Edge/Elements/Pressable.php::applyAttributes()`
  processes `menu` only; direct hydration does not call `applyA11yAttributes()`.
  However, `NativeElementCollector.php:1644–1655` centrally copies both accessible
  attributes for normal Blade publication, and fluent setters also preserve them.
  This is not a Blade prop-loss bug.
- `vendor/nativephp/mobile/resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/nativerender/NodeView.kt::ResolvedNodeView()`
  composes style, layout and `nodeGestures`, then dispatches the registered renderer
  or fallback container. It does not consume `a11y_label` / `a11y_hint`, set a button
  role, or replace descendant semantics. `NodeModifiers.kt:410–440` uses Compose
  clickable/combinedClickable with no explicit role, enabled or selected mapping.
  Clickable supplies implicit Compose grouping; do not claim no grouping exists.
  Mobile UI `ContainerRenderers.kt:113–138` adds no missing semantics.
- `vendor/nativephp/mobile/resources/xcode/NativePHP/NativeRender/SwiftUINodeRenderer.swift::NodeView`
  composes child rendering and gestures without applying a grouped accessibility
  element, label, hint or button trait. `TapModifier` uses a tap gesture, not
  a SwiftUI Button. The corresponding `ViewClickHandlers.swift` path also uses
  gestures, not a semantic button.
- Mobile UI's `resources/ios/NativeUIColumnRenderer.swift` renders children through
  `FlexContainer`/`NodeView`, without grouping their accessibility. A column is
  layout, so a domain display requiring grouping needs a semantic display control.

Upstream request: consume the already-published accessible props, publish and consume disabled
and selected state, use genuine native action semantics and feedback, replace
decorative descendant accessibility, and guarantee 44 pt / 48 dp targets. Verify
the resulting tree and one-swipe VoiceOver/TalkBack navigation, not just visual taps.
Firstlight should document the limitation and recommend labelled Button or List
Item when their content contracts fit. No vendor patch is included here.

Authoritative released source: [Mobile 4.6.0 collector](https://github.com/NativePHP/mobile-air/blob/4.6.0/src/Edge/NativeElementCollector.php#L1644-L1655),
[Android modifier chain](https://github.com/NativePHP/mobile-air/blob/4.6.0/resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/nativerender/NodeView.kt#L292-L317),
[Android gestures](https://github.com/NativePHP/mobile-air/blob/4.6.0/resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/nativerender/NodeModifiers.kt#L410-L440),
[iOS modifier chain](https://github.com/NativePHP/mobile-air/blob/4.6.0/resources/xcode/NativePHP/NativeRender/SwiftUINodeRenderer.swift#L338-L375),
[iOS tap](https://github.com/NativePHP/mobile-air/blob/4.6.0/resources/xcode/NativePHP/NativeRender/SwiftUINodeRenderer.swift#L516-L531).

## Status Label icons — existing catalogue extension

Add the established shared icon contract to the existing static status control.
The tone owns foreground tint; icons are decorative. No new tag or style system.

## Metric — new semantic display

`<firstlight:metric label="Events" value="5" detail="last 10h ago" />`
is a domain value display, not an arbitrary column or tile. It owns visible label,
tabular-figure value, optional detail, five semantic tones, and one accessible
description. Require nonempty string label/value, accept optional string detail,
and allow `a11y-label` / `a11y-hint` overrides. No model, events, arbitrary children,
card border, background, radius, or internal layout API. External `class` is EDGE
layout. Large text wraps rather than scales down or truncates. iOS uses SwiftUI
text with monospaced digits; Android uses Material typography with `tnum`.
There is no optional action in this pass: use a purpose-specific action alongside
the metric until core's generic pressable accessibility is corrected.

## Status history — design only

A bounded status-history display fits the catalogue as an ordered outcome series,
not as a row of layout bars. A future contract should accept an ordered list of
`up` / `down` / `pending` checks (oldest first) and expose one summary; individual
bars are decorative and never actions. Pending is neither passed nor failed.
Empty history must say no checks, not report success. The summary must account for
every state. An optional explicit summary can supply domain language and timing.

States alone cannot establish “last failure 12 minutes ago”: require timestamps
or an authored summary before promising that information. Never infer timing from
bar position. Non-colour cues must distinguish failure/pending (shape or pattern).
Bars share the available width with a minimum readable width; when the full series
cannot fit, aggregate contiguous ranges and preserve counts and the last failure,
rather than silently dropping checks. Large text scales the summary, not the bar
count. Native theme tokens own colours in both appearances. No hover-only tooltips.

Defer implementation and final aggregation API until real width, chronology,
timestamp and non-colour accessibility fixtures are reviewed on both platforms.
This pass adds no tag, renderer or screenshot claim for history.

## Feedback — PHP event lifecycle

Keep the service and package-owned host; no new catalogue tag. Use the official
NativePHP event delivery seam for active-screen listeners, preserving existing
Laravel event listeners and exactly-once removal. The transient-feedback maintained
contract records the final supported mechanism and Undo example.
