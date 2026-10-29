<?php

use FirstlightUI\Elements\SwitchControl;
use FirstlightUI\NativeComponent;
use Illuminate\Container\Container;
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
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\Elements\Column;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;
use Native\Mobile\Testing\FakeBridge;
use Native\Mobile\Testing\Native;

require_once __DIR__.'/../../vendor/nativephp/mobile/src/jump_bridge_functions.php';

if (! function_exists('app')) {
    function app(?string $abstract = null): mixed
    {
        $container = Container::getInstance();

        return $abstract === null ? $container : $container->make($abstract);
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
        return $default;
    }
}

class ResponseBladeScreen extends NativeComponent
{
    public bool $accepted = false;

    public function reject(bool $value): void {}

    public function render(): Element|View
    {
        return view('native.response');
    }

    public function fragment(): Element
    {
        return $this->fromViewPartial(view('native.response'));
    }
}

class ResponseViewHelperScreen extends ResponseBladeScreen
{
    public function render(): Element
    {
        return $this->view('response');
    }
}

class ResponseInferredScreen extends NativeComponent
{
    public bool $accepted = false;

    public function reject(bool $value): void {}

    public static function inferViewName(): string
    {
        return 'response';
    }
}

class ResponseElementScreen extends ResponseBladeScreen
{
    public function render(): Element
    {
        return $this->response(Column::make()->addChild(
            SwitchControl::make()->label('Rejected')->value(false)->onChange('reject')->ref('rejected')
        ));
    }
}

class ResponseNestedScreen extends ResponseBladeScreen
{
    public function render(): View
    {
        return view('native.nested-response');
    }
}

class ResponseOverlayScreen extends ResponseElementScreen
{
    public bool $visible = true;

    private ?Element $content = null;

    public function render(): Element
    {
        return $this->response($this->content ??= Column::make(
            \FirstlightUI\Elements\BottomSheet::make()->visible($this->visible)->onDismiss('dismiss'),
            Column::make(\FirstlightUI\Elements\Modal::make()->visible($this->visible)->onDismiss('dismiss')),
        ));
    }

    public function dismiss(): void {}
}

beforeEach(function () {
    $this->previousContainer = Container::getInstance();
    $container = new Container;
    Container::setInstance($container);

    $this->files = new Filesystem;
    $this->viewDirectory = sys_get_temp_dir().'/firstlight-response-'.bin2hex(random_bytes(8));
    $this->files->makeDirectory($this->viewDirectory.'/native', 0755, true);
    $this->files->makeDirectory($this->viewDirectory.'/compiled', 0755, true);
    $this->files->put($this->viewDirectory.'/native/response.blade.php', <<<'BLADE'
        <native:column>
            <native:response-switch label="Rejected" :value="$accepted" @change="reject" ref="rejected" />
        </native:column>
        BLADE);
    $this->files->put($this->viewDirectory.'/native/nested-response.blade.php', <<<'BLADE'
        <native:column>
            <native:response-child key="child" />
        </native:column>
        BLADE);

    $blade = new BladeCompiler($this->files, $this->viewDirectory.'/compiled');
    $blade->precompiler(new NativeTagPrecompiler);
    $engines = new EngineResolver;
    $engines->register('blade', fn () => new CompilerEngine($blade, $this->files));
    $views = new Factory($engines, new FileViewFinder($this->files, [$this->viewDirectory]), new Dispatcher($container));
    $views->setContainer($container);
    $container->instance('view', $views);
    $container->instance('blade.compiler', $blade);

    NativeElementCollector::reset();
    ChromeContributorRegistry::reset();
    ComponentRegistry::reset();
    ElementRegistry::reset();
    ElementRegistry::register('response_switch', SwitchControl::class);
});

afterEach(function () {
    FakeBridge::disable();
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ChromeContributorRegistry::reset();
    ComponentRegistry::reset();
    ElementRegistry::reset();
    Container::setInstance($this->previousContainer);
    $this->files->deleteDirectory($this->viewDirectory);
});

