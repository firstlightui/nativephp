# Firstlight roadmap

Updated 2026-08-24. One sequence; one **Now**. Catalogue tags follow the [boundary](spec/reference/catalogue-boundary.md). Layout, navigation chrome, masks, schema builders, and WebView stay out.

## Now

**Media field** — first new catalogue tag after the PHP layer. One image or document; Storage + Validator `error` slot; native picker/camera. No crop editor.

## Next

1. Destructive list actions (authorize + Confirmation Dialog + Feedback)
2. Notification → Feedback bridge
3. Package locale chrome (picker locale, built-in strings)

## Shipped

Alpha catalogue; Activity Indicator, Checkbox, List Item, Confirmation Dialog; Transient Feedback (development); List and List Section; Modal and Bottom Sheet; Alert Dialog; `ValidatesFields`, `SubmitsForms`, `AuthorizesActions`, `PaginatesLists`.

## Release (parallel, not the next feature)

NativePHP identical publication ([mobile-air#365](https://github.com/NativePHP/mobile-air/issues/365)). Physical VoiceOver / TalkBack. Transient Feedback release screenshots and alpha review.

## Hold / research

Tabs: research. Chip: hold until a real standalone filter. Virtual List, Carousel, drawers, overlays: only from a concrete consumer. Column/Row/Stack wrappers: do not add unless they earn a contract Mobile UI lacks.
