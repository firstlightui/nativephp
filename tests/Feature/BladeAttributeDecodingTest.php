<?php

use FirstlightUI\Components\Concerns\DecodesBladeAttributes;
use FirstlightUI\Elements\Callout;
use FirstlightUI\Elements\CodeEditor;
use FirstlightUI\Elements\ListContainer;
use FirstlightUI\Elements\ListItem;
use FirstlightUI\Elements\TextArea;
use FirstlightUI\Elements\TextField;
use FirstlightUI\FirstlightServiceProvider;
use Illuminate\Container\Container;
use Illuminate\Contracts\View\Factory as ViewFactoryContract;
use Illuminate\Events\Dispatcher;
use FirstlightUI\Validation\FieldErrorBag;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\MessageBag;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Component;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Illuminate\View\View;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

if (! function_exists('view')) {
    function view(?string $view = null, array $data = []): Factory|View
    {
        $factory = Container::getInstance()->make('view');

        return $view === null ? $factory : $factory->make($view, $data);
    }
}

beforeEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    ElementRegistry::register('firstlight.code-editor', CodeEditor::class);
    ElementRegistry::register('firstlight.text-area', TextArea::class);
    ElementRegistry::register('firstlight.text-field', TextField::class);
    ElementRegistry::register('firstlight.list', ListContainer::class);
    ElementRegistry::register('firstlight.list-item', ListItem::class);
    ElementRegistry::register('firstlight.callout', Callout::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function renderFirstlightBladeTree(string $source, array $data): array
{
    $filesystem = new Filesystem;
    $temporaryPath = sys_get_temp_dir().'/firstlight-blade-decoding-'.bin2hex(random_bytes(8));
    $compiledPath = $temporaryPath.'/compiled';
    $filesystem->makeDirectory($compiledPath, 0755, true);

    $previousContainer = Container::getInstance();
    $container = new Container;
    Container::setInstance($container);
    $container->instance('config', new class($compiledPath)
    {
        public function __construct(private string $compiledPath) {}

        public function get(string $key, mixed $default = null): mixed
        {
            return $key === 'view.compiled' ? $this->compiledPath : $default;
        }
    });

    $compiler = new BladeCompiler($filesystem, $compiledPath);
    $resolver = new EngineResolver;
    $resolver->register('blade', fn () => new CompilerEngine($compiler, $filesystem));
    $factory = new Factory($resolver, new FileViewFinder($filesystem, [$temporaryPath]), new Dispatcher($container));
    $factory->setContainer($container);
    $factory->addNamespace('__components', $compiledPath);
    $factory->addNamespace('nativephp-mobile', dirname(__DIR__, 2).'/vendor/nativephp/mobile/src/resources/views');
    $container->instance(ViewFactoryContract::class, $factory);
    $container->instance('view', $factory);
    $container->instance('blade.compiler', $compiler);

    Component::flushCache();
    $compiler->component('native-firstlight-code-editor', FirstlightUI\Components\CodeEditor::class);
    $compiler->component('native-firstlight-text-area', FirstlightUI\Components\TextArea::class);
    $compiler->component('native-firstlight-text-field', FirstlightUI\Components\TextField::class);
    $compiler->component('native-firstlight-list', FirstlightUI\Components\ListContainer::class);
    $compiler->component('native-firstlight-list-item', FirstlightUI\Components\ListItem::class);
    $compiler->component('native-firstlight-callout', FirstlightUI\Components\Callout::class);
    $compiler->precompiler(new NativeTagPrecompiler);
    (new FirstlightServiceProvider($container))->boot();
    NativeElementCollector::reset();
    NativeTagPrecompiler::setActive(true);

    try {
        $compiled = $compiler->compileString($source);
        extract($data, EXTR_SKIP);
        $__env = $factory;
        $compiledFile = $compiledPath.'/'.hash('sha256', $compiled).'.php';
        $filesystem->put($compiledFile, $compiled);
        $factory->incrementRender();
        ob_start();
        try {
            include $compiledFile;
        } finally {
            ob_end_clean();
            $factory->decrementRender();
        }

        return NativeElementCollector::collect()->toArray(new CallbackRegistry);
    } finally {
        NativeTagPrecompiler::setActive(false);
        NativeElementCollector::reset();
        Component::flushCache();
        Container::setInstance($previousContainer);
        $filesystem->deleteDirectory($temporaryPath);
    }
}

const FIRSTLIGHT_ESCAPABLE_TEXT = "{\"say\": \"hi\" & <b>it's</b>}\nliteral &amp; stays";

it('publishes the documented list pagination callback through Blade', function () {
    $tree = renderFirstlightBladeTree(
        '<firstlight:list @endReached="loadMore"><firstlight:list-item headline="Issue" @press="openIssue" /></firstlight:list>',
        [],
    );

    expect($tree['props']['on_end_reached'])->toBeInt()->toBeGreaterThan(0);
});

it('keeps upstream unknown-tag diagnostics inside Firstlight containers', function () {
    renderFirstlightBladeTree('<firstlight:list><missing-control /></firstlight:list>', []);
})->throws(\Native\Mobile\Edge\Exceptions\UnknownElementException::class, 'missing-control');

it('omits nullable and empty optional list row text through Blade', function (mixed $value) {
    $tree = renderFirstlightBladeTree(
        '<firstlight:list-item headline="Issue" :supporting="$value" :trailing-text="$value" @press="openIssue" />',
        ['value' => $value],
    );

    expect($tree['props'])->not->toHaveKeys(['supporting', 'trailing_type', 'trailing_value']);
})->with([null, '', '  ']);

it('publishes dynamic string attributes exactly as PHP holds them', function (string $tag) {
    $tree = renderFirstlightBladeTree(
        "<firstlight:{$tag} :label=\"\$label\" :value=\"\$value\" />",
        ['label' => 'Tom & "Jerry"', 'value' => FIRSTLIGHT_ESCAPABLE_TEXT],
    );

    expect($tree['props']['value'])->toBe(FIRSTLIGHT_ESCAPABLE_TEXT)
        ->and($tree['props']['label'])->toBe('Tom & "Jerry"');
})->with(['code-editor', 'text-area', 'text-field']);

it('publishes native:model values without HTML entities', function () {
    $tree = renderFirstlightBladeTree(
        '<firstlight:code-editor label="Payload" language="json" native:model="payload" />',
        ['payload' => FIRSTLIGHT_ESCAPABLE_TEXT],
    );

    expect($tree['props']['value'])->toBe(FIRSTLIGHT_ESCAPABLE_TEXT);
});

it('decodes Blade attributes in every Firstlight adapter', function () {
    $missing = collect(glob(dirname(__DIR__, 2).'/src/Components/*.php'))
        ->map(fn (string $path): string => 'FirstlightUI\\Components\\'.basename($path, '.php'))
        ->reject(fn (string $class): bool => in_array(DecodesBladeAttributes::class, class_uses_recursive($class), true))
        ->values()
        ->all();

    expect($missing)->toBe([]);
});

it('publishes error-bound Callouts only while the bag has their messages', function () {
    $source = '<native:column><firstlight:callout error-for="form" /><firstlight:callout error-summary /><firstlight:callout tone="warning" error-for="payment" action-label="Contact support" @press="contactSupport" /></native:column>';

    $failed = FieldErrorBag::using(new MessageBag([
        'form' => ['These credentials do not match our records.'],
        'email' => ['The email field is required.', 'Second email message'],
        'payment' => ['The card was declined.'],
    ]), fn () => renderFirstlightBladeTree($source, []));
    $single = FieldErrorBag::using(new MessageBag([
        'form' => ['These credentials do not match our records.'],
    ]), fn () => renderFirstlightBladeTree($source, []));
    $clean = FieldErrorBag::using(new MessageBag, fn () => renderFirstlightBladeTree($source, []));

    [$form, $summary, $payment] = $failed['children'];

    expect($form['props'])->toMatchArray([
        'message' => 'These credentials do not match our records.',
        'tone' => 'danger',
    ])
        ->and($summary['props']['message'])->toBe(implode("\n", [
            'There are 3 errors.',
            'These credentials do not match our records.',
            'The email field is required.',
            'The card was declined.',
        ]))
        ->and($payment['props'])->toMatchArray([
            'message' => 'The card was declined.',
            'tone' => 'warning',
            'action_label' => 'Contact support',
        ])
        ->and(array_column($single['children'], 'props'))->toBe([
            ['message' => 'These credentials do not match our records.', 'tone' => 'danger', 'action_label' => ''],
            ['message' => 'These credentials do not match our records.', 'tone' => 'danger', 'action_label' => ''],
        ])
        ->and($clean['children'] ?? [])->toBe([]);
});
