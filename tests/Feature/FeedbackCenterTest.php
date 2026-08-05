<?php

use FirstlightUI\Elements\FeedbackCenter as FeedbackCenterElement;
use FirstlightUI\Events\FeedbackActionPressed;
use FirstlightUI\Events\FeedbackDismissed;
use FirstlightUI\Feedback\FeedbackDismissReason;
use FirstlightUI\Feedback\FeedbackManager;
use FirstlightUI\Feedback\FeedbackStore;
use FirstlightUI\FirstlightServiceProvider;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Illuminate\View\View;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ChromeContributorRegistry;
use Native\Mobile\Edge\ComponentRegistry;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

if (! function_exists('app')) {
    function app(?string $abstract = null): mixed
    {
        $container = Container::getInstance();

        return $abstract === null ? $container : $container->make($abstract);
    }
}

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

final class FeedbackCenterTestHost extends NativeComponent
{
    public function __construct(private readonly CallbackRegistry $consumerCallbacks)
    {
        $this->nativeCallbacks = $consumerCallbacks;
    }

    /** @return array<string, mixed> */
    public function renderFeedbackCenter(): array
    {
        NativeElementCollector::reset();
        NativeElementCollector::setCallbacks($this->consumerCallbacks);
        NativeElementCollector::setOwner($this);

        $this->mountChildComponent('firstlight-feedback-center', [
            'key' => 'firstlight-feedback-center',
        ]);

        return NativeElementCollector::collect()->toArray($this->consumerCallbacks);
    }

    public function feedbackCenter(): NativeComponent
    {
        return $this->nativeChildComponents['firstlight-feedback-center|key:firstlight-feedback-center'];
    }

    public function feedbackCallbacks(): CallbackRegistry
    {
        return $this->feedbackCenter()->nativeCallbacks;
    }

    public function dispatchFeedbackCallback(int $callbackId): void
    {
        $this->dispatch([
            'callback_id' => $callbackId,
            'type' => 1,
        ]);
    }

    public function renderPartial(View $view): Element
    {
        return $this->fromViewPartial($view);
    }
}

beforeEach(function () {
    $this->previousContainer = Container::getInstance();
    $this->container = new Container;
    Container::setInstance($this->container);

    $this->events = new Dispatcher($this->container);
    $this->container->instance('events', $this->events);
    $this->container->instance(DispatcherContract::class, $this->events);
    $this->container->instance('config', new ArrayObject([
        'view' => ['paths' => []],
    ]));

    ComponentRegistry::reset();
    ChromeContributorRegistry::reset();
    NativeElementCollector::reset();

    $this->provider = new FirstlightServiceProvider($this->container);
    $this->provider->register();
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ComponentRegistry::reset();
    ChromeContributorRegistry::reset();
    Container::setInstance($this->previousContainer);
});

/** @return array{tree: array<string, mixed>, host: FeedbackCenterTestHost, consumer: CallbackRegistry} */
function renderFeedbackCenterFrame(FeedbackStore $store): array
{
    expect(app(FeedbackStore::class))->toBe($store);

    $consumer = new CallbackRegistry;
    $host = new FeedbackCenterTestHost($consumer);

    return [
        'tree' => $host->renderFeedbackCenter(),
        'host' => $host,
        'consumer' => $consumer,
    ];
}

/** @return array<string, mixed> */
function renderFeedbackCenter(FeedbackStore $store): array
{
    return renderFeedbackCenterFrame($store)['tree'];
}

function configureFeedbackCenterViews(Container $container, Dispatcher $events): void
{
    $files = new Filesystem;
    $cache = sys_get_temp_dir().'/firstlight-feedback-center-blade';
    $files->ensureDirectoryExists($cache);

    $blade = new BladeCompiler($files, $cache);
    $blade->precompiler(new NativeTagPrecompiler);

    $engines = new EngineResolver;
    $engines->register('blade', fn () => new CompilerEngine($blade, $files));

    $views = new Factory($engines, new FileViewFinder($files, []), $events);
    $views->setContainer($container);

    $container->instance('blade.compiler', $blade);
    $container->instance('view', $views);
}

