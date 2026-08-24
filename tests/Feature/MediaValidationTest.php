<?php

use FirstlightUI\Media\MediaStorage;
use FirstlightUI\Media\MediaValidation;
use FirstlightUI\Media\MediaValue;
use FirstlightUI\NativeComponent;
use Illuminate\Container\Container;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidatorFactory;
use Illuminate\Validation\ValidationException;

final class MediaValidationTestParallelTesting
{
    public function token(): ?string
    {
        return null;
    }
}

final class MediaValidationTestConfig implements ArrayAccess
{
    public function __construct(private array $config) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->config, $key, $default);
    }

    public function offsetExists(mixed $offset): bool
    {
        return Arr::has($this->config, $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return Arr::get($this->config, $offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        Arr::set($this->config, $offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        Arr::forget($this->config, $offset);
    }
}

final class MediaValidatingScreen extends NativeComponent
{
    public ?MediaValue $avatar = null;

    /** @var array<string, string> */
    protected array $rules = [
        'avatar' => 'required|image|max:100',
    ];

    /** @return array<string, mixed> */
    public function save(): array
    {
        return $this->validate();
    }
}

beforeEach(function () {
    $this->previousContainer = Container::getInstance();
    $this->previousFacadeApplication = Facade::getFacadeApplication();

    $this->container = new Container;
    $this->container->instance('config', new MediaValidationTestConfig([
        'filesystems' => [
            'default' => 'mobile_public',
            'disks' => [
                'mobile_public' => [
                    'driver' => 'local',
                    'root' => sys_get_temp_dir().'/firstlight-mobile-public-validation-test',
                    'throw' => false,
                ],
            ],
        ],
    ]));

    (new FilesystemServiceProvider($this->container))->register();
    $this->container->instance('Illuminate\Testing\ParallelTesting', new MediaValidationTestParallelTesting);

    $translator = new Translator(new ArrayLoader, 'en');
    $translator->addLines([
        'validation.required' => 'The :attribute field is required.',
        'validation.image' => 'The :attribute must be an image.',
        'validation.max.file' => 'The :attribute must not be greater than :max kilobytes.',
        'validation.uploaded' => 'The :attribute failed to upload.',
    ], 'en');
    $this->container->instance('translator', $translator);
    $this->container->singleton('validator', fn () => new ValidatorFactory($translator, $this->container));

    Container::setInstance($this->container);
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($this->container);

    if (! function_exists('storage_path')) {
        function storage_path(string $path = ''): string
        {
            $base = sys_get_temp_dir().'/firstlight-storage-validation';

            if (! is_dir($base)) {
                mkdir($base, 0777, true);
            }

            return $path === '' ? $base : $base.'/'.ltrim($path, '/');
        }
    }

    Storage::fake('mobile_public');
});

afterEach(function () {
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($this->previousFacadeApplication);
    Container::setInstance($this->previousContainer);
});

function createTinyPngFile(): string
{
    $path = tempnam(sys_get_temp_dir(), 'fl-png-');
    // 1x1 PNG
    file_put_contents($path, base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
    ));

    return $path;
}

function createOversizedPngFile(int $minBytes): string
{
    $path = createTinyPngFile();
    file_put_contents($path, file_get_contents($path).str_repeat('x', $minBytes));

    return $path;
}

function createPdfLikeFile(): string
{
    $path = tempnam(sys_get_temp_dir(), 'fl-pdf-');
    file_put_contents($path, "%PDF-1.4\n%âãÏÓ\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n");

    return $path;
}

it('null fails required', function () {
    $validator = Validator::make(
        MediaValidation::attributes(null),
        ['media' => 'required'],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('media'))->toBeTrue();
});

it('image MediaValue passes image and max rules', function () {
    $temp = createTinyPngFile();

    try {
        $value = MediaStorage::commit($temp, 'mobile_public', 'avatars', 'a.png');

        $validator = Validator::make(
            MediaValidation::attributes($value),
            ['media' => 'required|image|max:100'],
        );

        expect($validator->passes())->toBeTrue();
    } finally {
        @unlink($temp);
    }
});

it('oversized MediaValue fails max', function () {
    $temp = createOversizedPngFile(120 * 1024);

    try {
        $value = MediaStorage::commit($temp, 'mobile_public', 'avatars', 'big.png');

        $validator = Validator::make(
            MediaValidation::attributes($value),
            ['media' => 'image|max:100'],
        );

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('media'))->toBeTrue();
    } finally {
        @unlink($temp);
    }
});

it('document MediaValue fails closed under image rule', function () {
    $temp = createPdfLikeFile();

    try {
        $value = MediaStorage::commit($temp, 'mobile_public', 'docs', 'contract.pdf');

        $validator = Validator::make(
            MediaValidation::attributes($value),
            ['media' => 'image'],
        );

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('media'))->toBeTrue();
    } finally {
        @unlink($temp);
    }
});

it('ValidatesFields converts MediaValue public properties for stock file rules', function () {
    $temp = createTinyPngFile();

    try {
        $screen = new MediaValidatingScreen;
        $screen->avatar = MediaStorage::commit($temp, 'mobile_public', 'avatars', 'ok.png');

        $validated = $screen->save();

        expect($validated)->toHaveKey('avatar')
            ->and($screen->hasError('avatar'))->toBeFalse();
    } finally {
        @unlink($temp);
    }
});

it('ValidatesFields required fails when MediaValue property is null', function () {
    $screen = new MediaValidatingScreen;
    $screen->avatar = null;

    expect(fn () => $screen->save())->toThrow(ValidationException::class);
    expect($screen->hasError('avatar'))->toBeTrue();
});
