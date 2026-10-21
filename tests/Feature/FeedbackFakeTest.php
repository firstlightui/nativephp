<?php

use FirstlightUI\Facades\Feedback;
use FirstlightUI\Feedback\FeedbackManager;
use FirstlightUI\Feedback\FeedbackRecord;
use FirstlightUI\Feedback\FeedbackStore;
use FirstlightUI\Feedback\FeedbackTone;
use Illuminate\Container\Container;
use PHPUnit\Framework\AssertionFailedError;

beforeEach(function () {
    $this->previousFeedbackApplication = Feedback::getFacadeApplication();
    $this->feedbackApplication = new Container;
    $this->realFeedbackStore = new FeedbackStore;
    $this->feedbackApplication->instance(FeedbackManager::class, new FeedbackManager($this->realFeedbackStore));
    Feedback::clearResolvedInstance(FeedbackManager::class);
    Feedback::setFacadeApplication($this->feedbackApplication);
});

afterEach(function () {
    Feedback::clearResolvedInstance(FeedbackManager::class);
    Feedback::setFacadeApplication($this->previousFeedbackApplication);
});

it('captures only sends with full semantic records without publishing to the real store', function () {
    $fake = Feedback::fake();
    Feedback::message('Unsent draft');
    Feedback::assertNothingSent();

    $id = Feedback::success('Saved')->action('Undo', 'undo-save')->hold()->send();
    Feedback::warning('Retry')->id('retry')->send();
    Feedback::danger('Failed')->send();
    Feedback::message('Information')->send();

    Feedback::assertSent(fn (FeedbackRecord $record) => $record->id === $id
        && $record->message === 'Saved' && $record->tone === FeedbackTone::Success
        && $record->actionLabel === 'Undo' && $record->actionKey === 'undo-save' && $record->hold);
    Feedback::assertNotSent(fn (FeedbackRecord $record) => $record->message === 'Unsent draft');
    expect($fake)->toBe(Feedback::getFacadeRoot())
        ->and($this->realFeedbackStore->all())->toBe([])
        ->and(Feedback::sent()->pluck('tone')->all())->toBe([
            FeedbackTone::Success, FeedbackTone::Warning, FeedbackTone::Danger, FeedbackTone::Default,
        ])
        ->and(Feedback::sent(fn (FeedbackRecord $record) => $record->actionKey === 'undo-save')->count())->toBe(1);
});

it('retains chronological send history across updates and dismissal', function () {
    Feedback::fake();
    Feedback::message('Connecting')->id('connection')->send();
    Feedback::warning('Interrupted')->id('connection')->send();

    expect(Feedback::dismiss('connection'))->toBeTrue()
        ->and(Feedback::dismiss('connection'))->toBeFalse()
        ->and(Feedback::sent()->pluck('message')->all())->toBe(['Connecting', 'Interrupted']);

    $snapshot = Feedback::sent();
    $snapshot->pop();
    expect(Feedback::sent())->toHaveCount(2);
});

it('starts a fresh history when faked again', function () {
    Feedback::fake();
    Feedback::success('Saved')->send();

    Feedback::fake();

    Feedback::assertNothingSent();
    expect(Feedback::sent())->toHaveCount(0);
});

it('fails feedback assertions when their expectations are not met', function (string $assertion) {
    Feedback::fake();
    Feedback::success('Saved')->send();

    expect(fn () => match ($assertion) {
        'sent' => Feedback::assertSent(fn (FeedbackRecord $record) => $record->message === 'Missing'),
        'not sent' => Feedback::assertNotSent(fn (FeedbackRecord $record) => $record->message === 'Saved'),
        'nothing sent' => Feedback::assertNothingSent(),
    })->toThrow(AssertionFailedError::class);
})->with(['sent', 'not sent', 'nothing sent']);

it('preserves builder validation and immutability while faked', function () {
    Feedback::fake();
    $pending = Feedback::message('Saved');
    expect(fn () => $pending->action('Undo', ' '))->toThrow(InvalidArgumentException::class);
    Feedback::assertNothingSent();

    $pending->id('held')->action('Undo', 'undo')->hold()->send();
    $generated = $pending->send();

    expect($generated)->not->toBe('held')
        ->and(Feedback::sent()[1]->actionKey)->toBeNull()
        ->and(Feedback::sent()[1]->hold)->toBeFalse();
});