it('publishes distinct rejection responses without changing values, callbacks, or node identity', function (string $component) {
    $screen = Native::test($component);
    $first = $screen->tree();
    $screen->toggle('rejected', true)->assertSet('accepted', false);
    $second = $screen->tree();
    $screen->toggle('rejected', true)->assertSet('accepted', false);
    $third = $screen->tree();

    expect($first['props']['firstlight_response_revision'] ?? null)->toBeInt()
        ->and($second['props']['firstlight_response_revision'])->toBe($first['props']['firstlight_response_revision'] + 1)
        ->and($third['props']['firstlight_response_revision'])->toBe($second['props']['firstlight_response_revision'] + 1);

    unset($first['props']['firstlight_response_revision'], $second['props']['firstlight_response_revision'], $third['props']['firstlight_response_revision']);
    unset($first['_hash'], $second['_hash'], $third['_hash']);
    expect($second)->toBe($first)->and($third)->toBe($first);
})->with([
    ResponseBladeScreen::class,
    ResponseViewHelperScreen::class,
    ResponseInferredScreen::class,
    ResponseElementScreen::class,
]);

it('does not reuse a revision when a screen is remounted', function () {
    $first = Native::test(ResponseBladeScreen::class)->tree();
    $second = Native::test(ResponseBladeScreen::class)->tree();

    expect($first['props']['firstlight_response_revision'] ?? null)->toBeInt()
        ->and($second['props']['firstlight_response_revision'])->toBeGreaterThan($first['props']['firstlight_response_revision']);
});

it('republishes an unchanged screen identically when leaving it with back', function () {
    $screen = Native::test(ResponseBladeScreen::class);
    $before = $screen->tree();
    $screen->toggle('rejected', true);
    $rejected = $screen->tree();
    $leaving = $screen->pressBack()->assertWentBack()->tree();

    unset($rejected['_hash'], $leaving['_hash']);
    expect($rejected['props']['firstlight_response_revision'])->toBe($before['props']['firstlight_response_revision'] + 1)
        ->and($leaving)->toBe($rejected);
});

it('leaves detached partials unstamped', function () {
    $screen = Native::test(ResponseBladeScreen::class);
    $fragment = $screen->instance()->fragment()->toArray(new CallbackRegistry);

    expect($fragment['props'] ?? [])->not->toHaveKey('firstlight_response_revision')
        ->and($fragment['children'][0]['props']['value'])->toBeFalse();
});

it('closes Firstlight overlays in the farewell frame without changing the model or retained tree', function (string $action) {
    $screen = Native::test(ResponseOverlayScreen::class);
    $initial = $screen->tree();

    $screen->instance()->{$action}(...($action === 'back' ? [] : ['/next']));
    $farewell = FakeBridge::current()->lastPublish();
    $returned = $screen->instance()->render()->toArray(new CallbackRegistry);

    expect($initial['children'][0]['props']['visible'])->toBeTrue()
        ->and($initial['children'][1]['children'][0]['props']['visible'])->toBeTrue()
        ->and($farewell['children'][0]['props']['visible'])->toBeFalse()
        ->and($farewell['children'][1]['children'][0]['props']['visible'])->toBeFalse()
        ->and($returned['children'][0]['props']['visible'])->toBeTrue()
        ->and($returned['children'][1]['children'][0]['props']['visible'])->toBeTrue()
        ->and($screen->instance()->visible)->toBeTrue();
})->with(['navigate', 'replace', 'back']);

it('stamps only the screen root while preserving nested Blade and Element callback ownership', function (string $child) {
    ComponentRegistry::register('response-child', $child);
    $screen = Native::test(ResponseNestedScreen::class);
    $first = $screen->tree();
    $screen->toggle('rejected', true);
    $second = $screen->tree();

    expect($first['props']['firstlight_response_revision'] ?? null)->toBeInt()
        ->and($second['props']['firstlight_response_revision'])->toBe($first['props']['firstlight_response_revision'] + 1)
        ->and($second['children'])->toBe($first['children'])
        ->and($second['children'][0]['props'] ?? [])->not->toHaveKey('firstlight_response_revision');
})->with([ResponseBladeScreen::class, ResponseViewHelperScreen::class, ResponseElementScreen::class]);
