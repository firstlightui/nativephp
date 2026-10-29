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
    ->beforeEach(fn () => function_exists('app') ? FakeBridge::enable() : null)
    ->afterEach(fn () => FakeBridge::disable())
    ->in('.');
