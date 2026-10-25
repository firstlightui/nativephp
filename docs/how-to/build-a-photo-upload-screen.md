---
title: Build a photo upload screen
description: Recipe for a Media field that crops a photo, validates it with Laravel rules, and saves it to a model only when the user taps Save.
type: how-to
audience: consumer
sources:
  - src/Elements/Media.php
  - src/Media/MediaValue.php
  - src/Media/MediaStorage.php
  - src/Media/MediaValidation.php
  - src/Concerns/SubmitsForms.php
  - src/FirstlightTagPrecompiler.php
  - src/Validation/FieldErrorBinder.php
---

# Build a Photo Upload Screen

This recipe adds a cover photo to a post: the user picks or takes a photo,
crops it to 16:9, and taps Save. Laravel validation checks the file, the post
stores where it lives, and replaced files are deleted.

This recipe runs unchanged in the
[showcase app](https://github.com/firstlightui/showcase), where
[`MediaUploadRecipeTest`](https://github.com/firstlightui/showcase/blob/main/tests/Feature/Recipes/MediaUploadRecipeTest.php)
exercises it.

## How the pieces fit

[Media](../components/media.md) copies a picked file to Storage as soon as the
user confirms it and binds a `MediaValue`. Until Save, that file is a draft:

- Picking again or clearing deletes the draft, never the saved cover.
- Save stores the new `MediaValue` on the post, then deletes the old cover.

So leaving without saving, or replacing a photo twice, never loses the saved
cover.

## 1. Store the value

`MediaValue` is a small readonly object. Store its fields as JSON:

```php
Schema::table('posts', function (Blueprint $table) {
    $table->json('cover')->nullable();
});
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = ['title', 'excerpt', 'body', 'cover'];

    protected function casts(): array
    {
        return [
            'cover' => 'array',
        ];
    }
}
```

## 2. Register the route

```php
use App\NativeComponents\Layouts\AppLayout;
use App\NativeComponents\PostCoverScreen;
use Illuminate\Support\Facades\Route;

Route::native('/posts/{post}/cover', PostCoverScreen::class)
    ->name('recipes.post.cover')
    ->layout(AppLayout::class);
```

Register it before `/posts/{post}`.

## 3. Write the screen

```php
<?php

namespace App\NativeComponents;

use App\Models\Post;
use FirstlightUI\Media\MediaStorage;
use FirstlightUI\Media\MediaValue;
use FirstlightUI\NativeComponent;
use Illuminate\View\View;

class PostCoverScreen extends NativeComponent
{
    public Post $post;

    public ?MediaValue $cover = null;

    /** @var array<string, string> */
    protected array $rules = [
        'cover' => 'nullable|image|max:4096',
    ];

    public function mount(): void
    {
        $this->cover = $this->savedCover();
    }

    public function coverChosen(string $tempPath): void
    {
        $this->discardDraft();
        $this->cover = MediaStorage::commit($tempPath, 'mobile_public', 'covers');
    }

    public function coverCleared(): void
    {
        $this->discardDraft();
        $this->cover = null;
    }

    public function save(): void
    {
        $saved = $this->submit(
            action: function (): void {
                $previous = $this->savedCover();

                $this->post->update([
                    'cover' => $this->cover === null ? null : get_object_vars($this->cover),
                ]);

                if ($previous !== null && $previous->path !== $this->cover?->path) {
                    MediaStorage::delete($previous);
                }
            },
            successMessage: 'Cover saved',
        );

        if ($saved) {
            $this->back();
        }
    }

    public function navTitle(): string
    {
        return 'Cover photo';
    }

    public function render(): View
    {
        return view('native.post-cover');
    }

    private function savedCover(): ?MediaValue
    {
        return $this->post->cover === null ? null : new MediaValue(...$this->post->cover);
    }

    /** Deletes the picked file only while it is an unsaved draft. */
    private function discardDraft(): void
    {
        if ($this->cover !== null && $this->cover->path !== $this->savedCover()?->path) {
            MediaStorage::delete($this->cover);
        }
    }
}
```

`submit()` validates `$rules` first. Media adapts the `MediaValue` to an
`UploadedFile`, so `image`, `mimes`, `max`, and `dimensions` work as they do
for web uploads. `nullable` lets the user remove the cover by clearing the
field and saving.

## 4. Write the view

```blade
<native:scroll-view class="w-full flex-1">
    <native:column class="w-full p-4 gap-4">
        <firstlight:media
            mode="image"
            label="Cover photo"
            helper="Cropped to 16:9. Saved when you tap Save cover."
            aspect="16:9"
            directory="covers"
            native:model="cover"
            @change="coverChosen"
            @clear="coverCleared"
        />

        <firstlight:button variant="primary" :loading="$submitting" @press="save">
            Save cover
        </firstlight:button>
    </native:column>
</native:scroll-view>
```

`aspect="16:9"` makes the crop required and locked to that ratio. A failed
rule shows under the field, as on every Firstlight field.

## 5. Test it

`coverChosen()` receives the path native hands over after the pick, so a test
calls it with a fake upload:

```php
<?php

use App\Models\Post;
use FirstlightUI\Facades\Feedback;
use FirstlightUI\Testing\ScreenAssertions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Native\Mobile\Testing\Native;

uses(RefreshDatabase::class);

beforeEach(function () {
    ScreenAssertions::register();
    Storage::fake('mobile_public');
    $this->post = Post::where('title', 'Sample post 1')->firstOrFail();
});

it('saves a picked cover to the post and goes back', function () {
    Feedback::fake();
    $photo = UploadedFile::fake()->image('beach.jpg', 1600, 900);

    $screen = Native::visit("/posts/{$this->post->id}/cover")
        ->call('coverChosen', $photo->getPathname())
        ->call('save')
        ->assertHasNoErrors()
        ->assertFeedbackSent('Cover saved')
        ->assertWentBack();

    $cover = $this->post->fresh()->cover;
    expect($cover['path'])->toStartWith('covers/')
        ->and($cover['width'])->toBe(1600)
        ->and($cover['height'])->toBe(900);
    Storage::disk('mobile_public')->assertExists($cover['path']);
});

it('rejects a file that is not an image and saves nothing', function () {
    $notes = UploadedFile::fake()->createWithContent('notes.txt', 'Not an image');

    Native::visit("/posts/{$this->post->id}/cover")
        ->call('coverChosen', $notes->getPathname())
        ->call('save')
        ->assertHasErrors(['cover' => 'The cover field must be an image.'])
        ->assertElement('firstlight.media', fn (array $node) => $node['props']['error'] === 'The cover field must be an image.');

    expect($this->post->fresh()->cover)->toBeNull();
});

it('deletes a replaced draft but keeps the saved cover until Save', function () {
    $first = UploadedFile::fake()->image('first.jpg', 1600, 900);
    $draft = UploadedFile::fake()->image('draft.jpg', 1600, 900);
    $final = UploadedFile::fake()->image('final.jpg', 1600, 900);

    Native::visit("/posts/{$this->post->id}/cover")
        ->call('coverChosen', $first->getPathname())
        ->call('save');
    $saved = $this->post->fresh()->cover['path'];

    $screen = Native::visit("/posts/{$this->post->id}/cover")
        ->call('coverChosen', $draft->getPathname());
    $draftPath = $screen->get('cover')->path;

    $screen->call('coverChosen', $final->getPathname());
    $finalPath = $screen->get('cover')->path;

    $disk = Storage::disk('mobile_public');
    $disk->assertMissing($draftPath);
    $disk->assertExists($saved);

    $screen->call('save');
    $disk->assertMissing($saved);
    $disk->assertExists($finalPath);
    expect($this->post->fresh()->cover['path'])->toBe($finalPath);
});

it('removes the cover only when the cleared field is saved', function () {
    $photo = UploadedFile::fake()->image('beach.jpg', 1600, 900);

    Native::visit("/posts/{$this->post->id}/cover")
        ->call('coverChosen', $photo->getPathname())
        ->call('save');
    $saved = $this->post->fresh()->cover['path'];

    $screen = Native::visit("/posts/{$this->post->id}/cover")
        ->assertSet('cover.path', $saved)
        ->call('coverCleared');
    Storage::disk('mobile_public')->assertExists($saved);

    $screen->call('save');
    Storage::disk('mobile_public')->assertMissing($saved);
    expect($this->post->fresh()->cover)->toBeNull();
});
```

## Rules to keep

- Commit picks with `MediaStorage::commit()` in `@change`; never keep the
  temporary path.
- Delete only drafts in `@change` and `@clear`. Delete the old saved file
  after the model is updated.
- On iOS, set a camera usage description written for your app; see
  [Media host application setup](../components/media.md#host-application-setup).

## Adapt the recipe

- **Profile photo:** use `aspect="1:1"` and store the value on the user.
- **Documents:** use `mode="document"` and a `mimes:pdf` rule. Document mode
  has no crop.
- **Upload to a server:** in the `submit()` action, send
  `Storage::disk($this->cover->disk)->path($this->cover->path)` to your API
  before updating the model, and throw
  `ValidationException::withMessages(['cover' => ...])` if it fails.
