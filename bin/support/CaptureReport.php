<?php

declare(strict_types=1);

namespace FirstlightUI\Documentation;

final readonly class CaptureReport
{
    /** @param array<string, string> $commands
     *  @param array<string, string> $outputs
     */
    public function __construct(
        public string $packageRevision,
        public string $showcaseRevision,
        public bool $packageDirty,
        public bool $showcaseDirty,
        public string $iosUdid,
        public string $androidSerial,
        public array $commands,
        public array $outputs,
    ) {}
}
