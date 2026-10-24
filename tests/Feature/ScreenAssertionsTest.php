<?php

use FirstlightUI\Authorization\GateEvaluator;
use FirstlightUI\Facades\Feedback;
use FirstlightUI\Feedback\FeedbackTone;
use FirstlightUI\NativeComponent;
use FirstlightUI\Testing\ScreenAssertions;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\View;
use Native\Mobile\Testing\Native;
use PHPUnit\Framework\AssertionFailedError;

require_once __DIR__.'/../Support/ScreenHarness.php';

class AssertedPostsScreen extends NativeComponent
{
    public string $email = '';

    public string $plan = 'free';

    public ?int $opened = null;

    /** @var array<string, string> */
    public array $planOptions = ['free' => 'Free', 'team' => 'Team', 'pro' => 'Pro'];

    protected array $rules = ['email' => 'required|email'];

    public function mount(): void
    {
        $this->listItems = [
            ['id' => 1, 'title' => 'Alpha', 'locked' => false],
            ['id' => 2, 'title' => 'Beta', 'locked' => true],
        ];
    }

    public function save(): void
    {
        $this->submit(fn () => null, successMessage: 'Saved');
    }

    public function open(int $id): void
    {
        $this->opened = $id;
    }

    public function requestDeletion(int $id): void
    {
        $this->requestDestructiveListAction($id);
    }

    public function cancelDeletion(): void
    {
        $this->cancelDestructiveListAction();
    }

    public function deletePost(): void
    {
        $this->confirmDestructiveListAction(fn () => null, successMessage: 'Post deleted');
    }

    public function render(): View
    {
        return view('native.asserted-posts');
    }

    protected function actionGateEvaluator(): GateEvaluator
    {
        return new GateEvaluator(fn (string $ability, array $arguments): bool => ! $arguments[0]['locked']);
    }
}

beforeEach(function () {
    ScreenAssertions::register();

    $this->viewDirectory = sys_get_temp_dir().'/firstlight-assertions-'.bin2hex(random_bytes(8));
    $this->files = new Filesystem;
    $this->files->makeDirectory($this->viewDirectory.'/native', 0755, true);
    $this->files->put($this->viewDirectory.'/native/asserted-posts.blade.php', <<<'BLADE'
        <native:column>
            <firstlight:text-field label="Email" native:model="email" ref="email-field" />
            <firstlight:select label="Plan" native:model="plan" :options="$planOptions" />
            <firstlight:button label="Save" @press="save" />
            <firstlight:list>
                @foreach ($this->listItems as $post)
                    <firstlight:list-item
                        :headline="$post['title']"
                        :disabled="$this->denies('delete', $post)"
                        @press="open({{ $post['id'] }})"
                        :trailing-actions="[['method' => 'requestDeletion('.$post['id'].')', 'label' => 'Delete', 'role' => 'destructive']]"
                    />
                @endforeach
            </firstlight:list>
            <firstlight:confirmation-dialog
                :visible="$confirmingListDestruction"
                title="Delete post?"
                message="This cannot be undone."
                @press="deletePost"
                @dismiss="cancelDeletion"
            />
        </native:column>
        BLADE);

    $this->harness = bootFirstlightScreenHarness(new Container, $this->viewDirectory);
    $this->feedback = Feedback::fake();
});

afterEach(function () {
    tearDownFirstlightScreenHarness($this->harness);
    $this->files->deleteDirectory($this->viewDirectory);
});

it('asserts the validation error a bound field publishes, by binding or ref', function () {
    $screen = Native::test(AssertedPostsScreen::class)
        ->assertNoFieldError('email')
        ->input('email', 'not-an-email')
        ->tap('Save')
        ->assertFieldError('email', 'The email must be a valid email address.')
        ->assertFieldError('email-field')
        ->assertNoFieldError('plan')
        ->assertHasErrors(['email' => 'The email must be a valid email address.'])
        ->assertHasNoErrors('plan')
        ->assertNoFeedbackSent();

    expect($screen)->toHaveFieldError('email');
    expect(fn () => $screen->assertFieldError('email', 'The email field is required.'))
        ->toThrow(AssertionFailedError::class);

    $screen->input('email', 'clinician@example.com')
        ->tap('Save')
        ->assertNoFieldError('email')
        ->assertHasNoErrors()
        ->assertFeedbackSent('Saved', FeedbackTone::Success);
});

it('finds bound fields and chooses an option by stable value', function () {
    $screen = Native::test(AssertedPostsScreen::class);

    expect($screen->field('email')['type'])->toBe('firstlight.text-field')
        ->and($screen->field('plan')['type'])->toBe('firstlight.select');

    $screen->choose('plan', 'pro')->assertSet('plan', 'pro');

    expect(fn () => $screen->choose('plan', 'pro'))->toThrow(AssertionFailedError::class, 'already selected')
        ->and(fn () => $screen->choose('plan', 'enterprise'))->toThrow(AssertionFailedError::class, 'no option')
        ->and(fn () => $screen->field('missing'))->toThrow(AssertionFailedError::class);
});

it('asserts and presses List rows by headline without pressing disabled rows', function () {
    $screen = Native::test(AssertedPostsScreen::class)
        ->assertListItem('Alpha', fn (array $row) => $row['props']['disabled'] === false)
        ->assertListItem('Beta', fn (array $row) => $row['props']['disabled'] === true)
        ->assertListItemCount(2)
        ->assertMissingListItem('Gamma')
        ->pressListItem('Alpha')
        ->assertSet('opened', 1);

    expect($screen)->toHaveListItem('Beta');
    expect(fn () => $screen->pressListItem('Beta'))->toThrow(AssertionFailedError::class, 'disabled');
});

it('drives destructive swipe actions through Confirmation Dialog', function () {
    $screen = Native::test(AssertedPostsScreen::class)
        ->assertNoConfirmationDialog()
        ->pressListItem('Alpha', 'Delete')
        ->assertConfirmationDialog('Delete post?')
        ->cancelDialog()
        ->assertNoConfirmationDialog()
        ->assertListItem('Alpha')
        ->pressListItem('Alpha', 'Delete')
        ->confirmDialog('Delete post?')
        ->assertNoConfirmationDialog()
        ->assertMissingListItem('Alpha')
        ->assertListItemCount(1)
        ->assertFeedbackSent('Post deleted', FeedbackTone::Success);

    expect(fn () => $screen->confirmDialog())->toThrow(AssertionFailedError::class, 'No visible Confirmation Dialog')
        ->and(fn () => $screen->pressListItem('Beta', 'Archive'))->toThrow(AssertionFailedError::class);
});