it('publishes the full fifo queue with package-owned callbacks', function () {
    $store = app(FeedbackStore::class);
    app(FeedbackManager::class)->success('Saved')->id('saved')
        ->action('Undo', 'undo-save')->send();
    app(FeedbackManager::class)->warning('Offline')->id('offline')->hold()->send();

    $frame = renderFeedbackCenterFrame($store);
    $tree = $frame['tree'];

    expect($tree['type'])->toBe('firstlight.feedback-center')
        ->and(array_column($tree['children'], 'props', 'type'))->toHaveKeys([
            'firstlight.feedback-item',
        ])
        ->and($tree['children'])->toHaveCount(2)
        ->and($tree['children'][0]['props'])->toMatchArray([
            'feedback_id' => 'saved',
            'message' => 'Saved',
            'tone' => 'success',
            'hold' => false,
            'action_label' => 'Undo',
        ])
        ->and($tree['children'][0]['props']['on_action'])->toBeInt()->not->toBe(0)
        ->and($tree['children'][0]['props']['on_timeout'])->toBeInt()->not->toBe(0)
        ->and($tree['children'][0]['props'])->not->toHaveKey('on_manual')
        ->and($tree['children'][1]['props'])->toMatchArray([
            'feedback_id' => 'offline',
            'message' => 'Offline',
            'tone' => 'warning',
            'hold' => true,
        ])
        ->and($tree['children'][1]['props']['on_manual'])->toBeInt()->not->toBe(0)
        ->and($tree['children'][1]['props'])->not->toHaveKeys([
            'action_label',
            'on_action',
            'on_timeout',
        ])
        ->and($tree)->not->toHaveKeys(['layout', 'style'])
        ->and($tree['children'][0])->not->toHaveKeys(['layout', 'style']);

    foreach ([
        $tree['children'][0]['props']['on_action'],
        $tree['children'][0]['props']['on_timeout'],
        $tree['children'][1]['props']['on_manual'],
    ] as $callbackId) {

        expect($frame['consumer']->resolve($callbackId))->toBeNull()
            ->and($frame['host']->feedbackCallbacks()->resolve($callbackId))->not->toBeNull();
    }
});

it('removes an action first and dispatches action then dismissal exactly once', function () {
    $store = app(FeedbackStore::class);
    app(FeedbackManager::class)->success('Saved')->id('saved')
        ->action('Undo', 'undo-save')->send();

    $observed = [];
    $this->events->listen(FeedbackActionPressed::class, function (FeedbackActionPressed $event) use ($store, &$observed): void {
        expect($store->all())->toBe([]);
        $observed[] = ['action', $event->id, $event->actionKey];
    });
    $this->events->listen(FeedbackDismissed::class, function (FeedbackDismissed $event) use (&$observed): void {
        $observed[] = ['dismissed', $event->id, $event->reason];
    });

    $frame = renderFeedbackCenterFrame($store);
    $callbackId = $frame['tree']['children'][0]['props']['on_action'];

    expect($frame['host']->feedbackCallbacks()->resolve($callbackId))->toBe([
        'method' => 'action',
        'args' => ['saved', 'undo-save'],
    ]);

    $frame['host']->dispatchFeedbackCallback($callbackId);
    $frame['host']->dispatchFeedbackCallback($callbackId);

    expect($observed)->toBe([
        ['action', 'saved', 'undo-save'],
        ['dismissed', 'saved', FeedbackDismissReason::Action],
    ])->and($store->all())->toBe([]);
});

it('guarantees action dismissal when an action listener throws without swallowing the failure', function () {
    $store = app(FeedbackStore::class);
    app(FeedbackManager::class)->message('Retry?')->id('retry')
        ->action('Retry', 'retry-now')->send();

    $dismissals = [];
    $this->events->listen(FeedbackActionPressed::class, function (): never {
        throw new RuntimeException('application listener failed');
    });
    $this->events->listen(FeedbackDismissed::class, function (FeedbackDismissed $event) use (&$dismissals): void {
        $dismissals[] = [$event->id, $event->reason];
    });

    $frame = renderFeedbackCenterFrame($store);
    $callbackId = $frame['tree']['children'][0]['props']['on_action'];

    expect(fn () => $frame['host']->dispatchFeedbackCallback($callbackId))
        ->toThrow(RuntimeException::class, 'application listener failed')
        ->and($dismissals)->toBe([['retry', FeedbackDismissReason::Action]])
        ->and($store->all())->toBe([]);
});

