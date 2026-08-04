<?php

use FirstlightUI\Elements\Checkbox;
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
    ElementRegistry::register('firstlight.checkbox', Checkbox::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function collectFirstlightCheckbox(array $attributes, ?CallbackRegistry $registry = null): array
{
    NativeElementCollector::leaf('firstlight.checkbox', $attributes);

    return NativeElementCollector::collect()->toArray($registry ?? new CallbackRegistry);
}

/** @return array{compiled: string, output: string, tree: ?array, registry: CallbackRegistry} */
function compileFirstlightCheckboxView(string $source, array $data, bool $native = true): array
{
    $filesystem = new Filesystem;
    $temporaryPath = sys_get_temp_dir().'/firstlight-checkbox-blade-'.bin2hex(random_bytes(8));
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

    $compiler->component('native-firstlight-checkbox', \FirstlightUI\Components\Checkbox::class);
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

it('publishes the default strict boolean field contract', function () {
    $tree = collectFirstlightCheckbox([
        'label' => 'I agree to the terms',
        'helper' => 'Required before continuing.',
        'required' => true,
        'width' => 'fill',
    ]);

    expect($tree['type'])->toBe('firstlight.checkbox')
        ->and($tree['props'])->toBe([
            'value' => false,
            'label' => 'I agree to the terms',
            'helper' => 'Required before continuing.',
            'error' => '',
            'required' => true,
            'disabled' => false,
        ])
        ->and($tree['layout']['width'])->toBe('fill')
        ->and($tree['style'] ?? [])->toBe([])
        ->and($tree['props'])->not->toHaveKeys(['on_press', 'on_submit']);
});

it('publishes accepted state metadata accessibility and change callback', function () {
    $registry = new CallbackRegistry;
    $tree = collectFirstlightCheckbox([
        'value' => true,
        'label' => 'I agree to the terms',
        'helper' => 'Read the terms first.',
        'error' => 'Agreement is required.',
        'required' => true,
        'disabled' => true,
        'a11y-label' => 'Accept the terms',
        'a11y-hint' => 'Required before continuing',
        '_change' => 'termsChanged',
    ], $registry);

    expect($tree['props'])->toMatchArray([
        'value' => true,
        'label' => 'I agree to the terms',
        'helper' => 'Read the terms first.',
        'error' => 'Agreement is required.',
        'required' => true,
        'disabled' => true,
        'a11y_label' => 'Accept the terms',
        'a11y_hint' => 'Required before continuing',
    ])->and($registry->resolve($tree['props']['on_change']))->toBe([
        'method' => 'termsChanged',
        'args' => [],
    ]);
});

it('accepts accessibility camel case aliases', function () {
    $tree = collectFirstlightCheckbox([
        'label' => 'Terms',
        'a11yLabel' => 'Accept the terms',
        'a11yHint' => 'Required before continuing',
    ]);

    expect($tree['props']['a11y_label'])->toBe('Accept the terms')
        ->and($tree['props']['a11y_hint'])->toBe('Required before continuing');
});

it('rejects non boolean values', function (mixed $candidate) {
    collectFirstlightCheckbox([
        'value' => $candidate,
        'label' => 'Terms',
    ]);
})->with([[null], [0], [1], ['0'], ['1'], ['false'], [[]], [new stdClass]])
    ->throws(InvalidArgumentException::class, 'Checkbox `value` must be a boolean');

it('recommends a bound boolean for authored string false', function () {
    expect(fn () => collectFirstlightCheckbox([
        'value' => 'false',
        'label' => 'Terms',
    ]))->toThrow(
        InvalidArgumentException::class,
        'Use :value="false" or native:model so Blade supplies a boolean.',
    );
});

it('rejects non boolean field flags', function (string $attribute, mixed $candidate) {
    collectFirstlightCheckbox([
        'label' => 'Terms',
        $attribute => $candidate,
    ]);
})->with([
    ['required', 1],
    ['required', 'false'],
    ['disabled', 0],
    ['disabled', 'true'],
])->throws(InvalidArgumentException::class, 'must be a boolean');

it('rejects non string field metadata', function (string $attribute, mixed $candidate) {
    collectFirstlightCheckbox([
        'label' => 'Terms',
        $attribute => $candidate,
    ]);
})->with([
    ['label', 42],
    ['helper', true],
    ['error', []],
    ['a11y-label', 42],
    ['a11y-hint', new stdClass],
])->throws(InvalidArgumentException::class, 'must be a string');

it('accepts native model and live through the public tag', function (string $attribute) {
    $result = compileFirstlightCheckboxView(
        "<firstlight:checkbox {$attribute}=\"acceptedTerms\" label=\"Terms\" />",
        ['acceptedTerms' => true],
    );

    expect($result['tree']['props']['value'])->toBeTrue()
        ->and($result['registry']->resolve($result['tree']['props']['on_change']))->toBe([
            'method' => '__syncProperty',
            'args' => ['acceptedTerms'],
        ])
        ->and($result['tree']['props'])->not->toHaveKeys(['sync_mode', 'debounce_ms']);
})->with(['native:model', 'native:model.live']);

it('rejects deferred sync modes', function (string $attribute) {
    compileFirstlightCheckboxView(
        "<firstlight:checkbox {$attribute}=\"acceptedTerms\" label=\"Terms\" />",
        ['acceptedTerms' => false],
    );
})->with(['native:model.blur', 'native:model.lazy', 'native:model.debounce.250ms'])
    ->throws(InvalidArgumentException::class, 'Checkbox supports only native:model or native:model.live');

it('rejects unsupported state event and styling APIs', function (string $attribute, mixed $value) {
    collectFirstlightCheckbox([
        'label' => 'Terms',
        $attribute => $value,
    ]);
})->with([
    ['indeterminate', true],
    ['placement', 'leading'],
    ['variant', 'square'],
    ['tone', 'success'],
    ['color', '#ffffff'],
    ['icon', 'check'],
    ['_press', 'pressed'],
    ['_submit', 'submitted'],
])->throws(InvalidArgumentException::class, 'Checkbox does not support');

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
        collectFirstlightCheckbox(['label' => '  ', 'a11y-label' => '']);
    } finally {
        restore_error_handler();
    }

    expect($warnings)->toBe([
        'Firstlight Checkbox requires a visible label or a11y-label.',
    ]);
});

it('preserves unregistered Checkbox like tags', function (string $source) {
    NativeTagPrecompiler::setActive(true);

    try {
        expect((new FirstlightTagPrecompiler)($source))->toBe($source);
    } finally {
        NativeTagPrecompiler::setActive(false);
    }
})->with([
    '<firstlight:checkbox.extra />',
    '<firstlight:checkbox-extra />',
    '<firstlight:checkbox:other />',
]);

it('keeps the self closing contract by leaving paired Checkbox content untouched', function () {
    $source = '<firstlight:checkbox label="Terms">Unsupported content</firstlight:checkbox>';
    NativeTagPrecompiler::setActive(true);

    try {
        expect((new FirstlightTagPrecompiler)($source))->toBe($source);
    } finally {
        NativeTagPrecompiler::setActive(false);
    }
});

it('leaves the public tag untouched through web compilation', function () {
    $source = '<firstlight:checkbox label="Terms" />';
    $result = compileFirstlightCheckboxView($source, [], native: false);

    expect($result['compiled'])->toContain($source)
        ->and($result['output'])->toBe($source)
        ->and($result['tree'])->toBeNull();
});
