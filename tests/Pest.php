<?php

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Native\Mobile\Testing\FakeBridge;

// NativePHP core resolves the platform through the System facade, so give
// tests a container to resolve facades from. Tests that boot their own
// container restore this one afterwards.
Container::setInstance(new Container);
Facade::setFacadeApplication(Container::getInstance());

uses()
    // With no platform set, System::platform() asks the device; a fake bridge
    // answers instead of a Jump socket on 127.0.0.1.
    ->beforeEach(function () {
        Facade::setFacadeApplication(Container::getInstance());
        FakeBridge::enable();
    })
    ->afterEach(fn () => FakeBridge::disable())
    ->in('.');

if (! function_exists('app')) {
    function app(?string $abstract = null): mixed
    {
        $container = Illuminate\Container\Container::getInstance();

        return $abstract === null ? $container : $container->make($abstract);
    }
}

if (! function_exists('config')) {
    function config(?string $key = null, mixed $default = null): mixed
    {
        $container = Illuminate\Container\Container::getInstance();

        if (! $container->bound('config')) {
            return $default;
        }

        $repository = $container->make('config');

        if ($key === null) {
            return $repository;
        }

        return is_array($repository) || $repository instanceof ArrayAccess
            ? Illuminate\Support\Arr::get($repository, $key, $default)
            : $repository->get($key, $default);
    }
}

if (! function_exists('report')) {
    function report(Throwable $exception): void
    {
        throw $exception;
    }
}
