<?php

namespace FirstlightUI\Media;

final readonly class MediaValue
{
    public function __construct(
        public string $disk,
        public string $path,
        public string $mime,
        public int $size,
        public ?int $width = null,
        public ?int $height = null,
    ) {}
}
