<?php

use Illuminate\Container\Container;
use Illuminate\Contracts\View\Factory as ViewFactoryContract;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Component;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Illuminate\View\View;
use FirstlightUI\FirstlightTagPrecompiler;
use Native\Mobile\Edge\ChromeContributorRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;
use Native\Mobile\Testing\FakeBridge;

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

/**
 * Boot just enough of Laravel for Native::test() to render Firstlight Blade
 * screens from `$viewPath`: a container, the view factory with both tag
 * precompilers, and the Firstlight components a screen test needs.
 *
 * @return array{previous: ?Container, facadeApplication: mixed, compiled: string}
 */
function bootFirstlightScreenHarness(Container $container, string $viewPath): array
{
    $state = [
        'previous' => Container::getInstance(),
        'facadeApplication' => Facade::getFacadeApplication(),
        'compiled' => sys_get_temp_dir().'/firstlight-harness-'.bin2hex(random_bytes(8)),
    ];

    Container::setInstance($container);
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($container);

    $files = new Filesystem;
    $files->makeDirectory($state['compiled'], 0755, true);

    $blade = new BladeCompiler($files, $state['compiled']);
    $blade->prepareStringsForCompilationUsing(new FirstlightTagPrecompiler);
    $blade->precompiler(new NativeTagPrecompiler);
    $engines = new EngineResolver;
    $engines->register('blade', fn () => new CompilerEngine($blade, $files));
    $views = new Factory($engines, new FileViewFinder($files, [$viewPath]), new Dispatcher($container));
    $views->setContainer($container);
    $views->addNamespace('__components', $state['compiled']);
    $views->addNamespace('nativephp-mobile', dirname(__DIR__, 2).'/vendor/nativephp/mobile/src/resources/views');
    $container->instance('view', $views);
    $container->instance(ViewFactoryContract::class, $views);
    $container->instance('blade.compiler', $blade);
    $container->instance('config', new class($state['compiled'])
    {
        public function __construct(private string $compiledPath) {}

        public function get(string $key, mixed $default = null): mixed
        {
            return $key === 'view.compiled' ? $this->compiledPath : $default;
        }
    });

    NativeElementCollector::reset();
    ChromeContributorRegistry::reset();
    ElementRegistry::reset();
    Component::flushCache();
    ElementRegistry::register('text', Native\Mobile\Edge\Elements\Text::class);

    foreach ([
        'text-field' => 'TextField',
        'select' => 'Select',
        'button' => 'Button',
        'list' => 'ListContainer',
        'list-item' => 'ListItem',
        'confirmation-dialog' => 'ConfirmationDialog',
    ] as $tag => $class) {
        $blade->component("native-firstlight-{$tag}", "FirstlightUI\\Components\\{$class}");
        ElementRegistry::register("firstlight.{$tag}", "FirstlightUI\\Elements\\{$class}");
    }

    return $state;
}

/** @param array{previous: ?Container, facadeApplication: mixed, compiled: string} $state */
function tearDownFirstlightScreenHarness(array $state): void
{
    FakeBridge::disable();
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ChromeContributorRegistry::reset();
    ElementRegistry::reset();
    Component::flushCache();
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($state['facadeApplication']);
    Container::setInstance($state['previous']);
    (new Filesystem)->deleteDirectory($state['compiled']);
}
