<?php

use FirstlightUI\Authorization\GateEvaluator;
use FirstlightUI\Concerns\AuthorizesActions;
use FirstlightUI\Concerns\DestroysListItems;
use FirstlightUI\Concerns\PaginatesLists;
use FirstlightUI\Feedback\FeedbackManager;
use FirstlightUI\Feedback\FeedbackStore;
use FirstlightUI\Feedback\FeedbackTone;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use InvalidArgumentException;
use Native\Mobile\Edge\NativeComponent;
use RuntimeException;
use stdClass;

final class DeletablePost
{
    public function __construct(
        public int $id,
        public string $title,
        public bool $deleted = false,
    ) {}

    public function getKey(): int
    {
        return $this->id;
    }

    public function delete(): void
    {
        $this->deleted = true;
    }
}

final class DestroyingListScreen extends NativeComponent
{
    use AuthorizesActions;
    use DestroysListItems;
    use PaginatesLists;

    public function __construct(
        private readonly ?GateEvaluator $evaluator = null,
    ) {}

    protected function actionGateEvaluator(): GateEvaluator
    {
        return $this->evaluator ?? new GateEvaluator(fn (): bool => true);
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

it('authorizes, opens confirmation, destroys by stable key, and republishes the list', function () {
    $first = new DeletablePost(1, 'Alpha');
    $second = new DeletablePost(2, 'Beta');
    $screen = new DestroyingListScreen;
    $screen->listItems = [$first, $second];

    expect($screen->requestDestructiveListAction(2))->toBeTrue()
        ->and($screen->confirmingListDestruction)->toBeTrue()
        ->and($screen->pendingListDestructionKey)->toBe(2);

    expect($screen->confirmDestructiveListAction(
        destroy: fn (DeletablePost $post): mixed => $post->delete(),
        successMessage: 'Post deleted',
    ))->toBeTrue()
        ->and($second->deleted)->toBeTrue()
        ->and($first->deleted)->toBeFalse()
        ->and($screen->listItems)->toBe([$first])
        ->and($screen->confirmingListDestruction)->toBeFalse()
        ->and($screen->pendingListDestructionKey)->toBeNull()
        ->and($this->feedbackStore->all())->toHaveCount(1)
        ->and($this->feedbackStore->all()[0]->tone)->toBe(FeedbackTone::Success)
        ->and($this->feedbackStore->all()[0]->message)->toBe('Post deleted');
});

it('matches string and integer keys without using renderer indexes', function () {
    $post = new DeletablePost(7, 'Gamma');
    $screen = new DestroyingListScreen;
    $screen->listItems = [$post];

    expect($screen->requestDestructiveListAction('7'))->toBeTrue()
        ->and($screen->pendingListDestructionKey)->toBe('7');

    expect($screen->confirmDestructiveListAction(fn (DeletablePost $item) => $item->delete()))
        ->toBeTrue()
        ->and($screen->listItems)->toBe([])
        ->and($post->deleted)->toBeTrue();
});

it('denies opening confirmation when authorization fails', function () {
    $post = new DeletablePost(3, 'Blocked');
    $screen = new DestroyingListScreen(new GateEvaluator(fn (): bool => false));
    $screen->listItems = [$post];

    expect($screen->requestDestructiveListAction(3))->toBeFalse()
        ->and($screen->confirmingListDestruction)->toBeFalse()
        ->and($screen->pendingListDestructionKey)->toBeNull()
        ->and($this->feedbackStore->all())->toHaveCount(1)
        ->and($this->feedbackStore->all()[0]->tone)->toBe(FeedbackTone::Danger);
});

it('cancels confirmation without destroying', function () {
    $post = new DeletablePost(4, 'Keep');
    $screen = new DestroyingListScreen;
    $screen->listItems = [$post];

    $screen->requestDestructiveListAction(4);
    $screen->cancelDestructiveListAction();

    expect($screen->confirmingListDestruction)->toBeFalse()
        ->and($screen->pendingListDestructionKey)->toBeNull()
        ->and($screen->listItems)->toBe([$post])
        ->and($post->deleted)->toBeFalse()
        ->and($this->feedbackStore->all())->toBe([]);
});

it('returns false when the pending key is missing on confirm', function () {
    $screen = new DestroyingListScreen;

    expect($screen->confirmDestructiveListAction(fn () => null))->toBeFalse()
        ->and($this->feedbackStore->all())->toBe([]);
});

it('returns false when the keyed item is no longer in the list', function () {
    $screen = new DestroyingListScreen;
    $screen->listItems = [new DeletablePost(5, 'Gone')];

    $screen->requestDestructiveListAction(5);
    $screen->listItems = [];

    expect($screen->confirmDestructiveListAction(fn () => null))->toBeFalse()
        ->and($screen->confirmingListDestruction)->toBeFalse()
        ->and($this->feedbackStore->all())->toBe([]);
});

it('re-authorizes on confirm and skips destroy when denied', function () {
    $allowed = true;
    $post = new DeletablePost(6, 'Race');
    $screen = new DestroyingListScreen(new GateEvaluator(function () use (&$allowed): bool {
        return $allowed;
    }));
    $screen->listItems = [$post];

    expect($screen->requestDestructiveListAction(6))->toBeTrue();

    $allowed = false;

    expect($screen->confirmDestructiveListAction(
        destroy: fn (DeletablePost $item) => $item->delete(),
        successMessage: 'Deleted',
    ))->toBeFalse()
        ->and($post->deleted)->toBeFalse()
        ->and($screen->listItems)->toBe([$post])
        ->and($this->feedbackStore->all())->toHaveCount(1)
        ->and($this->feedbackStore->all()[0]->tone)->toBe(FeedbackTone::Danger);
});

it('rethrows destroy failures after closing confirmation state', function () {
    $post = new DeletablePost(8, 'Boom');
    $screen = new DestroyingListScreen;
    $screen->listItems = [$post];

    $screen->requestDestructiveListAction(8);

    expect(fn () => $screen->confirmDestructiveListAction(
        fn () => throw new RuntimeException('storage failed'),
    ))->toThrow(RuntimeException::class, 'storage failed')
        ->and($screen->confirmingListDestruction)->toBeFalse()
        ->and($screen->pendingListDestructionKey)->toBeNull()
        ->and($screen->listItems)->toBe([$post]);
});

it('rejects items without a stable key', function () {
    $screen = new DestroyingListScreen;
    $screen->listItems = [new stdClass];

    expect(fn () => $screen->requestDestructiveListAction(1))
        ->toThrow(InvalidArgumentException::class, 'stable key');
});

it('resolves array items by id key', function () {
    $screen = new DestroyingListScreen;
    $screen->listItems = [
        ['id' => 10, 'title' => 'Array row'],
        ['id' => 11, 'title' => 'Other'],
    ];
    $destroyed = null;

    $screen->requestDestructiveListAction(10);
    $screen->confirmDestructiveListAction(function (array $item) use (&$destroyed): void {
        $destroyed = $item;
    });

    expect($destroyed)->toBe(['id' => 10, 'title' => 'Array row'])
        ->and($screen->listItems)->toBe([
            ['id' => 11, 'title' => 'Other'],
        ]);
});

it('exposes destructive list actions on Firstlight NativeComponent', function () {
    $screen = new class extends FirstlightUI\NativeComponent
    {
        protected function actionGateEvaluator(): GateEvaluator
        {
            return new GateEvaluator(fn (): bool => true);
        }
    };
    $screen->listItems = [new DeletablePost(9, 'Native')];

    expect($screen->requestDestructiveListAction(9))->toBeTrue()
        ->and($screen->confirmDestructiveListAction(fn (DeletablePost $post) => $post->delete()))
        ->toBeTrue()
        ->and($screen->listItems)->toBe([]);
});
