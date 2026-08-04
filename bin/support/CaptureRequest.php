<?php

declare(strict_types=1);

namespace FirstlightUI\Documentation;

final readonly class CaptureRequest
{
    public function __construct(
        public string $component,
        public string $packageRoot,
        public string $showcaseRoot,
        public string $iosUdid,
        public string $androidSerial,
        public bool $release,
        public bool $keepFailed,
    ) {}
}
