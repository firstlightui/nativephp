<?php

namespace FirstlightUI\Media;

use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class MediaStorage
{
    public static function commit(
        string $tempAbsolutePath,
        string $disk,
        string $directory,
        ?string $filename = null,
    ): MediaValue {
        if (! is_file($tempAbsolutePath) || ! is_readable($tempAbsolutePath)) {
            throw new InvalidArgumentException(
                "Media commit requires a readable temp file at `{$tempAbsolutePath}`."
            );
        }

        $mime = self::detectMime($tempAbsolutePath);
        $size = filesize($tempAbsolutePath);

        if ($size === false) {
            throw new InvalidArgumentException(
                "Media commit could not determine the size of `{$tempAbsolutePath}`."
            );
        }

        [$width, $height] = self::detectDimensions($tempAbsolutePath, $mime);

        $filename ??= self::generateFilename($tempAbsolutePath, $mime);
        $directory = trim($directory, '/');
        $path = $directory === '' ? $filename : "{$directory}/{$filename}";

        $stored = Storage::disk($disk)->putFileAs(
            $directory === '' ? '' : $directory,
            new File($tempAbsolutePath),
            $filename,
        );

        if ($stored === false) {
            throw new InvalidArgumentException(
                "Media commit could not store `{$tempAbsolutePath}` on disk `{$disk}`."
            );
        }

        return new MediaValue($disk, $path, $mime, $size, $width, $height);
    }

    public static function delete(?MediaValue $value): void
    {
        if ($value === null) {
            return;
        }

        Storage::disk($value->disk)->delete($value->path);
    }

    private static function detectMime(string $path): string
    {
        $mime = mime_content_type($path);

        if ($mime !== false && $mime !== '') {
            return $mime;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo === false) {
            throw new InvalidArgumentException(
                "Media commit could not detect the mime type of `{$path}`."
            );
        }

        $detected = finfo_file($finfo, $path);
        finfo_close($finfo);

        if ($detected === false || $detected === '') {
            throw new InvalidArgumentException(
                "Media commit could not detect the mime type of `{$path}`."
            );
        }

        return $detected;
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    private static function detectDimensions(string $path, string $mime): array
    {
        if (! str_starts_with($mime, 'image/')) {
            return [null, null];
        }

        $dimensions = @getimagesize($path);

        if ($dimensions === false) {
            return [null, null];
        }

        return [$dimensions[0], $dimensions[1]];
    }

    private static function generateFilename(string $path, string $mime): string
    {
        $basename = basename($path);

        if ($basename !== '' && $basename !== '.' && $basename !== '..') {
            return $basename;
        }

        $extension = self::extensionForMime($mime);

        return Str::uuid()->toString().($extension !== '' ? ".{$extension}" : '');
    }

    private static function extensionForMime(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            default => '',
        };
    }
}
