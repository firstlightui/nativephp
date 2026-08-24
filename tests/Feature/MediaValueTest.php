<?php

use FirstlightUI\Media\MediaValue;

it('exposes disk path mime size and optional dimensions', function () {
    $value = new MediaValue('mobile_public', 'avatars/a.jpg', 'image/jpeg', 1200, 100, 100);

    expect($value->disk)->toBe('mobile_public')
        ->and($value->path)->toBe('avatars/a.jpg')
        ->and($value->mime)->toBe('image/jpeg')
        ->and($value->size)->toBe(1200)
        ->and($value->width)->toBe(100)
        ->and($value->height)->toBe(100);
});

it('allows null width and height', function () {
    $value = new MediaValue('mobile_public', 'docs/report.pdf', 'application/pdf', 4096);

    expect($value->width)->toBeNull()
        ->and($value->height)->toBeNull();
});
