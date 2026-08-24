<?php

use FirstlightUI\Authorization\GateEvaluator;
use FirstlightUI\Concerns\SubmitsForms;
use FirstlightUI\Concerns\ValidatesFields;
use FirstlightUI\Feedback\FeedbackManager;
use FirstlightUI\Feedback\FeedbackStore;
use FirstlightUI\Feedback\FeedbackTone;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Native\Mobile\Edge\NativeComponent;

final class SubmittingFormScreen extends NativeComponent
{
    use SubmitsForms;
    use ValidatesFields;

    public string $email = '';

    /** @var array<string, string> */
    protected array $rules = [
        'email' => 'required|email',
    ];
}

beforeEach(function () {
    $this->previousContainer = Container::getInstance();
    $this->previousFacadeApplication = Facade::getFacadeApplication();

    $this->container = new Container;
    $this->feedbackStore = new FeedbackStore;
    $this->container->instance(
        FeedbackManager::class,
        new FeedbackManager($this->feedbackStore),
    );

    Container::setInstance($this->container);
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($this->container);
});

afterEach(function () {
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($this->previousFacadeApplication);
    Container::setInstance($this->previousContainer);
});

it('validates, runs the action, and sends success Feedback', function () {
    $screen = new SubmittingFormScreen;
    $screen->email = 'person@example.com';
    $saved = 0;

    $result = $screen->submit(function () use (&$saved): void {
        $saved++;
    }, 'Profile saved');

    expect($result)->toBeTrue()
        ->and($saved)->toBe(1)
        ->and($screen->submitting)->toBeFalse()
        ->and($this->feedbackStore->all())->toHaveCount(1)
        ->and($this->feedbackStore->all()[0]->message)->toBe('Profile saved')
        ->and($this->feedbackStore->all()[0]->tone)->toBe(FeedbackTone::Success);
});

it('returns false on validation failure without running the action or sending success Feedback', function () {
    $screen = new SubmittingFormScreen;
    $saved = 0;

    $result = $screen->submit(function () use (&$saved): void {
        $saved++;
    }, 'Profile saved');

    expect($result)->toBeFalse()
        ->and($saved)->toBe(0)
        ->and($screen->hasError('email'))->toBeTrue()
        ->and($screen->submitting)->toBeFalse()
        ->and($this->feedbackStore->all())->toBe([]);
});

it('skips re-entry while a submission is running', function () {
    $screen = new SubmittingFormScreen;
    $screen->email = 'person@example.com';
    $outerRuns = 0;
    $innerRuns = 0;
    $innerResult = null;

    $outerResult = $screen->submit(function () use (
        $screen,
        &$outerRuns,
        &$innerRuns,
        &$innerResult,
    ): void {
        $outerRuns++;
        $innerResult = $screen->submit(function () use (&$innerRuns): void {
            $innerRuns++;
        });
    });

    expect($outerResult)->toBeTrue()
        ->and($innerResult)->toBeFalse()
        ->and($outerRuns)->toBe(1)
        ->and($innerRuns)->toBe(0)
        ->and($screen->submitting)->toBeFalse();
});

it('exposes submit and authorize on Firstlight NativeComponent', function () {
    $screen = new class extends FirstlightUI\NativeComponent
    {
        public string $email = 'person@example.com';

        /** @var array<string, string> */
        protected array $rules = [
            'email' => 'required|email',
        ];

        protected function actionGateEvaluator(): GateEvaluator
        {
            return new GateEvaluator(fn (): bool => true);
        }
    };

    expect($screen->submit(function (): void {}, validate: false))->toBeTrue()
        ->and($screen->authorize('update'))->toBeTrue();
});

it('can skip validation explicitly', function () {
    $screen = new SubmittingFormScreen;
    $saved = 0;

    $result = $screen->submit(function () use (&$saved): void {
        $saved++;
    }, validate: false);

    expect($result)->toBeTrue()
        ->and($saved)->toBe(1)
        ->and($screen->hasError('email'))->toBeFalse()
        ->and($this->feedbackStore->all())->toBe([]);
});

it('rethrows unexpected exceptions and releases the submission guard', function () {
    $screen = new SubmittingFormScreen;
    $screen->email = 'person@example.com';

    expect(fn () => $screen->submit(
        fn () => throw new RuntimeException('boom'),
        'Profile saved',
    ))->toThrow(RuntimeException::class, 'boom')
        ->and($screen->submitting)->toBeFalse()
        ->and($this->feedbackStore->all())->toBe([]);
});
