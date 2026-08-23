# NativePHP Mobile UI vs Firstlight UI component gap

Scratchpad date: 2026-08-23

## Scope and comparison basis

This compares:

- NativePHP Mobile UI `main` at [`bffbe21187a9533867ca4be17580401e29ea3a0a`](https://github.com/NativePHP/mobile-ui/tree/bffbe21187a9533867ca4be17580401e29ea3a0a), committed 2026-08-04. Its [`nativephp.json`](https://github.com/NativePHP/mobile-ui/blob/bffbe21187a9533867ca4be17580401e29ea3a0a/nativephp.json) registers **59 manifest types**.
- Firstlight UI at the current working tree on 2026-08-05. Its [`nativephp.json`](nativephp.json) registers **21 public component types**.
- Firstlight's installed dependency, Mobile UI `0.3.0` at `548b431ad5c7989c7da766aceb1a1bc02948606f`, which registers **54 types**. Upstream `main` adds `accordion`, `accordion_content`, `accordion_header`, `background_layer`, and `sheet_pane` beyond that installed release.
- The current [V2 roadmap](roadmap-v2.md) and [Konsta UI coverage comparison](spec/reference/konsta-ui-coverage.md). The roadmap's status cells predate the current manifest, so current code is used to distinguish delivered work from remaining or in-progress work.

“Missing” means that Mobile UI has a manifest type for which Firstlight does not expose a clear public counterpart. It does **not** mean that a Firstlight application lacks the capability: Firstlight depends on Mobile UI, so consumers can use these types directly with `<native:...>`.

The native-first assessment applies the current [Firstlight constitution](Constitution.md), especially Articles I, III, IV, V, VII, and VIII. It distinguishes two questions:

1. **Native substrate** — does the type remain in the EDGE/Element Tree lifecycle and render through SwiftUI or Jetpack Compose rather than an HTML control layer?
2. **Firstlight disposition** — should Firstlight expose it, and can the upstream contract be adopted unchanged?

This is a source-level architectural assessment, not a release-readiness certification of every upstream prop, event, accessibility path, test, or device behavior.

## Status key

| Disposition | Meaning |
| --- | --- |
| **Direct dependency** | Conforms to the native-first architecture, but Firstlight should normally use/document the existing `<native:...>` primitive rather than duplicate it. |
| **Adapter candidate** | The native primitive is a plausible substrate for a narrower Firstlight contract, subject to paired platform, state, accessibility, and device evidence. |
| **Conditional / alternative** | The capability is native, but the upstream public contract or cross-platform expression cannot be adopted unchanged. It needs a narrower contract, more evidence, or paired alternative renderers. |
| **Exclude** | Conflicts with a binding Firstlight boundary and should not become a Firstlight component. |

## Unified Firstlight missing-surface summary

### Current roadmap position

The current Firstlight manifest shows that the V2 roadmap's Activity Indicator,
Checkbox, List Item, and Confirmation Dialog targets have been delivered.
Transient Feedback is **in progress**: implementation, platform tests, showcase
dogfood, and Android Paparazzi goldens pass; release screenshots, alpha review,
and physical-device accessibility rows remain open. When those gates close, it
will cover the Konsta **Notification** and **Toast** intents.

Two V2 semantic questions remain after that work:

- **Tabs** remains research. Mobile UI's `tab_row` and `tab` are index-bound
  and do not yet distinguish peer-view switching from application navigation.
- **Standalone Chip** remains on hold. Firstlight Pill Group covers grouped
  one-or-many selection and Status Label covers display, but neither is a
  dedicated standalone action/filter chip contract.

### Complete ledger of the 46 Mobile UI-only manifest types

Every upstream-only type is retained below, including the low-priority direct
Mobile UI primitives. Priority means priority for a **Firstlight-specific
contract**, not whether the capability is usable in a Firstlight application.

| Priority / disposition | Count | Mobile UI types | Firstlight summary |
| --- | ---: | --- | --- |
| **Nearer adapter candidates** | 5 | `scroll_view`, `lazy_grid`, `list`, `list_section`, `modal` | Plausible thin adapters if Firstlight can add a narrower portable contract and paired evidence. List + List Section have the clearest leverage because List Item already exists. Modal is broader than Confirmation Dialog. |
| **Conditional interaction and state** | 6 | `gesture_area`, `refreshable`, `pressable`, `virtual_list`, `tab_row`, `tab` | Native substrate, but gesture arbitration, refresh ownership, semantic roles, virtualization identity/restoration, stable values, or tab intent prevent unchanged adoption. |
| **Conditional application chrome** | 11 | `top_bar`, `top_bar_action`, `bottom_nav`, `bottom_nav_item`, `side_nav`, `side_nav_item`, `side_nav_group`, `side_nav_header`, `native_drawer`, `floating_overlay`, `background_layer` | Needs a coherent adaptive shell, stable destinations, native platform structures, ownership, restoration, safe-area, focus, and accessibility contracts. Collector/sentinel nodes are legitimate, but only with a conforming parent/root-host contract. |
| **Conditional presentation and composition** | 7 | `bottom_sheet`, `button_group`, `carousel`, `accordion`, `accordion_content`, `accordion_header`, `sheet_pane` | Bottom Sheet needs a reduced equal-platform API; Button Group is replaced by stable-value Firstlight Segmented; Carousel is materially unequal today; Accordion needs state/accessibility review; Sheet Pane exposes geometry and detent concerns that need a system-first contract. |
| **Maintained direct primitives — low priority** | 16 | `text`, `image`, `column`, `row`, `stack`, `canvas`, `spacer`, `divider`, `horizontal_divider`, `rect`, `circle`, `line`, `icon`, `outlined_text_input`, `bare_text_input`, `filled_text_input` | Keep available and maintained through the Mobile UI dependency. Firstlight should normally document direct `<native:...>` composition rather than add aliases. Revisit only when a real validation, migration, or semantic contract appears. |
| **Explicit exclusion** | 1 | `webview` | Do not expose as Firstlight. Web content may be a legitimate direct Mobile UI requirement, but a WebView-backed Firstlight control conflicts with Article I. |
| **Total** | **46** |  | Complete upstream-only manifest inventory. |

### Konsta concepts not supplied as exact Firstlight components

This is the Konsta delta **in addition to** the raw manifest ledger. Some rows
are already implementable by composing the Mobile UI types above; they remain
listed because Firstlight does not expose a dedicated application-level
component for the intent.

| Proposed priority | Konsta concept | Existing substrate | Firstlight disposition |
| --- | --- | --- | --- |
| **In progress** | Notification | Firstlight Feedback Center work in progress; Mobile UI `floating_overlay` composition | Transient Feedback should own the durable application outcome/queue intent when its full delivery gate closes. |
| **In progress** | Toast | Same Transient Feedback work; NativePHP also has a separate imperative toast | Cover through the semantic Firstlight service rather than mirror an imperative or platform-only toast API. |
| **Higher** | Action Sheet | Mobile UI `bottom_sheet` plus Button/List composition; Confirmation Dialog is narrower | Define a semantic action-list presentation only if action roles, ordering, cancellation, destructive intent, focus, and dismissal can be shared honestly. Natural substrate may belong upstream. |
| **Higher** | Popover | No dedicated Mobile UI or Firstlight type | Clear dedicated-control gap. Define anchoring, adaptive fallback, dismissal, focus, safe-area, and accessibility before choosing adapter/plugin renderers. |
| **Higher** | Data Table | `lazy_grid` is only a layout primitive | Clear dedicated-control gap, but a substantial one: columns, headers, sort, selection, horizontal/vertical virtualization, large text, and accessibility need a real product contract. |
| **Medium** | Breadcrumbs | Back stack and top-bar navigation only | Dedicated-trail gap. First establish a real mobile/tablet use case and adaptive collapse behavior; avoid importing a desktop-web pattern unchanged. |
| **Medium** | Standalone Chip | Mobile UI `chip`; Firstlight Pill Group and Status Label are adjacent | Keep separate from grouped selection. Research action-chip and Boolean filter-chip intents independently before accepting one API. |
| **Medium** | Floating Action Button | `floating_overlay` plus Firstlight Icon Button or Mobile UI Button/Icon | A useful semantic composition candidate after overlay ownership, placement, keyboard/safe-area behavior, target, label, and route persistence are settled. |
| **Medium** | Link | Mobile UI `pressable` + `text`, or existing Button/List Item | Consider only if Firstlight needs a durable navigation/URL intent with link role, visited/external behavior, and accessibility beyond ordinary press actions. |
| **Low / compose** | Card | Styled native `column` or `stack` | Prefer composition unless repeated use reveals durable surface, grouping, elevation, action, and accessibility semantics. |
| **Low / compose** | Block | `column`, `row`, `stack`, and `text` | Layout vocabulary, not a distinct Firstlight application component. Keep direct. |
| **Low / compose** | Contacts List | Mobile UI `list`, `list_section`, `list_item`, and `virtual_list` | Treat as a List recipe or higher-level application pattern after the generic List contract, not a base component. |
| **Low / compose** | List | Mobile UI `list`, `virtual_list`, and `list_section`; Firstlight List Item exists | Covered by the List/List Section adapter investigation in the 46-type ledger. |
| **Low / compose** | List Button | Firstlight List Item or Button | No dedicated component unless repeated product use exposes semantics not covered by the existing action controls. |
| **Low / compose** | Menu List | Mobile UI List, Side Nav, or Tab Row; Firstlight List Item | Keep as composition until active destination, hierarchy, adaptive shell, and selection semantics justify a dedicated contract. |
| **Low / compose** | Messagebar | Firstlight Text Area + Icon Button | Prefer an authored pattern; promote only if draft, attachments, send state, keyboard, and accessibility repeat across real consumers. |
| **Low / compose** | Messages | Mobile UI Virtual List, Text, and Image | Domain-heavy chat recipe. Do not add a generic Firstlight component without a concrete consumer and message identity/lifecycle contract. |
| **Low / structural** | Navbar | Mobile UI `top_bar` and NativeLayout navigation | Accounted for by the conditional application-chrome family; do not add a decorative alias. |
| **Low / structural** | Page | Routed NativeComponent plus `scroll_view` or `column` | Already an exact NativePHP architectural concept. No Firstlight wrapper is missing. |
| **Low / structural** | Panel / Side Panels | Mobile UI `native_drawer` and `side_nav` | Accounted for by the conditional application-chrome family. |
| **Low / structural** | Popup | Mobile UI `modal` | Accounted for by the Modal adapter investigation. |
| **Low / structural** | Sheet Modal | Mobile UI `bottom_sheet` | Accounted for by the reduced-contract Bottom Sheet investigation. |
| **Low / structural** | Tabbar | Mobile UI `bottom_nav`, `bottom_nav_item`, `tab_row`, and `tab` | Accounted for by the application-chrome and stable-value Tabs investigations. |
| **Low / structural** | Toolbar | Mobile UI Top Bar, Bottom Nav, or Row composition | Treat as application chrome or composition, not one geometry forced across platforms. |
| **Do not add** | Glass | Renderer-owned platform materials | A public shared glass styling primitive conflicts with system-first theming and honest platform expression. |
| **Do not add** | Toolbar Pane | Native chrome already owns platform material | Web styling abstraction with no durable shared native intent; do not create an authored equivalent merely for parity. |

KonstaProvider and App do not appear in this missing list because NativePHP
package/theme registration, routed NativeComponents, and NativeLayouts already
cover those architectural jobs. Konsta List Input, Searchbar, and the remaining
form controls are already covered by current Firstlight control families.

### Recommended ordering after Transient Feedback

1. Finish and prove **Transient Feedback** without counting partial platform
   work as delivered.
2. Define **List + List Section** around the existing List Item.
3. Investigate **Modal**, a reduced cross-platform **Bottom Sheet**, and the
   separate **Action Sheet** intent together so their presentation boundaries
   do not overlap accidentally.
4. Contract **Accordion** if server-authoritative state and accessibility can
   be preserved; investigate **Popover** as the clearest no-substrate Konsta
   control gap.
5. Research **Data Table** and **Breadcrumbs** from concrete tablet/desktop or
   dense-data consumers rather than importing web behavior wholesale.
6. Keep Tabs, application chrome, virtualized collections, gesture surfaces,
   Carousel, root layers, Sheet Pane, and standalone Chip in research/hold
   until their cross-platform intent is proven.
7. Maintain the 16 direct Mobile UI primitives as documented low-priority
   dependencies. Add Firstlight aliases only when they create a real contract.

## Mobile UI types Firstlight does not currently expose

| Group | Mobile UI type | What it provides | Native substrate | Firstlight contract assessment | Recommended disposition |
| --- | --- | --- | --- | --- | --- |
| Visual primitive | `text` | Native text rendering and typography | **Yes** | Conforms technically. A Firstlight alias adds no application-level semantic contract. | **Direct dependency** |
| Visual primitive | `image` | Native image rendering | **Yes** | Conforms technically. Keep media loading and display as NativePHP composition vocabulary. | **Direct dependency** |
| Layout | `column` | Vertical child layout | **Yes** | Conforms and already uses the platform layout engine. An alias is justified only if Firstlight can add a durable, smaller contract. | **Direct dependency** |
| Layout | `row` | Horizontal child layout | **Yes** | Same as Column: native and adequate, with no present Firstlight-specific intent. | **Direct dependency** |
| Layout | `stack` | Overlaid child layout | **Yes** | Native composition primitive; wrapping it solely for namespace parity would violate Article VIII. | **Direct dependency** |
| Layout | `scroll_view` | One- or two-axis scrolling container | **Yes** | Native in both renderers. A future adapter must prove nested scrolling, keyboard behavior, restoration, RTL, and accessibility. | **Adapter candidate**, otherwise direct |
| Layout | `lazy_grid` | Lazy native grid | **Yes** | Native in both renderers, but a Firstlight contract would need stable child identity, restoration, large-text, and assistive-technology evidence. | **Adapter candidate**, otherwise direct |
| Interaction container | `gesture_area` | Gesture recognition around arbitrary children | **Yes** | The substrate is native, but the API needs a bounded gesture vocabulary with equal gesture arbitration and accessibility alternatives on both platforms. | **Conditional / alternative** |
| Interaction container | `refreshable` | Pull-to-refresh container | **Yes** | Native refresh UI fits the philosophy; adoption requires one server-owned refresh lifecycle, duplicate suppression, completion/failure behavior, and announcements. | **Conditional / alternative** |
| Interaction container | `pressable` | Makes arbitrary child content pressable | **Yes** | Native, but a Firstlight wrapper must require an explicit role/name, minimum target, disabled behavior, and a semantic reason not to use Button or List Item. | **Conditional / alternative** |
| Drawing primitive | `canvas` | Native drawing surface | **Yes** | Uses native drawing APIs, not HTML. It is low-level composition vocabulary rather than an application component. | **Direct dependency** |
| Layout | `spacer` | Flexible or fixed native space | **Yes** | Conforms technically; no distinct Firstlight semantic value. | **Direct dependency** |
| Layout | `divider` | Native separator | **Yes** | Conforms. If Firstlight ever adds an alias, it should publish one Divider API rather than mirror duplicate upstream names. | **Direct dependency** |
| Drawing primitive | `rect` | Native rectangle shape | **Yes** | Native but purely visual composition vocabulary. | **Direct dependency** |
| Drawing primitive | `circle` | Native circle shape | **Yes** | Native but purely visual composition vocabulary. | **Direct dependency** |
| Drawing primitive | `line` | Native line shape | **Yes** | Native but purely visual composition vocabulary. | **Direct dependency** |
| App chrome | `top_bar` | Screen top bar | **Yes** | The current renderers hand-compose bars. A Firstlight shell should use genuine SwiftUI navigation/toolbar and Material 3 top-app-bar patterns, with stable actions and restoration. | **Conditional / paired alternative** |
| App chrome child | `top_bar_action` | Declarative action consumed by Top Bar | **Yes, structural** | An intentional collector node, not a degraded empty renderer. Its fit depends on a conforming parent Top Bar contract and accessible icon/action semantics. | **Conditional with parent** |
| App chrome | `bottom_nav` | Bottom destination navigation | **Yes** | Native tree, but Firstlight must preserve platform-native tab/navigation structures, stable destination values, badges, restoration, and adaptive placement. | **Conditional / paired alternative** |
| App chrome child | `bottom_nav_item` | Destination consumed by Bottom Nav | **Yes, structural** | Collector-node rendering is legitimate, but the item contract is only conforming with a conforming parent and stable destination identity. | **Conditional with parent** |
| App chrome | `side_nav` | Side navigation container | **Yes** | Native tree but not yet an honest cross-platform application-shell contract. It should adapt between rail/drawer/split-view conventions rather than force one geometry. | **Conditional / paired alternative** |
| App chrome child | `side_nav_item` | Destination consumed by Side Nav | **Yes, structural** | Structurally valid; inherits the parent shell's unresolved identity, restoration, and accessibility contract. | **Conditional with parent** |
| App chrome child | `side_nav_group` | Group consumed by Side Nav | **Yes, structural** | Structurally valid; group semantics and platform adaptation need to be defined with Side Nav. | **Conditional with parent** |
| App chrome child | `side_nav_header` | Header consumed by Side Nav | **Yes, structural** | Structurally valid; only useful within a conforming adaptive Side Nav contract. | **Conditional with parent** |
| Layout | `horizontal_divider` | Horizontal separator alias | **Yes** | Conforms technically, but Firstlight should not preserve two names for the same divider intent. | **Direct dependency** |
| Collection | `list` | Native finite list with sections, refresh, and end-reached hooks | **Yes** | Strong candidate around the existing Firstlight List Item. It still needs stable child identity, lifecycle ownership, empty/loading state, restoration, and accessibility proof. | **Adapter candidate** |
| Collection | `virtual_list` | PHP-windowed native list | **Yes** | The native lazy list is appropriate, but the public contract exposes window callbacks and an iOS-only indicator effect. Identity, estimation, restoration, loading, and assistive-technology behavior remain unresolved. | **Conditional / research** |
| Presentation | `modal` | Server-visible full-screen modal | **Yes** | Uses SwiftUI presentation and a full-screen Compose dialog. A Firstlight adapter must normalize dismissal, focus trapping, programmatic closure, naming, and content ownership; it is broader than Confirmation Dialog. | **Adapter candidate** |
| Visual primitive | `icon` | SF Symbols / Material icon resolution | **Yes** | Conforms and already underpins Firstlight icon props. A standalone alias adds no new semantic intent. | **Direct dependency** |
| Input primitive | `outlined_text_input` | General outlined input primitive | **Yes** | Native, but Firstlight already supplies purpose-specific Text Field, Text Area, and Search Field contracts. Do not re-export a styling variant as a separate Firstlight intent. | **Direct dependency / existing alternative** |
| Input primitive | `bare_text_input` | General unframed input primitive | **Yes** | Same capability boundary as Outlined Text Input; it is a presentation variant, not a distinct Firstlight field intent. | **Direct dependency / existing alternative** |
| Input primitive | `filled_text_input` | General filled input primitive | **Yes** | Same capability boundary as the other generic text-input variants. | **Direct dependency / existing alternative** |
| Collection child | `list_section` | Section header/content collector for List | **Yes, structural** | Intentional collector nodes conform when consumed by a conforming List. Firstlight should define section semantics with its List contract, not expose this in isolation. | **Adapter candidate with List** |
| Selection / tabs | `tab_row` | Horizontal tab selector bound to a selected index | **Yes** | **Does not conform unchanged:** index binding violates stable public values, and one authored control has not yet established an honest peer-view versus navigation intent on both platforms. | **Conditional / paired alternative** |
| Selection / tabs child | `tab` | Label/icon collector consumed by Tab Row | **Yes, structural** | The collector pattern is valid, but the child has no stable public value and inherits the parent row's index-based contract. | **Conditional with parent** |
| Presentation | `bottom_sheet` | Native bottom sheet with detents and dismissal | **Yes** | Strong native substrate, but `background-interaction` is explicitly iOS-only and arbitrary fractions do not map equally to Material 3. A Firstlight API must omit or normalize unequal props. | **Conditional adapter** |
| Selection | `button_group` | Segmented single-choice control over string labels, bound to an index | **Yes** | **Does not conform unchanged:** it publishes renderer indexes rather than stable domain values. Firstlight Segmented already supplies the conforming application-level contract. | **Do not add unchanged; use Firstlight Segmented** |
| Horizontal content | `carousel` | Horizontal carousel/browse content | **Yes** | **Does not meet equal-platform intent unchanged:** Android uses Material 3 carousel behavior while iOS is a free-scrolling `ScrollView`/`LazyHStack` with no paging or snapping contract. | **Conditional / paired alternative** |
| Root chrome | `native_drawer` | Layout-level drawer sentinel and native root host | **Yes, structural** | The root-host seam is native-first, but ownership, dismissal, modes, restoration, adaptive placement, and arbitrary child accessibility need a bounded application-shell contract. | **Conditional / research** |
| Root chrome | `floating_overlay` | Persistent top/bottom overlay sentinel and native root host | **Yes, structural** | Native-first architecture, but arbitrary floating content needs lifecycle, focus order, hit testing, safe-area, restoration, and dismissal rules before becoming public Firstlight API. | **Conditional / research** |
| Web content | `webview` | Embedded `WKWebView` / Android `WebView`, including an enriched PHP mode | **No under Firstlight's definition** | **Explicitly excluded:** Article I forbids a WebView-backed control system. Consumers can deliberately use Mobile UI Webview when the product requirement is web content, but Firstlight must not rebrand it as a native control. | **Exclude** |
| Disclosure | `accordion` | Expand/collapse disclosure container | **Yes** | SwiftUI uses `DisclosureGroup`; Android uses Compose disclosure composition. The current optimistic local expansion model needs review against Firstlight's PHP-authoritative discrete-state rule, plus heading semantics, focus, motion, and target evidence. | **Conditional adapter** |
| Disclosure child | `accordion_content` | Content collector consumed by Accordion | **Yes, structural** | Collector rendering is valid. Its contract and accessibility order depend on the parent Accordion. | **Conditional with parent** |
| Disclosure child | `accordion_header` | Header collector consumed by Accordion | **Yes, structural** | Collector rendering is valid, but the parent must guarantee one accessible expansion control and avoid duplicate activation targets. | **Conditional with parent** |
| Root chrome | `background_layer` | Persistent content layer beneath routed screens | **Yes, structural** | The root-host pattern is native-first. Firstlight should defer it until persistent child identity, lifecycle, interaction, safe-area, navigation restoration, and accessibility ordering are proven. | **Conditional / research** |
| Presentation / layout | `sheet_pane` | Always-visible draggable bottom pane with native-thread tracking and detent events | **Yes** | Native-thread interaction and semantic settle events fit Article I, but the shared API exposes arbitrary radius/insets and hard-coded point/dp detents. It needs system-first geometry, validation, responsive sizing, restoration, and accessibility adjustments before adoption. | **Conditional / paired alternative** |

## Mobile UI types excluded from the gap because Firstlight already covers the intent

These 13 Mobile UI manifest types reconcile the 59-type upstream total to the 46-type gap above.

| Mobile UI type | Firstlight counterpart | Relationship |
| --- | --- | --- |
| `button` | `firstlight.button` | Thin adapter over the official renderer with a narrower public contract. |
| `progress_bar` | `firstlight.progress` | Thin adapter over the official renderer with stricter values and accessibility. |
| `activity_indicator` | `firstlight.activity-indicator` | Same display intent with a dedicated Firstlight contract and renderers. |
| `checkbox` | `firstlight.checkbox` | Same Boolean checklist intent with Firstlight server-authoritative state and field semantics. |
| `slider` | `firstlight.slider` | Same range-selection intent with strict finite/grid validation and synchronization policy. |
| `select` | `firstlight.select` | Same single-selection intent with stable values, rich options, and searchable presentation rules. |
| `date_picker` | `firstlight.date-picker` | Same calendar-date intent with strict nullable `YYYY-MM-DD` state. Mobile UI's combined date/time modes are intentionally split by Firstlight Date Picker and Time Picker. |
| `badge` | `firstlight.badge` | Same compact display intent with stricter source, tone, zero, and accessibility rules. |
| `list_item` | `firstlight.list-item` | Same application-row intent with a narrower content/action contract. |
| `toggle` | `firstlight.switch` | Same Boolean setting intent with Firstlight field and server-authoritative semantics. |
| `radio_group` | `firstlight.choice-group` | Firstlight owns the group as one stable scalar/list value rather than exposing renderer selection state. |
| `radio` | `firstlight.choice-group` | Mobile UI's child radio type is absorbed into Firstlight's option-array group contract. |
| `chip` | `firstlight.pill-group` | Firstlight owns compact one-or-many selection as one stable field value; it does not mirror standalone optimistic chip state. |

## Bottom line

- **46** current upstream manifest types have no clear public Firstlight counterpart.
- **16** are adequate visual, layout, drawing, icon, or generic input primitives whose recommended disposition is direct Mobile UI use rather than a Firstlight namespace alias.
- **5** are plausible thin-adapter candidates: `scroll_view`, `lazy_grid`, `list`, `modal`, and `list_section` as part of List. They still need a meaningful Firstlight contract and paired evidence before adoption.
- **24** are conditional, paired-alternative, or research cases because their public state, platform expression, ownership, restoration, accessibility, or lifecycle does not yet meet the whole Firstlight contract unchanged. This count includes `button_group`, which should remain replaced by Firstlight Segmented rather than enter Firstlight under its index-bound API.
- **1** is explicitly excluded: `webview`.
- The highest-value near-term investigations are **List + List Section**, **Modal**, **Bottom Sheet with a reduced cross-platform API**, **Accordion**, and a small **layout family only if it adds meaningful diagnostics or a migration seam**.
- Tabs/Button Group, application chrome, Carousel, Virtual List, gesture surfaces, root overlays/layers, and Sheet Pane should remain conditional until their stable-value, native-expression, state, lifecycle, and accessibility boundaries are proven.

## Evidence pointers

- [Firstlight constitution](Constitution.md)
- [Firstlight and NativePHP Mobile UI](docs/concepts/firstlight-and-mobile-ui.md)
- [Firstlight V3 working roadmap](roadmap-v3.md)
- [NativePHP Mobile UI manifest at the audited commit](https://github.com/NativePHP/mobile-ui/blob/bffbe21187a9533867ca4be17580401e29ea3a0a/nativephp.json)
- [NativePHP v4 native UI architecture](https://nativephp.com/docs/mobile/4/the-basics/native-ui)
- [NativePHP v4 renderer architecture](https://nativephp.com/docs/mobile/4/architecture/renderer)
