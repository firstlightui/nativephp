## Learned User Preferences

- Prefer landing Firstlight work by committing and merging straight to `main` rather than opening PRs when asked to ship.
- Keep local planning files out of git; do not commit `roadmap.md` or the mobile-ui gap scratchpad.
- Prefer genuine native platform presentation for dialogs and sheets (benchmark against Confirmation Dialog) over web-like chrome.
- Commit documentation screenshot PNGs under `docs/screenshots/` so the public docs site does not 404.
- Prefer Laravel-shaped SuperNative PHP extensions that reuse existing tags over new catalogue tags when the need is orchestration (validation, submit, authorize, pagination, destructive list actions).
- For Media field design, prefer one catalogue control with `image`/`document` modes, author-controlled crop/`aspect`, and a rich `MediaValue` model rather than a picker-only control.
- When capturing docs screenshots, prefer already-open simulators/emulators rather than launching new devices.
- Prefer continuing feature work inline in the primary checkout when subagent-driven or git-worktree setup is causing friction.
- Prefer package locale chrome next (picker locale/timezone plus Laravel lang for package-owned strings; no translation UI) over a Notification→Feedback bridge.

## Learned Workspace Facts

- Firstlight UI (`firstlightui/nativephp`) exposes a shared EDGE API backed by SwiftUI and Jetpack Compose for NativePHP; public docs are at https://firstlightui.dev.
- Firstlight's publication contract is `FirstlightUI\NativeComponent`: final chrome-wrapped roots carry a private process-wide increasing `firstlight_response_revision`; Blade is automatic, programmatic roots return `$this->response($element)`, nested children and partials are unstamped, and streaming is final-disabled. Use official unpatched Mobile `^4.6` / UI `^0.8` binaries; #365 remains upstream but is no longer the release dependency. Pre-alpha qualification still requires physical-device, soak/performance, and wider app evidence; revisions are not per-event acknowledgements or concurrency serialization.
- Catalogue boundary: new `<firstlight:...>` tags only for semantic controls with equal iOS/Android contracts; layout and navigation chrome stay in Mobile UI; Laravel familiarity is delivered via PHP SuperNative extensions.
- SuperNative PHP helpers live as concerns on `NativeComponent` (for example `ValidatesFields`, `SubmitsForms`, `AuthorizesActions`, `PaginatesLists`, `DestroysListItems`).
- Approved Media field is `<firstlight:media>` binding `?MediaValue` (default disk `mobile_public`); image mode uses a Firstlight-owned crop sheet, document mode uses the system file picker.
- Component screenshot evidence belongs at `docs/screenshots/<component>/{ios,android}-{light,dark}.png`.
- `roadmap.md` and `mobile-ui-component-gap-scratchpad.md` are gitignored local planning files.
- iOS and Android simulators are available in the local macOS environment. Debian orbs support PHP, web, and Android JVM tests; iOS builds and simulator captures require macOS.
- Firstlight must not POST to FCM from the device; a Notification→Feedback bridge, if resumed, is inbound-only (foreground receive and tap enqueue existing Transient Feedback; no `FeedbackChannel` in v1).
