<?php

use Clinically\Firstlight\Elements\Segmented;
use Clinically\Firstlight\FirstlightServiceProvider;
use Clinically\Firstlight\FirstlightTagPrecompiler;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

beforeEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    ElementRegistry::register('firstlight.segmented', Segmented::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function collectSegmented(array $attributes, ?CallbackRegistry $registry = null): array
{
    NativeElementCollector::leaf('firstlight.segmented', $attributes);

    return NativeElementCollector::collect()->toArray($registry ?? new CallbackRegistry);
}

it('publishes the string-valued element tree through a SELECT_CHANGE callback', function () {
    $registry = new CallbackRegistry;
    $tree = collectSegmented([
        'options' => ['mine' => 'Mine', 'all' => 'All'],
        'value' => 'mine',
        'label' => 'Queue',
        'helper' => 'Choose a queue',
        'required' => true,
        '_change' => 'selectQueue',
        'a11y-label' => 'Document queue',
        'a11y-hint' => 'Changes the active queue',
    ], $registry);

    expect($tree['type'])->toBe('firstlight.segmented')
        ->and($tree['props']['value_type'])->toBe('string')
        ->and($tree['props']['selected_value'])->toBe('mine')
        ->and($tree['props']['option_values'])->toBe(['mine', 'all'])
        ->and($tree['props']['option_labels'])->toBe(['Mine', 'All'])
        ->and($tree['props']['option_enabled'])->toBe(['1', '1'])
        ->and($tree['props']['label'])->toBe('Queue')
        ->and($tree['props']['helper'])->toBe('Choose a queue')
        ->and($tree['props']['required'])->toBeTrue()
        ->and($tree['props']['disabled'])->toBeFalse()
        ->and($tree['props']['a11y_label'])->toBe('Document queue')
        ->and($tree['props']['a11y_hint'])->toBe('Changes the active queue')
        ->and($tree['props'])->not->toHaveKey('option_callbacks')
        ->and($registry->resolve($tree['props']['on_change']))->toBe([
            'method' => 'selectQueue',
            'args' => [],
        ]);
});

it('preserves arbitrary UTF-8 string values outside callback-expression parsing', function () {
    $value = "O'Connor, 東京";
    $registry = new CallbackRegistry;
    $tree = collectSegmented([
        'options' => [$value => 'Owner'],
        'value' => $value,
        '_change' => 'ownerChanged',
        'a11y-label' => 'Owner',
    ], $registry);

    expect($tree['props']['selected_value'])->toBe($value)
        ->and($tree['props']['option_values'])->toBe([$value])
        ->and($registry->resolve($tree['props']['on_change']))->toBe([
            'method' => 'ownerChanged',
            'args' => [],
        ]);
});

it('publishes per-option integer callbacks with original handler arguments intact', function () {
    $registry = new CallbackRegistry;
    $tree = collectSegmented([
        'options' => [
            ['value' => 10, 'label' => 'Routine'],
            ['value' => 20, 'label' => 'Urgent', 'disabled' => true],
        ],
        'value' => 20,
        'label' => 'Priority',
        '_change' => "selectQueue('documents')",
    ], $registry);

    $callbacks = $tree['props']['option_callbacks'];

    expect($tree['props']['value_type'])->toBe('integer')
        ->and($tree['props']['selected_value'])->toBe('20')
        ->and($tree['props']['option_values'])->toBe(['10', '20'])
        ->and($tree['props']['option_enabled'])->toBe(['1', '0'])
        ->and($tree['props'])->not->toHaveKey('on_change')
        ->and($callbacks)->toHaveCount(2)
        ->and($callbacks[0])->toBeString()->toMatch('/^\d+$/')
        ->and($callbacks[1])->toBeString()->toMatch('/^\d+$/')
        ->and($registry->resolve((int) $callbacks[0]))->toBe([
            'method' => 'selectQueue',
            'args' => ['documents', 10],
        ])
        ->and($registry->resolve((int) $callbacks[1]))->toBe([
            'method' => 'selectQueue',
            'args' => ['documents', 20],
        ]);
});

it('represents null as no selection', function () {
    $tree = collectSegmented([
        'options' => ['mine' => 'Mine', 'all' => 'All'],
        'value' => null,
        'label' => 'Queue',
    ]);

    expect($tree['props']['selected_value'])->toBe('');
});

it('publishes empty options as an inert disabled string control without an implicit choice', function () {
    $tree = collectSegmented([
        'options' => [],
        'value' => null,
        'label' => 'Queue',
    ]);

    expect($tree['props']['value_type'])->toBe('string')
        ->and($tree['props']['selected_value'])->toBe('')
        ->and($tree['props']['option_values'])->toBe([])
        ->and($tree['props']['option_labels'])->toBe([])
        ->and($tree['props']['option_enabled'])->toBe([])
        ->and($tree['props']['disabled'])->toBeTrue();
});

it('publishes group field metadata and disabled state as primitives', function () {
    $tree = collectSegmented([
        'options' => ['Mine', 'All'],
        'value' => 'Mine',
        'label' => 'Queue',
        'helper' => 'Choose one',
        'error' => 'Queue is required',
        'required' => true,
        'disabled' => true,
    ]);

    expect($tree['props']['helper'])->toBe('Choose one')
        ->and($tree['props']['error'])->toBe('Queue is required')
        ->and($tree['props']['required'])->toBeTrue()
        ->and($tree['props']['disabled'])->toBeTrue()
        ->and(json_decode(json_encode($tree, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR))->toBe($tree);
});

it('rejects a selected value whose type differs from the option values', function () {
    collectSegmented([
        'options' => [10 => 'Routine', 20 => 'Urgent'],
        'value' => '20',
        'label' => 'Priority',
    ]);
})->throws(\InvalidArgumentException::class, 'selected value type');

it('publishes a same-typed missing selection without selecting the first option', function () {
    $tree = collectSegmented([
        'options' => ['mine' => 'Mine', 'all' => 'All'],
        'value' => 'archived',
        'label' => 'Queue',
    ]);

    expect($tree['props']['selected_value'])->toBe('archived')
        ->and($tree['props']['option_values'])->toBe(['mine', 'all']);
});

it('accepts live sync mode without publishing renderer timing semantics', function () {
    $tree = collectSegmented([
        'options' => ['Mine', 'All'],
        'value' => 'Mine',
        'label' => 'Queue',
        'sync-mode' => 'live',
    ]);

    expect($tree['props'])->not->toHaveKey('sync_mode');
});

it('rejects deferred sync modes with actionable native:model guidance', function (string $mode) {
    collectSegmented([
        'options' => ['Mine', 'All'],
        'value' => 'Mine',
        'label' => 'Queue',
        'sync-mode' => $mode,
    ]);
})->with(['blur', 'debounce'])->throws(\InvalidArgumentException::class, 'Use plain `native:model`');

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
        collectSegmented([
            'options' => ['Mine', 'All'],
            'value' => 'Mine',
            'label' => '  ',
            'a11y-label' => '',
        ]);
    } finally {
        restore_error_handler();
    }

    expect($warnings)->toBe([
        'Firstlight Segmented requires a visible label or a11y-label.',
    ]);
});

