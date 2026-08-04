<?php

declare(strict_types=1);

namespace FirstlightUI\Documentation;

final readonly class BatchCaptureRequest
{
    /** @param list<string> $components */
    public function __construct(
        public array $components,
        public string $packageRoot,
        public string $showcaseRoot,
        public string $iosUdid,
        public string $androidSerial,
        public bool $release,
        public bool $keepFailed,
    ) {}
}
