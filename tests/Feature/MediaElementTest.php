<?php

use FirstlightUI\Elements\Media;
use FirstlightUI\FirstlightServiceProvider;
use FirstlightUI\FirstlightTagPrecompiler;
use FirstlightUI\Media\MediaValue;
use FirstlightUI\Validation\FieldErrorBag;
use Illuminate\Container\Container;
use Illuminate\Contracts\View\Factory as ViewFactoryContract;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\MessageBag;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Component;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

beforeEach(function () {
    FieldErrorBag::reset();
    NativeElementCollector::reset();
    ElementRegistry::reset();
    ElementRegistry::register('firstlight.media', Media::class);
});

afterEach(function () {
    FieldErrorBag::reset();
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function collectFirstlightMedia(array $attributes, ?CallbackRegistry $registry = null): array
{
    NativeElementCollector::leaf('firstlight.media', $attributes);

    return NativeElementCollector::collect()->toArray($registry ?? new CallbackRegistry);
}

/** @return array{compiled: string, output: string, tree: ?array, registry: CallbackRegistry} */
function compileFirstlightMediaView(string $source, array $data = []): array
{
    $filesystem = new Filesystem;
    $temporaryPath = sys_get_temp_dir().'/firstlight-media-blade-'.bin2hex(random_bytes(8));
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

    $compiler->component('native-firstlight-media', \FirstlightUI\Components\Media::class);
    $compiler->precompiler(new NativeTagPrecompiler);
    (new FirstlightServiceProvider($container))->boot();

    NativeElementCollector::reset();
    NativeTagPrecompiler::setActive(true);

    try {
        $compiled = $compiler->compileString($source);
        extract($data, EXTR_SKIP);
        $__env = $factory;

        $outputLevel = ob_get_level();
        ob_start();
        try {
            $compiledFile = $compiledPath.'/'.hash('sha256', $compiled).'.php';
            $filesystem->put($compiledFile, $compiled);
            include $compiledFile;
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

it('requires mode', function () {
    collectFirstlightMedia(['label' => 'Photo']);
})->throws(InvalidArgumentException::class, 'Media requires `mode`');

it('publishes the empty image field contract', function () {
    $tree = collectFirstlightMedia([
        'mode' => 'image',
        'label' => 'Profile photo',
        'helper' => 'Square crop required',
        'required' => true,
        'disk' => 'mobile_public',
        'directory' => 'avatars',
        'aspect' => '1:1',
    ]);

    expect($tree['type'])->toBe('firstlight.media')
        ->and($tree['props'])->toMatchArray([
            'mode' => 'image',
            'label' => 'Profile photo',
            'helper' => 'Square crop required',
            'error' => '',
            'required' => true,
            'disabled' => false,
            'disk' => 'mobile_public',
            'directory' => 'avatars',
            'aspect' => '1:1',
            'crop' => 'required',
            'has_value' => false,
            'path' => '',
            'preview_url' => '',
        ]);
});

it('publishes document mode without crop props', function () {
    $tree = collectFirstlightMedia([
        'mode' => 'document',
        'label' => 'Contract',
    ]);

    expect($tree['props'])->toMatchArray([
        'mode' => 'document',
        'label' => 'Contract',
        'has_value' => false,
    ])->and($tree['props'])->not->toHaveKeys(['aspect', 'crop']);
});

it('publishes MediaValue path mime size and optional dimensions', function () {
    $value = new MediaValue('mobile_public', 'avatars/a.jpg', 'image/jpeg', 1200, 100, 100);

    $tree = collectFirstlightMedia([
        'mode' => 'image',
        'label' => 'Profile photo',
        'value' => $value,
    ]);

    expect($tree['props'])->toMatchArray([
        'has_value' => true,
        'path' => 'avatars/a.jpg',
        'mime' => 'image/jpeg',
        'size' => 1200,
        'width' => 100,
        'height' => 100,
        'disk' => 'mobile_public',
    ]);
});

it('rejects invalid mode', function () {
    collectFirstlightMedia(['mode' => 'video', 'label' => 'Clip']);
})->throws(InvalidArgumentException::class, 'Media `mode` must be');

it('rejects invalid crop', function () {
    collectFirstlightMedia([
        'mode' => 'image',
        'label' => 'Photo',
        'crop' => 'maybe',
    ]);
})->throws(InvalidArgumentException::class, 'Media `crop` must be');

it('rejects crop and aspect in document mode', function (string $attr, string $value) {
    collectFirstlightMedia([
        'mode' => 'document',
        'label' => 'Contract',
        $attr => $value,
    ]);
})->with([
    ['crop', 'optional'],
    ['aspect', '1:1'],
])->throws(InvalidArgumentException::class, 'document mode rejects');

it('publishes optional crop without aspect', function () {
    $tree = collectFirstlightMedia([
        'mode' => 'image',
        'label' => 'Attachment',
        'crop' => 'optional',
    ]);

    expect($tree['props'])->toMatchArray([
        'crop' => 'optional',
    ])->and($tree['props'])->not->toHaveKey('aspect');
});

it('publishes change and clear callbacks', function () {
    $registry = new CallbackRegistry;

    $tree = collectFirstlightMedia([
        'mode' => 'image',
        'label' => 'Photo',
        '_change' => 'photoChosen',
        '_clear' => 'photoCleared',
    ], $registry);

    expect($tree['props']['on_change'])->toBeInt()
        ->and($tree['props']['on_clear'])->toBeInt()
        ->and($registry->resolve($tree['props']['on_change']))->toBe([
            'method' => 'photoChosen',
            'args' => [],
        ])
        ->and($registry->resolve($tree['props']['on_clear']))->toBe([
            'method' => 'photoCleared',
            'args' => [],
        ]);
});

it('binds FieldErrorBag into the error slot', function () {
    $tree = FieldErrorBag::using(new MessageBag([
        'avatar' => ['Choose a photo.'],
    ]), function () {
        return collectFirstlightMedia([
            'mode' => 'image',
            'label' => 'Photo',
            'native:model' => 'avatar',
        ]);
    });

    expect($tree['props']['error'])->toBe('Choose a photo.');
});

it('lets authored error win over the binder', function () {
    $tree = FieldErrorBag::using(new MessageBag([
        'avatar' => ['Choose a photo.'],
    ]), function () {
        return collectFirstlightMedia([
            'mode' => 'image',
            'label' => 'Photo',
            'error' => 'Authored wins.',
            'native:model' => 'avatar',
        ]);
    });

    expect($tree['props']['error'])->toBe('Authored wins.');
});

it('rejects unsupported attributes', function () {
    collectFirstlightMedia([
        'mode' => 'image',
        'label' => 'Photo',
        'multiple' => true,
    ]);
})->throws(InvalidArgumentException::class, 'does not support the `multiple`');

it('compiles the public Blade tag', function () {
    $result = compileFirstlightMediaView(
        '<firstlight:media mode="image" label="Profile photo" />'
    );

    expect($result['tree']['type'])->toBe('firstlight.media')
        ->and($result['tree']['props']['mode'])->toBe('image')
        ->and($result['tree']['props']['label'])->toBe('Profile photo');
});

it('publishes package chrome crop and clear labels', function () {
    expect(collectFirstlightMedia([
        'mode' => 'image',
        'label' => 'Profile photo',
    ])['props'])->toMatchArray([
        'confirm_label' => 'Confirm',
        'cancel_label' => 'Cancel',
        'clear_label' => 'Clear',
        'skip_label' => 'Skip',
        'crop_label' => 'Crop',
        'zoom_in_label' => 'Zoom in',
        'zoom_out_label' => 'Zoom out',
        'choose_media_label' => 'Choose media',
        'photo_library_label' => 'Photo Library',
        'camera_label' => 'Camera',
        'browse_files_label' => 'Browse Files',
    ]);
});
