## firstlightui/nativephp (Firstlight UI)

Firstlight gives NativePHP Mobile apps genuine SwiftUI and Jetpack Compose form, list, selection, dialog and feedback controls, plus Laravel-shaped helpers for validation, submission, authorization, pagination and deletion. Docs: https://firstlightui.dev. Full docs for agents: https://firstlightui.dev/llms-full.txt.

### Rules

- Every screen extends `FirstlightUI\NativeComponent`, never `Native\Mobile\Edge\NativeComponent` directly. It carries the response-revision contract and includes `ValidatesFields`, `SubmitsForms`, `AuthorizesActions`, `PaginatesLists` and `DestroysListItems`.
- Use `<firstlight:*>` tags for controls. Every tag has the same contract on iOS and Android; write one Blade view for both platforms.
- Navigation chrome comes from NativePHP, not Firstlight. Layouts return `true` from `usesNativeChrome()` so iOS gets `NavigationStack`/`TabView` and Android gets Material 3 `Scaffold`/`TopAppBar`. Pushed screens pass `->back(true)` on the `NavBar`; use `$screen->isRootScreen()` to decide.
- Never add the `custom` attribute to `<native:top-bar>` or `<native:bottom-nav>`, and do not build custom-drawn controls (charts, carousels, hand-drawn tab bars or drawers). If a control has no native equivalent on both platforms, leave it out or write your own native renderer.
- Validation is Laravel validation. Declare `$rules` or pass a Form Request class to `$this->validate()`. Fields show errors automatically; do not use `@nativeError` next to Firstlight fields and do not wrap handlers in try/catch. Use `<firstlight:callout error-for="form" />` or `error-summary` for form-level errors.
- `firstlight:list` accepts only `firstlight:list-item` and `firstlight:list-section`. Settings screens that mix controls use `native:list` with `native:list-section` and put Firstlight controls inside.
- Pass stable ids (model keys) into callbacks, never loop indexes.
- Destructive actions open `firstlight:confirmation-dialog` and authorize in PHP; swipe and context-menu actions never delete on their own.
- Colours come from the NativePHP theme in `config/native-ui.php` (`primary`/`on-primary` pairs and so on). Do not hard-code colours on Firstlight controls.

### Start from a recipe

Build new screens from these recipes rather than from scratch:

- List and detail flow (app layout with native chrome): https://firstlightui.dev/how-to/build-a-list-detail-flow
- Searchable list screen: https://firstlightui.dev/how-to/build-a-searchable-list-screen
- Form screen: https://firstlightui.dev/how-to/build-a-form-screen
- Settings screen: https://firstlightui.dev/how-to/build-a-settings-screen

Scaffold a screen with `php artisan make:firstlight-screen Name --form|--list|--destroy --test`.

### Testing

Register Firstlight checks once in `tests/Pest.php` with `FirstlightUI\Testing\ScreenAssertions::register();`, then test screens with `Native\Mobile\Testing\Native::test()` or `Native::visit()`:

@verbatim
<code-snippet name="Test a Firstlight form screen" lang="php">
Feedback::fake();

Native::visit('/posts/create')
    ->set('title', 'Hi')
    ->call('save')
    ->assertFieldError('title')
    ->set('title', 'A real title')
    ->set('body', 'A body long enough to pass.')
    ->call('save')
    ->assertHasNoErrors()
    ->assertFeedbackSent('Post created');
</code-snippet>
@endverbatim
