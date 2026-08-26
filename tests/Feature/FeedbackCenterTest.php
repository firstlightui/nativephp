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

if (! function_exists('config')) {
    function config(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return app('config');
        }

        return match ($key) {
            'app.debug' => false,
            default => $default,
        };
    }
}

if (! function_exists('view')) {
    function view(?string $view = null, array $data = []): Factory|View
    {
        $factory = app('view');

        return $view === null ? $factory : $factory->make($view, $data);
    }
}

if (! function_exists('config')) {
    function config(?string $key = null, mixed $default = null): mixed
    {
        /** @var ArrayObject<string, mixed> $repository */
        $repository = app('config');

        if ($key === null) {
            return $repository;
        }

        $segments = explode('.', $key);
        $value = $repository[array_shift($segments)] ?? null;

        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
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
        'app' => ['debug' => false],
        'view' => ['paths' => []],
        'app' => ['debug' => false],
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
    $automaticProps = $tree['children'][0]['props'];
    $heldProps = $tree['children'][1]['props'];
    $actionCallback = $automaticProps['on_action'];
    $timeoutCallback = $automaticProps['on_timeout'];
    $automaticManualCallback = $automaticProps['on_manual'];
    $heldManualCallback = $heldProps['on_manual'];

    expect($tree['type'])->toBe('firstlight.feedback-center')
        ->and(array_column($tree['children'], 'type'))->toBe([
            'firstlight.feedback-item',
            'firstlight.feedback-item',
        ])
        ->and($tree['children'])->toHaveCount(2)
        ->and($automaticProps)->toBe([
            'feedback_id' => 'saved',
            'message' => 'Saved',
            'tone' => 'success',
            'hold' => false,
            'action_label' => 'Undo',
            'on_action' => $actionCallback,
            'on_timeout' => $timeoutCallback,
            'on_manual' => $automaticManualCallback,
            'dismiss_label' => 'Dismiss',
            'dismiss_a11y_label' => 'Dismiss feedback',
        ])
        ->and($actionCallback)->toBeInt()->not->toBe(0)
        ->and($timeoutCallback)->toBeInt()->not->toBe(0)
        ->and($automaticManualCallback)->toBeInt()->not->toBe(0)
        ->and($heldProps)->toBe([
            'feedback_id' => 'offline',
            'message' => 'Offline',
            'tone' => 'warning',
            'hold' => true,
            'on_manual' => $heldManualCallback,
            'dismiss_label' => 'Dismiss',
            'dismiss_a11y_label' => 'Dismiss feedback',
        ])
        ->and($heldManualCallback)->toBeInt()->not->toBe(0)
        ->and($tree)->not->toHaveKeys(['layout', 'style'])
        ->and($tree['children'][0])->not->toHaveKeys(['layout', 'style']);

    foreach ([
        $actionCallback,
        $timeoutCallback,
        $automaticManualCallback,
        $heldManualCallback,
    ] as $callbackId) {

        expect($frame['consumer']->resolve($callbackId))->toBeNull()
            ->and($frame['host']->feedbackCallbacks()->resolve($callbackId))->not->toBeNull();
    }
});

it('removes an action first and dispatches action then dismissal exactly once', function () {
    $feedbackId = "saved'\"|,()\\path";
    $actionKey = "undo'\"|,()\\key";
    $store = app(FeedbackStore::class);
    app(FeedbackManager::class)->success('Saved')->id($feedbackId)
        ->action('Undo', $actionKey)->send();

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
        'args' => [$feedbackId, $actionKey, 1],
    ]);

    $frame['host']->dispatchFeedbackCallback($callbackId);
    $frame['host']->dispatchFeedbackCallback($callbackId);

    expect($observed)->toBe([
        ['action', $feedbackId, $actionKey],
        ['dismissed', $feedbackId, FeedbackDismissReason::Action],
    ])->and($store->all())->toBe([]);
});

it('removes a record before rejecting a mismatched action key without dispatching events', function () {
    $feedbackId = "saved'\"|,()\\path";
    $store = app(FeedbackStore::class);
    app(FeedbackManager::class)->success('Saved')->id($feedbackId)
        ->action('Undo', "undo'\"|,()\\key")->send();

    $observed = [];
    $this->events->listen(FeedbackActionPressed::class, function (FeedbackActionPressed $event) use (&$observed): void {
        $observed[] = $event;
    });
    $this->events->listen(FeedbackDismissed::class, function (FeedbackDismissed $event) use (&$observed): void {
        $observed[] = $event;
    });

    $frame = renderFeedbackCenterFrame($store);
    $frame['host']->feedbackCenter()->action($feedbackId, "wrong'\"|,()\\key");

    expect($store->all())->toBe([])
        ->and($observed)->toBe([]);
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
        'args' => ['status', $expectedReason->value, 1],
    ]);

    $frame['host']->dispatchFeedbackCallback($callbackId);
    $frame['host']->dispatchFeedbackCallback($callbackId);

    expect($observed)->toBe([['status', $expectedReason]])
        ->and($store->all())->toBe([]);
})->with([
    'automatic timeout' => [false, 'on_timeout', FeedbackDismissReason::Timeout],
    'automatic platform dismissal' => [false, 'on_manual', FeedbackDismissReason::Manual],
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

it('keeps content-addressed callback ids stable across navigation while updating semantic records', function () {
    $store = app(FeedbackStore::class);
    app(FeedbackManager::class)->success('Saved')->id('saved')
        ->action('Undo', 'undo-save')->send();
    app(FeedbackManager::class)->warning('Offline')->id('offline')->hold()->send();

    $first = renderFeedbackCenterFrame($store);

    app(FeedbackManager::class)->success('Saved again')->id('saved')
        ->action('Undo', 'undo-save')->send();
    app(FeedbackManager::class)->warning('Offline again')->id('offline')->hold()->send();

    $second = renderFeedbackCenterFrame($store);

    expect(array_column(array_column($second['tree']['children'], 'props'), 'feedback_id'))
        ->toBe(['saved', 'offline'])
        ->and($second['tree']['children'][0]['props']['message'])->toBe('Saved again')
        ->and($second['tree']['children'][1]['props']['message'])->toBe('Offline again');

    foreach ([
        [0, 'on_action'],
        [0, 'on_timeout'],
        [0, 'on_manual'],
        [1, 'on_manual'],
    ] as [$itemIndex, $callbackProp]) {
        $priorCallback = $first['tree']['children'][$itemIndex]['props'][$callbackProp];
        $refreshedCallback = $second['tree']['children'][$itemIndex]['props'][$callbackProp];

        expect($refreshedCallback)->toBeInt()->not->toBe(0)
            ->and($refreshedCallback)->toBe($priorCallback)
            ->and($second['consumer']->resolve($refreshedCallback))->toBeNull()
            ->and($second['host']->feedbackCallbacks()->resolve($refreshedCallback))->not->toBeNull();
    }
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

it('keeps native root hosts aligned with the published feedback center wire type', function () {
    $wireType = FeedbackCenterElement::make()->toArray(new CallbackRegistry)['type'];
    $root = dirname(__DIR__, 2);
    $ios = file_get_contents($root.'/resources/ios/FirstlightUIInit.swift');
    $android = file_get_contents($root.'/resources/android/FirstlightUIInit.kt');

    expect($wireType)->toBe('firstlight.feedback-center')
        ->and($ios)->toContain('consumes: "'.$wireType.'"')
        ->toContain('$0.type == "'.$wireType.'"')
        ->and($android)->toContain('consumes = "'.$wireType.'"')
        ->toContain('it.type == "'.$wireType.'"');
});
