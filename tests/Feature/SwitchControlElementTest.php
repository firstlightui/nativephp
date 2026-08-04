<?php

use FirstlightUI\Elements\SwitchControl;
use FirstlightUI\FirstlightServiceProvider;
use FirstlightUI\FirstlightTagPrecompiler;
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
    ElementRegistry::register('firstlight.switch', SwitchControl::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function collectFirstlightSwitch(array $attributes, ?CallbackRegistry $registry = null): array
{
    NativeElementCollector::leaf('firstlight.switch', $attributes);

    return NativeElementCollector::collect()->toArray($registry ?? new CallbackRegistry);
}

/**
 * Compile and execute an authored Firstlight view through Laravel's real Blade
 * component pipeline, then return the NativePHP tree produced by the standard
 * NativeBladeComponent collector adapter.
 *
 * @return array{compiled: string, output: string, tree: ?array, registry: CallbackRegistry}
 */
function compileFirstlightSwitchView(string $source, array $data, bool $native = true): array
{
    $filesystem = new Filesystem;
    $temporaryPath = sys_get_temp_dir().'/firstlight-blade-'.bin2hex(random_bytes(8));
    $compiledPath = $temporaryPath.'/compiled';
    $viewsPath = $temporaryPath.'/views';
    $filesystem->makeDirectory($compiledPath, 0755, true);
    $filesystem->makeDirectory($viewsPath, 0755, true);

    $previousContainer = Container::getInstance();
    $container = new Container;
    Container::setInstance($container);
    Component::forgetFactory();

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

    $factory = new Factory(
        $resolver,
        new FileViewFinder($filesystem, [$viewsPath]),
        new Dispatcher($container),
    );
    $factory->setContainer($container);
    $factory->addExtension('blade.php', 'blade');

    $container->instance(ViewFactoryContract::class, $factory);
    $container->instance('view', $factory);
    $container->instance('blade.compiler', $compiler);

    $compiler->component('native-firstlight-switch', \FirstlightUI\Components\SwitchControl::class);
    $compiler->precompiler(new NativeTagPrecompiler);
    (new FirstlightServiceProvider($container))->boot();

    NativeElementCollector::reset();
    NativeTagPrecompiler::setActive($native);

    try {
        $compiled = $compiler->compileString($source);
        extract($data, EXTR_SKIP);
        $__env = $factory;

        $outputLevel = ob_get_level();
        ob_start();
        try {
            eval('?>'.$compiled);
            $output = ob_get_clean();
        } catch (Throwable $exception) {
            while (ob_get_level() > $outputLevel) {
                ob_end_clean();
            }

            $factory->flushState();

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
        Container::setInstance($previousContainer);
        Component::flushCache();
        Component::forgetFactory();
        $filesystem->deleteDirectory($temporaryPath);
    }
}

it('publishes the accepted boolean through the public Switch tag', function () {
    $result = compileFirstlightSwitchView(
        '<firstlight:switch native:model="notifications" label="Notifications" helper="Receive updates." />',
        ['notifications' => true],
    );

    expect($result['tree'])->not->toBeNull()
        ->and($result['tree']['type'])->toBe('firstlight.switch')
        ->and($result['tree']['props'])->toMatchArray([
            'value' => true,
            'label' => 'Notifications',
            'helper' => 'Receive updates.',
            'error' => '',
            'disabled' => false,
        ])
        ->and($result['registry']->resolve($result['tree']['props']['on_change']))->toBe([
            'method' => '__syncProperty',
            'args' => ['notifications'],
        ]);
});

it('defaults an omitted value to off', function () {
    $result = compileFirstlightSwitchView('<firstlight:switch label="Notifications" />', []);

    expect($result['tree'])->not->toBeNull()
        ->and($result['tree']['type'])->toBe('firstlight.switch')
        ->and($result['tree']['props'])->toMatchArray([
        'value' => false,
        'label' => 'Notifications',
        'helper' => '',
        'error' => '',
        'disabled' => false,
    ])->not->toHaveKey('on_change');
});

it('rejects non-boolean authored values', function (mixed $candidate) {
    expect(function () use ($candidate): array {
        return compileFirstlightSwitchView(
            '<firstlight:switch :value="$candidate" label="Notifications" />',
            compact('candidate'),
        );
    })->toThrow(InvalidArgumentException::class, 'Switch value must be a boolean');
})->with([[null], [0], [1], ['0'], ['1'], ['false'], [[]], [new stdClass]]);

it('recommends a bound boolean when a string false is authored', function () {
    expect(fn () => compileFirstlightSwitchView(
        '<firstlight:switch :value="$candidate" label="Notifications" />',
        ['candidate' => 'false'],
    ))->toThrow(
        InvalidArgumentException::class,
        'Use :value="false" or native:model so Blade supplies a boolean.',
    );
});

it('publishes helper error disabled and accessibility metadata', function () {
    $registry = new CallbackRegistry;
    $tree = collectFirstlightSwitch([
        'value' => true,
        'label' => 'Notifications',
        'helper' => 'Receive updates.',
        'error' => 'Notifications are unavailable.',
        'disabled' => true,
        'a11y-label' => 'Notification settings',
        'a11y-hint' => 'Turns update notifications on or off.',
        '_change' => 'updateNotifications',
    ], $registry);

    expect($tree['props'])->toMatchArray([
        'value' => true,
        'label' => 'Notifications',
        'helper' => 'Receive updates.',
        'error' => 'Notifications are unavailable.',
        'disabled' => true,
        'a11y_label' => 'Notification settings',
        'a11y_hint' => 'Turns update notifications on or off.',
    ])
        ->and($registry->resolve($tree['props']['on_change']))->toBe([
            'method' => 'updateNotifications',
            'args' => [],
        ]);
});

it('accepts live sync mode without renderer timing props', function () {
    $result = compileFirstlightSwitchView(
        '<firstlight:switch native:model.live="notifications" label="Notifications" />',
        ['notifications' => true],
    );

    expect($result['tree']['props'])->toMatchArray([
        'value' => true,
        'label' => 'Notifications',
    ])->not->toHaveKey('sync_mode')
        ->and($result['tree']['props'])->not->toHaveKey('debounce_ms')
        ->and($result['registry']->resolve($result['tree']['props']['on_change']))->toBe([
            'method' => '__syncProperty',
            'args' => ['notifications'],
        ]);
});

it('preserves unregistered Switch-like public tags', function (string $source) {
    NativeTagPrecompiler::setActive(true);

    try {
        expect((new FirstlightTagPrecompiler)($source))->toBe($source);
    } finally {
        NativeTagPrecompiler::setActive(false);
    }
})->with([
    '<firstlight:switch.foo />',
    '<firstlight:switch-extra />',
    '<firstlight:switch:other />',
]);

it('rejects deferred sync modes', function (string $attribute) {
    expect(fn () => compileFirstlightSwitchView(
        "<firstlight:switch {$attribute}=\"notifications\" label=\"Notifications\" />",
        ['notifications' => false],
    ))->toThrow(InvalidArgumentException::class, 'Switch supports only native:model or native:model.live');
})->with(['native:model.blur', 'native:model.lazy', 'native:model.debounce.250ms']);

it('rejects unsupported required and placement attributes', function (string $attribute) {
    expect(fn () => collectFirstlightSwitch([
        'value' => true,
        'label' => 'Notifications',
        $attribute => true,
    ]))->toThrow(InvalidArgumentException::class, "Switch does not support the `{$attribute}` attribute.");
})->with(['required', 'placement']);

it('warns in development when visible and accessibility labels are blank', function () {
    $warnings = [];
    set_error_handler(function (int $severity, string $message) use (&$warnings): bool {
        if ($severity === E_USER_WARNING) {
            $warnings[] = $message;

            return true;
        }

        return false;
    });

    try {
        collectFirstlightSwitch([
            'value' => true,
            'label' => '  ',
            'a11y-label' => '',
        ]);
    } finally {
        restore_error_handler();
    }

    expect($warnings)->toBe([
        'Firstlight Switch requires a visible label or a11y-label.',
    ]);
});

it('leaves the public tag untouched through web compilation', function () {
    $source = '<firstlight:switch label="Notifications" />';
    $result = compileFirstlightSwitchView($source, [], native: false);

    expect($result['compiled'])->toContain($source)
        ->and($result['output'])->toBe($source)
        ->and($result['tree'])->toBeNull();
});