it('dispatches only the supported user dismissal reason', function (
    bool $hold,
    string $callbackProp,
    FeedbackDismissReason $expectedReason,
) {
    $store = app(FeedbackStore::class);
    $pending = app(FeedbackManager::class)->message('Status')->id('status');
    ($hold ? $pending->hold() : $pending)->send();

    $observed = [];
    $this->events->listen(FeedbackDismissed::class, function (FeedbackDismissed $event) use (&$observed): void {
        $observed[] = [$event->id, $event->reason];
    });

    $frame = renderFeedbackCenterFrame($store);
    $callbackId = $frame['tree']['children'][0]['props'][$callbackProp];

    expect($frame['host']->feedbackCallbacks()->resolve($callbackId))->toBe([
        'method' => 'dismiss',
        'args' => ['status', $expectedReason->value],
    ]);

    $frame['host']->dispatchFeedbackCallback($callbackId);
    $frame['host']->dispatchFeedbackCallback($callbackId);

    expect($observed)->toBe([['status', $expectedReason]])
        ->and($store->all())->toBe([]);
})->with([
    'automatic timeout' => [false, 'on_timeout', FeedbackDismissReason::Timeout],
    'held manual dismissal' => [true, 'on_manual', FeedbackDismissReason::Manual],
]);

it('fails closed for malformed and action dismissal reasons', function (string $reason) {
    $store = app(FeedbackStore::class);
    app(FeedbackManager::class)->message('Status')->id('status')->hold()->send();

    $observed = [];
    $this->events->listen(FeedbackDismissed::class, function (FeedbackDismissed $event) use (&$observed): void {
        $observed[] = $event;
    });

    $frame = renderFeedbackCenterFrame($store);
    $frame['host']->feedbackCenter()->dismiss('status', $reason);

    expect($store->all())->toHaveCount(1)
        ->and($store->all()[0]->id)->toBe('status')
        ->and($observed)->toBe([]);
})->with(['', 'later', FeedbackDismissReason::Action->value]);

it('refreshes callback ids across navigation without reordering updated semantic records', function () {
    $store = app(FeedbackStore::class);
    app(FeedbackManager::class)->success('Saved')->id('saved')
        ->action('Undo', 'undo-save')->send();
    app(FeedbackManager::class)->warning('Offline')->id('offline')->send();

    $first = renderFeedbackCenter($store);

    app(FeedbackManager::class)->success('Saved again')->id('saved')
        ->action('Undo', 'undo-save')->send();

    $second = renderFeedbackCenter($store);

    expect(array_column(array_column($second['children'], 'props'), 'feedback_id'))
        ->toBe(['saved', 'offline'])
        ->and($second['children'][0]['props']['message'])->toBe('Saved again')
        ->and($second['children'][0]['props']['on_action'])
        ->not->toBe($first['children'][0]['props']['on_action'])
        ->and($second['children'][0]['props']['on_timeout'])
        ->not->toBe($first['children'][0]['props']['on_timeout']);
});

it('registers package chrome that always renders the empty feedback sentinel', function () {
    configureFeedbackCenterViews($this->container, $this->events);
    $this->provider->boot();

    $consumer = new CallbackRegistry;
    $screen = new FeedbackCenterTestHost($consumer);
    $published = ChromeContributorRegistry::collect(
        $screen,
        null,
        fn (View $view): Element => $screen->renderPartial($view),
    );

    expect(ComponentRegistry::resolve('firstlight-feedback-center'))
        ->toBe(FirstlightUI\NativeComponents\FeedbackCenter::class)
        ->and($published)->toHaveCount(1)
        ->and($published[0])->toBeInstanceOf(FeedbackCenterElement::class)
        ->and($published[0]->toArray($consumer)['type'])->toBe('firstlight.feedback-center')
        ->and($published[0]->toArray($consumer))->not->toHaveKey('children');
});
