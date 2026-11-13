<?php

use FirstlightUI\Concerns\KeepsDrafts;
use FirstlightUI\Drafts\Draft;
use FirstlightUI\Drafts\DraftStore;
use FirstlightUI\Facades\Drafts;
use FirstlightUI\Feedback\FeedbackStore;
use FirstlightUI\Feedback\FeedbackTone;
use FirstlightUI\FirstlightServiceProvider;
use FirstlightUI\NativeComponent;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Illuminate\View\View;
use Native\Mobile\Edge\ChromeContributorRegistry;
use Native\Mobile\Edge\ComponentRegistry;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\Elements\Column;
use Native\Mobile\Edge\Elements\Text;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;
use Native\Mobile\Testing\Native;
use Native\Mobile\Testing\TestableComponent;

require_once __DIR__.'/../../vendor/nativephp/mobile/src/jump_bridge_functions.php';

if (! function_exists('event')) {
    function event(object $event): mixed
    {
        return app('events')->dispatch($event);
    }
}

if (! function_exists('view')) {
    function view(?string $view = null, array $data = []): Factory|View
    {
        $factory = app('view');

        return $view === null ? $factory : $factory->make($view, $data);
    }
}

/** An editor whose server copy the tests change between visits. */
class DraftNoteEditor extends NativeComponent
{
    use KeepsDrafts;

    public static string $serverTitle = 'Shopping';

    public static string $serverBody = 'Milk';

    public static string $serverVersion = 'v1';

    public static string $draftProperty = 'body';

    public string $title = '';

    public string $body = '';

    public string $version = '';

    public bool $restored = false;

    public function mount(): void
    {
        $this->title = self::$serverTitle;
        $this->body = self::$serverBody;
        $this->version = self::$serverVersion;
        $this->restored = $this->restoreDraft('note:1', basedOn: $this->version);
    }

    protected function draft(): ?Draft
    {
        if ($this->title === self::$serverTitle && $this->body === self::$serverBody) {
            return null;
        }

        return new Draft('note:1', ['title' => $this->title, self::$draftProperty => $this->body], basedOn: $this->version);
    }

    public function render(): Element
    {
        return $this->response(Column::make(Text::make($this->title), Text::make($this->body)));
    }
}

/** Another screen using the same draft key, which must not see the editor's draft. */
final class DraftNoteViewer extends DraftNoteEditor {}

function openNoteEditor(string $screen = DraftNoteEditor::class): TestableComponent
{
    return Native::test($screen);
}

/** Leave the way the router does on a system back: back intent, then unmount. */
function leaveNoteEditor(TestableComponent $editor): void
{
    $editor->pressBack()->assertWentBack();
    $editor->instance()->unmount();
}

beforeEach(function () {
    $this->previousContainer = Container::getInstance();
    $this->container = new Container;
    Container::setInstance($this->container);
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($this->container);

    $events = new Dispatcher($this->container);
    $this->container->instance('events', $events);
    $this->container->instance(DispatcherContract::class, $events);
    $this->container->instance('config', new ArrayObject(['app' => ['debug' => false], 'view' => ['paths' => []]]));

    $files = new Filesystem;
    $cache = sys_get_temp_dir().'/firstlight-keeps-drafts-blade';
    $files->ensureDirectoryExists($cache);
    $blade = new BladeCompiler($files, $cache);
    $blade->precompiler(new NativeTagPrecompiler);
    $engines = new EngineResolver;
    $engines->register('blade', fn () => new CompilerEngine($blade, $files));
    $views = new Factory($engines, new FileViewFinder($files, []), $events);
    $views->setContainer($this->container);
    $this->container->instance('blade.compiler', $blade);
    $this->container->instance('view', $views);

    ComponentRegistry::reset();
    ChromeContributorRegistry::reset();
    NativeElementCollector::reset();

    $provider = new FirstlightServiceProvider($this->container);
    $provider->register();
    $provider->boot();

    DraftNoteEditor::$serverTitle = 'Shopping';
    DraftNoteEditor::$serverBody = 'Milk';
    DraftNoteEditor::$serverVersion = 'v1';
    DraftNoteEditor::$draftProperty = 'body';
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ComponentRegistry::reset();
    ChromeContributorRegistry::reset();
    Facade::clearResolvedInstances();
    Container::setInstance($this->previousContainer);
    Facade::setFacadeApplication($this->previousContainer);
});

function feedbackMessages(): array
{
    return array_map(
        fn ($record) => [$record->tone, $record->message, $record->actionLabel, $record->hold],
        app(FeedbackStore::class)->all(),
    );
}

it('restores edits on the same version after the screen is left and reopened', function () {
    $editor = openNoteEditor()->set('title', 'Groceries')->set('body', 'Milk, eggs');
    leaveNoteEditor($editor);

    $reopened = openNoteEditor()
        ->assertSet('title', 'Groceries')
        ->assertSet('body', 'Milk, eggs')
        ->assertSet('restored', true)
        ->assertSee('Milk, eggs');

    expect(feedbackMessages())->toBe([[FeedbackTone::Default, 'Restored your unsaved changes.', 'Discard', false]])
        ->and(app(DraftStore::class)->has(DraftNoteEditor::class, 'note:1'))->toBeFalse();

    // A third visit finds nothing: the reopened screen took the draft.
    $reopened->instance()->title = 'Shopping';
    $reopened->instance()->body = 'Milk';
    leaveNoteEditor($reopened);

    openNoteEditor()->assertSet('restored', false)->assertSet('body', 'Milk');
});

