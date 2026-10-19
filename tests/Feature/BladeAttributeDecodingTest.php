<?php

use FirstlightUI\Components\Concerns\DecodesBladeAttributes;
use FirstlightUI\Elements\CodeEditor;
use FirstlightUI\Elements\TextArea;
use FirstlightUI\Elements\TextField;
use FirstlightUI\FirstlightServiceProvider;
use Illuminate\Container\Container;
use Illuminate\Contracts\View\Factory as ViewFactoryContract;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Component;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

beforeEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    ElementRegistry::register('firstlight.code-editor', CodeEditor::class);
    ElementRegistry::register('firstlight.text-area', TextArea::class);
    ElementRegistry::register('firstlight.text-field', TextField::class);
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
    $container->instance(ViewFactoryContract::class, $factory);
    $container->instance('view', $factory);
    $container->instance('blade.compiler', $compiler);

    Component::flushCache();
    $compiler->component('native-firstlight-code-editor', \FirstlightUI\Components\CodeEditor::class);
    $compiler->component('native-firstlight-text-area', \FirstlightUI\Components\TextArea::class);
    $compiler->component('native-firstlight-text-field', \FirstlightUI\Components\TextField::class);
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
        ob_start();
        try {
            include $compiledFile;
        } finally {
            ob_end_clean();
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
