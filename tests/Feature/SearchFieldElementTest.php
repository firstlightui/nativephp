<?php

use FirstlightUI\Elements\SearchField;
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
    ElementRegistry::register('firstlight.search-field', SearchField::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

/** @return array{compiled: string, output: string, tree: ?array, registry: CallbackRegistry} */
function compileFirstlightSearchFieldView(string $source, array $data, bool $native = true): array
{
    $filesystem = new Filesystem;
    $temporaryPath = sys_get_temp_dir().'/firstlight-search-field-blade-'.bin2hex(random_bytes(8));
    $compiledPath = $temporaryPath.'/compiled';
    $viewsPath = $temporaryPath.'/views';
    $filesystem->makeDirectory($compiledPath, 0755, true);
    $filesystem->makeDirectory($viewsPath, 0755, true);

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
    $factory = new Factory($resolver, new FileViewFinder($filesystem, [$viewsPath]), new Dispatcher($container));
    $factory->setContainer($container);
    $factory->addExtension('blade.php', 'blade');
    $factory->addNamespace('__components', $compiledPath);
    $container->instance(ViewFactoryContract::class, $factory);
    $container->instance('view', $factory);
    $container->instance('blade.compiler', $compiler);

    Component::flushCache();
    $compiler->component('native-firstlight-search-field', \FirstlightUI\Components\SearchField::class);
    $compiler->precompiler(new \FirstlightUI\FirstlightTagPrecompiler);
    (new FirstlightServiceProvider($container))->boot();
    NativeElementCollector::reset();
    NativeTagPrecompiler::setActive($native);

    try {
        $compiled = $compiler->compileString($source);
        extract($data, EXTR_SKIP);
        $__env = $factory;
        ob_start();
        try {
            Component::flushCache();
            eval('?>'.$compiled);
            $output = ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }

        $registry = new CallbackRegistry;
        try {
            $tree = NativeElementCollector::collect()->toArray($registry);
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() !== 'No root element was built by the Blade template.') {
                throw $exception;
            }
            $tree = null;
        }

        return compact('compiled', 'output', 'tree', 'registry');
    } finally {
        NativeTagPrecompiler::setActive(false);
        NativeElementCollector::reset();
        Component::flushCache();
        Container::setInstance($previousContainer);
        $filesystem->deleteDirectory($temporaryPath);
    }
}

function collectSearchField(array $attributes, ?CallbackRegistry $registry = null): array
{
    NativeElementCollector::leaf('firstlight.search-field', $attributes);

    return NativeElementCollector::collect()->toArray($registry ?? new CallbackRegistry);
}

it('publishes the strict search contract and callbacks', function () {
    $registry = new CallbackRegistry;
    $tree = collectSearchField([
        'value' => 'cardiology',
        'placeholder' => 'Search specialties',
        'disabled' => true,
        'autocapitalize' => 'words',
        'autocorrect' => false,
        'a11y-label' => 'Search specialties',
        'a11y-hint' => 'Enter a specialty name',
        '_change' => 'queryChanged',
        '_submit' => 'search',
    ], $registry);

    expect($tree['type'])->toBe('firstlight.search-field')
        ->and($tree['props'])->toMatchArray([
            'value' => 'cardiology',
            'placeholder' => 'Search specialties',
            'disabled' => true,
            'autocapitalize' => 'words',
            'autocorrect' => false,
            'autocorrect_policy' => 'disabled',
            'sync_mode' => 'live',
            'debounce_ms' => 300,
            'a11y_label' => 'Search specialties',
            'a11y_hint' => 'Enter a specialty name',
        ])
        ->and($registry->resolve($tree['props']['on_change']))->toBe(['method' => 'queryChanged', 'args' => []])
        ->and($registry->resolve($tree['props']['on_submit']))->toBe(['method' => 'search', 'args' => []]);
});

it('defaults the query and optional presentation while requiring a label', function () {
    $tree = collectSearchField(['a11y-label' => 'Search']);

    expect($tree['props'])->toMatchArray([
        'value' => '',
        'placeholder' => '',
        'disabled' => false,
        'sync_mode' => 'live',
        'debounce_ms' => 300,
        'a11y_label' => 'Search',
    ]);
});

it('accepts standard sync policies and validates debounce', function () {
    expect(collectSearchField(['a11y-label' => 'Search', 'sync-mode' => 'blur'])['props']['sync_mode'])->toBe('blur')
        ->and(collectSearchField(['a11y-label' => 'Search', 'syncMode' => 'lazy'])['props']['sync_mode'])->toBe('blur')
        ->and(collectSearchField([
            'a11y-label' => 'Search',
            'sync-mode' => 'debounce',
            'debounce-ms' => 500,
        ])['props'])->toMatchArray(['sync_mode' => 'debounce', 'debounce_ms' => 500]);

    collectSearchField(['a11y-label' => 'Search', 'sync-mode' => 'debounce', 'debounce-ms' => 49]);
})->throws(InvalidArgumentException::class, 'at least 50 milliseconds');

it('rejects invalid values labels enums and unsupported field attributes', function (array $attributes, string $message) {
    collectSearchField(['a11y-label' => 'Search', ...$attributes]);
})->with([
    'null value' => [['value' => null], 'value must be a string'],
    'numeric value' => [['value' => 42], 'value must be a string'],
    'blank label' => [['a11y-label' => '  '], 'non-empty a11y-label'],
    'capitalization' => [['autocapitalize' => 'title'], 'autocapitalize must be one of'],
    'sync mode' => [['sync-mode' => 'focus'], 'sync-mode must be one of'],
    'visible label' => [['label' => 'Query'], 'does not support label'],
    'helper' => [['helper' => 'Hint'], 'does not support helper'],
    'error' => [['error' => 'Invalid'], 'does not support error'],
    'required' => [['required' => true], 'does not support required'],
    'read-only' => [['read-only' => true], 'does not support read-only'],
    'clearable' => [['clearable' => true], 'owns its native clear action'],
])->throws(InvalidArgumentException::class);

it('rejects omission of the explicit accessible label', function () {
    collectSearchField([]);
})->throws(InvalidArgumentException::class, 'non-empty a11y-label');

it('compiles native model debounce submit and layout through the real Blade pipeline', function () {
    $result = compileFirstlightSearchFieldView(
        '<firstlight:search-field native:model.debounce.500ms="query" placeholder="Search referrals" a11y-label="Search referrals" @submit="search" class="mt-4" />',
        ['query' => 'Referral'],
    );

    expect($result['tree'])->not->toBeNull()
        ->and($result['tree']['type'])->toBe('firstlight.search-field')
        ->and($result['tree']['props'])->toMatchArray([
            'value' => 'Referral',
            'placeholder' => 'Search referrals',
            'sync_mode' => 'debounce',
            'debounce_ms' => 500,
        ])
        ->and($result['tree']['layout']['margin'][0])->toBe(16.0)
        ->and($result['registry']->resolve($result['tree']['props']['on_change']))->toBe([
            'method' => '__syncProperty',
            'args' => ['query'],
        ])
        ->and($result['registry']->resolve($result['tree']['props']['on_submit']))->toBe([
            'method' => 'search',
            'args' => [],
        ])
        ->and($result['output'])->toBe('');
});

it('leaves authored search tags untouched through web compilation', function () {
    $source = '<firstlight:search-field a11y-label="Search" />';
    $result = compileFirstlightSearchFieldView($source, [], native: false);

    expect($result['compiled'])->toContain($source)
        ->and($result['output'])->toBe($source)
        ->and($result['tree'])->toBeNull();
});