it('does not warn when an accessibility label is present', function () {
    $warnings = [];
    set_error_handler(function (int $severity, string $message) use (&$warnings): bool {
        if ($severity === E_USER_WARNING) {
            $warnings[] = $message;

            return true;
        }

        return false;
    });

    try {
        collectSegmented([
            'options' => ['Mine', 'All'],
            'value' => 'Mine',
            'a11y-label' => 'Queue',
        ]);
    } finally {
        restore_error_handler();
    }

    expect($warnings)->toBe([]);
});

it('expands native:model then maps the public tag to the official NativePHP Blade alias', function () {
    NativeTagPrecompiler::setActive(true);
    $source = '<firstlight:segmented native:model="queue" :options="$options" label="Queue" />';

    $nativeCompiled = (new NativeTagPrecompiler)($source);
    $firstlightCompiled = (new FirstlightTagPrecompiler)($nativeCompiled);

    expect($nativeCompiled)->toContain('<firstlight:segmented')
        ->and($nativeCompiled)->toContain(':value="$queue"')
        ->and($nativeCompiled)->toContain('_change="__syncProperty(\'queue\')"')
        ->and($nativeCompiled)->toContain('sync-mode="live"')
        ->and($firstlightCompiled)->toContain('<x-native-firstlight-segmented')
        ->and($firstlightCompiled)->toContain(':value="$queue"')
        ->and($firstlightCompiled)->toContain('_change="__syncProperty(\'queue\')"')
        ->and($firstlightCompiled)->toContain('sync-mode="live"');

    $registry = new CallbackRegistry;
    $tree = collectSegmented([
        'options' => ['mine' => 'Mine', 'all' => 'All'],
        'value' => 'mine',
        'label' => 'Queue',
        '_change' => "__syncProperty('queue')",
        'sync-mode' => 'live',
    ], $registry);

    expect($tree['props']['selected_value'])->toBe('mine')
        ->and($registry->resolve($tree['props']['on_change']))->toBe([
            'method' => '__syncProperty',
            'args' => ['queue'],
        ]);
});

it('leaves the public tag untouched outside native compilation', function () {
    NativeTagPrecompiler::setActive(false);
    $source = '<firstlight:segmented label="Queue" />';

    expect((new FirstlightTagPrecompiler)($source))->toBe($source);
});

it('registers the public tag precompiler with the Blade compiler', function () {
    $compiler = new class
    {
        public array $precompilers = [];

        public function precompiler(callable $precompiler): void
        {
            $this->precompilers[] = $precompiler;
        }
    };

    $app = new class($compiler)
    {
        public function __construct(private object $compiler) {}

        public function make(string $abstract): object
        {
            expect($abstract)->toBe('blade.compiler');

            return $this->compiler;
        }
    };

    (new FirstlightServiceProvider($app))->boot();

    expect($compiler->precompilers)->toHaveCount(1)
        ->and($compiler->precompilers[0] ?? null)->toBeInstanceOf(FirstlightTagPrecompiler::class);
});

it('declares the official Segmented renderer and Blade mappings', function () {
    $manifest = json_decode(file_get_contents(dirname(__DIR__, 2).'/nativephp.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['components'])->toContain([
        'type' => 'firstlight.segmented',
        'element' => 'Clinically\\Firstlight\\Elements\\Segmented',
        'blade' => 'Clinically\\Firstlight\\Components\\Segmented',
        'android_renderer' => 'com.clinically.plugins.firstlight_ui.ui.SegmentedRenderer',
        'ios_renderer' => 'SegmentedRenderer',
        'self_closing' => true,
    ]);
});
