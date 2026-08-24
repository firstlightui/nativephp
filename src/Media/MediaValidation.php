<?php

namespace FirstlightUI\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

final class MediaValidation
{
    /**
     * Build Validator::make() data for a MediaValue under the conventional field key.
     *
     * @return array{media: UploadedFile|null}
     */
    public static function attributes(?MediaValue $value, string $key = 'media'): array
    {
        return [$key => self::toUploadedFile($value)];
    }

    public static function toUploadedFile(?MediaValue $value): ?UploadedFile
    {
        if ($value === null) {
            return null;
        }

        $absolute = Storage::disk($value->disk)->path($value->path);

        if (! is_file($absolute) || ! is_readable($absolute)) {
            throw new InvalidArgumentException(
                "Media validation requires a readable stored file at `{$value->disk}:{$value->path}`."
            );
        }

        return new UploadedFile(
            $absolute,
            basename($value->path),
            $value->mime !== '' ? $value->mime : null,
            null,
            true,
        );
    }
}
