<?php

use FirstlightUI\Authorization\GateEvaluator;
use FirstlightUI\Concerns\AuthorizesActions;
use FirstlightUI\Feedback\FeedbackManager;
use FirstlightUI\Feedback\FeedbackStore;
use FirstlightUI\Feedback\FeedbackTone;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\Facades\Facade;

final class AuthorizingActionsTestScreen
{
    use AuthorizesActions;

    public function __construct(
        private readonly ?GateEvaluator $evaluator = null,
        private readonly mixed $user = null,
    ) {}

    protected function gateUser(): mixed
    {
        return $this->user;
    }

    protected function actionGateEvaluator(): GateEvaluator
    {
        return $this->evaluator ?? new GateEvaluator;
    }
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

it('evaluates allows and denies with a callable fake gate', function () {
    $post = (object) ['id' => 42];
    $checks = [];
    $evaluator = new GateEvaluator(function (string $ability, array $arguments, mixed $user) use (&$checks): bool {
        $checks[] = [$ability, $arguments, $user];

        return $ability === 'update';
    });
    $screen = new AuthorizingActionsTestScreen($evaluator);

    expect($screen->allows('update', $post))->toBeTrue()
        ->and($screen->denies('delete', $post))->toBeTrue()
        ->and($checks)->toBe([
            ['update', [$post], null],
            ['delete', [$post], null],
        ]);
});

it('uses the Gate facade and an overridden gate user when Gate is bound', function () {
    $user = (object) ['id' => 7];
    $post = (object) ['owner_id' => 7];
    $calls = new ArrayObject;
    $fakeGate = new class($calls)
    {
        private mixed $user = null;

        public function __construct(private readonly ArrayObject $calls) {}

        public function forUser(mixed $user): static
        {
            $gate = clone $this;
            $gate->user = $user;

            return $gate;
        }

        public function allows(string $ability, array $arguments = []): bool
        {
            $this->calls[] = [$ability, $arguments, $this->user];

            return $ability === 'update';
        }
    };

    $this->container->instance(GateContract::class, $fakeGate);

    expect((new AuthorizingActionsTestScreen(user: $user))->allows('update', $post))->toBeTrue()
        ->and($calls->getArrayCopy())->toBe([
            ['update', [$post], $user],
        ]);
});

it('denies safely when Gate is not bound', function () {
    $screen = new AuthorizingActionsTestScreen;

    expect($screen->allows('update', new stdClass))->toBeFalse()
        ->and($screen->denies('update', new stdClass))->toBeTrue();
});

it('sends danger Feedback and returns false when authorization is denied', function () {
    $screen = new AuthorizingActionsTestScreen(
        new GateEvaluator(fn (): bool => false),
    );

    expect($screen->authorize('delete', new stdClass))->toBeFalse()
        ->and($this->feedbackStore->all())->toHaveCount(1)
        ->and($this->feedbackStore->all()[0]->tone)->toBe(FeedbackTone::Danger)
        ->and($this->feedbackStore->all()[0]->message)->toBe('This action is unauthorized.');
});

it('returns true without Feedback when authorization is allowed', function () {
    $screen = new AuthorizingActionsTestScreen(
        new GateEvaluator(fn (): bool => true),
    );

    expect($screen->authorize('update', new stdClass))->toBeTrue()
        ->and($this->feedbackStore->all())->toBe([]);
});
