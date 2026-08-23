# Firstlight UI Roadmap V2

Last updated: 2026-08-23

This is the working component hit list for Firstlight after the original alpha
catalogue. It records what exists, what still blocks the alpha release, and
which component earns the next complete design, implementation, showcase, and
review cycle.

V2 is not a promise to mirror every `nativephp/mobile-ui` type. A candidate
stays on this list only when it adds a distinct application-level intent and a
coherent `<firstlight:...>` contract or service.

## Overall progress

| Area | Progress | Current state |
| --- | ---: | --- |
| V1 component implementation | 17 / 17 | Every approved alpha component is on `main`. |
| V1 public component documentation | 17 / 17 | Every V1 component has a public guide and current contract. |
| V1 showcase coverage | 17 / 17 | Every V1 public tag is registered and dogfooded in the showcase. |
| V1 development screenshot matrix | 68 / 68 | iOS and Android light/dark captures are present and visually approved. |
| V2 manifest components delivered | 4 / 4 | Activity Indicator, Checkbox, List Item, and Confirmation Dialog are on `main` with docs, showcase, and development reviews. |
| Transient Feedback (service) | In progress | PHP API, paired native hosts, platform tests, showcase, and Android Paparazzi goldens pass. Release screenshots, alpha review, and physical-device rows remain open. |
| V1 public-alpha release gate | Blocked | Clean release capture, exact iOS execution, physical-device accessibility, and the NativePHP identical-publication prerequisite remain open. |
| V3 structural work | Not started | See [roadmap-v3.md](roadmap-v3.md). |

V2 manifest components are implemented, documented, showcased, and covered by
development reviews. That is not the same as catalogue release completion.

V3 work may proceed only after Transient Feedback closes its release boundary
and the shared alpha gate rows above are honestly recorded.

## How the hit list is prioritised

Candidates are ordered by:

1. a distinct consumer need not already covered by the V1 catalogue;
2. a clear cross-platform semantic contract;
3. the quality of the installed official primitive, when one exists;
4. leverage for later application components; and
5. the cost and risk of proving equal native behaviour and accessibility.

Statuses have deliberate meanings:

- **Now** — the next component to take through the complete workflow.
- **Delivered** — closed the full delivery boundary or the agreed development boundary.
- **Queued** — accepted direction waiting behind the current target.
- **Research** — investigate the intent before promising a component.
- **Hold** — do not implement until the stated consumer evidence exists.

## Component hit list

| Rank | Component | Status | Likely path | Why it is here | Next checkpoint |
| ---: | --- | --- | --- | --- | --- |
| 1 | Activity Indicator | **Delivered** | Thin adapter | Compact indeterminate spinner distinct from Progress. | Maintenance only. |
| 2 | Checkbox | **Delivered** | Paired renderers | Standalone Boolean checklist control for forms. | Maintenance only. |
| 3 | List Item | **Delivered** | Paired renderers | Application row contract for later List and List Section work. | Maintenance only. |
| 4 | Confirmation Dialog | **Delivered** | Paired presentation | Semantic confirm/cancel and destructive-action presentation. | Maintenance only. |
| 5 | Transient Feedback | **Now** | Paired root hosts + facade | Global queued outcomes (toast/notification intent) via `Feedback::` facade. | Close Paparazzi (done), release capture, four doc screenshots, alpha review, device/a11y evidence. |
| 6 | Tabs | **Research** | Split or paired alternative | Mobile UI `tab_row` is index-bound; peer-view vs navigation intent unresolved. | Decide between peer-view switcher and application navigation. |
| 7 | Chip | **Hold** | Likely paired alternative | Pill Group and Status Label cover grouped selection and display; standalone filter/action chips need dogfooding. | Real standalone filter case before adding an API. |

## Current target: Transient Feedback

Transient Feedback is the only remaining V2 delivery. It is a **service**, not
a Blade tag:

- `FirstlightUI\Facades\Feedback::{message, success, warning, danger, dismiss}`
- Package-owned root chrome registers automatically; consumers mount no host.
- PHP owns durable records; each platform host owns FIFO presentation, timing,
  tombstones, and accessibility announcements.

**Closed since the 2026-08-05 development review:**

- PHP domain, facade, events, nested wire types, and callback lifecycle.
- Paired SwiftUI and Material 3 root hosts with queue/timing semantics tests.
- Showcase gallery, navigation demo, deterministic capture fixture, and focused
  tests.
- Android Paparazzi goldens for tones, action, hold, long copy, font scale, and
  RTL (recorded 2026-08-23).

**Still open for component release:**

- Guarded release capture of the four registered documentation screenshots.
- `spec/reviews/transient-feedback-alpha.md` with substantive runtime and
  accessibility rows.
- Dated physical-device VoiceOver and TalkBack evidence.

## Accepted component boundaries

### List Item

List Item is an application row, not a generic layout container. V2 owns the
row's content and action semantics; V3 may later provide List and List Section
containers around it. Swipe actions, reordering, selection collections,
virtualisation, and arbitrary embedded controls remain separate capabilities.

### Transient Feedback

Use Transient Feedback for brief app-level outcomes that may survive navigation.
Use Callout for persistent in-layout guidance and Confirmation Dialog when work
must pause for a decision. Do not mirror imperative platform toast APIs.

## Deliberate exclusions

- Layout, scrolling, collection containers, presentation containers, and app
  chrome remain in the V3 roadmap.
- Visual primitives such as Text, Image, Icon, Canvas, and shapes remain direct
  `<native:...>` composition vocabulary.
- WebView remains explicitly outside the Firstlight component system.
- No candidate is accepted merely to increase namespace or manifest coverage.

## Delivery rule

Only one component may be **Now**. It moves to **Delivered** only after its
semantic contract, official-primitive audit, failing-first Pest coverage,
PHP/EDGE API, equal Apple and Android implementation or adapter evidence,
documentation, showcase and capture fixture, consumer tests, accessibility,
platform builds, screenshots, device evidence, and constitutional review all
pass — or, for Transient Feedback, after the maintained release review and
screenshot gate closes honestly.

When that boundary closes, update the overall progress row, mark the component
delivered with its evidence record, and promote the next accepted target from
[roadmap-v3.md](roadmap-v3.md).