it('keeps nothing when the screen is left unedited', function () {
    leaveNoteEditor(openNoteEditor());

    openNoteEditor()->assertSet('restored', false)->assertSet('body', 'Milk');

    expect(feedbackMessages())->toBe([]);
});

it('puts the loaded values back when Discard is pressed', function () {
    leaveNoteEditor(openNoteEditor()->set('body', 'Milk, eggs'));

    $reopened = openNoteEditor()
        ->fireEvent('action', 1)
        ->assertSet('body', 'Milk')
        ->assertSet('title', 'Shopping')
        ->assertSee('Milk');

    leaveNoteEditor($reopened);

    expect(app(DraftStore::class)->has(DraftNoteEditor::class, 'note:1'))->toBeFalse();
});

it('offers a draft of another version with Restore anyway instead of assigning it', function () {
    leaveNoteEditor(openNoteEditor()->set('body', 'Milk, eggs'));
    DraftNoteEditor::$serverBody = 'Milk, bread';
    DraftNoteEditor::$serverVersion = 'v2';

    $reopened = openNoteEditor()
        ->assertSet('restored', false)
        ->assertSet('body', 'Milk, bread');

    expect(feedbackMessages())->toBe([[
        FeedbackTone::Warning,
        "This changed since you last edited it. Your unsaved changes weren't restored.",
        'Restore anyway',
        true,
    ]]);

    $warningId = app(FeedbackStore::class)->all()[0]->id;
    $reopened->fireEvent('action', 1)->assertSet('body', 'Milk, eggs')->assertSee('Milk, eggs');

    // The native queue treats an ID whose action was just pressed as finished,
    // so the Restored message must arrive under a new one.
    expect(feedbackMessages())->toBe([[FeedbackTone::Default, 'Restored your unsaved changes.', 'Discard', false]])
        ->and(app(FeedbackStore::class)->all()[0]->id)->not->toBe($warningId);

    $restored = app(FeedbackStore::class)->all()[0];
    $reopened->fireEvent(sprintf('action("%s", "%s", 1)', $restored->id, $restored->actionKey), 1)
        ->assertSet('body', 'Milk, bread');
    $reopened->instance()->body = 'Milk, eggs';

    // The restored values now sit on the new version, so the next visit restores them without a warning.
    leaveNoteEditor($reopened);
    openNoteEditor()->assertSet('restored', true)->assertSet('body', 'Milk, eggs');
});

it('offers an unanswered draft again, and forgets one whose warning was swiped away', function () {
    leaveNoteEditor(openNoteEditor()->set('body', 'Milk, eggs'));
    DraftNoteEditor::$serverVersion = 'v2';

    leaveNoteEditor(openNoteEditor());

    expect(app(FeedbackStore::class)->all())->toBe([]);

    $declined = openNoteEditor()->assertSet('body', 'Milk');
    expect(feedbackMessages()[0][2])->toBe('Restore anyway');

    $declined->fireEvent('dismiss', 1);
    leaveNoteEditor($declined);

    openNoteEditor()->assertSet('restored', false)->assertSet('body', 'Milk');
    expect(feedbackMessages())->toBe([]);
});

it('keeps drafts apart by screen and by scope, keeps none without a scope, and flushes', function () {
    $account = 'alice';
    Drafts::scopeUsing(function () use (&$account) {
        return $account;
    });

    leaveNoteEditor(openNoteEditor()->set('body', 'Alice was here'));

    openNoteEditor(DraftNoteViewer::class)->assertSet('restored', false);

    $account = 'bob';
    openNoteEditor()->assertSet('restored', false)->assertSet('body', 'Milk');

    $account = null;
    leaveNoteEditor(openNoteEditor()->set('body', 'Signed out'));

    $account = 'alice';
    expect(Drafts::has(DraftNoteEditor::class, 'note:1'))->toBeTrue();

    Drafts::flush();

    openNoteEditor()->assertSet('restored', false)->assertSet('body', 'Milk');
});

it('rejects a draft value that is not a public property', function () {
    DraftNoteEditor::$draftProperty = 'nativeParams';
    leaveNoteEditor(openNoteEditor()->set('body', 'Milk, eggs'));

    expect(fn () => openNoteEditor())->toThrow(
        InvalidArgumentException::class,
        'Draft value [nativeParams] is not a public property of [DraftNoteEditor].',
    );
});

it('rejects a blank key and values that are not keyed by property name', function () {
    expect(fn () => new Draft(' ', ['body' => 'x']))->toThrow(InvalidArgumentException::class)
        ->and(fn () => new Draft('note:1', ['x']))->toThrow(InvalidArgumentException::class)
        ->and((new Draft('note:1', [], basedOn: 7))->basedOn)->toBe('7');
});
