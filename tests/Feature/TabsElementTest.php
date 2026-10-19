<?php

use FirstlightUI\Elements\Tabs;
use FirstlightUI\FirstlightServiceProvider;
use Illuminate\Container\Container;
use Illuminate\Contracts\View\Factory as ViewFactoryContract;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
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
    ElementRegistry::register('firstlight.tabs', Tabs::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function collectTabs(array $attributes, ?CallbackRegistry $registry = null): array
{
    NativeElementCollector::leaf('firstlight.tabs', $attributes);

    return NativeElementCollector::collect()->toArray($registry ?? new CallbackRegistry);
}

/** @return array{output: string, tree: array, registry: CallbackRegistry} */
function compileFirstlightTabsView(string $source, array $data): array
{
    $filesystem = new Filesystem;
    $temporaryPath = sys_get_temp_dir().'/firstlight-tabs-blade-'.bin2hex(random_bytes(8));
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

    $compiler->component('native-firstlight-tabs', \FirstlightUI\Components\Tabs::class);
    $compiler->precompiler(new NativeTagPrecompiler);
    (new FirstlightServiceProvider($container))->boot();

    NativeElementCollector::reset();
    NativeTagPrecompiler::setActive(true);

    try {
        $compiled = $compiler->compileString($source);
        extract($data, EXTR_SKIP);
        $__env = $factory;

        ob_start();
        try {
            eval('?>'.$compiled);
            $output = ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }

        $registry = new CallbackRegistry;
        $tree = NativeElementCollector::collect()->toArray($registry);

        return compact('output', 'tree', 'registry');
    } finally {
        NativeTagPrecompiler::setActive(false);
        NativeElementCollector::reset();
        Container::setInstance($previousContainer);
        $filesystem->deleteDirectory($temporaryPath);
    }
}

function serverTabs(): array
{
    return [
        'overview' => 'Overview',
        'sites' => 'Sites',
        'deploys' => 'Deployments',
        'manage' => 'Manage',
    ];
}

it('publishes a value-to-label map with a SELECT_CHANGE callback', function () {
    $registry = new CallbackRegistry;
    $tree = collectTabs([
        'tabs' => serverTabs(),
        'value' => 'deploys',
        'label' => 'Server sections',
        'a11y-hint' => 'Shows one section of this server',
        '_change' => 'selectTab',
    ], $registry);

    expect($tree['type'])->toBe('firstlight.tabs')
        ->and($tree['props']['tab_values'])->toBe(['overview', 'sites', 'deploys', 'manage'])
        ->and($tree['props']['tab_labels'])->toBe(['Overview', 'Sites', 'Deployments', 'Manage'])
        ->and($tree['props']['tab_badges'])->toBe(['', '', '', ''])
        ->and($tree['props']['tab_a11y_labels'])->toBe(['Overview', 'Sites', 'Deployments', 'Manage'])
        ->and($tree['props']['tab_enabled'])->toBe(['1', '1', '1', '1'])
        ->and($tree['props']['has_selection'])->toBeTrue()
        ->and($tree['props']['selected_value'])->toBe('deploys')
        ->and($tree['props']['disabled'])->toBeFalse()
        ->and($tree['props']['a11y_label'])->toBe('Server sections')
        ->and($tree['props']['a11y_hint'])->toBe('Shows one section of this server')
        ->and($registry->resolve($tree['props']['on_change']))->toBe([
            'method' => 'selectTab',
            'args' => [],
        ]);
});

it('lets an explicit a11y-label override the group label', function () {
    $tree = collectTabs([
        'tabs' => serverTabs(),
        'value' => 'overview',
        'label' => 'Sections',
        'a11y-label' => 'Server sections',
    ]);

    expect($tree['props']['a11y_label'])->toBe('Server sections')
        ->and($tree['props'])->not->toHaveKey('label');
});

it('publishes rich tabs with capped badges, badge accessibility text, and disabled tabs', function () {
    $tree = collectTabs([
        'tabs' => [
            ['value' => 'overview', 'label' => 'Overview', 'badge' => 0],
            ['value' => 'deploys', 'label' => 'Deployments', 'badge' => 2, 'badge-a11y-label' => '2 failed'],
            ['value' => 'sites', 'label' => 'Sites', 'badge' => 140],
            ['value' => 'manage', 'label' => 'Manage', 'badgeA11yLabel' => 'ignored', 'disabled' => true],
        ],
        'value' => 'overview',
        'label' => 'Server sections',
    ]);

    expect($tree['props']['tab_values'])->toBe(['overview', 'deploys', 'sites', 'manage'])
        ->and($tree['props']['tab_badges'])->toBe(['', '2', '99+', ''])
        ->and($tree['props']['tab_a11y_labels'])->toBe([
            'Overview',
            'Deployments, 2 failed',
            'Sites, 99+',
            'Manage',
        ])
        ->and($tree['props']['tab_enabled'])->toBe(['1', '1', '1', '0']);
});

it('distinguishes null from a missing value and never selects the first tab', function () {
    $unselected = collectTabs(['tabs' => serverTabs(), 'value' => null, 'label' => 'Sections']);
    $missing = collectTabs(['tabs' => serverTabs(), 'value' => 'archived', 'label' => 'Sections']);

    expect($unselected['props']['has_selection'])->toBeFalse()
        ->and($unselected['props']['selected_value'])->toBe('')
        ->and($missing['props']['has_selection'])->toBeTrue()
        ->and($missing['props']['selected_value'])->toBe('archived');
});

it('publishes an empty tab collection as an inert disabled control', function () {
    $tree = collectTabs(['tabs' => [], 'value' => null, 'label' => 'Sections']);

    expect($tree['props']['tab_values'])->toBe([])
        ->and($tree['props']['disabled'])->toBeTrue();
});

it('publishes group disabled state', function () {
    $tree = collectTabs(['tabs' => serverTabs(), 'value' => 'sites', 'label' => 'Sections', 'disabled' => true]);

    expect($tree['props']['disabled'])->toBeTrue();
});

it('rejects tab counts outside two to five', function (array $tabs) {
    collectTabs(['tabs' => $tabs, 'value' => null, 'label' => 'Sections']);
})->with([
    'one tab' => [['overview' => 'Overview']],
    'six tabs' => [['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D', 'e' => 'E', 'f' => 'F']],
])->throws(InvalidArgumentException::class, 'between 2 and 5 tabs');

it('rejects malformed tabs with actionable diagnostics', function (array $tabs, string $message) {
    expect(fn () => collectTabs(['tabs' => $tabs, 'value' => null, 'label' => 'Sections']))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'numeric-string map keys' => [['1' => 'One', '2' => 'Two'], 'must be non-empty strings'],
    'simple string list' => [['Overview', 'Sites'], 'must be non-empty strings'],
    'blank label' => [['overview' => 'Overview', 'sites' => ' '], 'label must not be blank'],
    'duplicate rich values' => [[
        ['value' => 'sites', 'label' => 'Sites'],
        ['value' => 'sites', 'label' => 'More sites'],
    ], 'duplicate value [sites]'],
    'integer rich value' => [[
        ['value' => 1, 'label' => 'One'],
        ['value' => 2, 'label' => 'Two'],
    ], 'must be non-empty strings'],
    'missing label' => [[
        ['value' => 'overview'],
        ['value' => 'sites', 'label' => 'Sites'],
    ], 'label is required'],
    'string badge' => [[
        ['value' => 'overview', 'label' => 'Overview', 'badge' => 'New'],
        ['value' => 'sites', 'label' => 'Sites'],
    ], '`badge` must be a non-negative integer'],
    'negative badge' => [[
        ['value' => 'overview', 'label' => 'Overview', 'badge' => -1],
        ['value' => 'sites', 'label' => 'Sites'],
    ], '`badge` must be a non-negative integer'],
    'non-boolean disabled' => [[
        ['value' => 'overview', 'label' => 'Overview', 'disabled' => 'yes'],
        ['value' => 'sites', 'label' => 'Sites'],
    ], '`disabled` must be a boolean'],
    'deferred icon' => [[
        ['value' => 'overview', 'label' => 'Overview', 'icon' => 'home'],
        ['value' => 'sites', 'label' => 'Sites'],
    ], 'Tab icons are not supported'],
    'unknown field' => [[
        ['value' => 'overview', 'label' => 'Overview', 'count' => 2],
        ['value' => 'sites', 'label' => 'Sites'],
    ], 'unknown field: count'],
]);

it('rejects non-string selected values', function () {
    collectTabs(['tabs' => serverTabs(), 'value' => 2, 'label' => 'Sections']);
})->throws(InvalidArgumentException::class, 'selected value must be a string or null');

it('rejects field and option props that do not belong to tabs', function (string $attribute) {
    collectTabs(['tabs' => serverTabs(), 'value' => 'sites', 'label' => 'Sections', $attribute => 'x']);
})->with(['options', 'helper', 'error', 'required', 'multiple', 'icon'])
    ->throws(InvalidArgumentException::class, 'Firstlight Tabs does not support');

it('rejects deferred sync modes with native:model guidance', function (string $mode) {
    collectTabs(['tabs' => serverTabs(), 'value' => 'sites', 'label' => 'Sections', 'sync-mode' => $mode]);
})->with(['blur', 'debounce'])->throws(InvalidArgumentException::class, 'Use plain `native:model`');

it('accepts live sync mode without publishing timing props', function () {
    $tree = collectTabs(['tabs' => serverTabs(), 'value' => 'sites', 'label' => 'Sections', 'sync-mode' => 'live']);

    expect($tree['props'])->not->toHaveKey('sync_mode');
});

it('warns in development when the group has no accessible name', function () {
    $warnings = [];
    set_error_handler(function (int $severity, string $message) use (&$warnings): bool {
        $warnings[] = $message;

        return $severity === E_USER_WARNING;
    });

    try {
        collectTabs(['tabs' => serverTabs(), 'value' => 'sites', 'label' => ' ']);
    } finally {
        restore_error_handler();
    }

    expect($warnings)->toBe(['Firstlight Tabs requires a label or a11y-label naming the tab group.']);
});

it('compiles the public native:model tag through the Blade pipeline', function () {
    $result = compileFirstlightTabsView(
        '<firstlight:tabs native:model="tab" :tabs="$tabs" label="Server sections" />',
        ['tab' => 'deploys', 'tabs' => serverTabs()],
    );

    expect($result['tree']['type'])->toBe('firstlight.tabs')
        ->and($result['tree']['props']['selected_value'])->toBe('deploys')
        ->and($result['registry']->resolve($result['tree']['props']['on_change']))->toBe([
            'method' => '__syncProperty',
            'args' => ['tab'],
        ])
        ->and($result['output'])->toBe('');
});

it('declares the Tabs renderer and Blade mappings', function () {
    $manifest = json_decode(file_get_contents(dirname(__DIR__, 2).'/nativephp.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['components'])->toContain([
        'type' => 'firstlight.tabs',
        'element' => 'FirstlightUI\\Elements\\Tabs',
        'blade' => 'FirstlightUI\\Components\\Tabs',
        'android_renderer' => 'dev.firstlightui.plugins.firstlight_ui.ui.TabsRenderer',
        'ios_renderer' => 'TabsRenderer',
        'self_closing' => true,
    ]);
});
