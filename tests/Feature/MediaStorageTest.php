<?php

use FirstlightUI\Media\MediaStorage;
use FirstlightUI\Media\MediaValue;
use Illuminate\Container\Container;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

final class MediaStorageTestParallelTesting
{
    public function token(): ?string
    {
        return null;
    }
}

final class MediaStorageTestConfig implements ArrayAccess
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

beforeEach(function () {
    $this->previousContainer = Container::getInstance();
    $this->previousFacadeApplication = Facade::getFacadeApplication();

    $this->container = new Container;
    $this->container->instance('config', new MediaStorageTestConfig([
        'filesystems' => [
            'default' => 'mobile_public',
            'disks' => [
                'mobile_public' => [
                    'driver' => 'local',
                    'root' => sys_get_temp_dir().'/firstlight-mobile-public-test',
                    'throw' => false,
                ],
            ],
        ],
    ]));

    (new FilesystemServiceProvider($this->container))->register();
    $this->container->instance('Illuminate\Testing\ParallelTesting', new MediaStorageTestParallelTesting);

    Container::setInstance($this->container);
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($this->container);

    if (! function_exists('storage_path')) {
        function storage_path(string $path = ''): string
        {
            $base = sys_get_temp_dir().'/firstlight-storage';

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

function createTempImageFile(string $contents = "fake-jpeg-bytes"): string
{
    $path = tempnam(sys_get_temp_dir(), 'fl-media-');
    file_put_contents($path, $contents);

    return $path;
}

it('commits a temp file into storage under the given directory', function () {
    $tempPath = createTempImageFile();

    try {
        $value = MediaStorage::commit($tempPath, 'mobile_public', 'avatars', 'a.jpg');

        expect($value)->toBeInstanceOf(MediaValue::class)
            ->and($value->disk)->toBe('mobile_public')
            ->and($value->path)->toBe('avatars/a.jpg')
            ->and($value->mime)->not->toBe('')
            ->and($value->size)->toBeGreaterThan(0);

        Storage::disk('mobile_public')->assertExists('avatars/a.jpg');
    } finally {
        @unlink($tempPath);
    }
});

it('generates a filename when none is provided', function () {
    $tempPath = createTempImageFile();

    try {
        $value = MediaStorage::commit($tempPath, 'mobile_public', 'uploads');

        expect($value->path)->toStartWith('uploads/')
            ->and(basename($value->path))->not->toBe('');

        Storage::disk('mobile_public')->assertExists($value->path);
    } finally {
        @unlink($tempPath);
    }
});

it('deletes a committed value from storage', function () {
    $tempPath = createTempImageFile();

    try {
        $value = MediaStorage::commit($tempPath, 'mobile_public', 'avatars', 'remove-me.jpg');

        Storage::disk('mobile_public')->assertExists('avatars/remove-me.jpg');

        MediaStorage::delete($value);

        Storage::disk('mobile_public')->assertMissing('avatars/remove-me.jpg');
    } finally {
        @unlink($tempPath);
    }
});

it('treats delete with null as a no-op', function () {
    MediaStorage::delete(null);
})->throwsNoExceptions();

it('does not throw when deleting a missing object', function () {
    $value = new MediaValue('mobile_public', 'avatars/missing.jpg', 'image/jpeg', 1);

    MediaStorage::delete($value);
})->throwsNoExceptions();

it('throws when the temp path does not exist', function () {
    MediaStorage::commit('/tmp/does-not-exist-'.uniqid(), 'mobile_public', 'avatars');
})->throws(InvalidArgumentException::class);
