# Firstlight UI Roadmap V3

Last updated: 2026-08-23

V3 covers layout, composition, collections, presentation, and application
chrome after the V2 control catalogue closes. V2 manifest components (Activity
Indicator, Checkbox, List Item, Confirmation Dialog) are delivered; Transient
Feedback must finish its release boundary before V3 sequence work starts in
earnest. It does not assume that every type in the Mobile UI manifest belongs
under the Firstlight namespace.

## Layout policy

Layout components should normally be thin adapters. SwiftUI and Jetpack
Compose already provide the platform layout engines, and Mobile UI 0.4.0 maps
many EDGE primitives directly to them. Firstlight should add a namespace only
when it supplies a smaller coherent API, portable diagnostics, or a durable
migration seam.

Paired alternative renderers are appropriate only when the installed
implementation imitates one platform on the other, exposes materially unequal
capabilities, or cannot satisfy a stable cross-platform state and
accessibility contract.

## Mobile UI layout and structure audit

| Group | Installed Mobile UI types | V3 direction |
| --- | --- | --- |
| Core containers | `column`, `row`, `stack`, `scroll_view`, `lazy_grid` | Adapter candidates. They already use SwiftUI layout/scroll/grid primitives and Compose `Column`, `Row`, `Box`, lazy lists, and lazy grids. Audit sizing, alignment, spacing, identity, nesting, and accessibility before adding aliases. |
| Space and separation | `spacer`, `divider`, `horizontal_divider` | Adapter candidates, but publish only one coherent Divider API. Do not preserve duplicate upstream names merely for parity with the manifest. |
| Interaction containers | `pressable`, `gesture_area`, `refreshable` | Pressable and Refreshable may be adapters after event and accessibility review. Defer Gesture Area until a bounded, portable gesture vocabulary is defined. |
| Collections | `list`, `virtual_list`, `list_section` | List and List Section are adapter candidates after parity and state review. Keep Virtual List direct-to-NativePHP until windowing callbacks, identity, loading, empty state, restoration, and assistive-technology behaviour are proven in a consumer. V2 owns the List Item contract. |
| Presentations | `modal`, `bottom_sheet`, `floating_overlay`, `native_drawer` | Modal and Bottom Sheet are strong adapter candidates because the installed renderers use SwiftUI `.fullScreenCover`/`.sheet` and Compose dialog/Material bottom-sheet presentation. Defer Floating Overlay and Native Drawer until their builder, ownership, dismissal, and restoration contracts are understood. |
| App chrome | `top_bar`, `top_bar_action`, `bottom_nav`, `bottom_nav_item`, `side_nav`, `side_nav_item`, `side_nav_group`, `side_nav_header` | Paired-alternative candidates. The installed renderers largely hand-compose bars from SwiftUI `HStack`/`VStack` and Compose `Row`/`Column` rather than delegating to native toolbar, tab, navigation-bar, rail, or drawer containers. |
| Horizontal content | `carousel` | Defer or build a paired alternative. The installed iOS implementation is a horizontal `ScrollView` with `LazyHStack`, not a paging carousel. First define whether the public intent is free scrolling, snapping, paging, or featured-card navigation. |
| Visual primitives | `text`, `image`, `icon`, `canvas`, `rect`, `circle`, `line` | Keep as `<native:...>` primitives. They are composition vocabulary, not Firstlight application components, and wrapping them would add namespace without durable semantics. |
| Web content | `webview` | Explicitly excluded. Firstlight's component system does not introduce or rebrand WebView-backed UI. Consumers may use NativePHP's WebView directly when web content is the actual requirement. |

## Proposed V3 sequence

### 1. Basic layout adapters

Evaluate one coherent family for Column, Row, Stack, Spacer, and Divider.
Preserve native measurement and alignment; do not invent a second style or
constraint system. Public props must map to durable layout concepts shared by
both platforms.

The family ships only if changing `<native:...>` to `<firstlight:...>` adds
real validation, consistency, or migration value. Otherwise document direct
NativePHP use and do not add wrappers.

### 2. Scrolling and grids

Evaluate Scroll View and Lazy Grid as adapters. Prove nested scrolling,
keyboard dismissal, scroll indicators, content sizing, programmatic
publication, right-to-left layout, large text, and restoration on both
platforms. Do not expose platform-only switches through the shared API.

### 3. Refreshable content

Define one refresh lifecycle with loading ownership, duplicate-request
suppression, completion, failure, disabled state, and accessibility
announcements. Adapt Mobile UI only if both renderers satisfy that lifecycle;
otherwise use paired native refresh containers.

### 4. Modal and Bottom Sheet

Create thin adapters around the adequate native presentation primitives.
Normalize visibility, dismissal, accessibility naming, server reconciliation,
and content ownership. Preserve Apple sheets/full-screen covers and Material
dialogs/bottom sheets rather than forcing shared geometry or detents that one
platform cannot express faithfully.

### 5. List and List Section

Pair the V2 List Item with list and section containers. Start with finite
authored children, optional separators, refresh, end-reached notification, and
stable child identity. Swipe actions, reordering, selection, and virtualization
remain separate capabilities with their own evidence requirements.

### 6. Platform-native application chrome

Design Top Bar, Bottom Navigation, and adaptive Side Navigation as application
shell components, not decorated rows. Use native SwiftUI navigation, toolbar,
tab, and split-view presentation where appropriate on iOS. Use Material 3 top
app bars, navigation bars, rails, and drawers on Android.

One authored hierarchy may resolve to different platform structures. Shared
contracts cover destinations, stable selection, badges, actions, accessibility,
and restoration; they do not force identical placement or animation.

### 7. Deferred structural research

Investigate Virtual List, Carousel, Gesture Area, Native Drawer, and Floating
Overlay only from concrete consumer use cases. Each must establish ownership,
identity, lifecycle, accessibility, and restoration before becoming a public
Firstlight component.

## V3 non-goals

- Re-exporting all 54 Mobile UI types under a second namespace.
- Replacing EDGE layout classes or NativePHP's style/layout transport.
- Recreating Material navigation on iOS or Apple navigation on Android.
- Treating drawing shapes as application-level design-system components.
- Wrapping WebView as a Firstlight-native control.
- Promising platform-only props through a nominally shared API.

Every accepted V3 component follows the standard one-at-a-time delivery and
evidence boundary. A component that adds no meaningful contract should remain
a documented direct `<native:...>` dependency rather than enter the Firstlight
catalogue.
