<?php

use FirstlightUI\Events\FeedbackActionPressed;
use FirstlightUI\Events\FeedbackDismissed;
use FirstlightUI\Feedback\FeedbackDismissReason;
use FirstlightUI\Feedback\FeedbackManager;
use FirstlightUI\Feedback\FeedbackRecord;
use FirstlightUI\Feedback\FeedbackStore;
use FirstlightUI\Feedback\FeedbackTone;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;

it('queues semantic feedback and returns a generated stable id', function () {
    $store = new FeedbackStore;
    $id = (new FeedbackManager($store))->success('Appointment saved')->send();

    expect($id)->toBeString()->not->toBe('')
        ->and($store->all())->toHaveCount(1)
        ->and($store->all()[0]->id)->toBe($id)
        ->and($store->all()[0]->tone)->toBe(FeedbackTone::Success)
        ->and($store->all()[0]->hold)->toBeFalse();
});

it('updates a pending id in place without moving it', function () {
    $store = new FeedbackStore;
    $feedback = new FeedbackManager($store);
    $feedback->message('First')->id('first')->send();
    $feedback->message('Second')->id('second')->send();
    $feedback->warning('Updated')->id('first')->action('Retry', 'retry')->hold()->send();

    expect(array_map(fn ($item) => $item->id, $store->all()))->toBe(['first', 'second'])
        ->and($store->all()[0]->message)->toBe('Updated')
        ->and($store->all()[0]->actionKey)->toBe('retry')
        ->and($store->all()[0]->hold)->toBeTrue();
});

it('removes programmatic feedback without an application event', function () {
    $previousContainer = Container::getInstance();
    $container = new Container;
    $dispatcher = new Dispatcher($container);
    $container->instance('events', $dispatcher);
    Container::setInstance($container);

    $observedEvents = [];
    $dispatcher->listen(FeedbackActionPressed::class, function (FeedbackActionPressed $event) use (&$observedEvents): void {
        $observedEvents[] = $event;
    });
    $dispatcher->listen(FeedbackDismissed::class, function (FeedbackDismissed $event) use (&$observedEvents): void {
        $observedEvents[] = $event;
    });
    $dispatcher->dispatch(new FeedbackActionPressed('probe', 'probe'));

    expect($observedEvents)->toHaveCount(1)
        ->and($observedEvents[0])->toBeInstanceOf(FeedbackActionPressed::class);

    $observedEvents = [];

    $store = new FeedbackStore;
    $feedback = new FeedbackManager($store);
    $feedback->danger('Connection failed')->id('failure')->send();

    try {
        expect($feedback->dismiss('failure'))->toBeTrue()
            ->and($feedback->dismiss('failure'))->toBeFalse()
            ->and($store->all())->toBe([])
            ->and($observedEvents)->toBe([]);
    } finally {
        Container::setInstance($previousContainer);
    }
});

it('rejects invalid direct feedback records', function (
    string $id,
    string $message,
    ?string $actionLabel,
    ?string $actionKey,
    string $expectedMessage,
) {
    expect(fn () => new FeedbackRecord(
        $id,
        $message,
        FeedbackTone::Default,
        false,
        $actionLabel,
        $actionKey,
    ))->toThrow(InvalidArgumentException::class, $expectedMessage);
})->with([
    'blank id' => [' ', 'Saved', null, null, 'non-empty `id`'],
    'blank message' => ['saved', "\t", null, null, 'non-empty `message`'],
    'action label without key' => ['saved', 'Saved', 'Undo', null, 'action label and key'],
    'action key without label' => ['saved', 'Saved', null, 'undo', 'action label and key'],
    'blank action label' => ['saved', 'Saved', ' ', 'undo', 'non-empty `action label`'],
    'blank action key' => ['saved', 'Saved', 'Undo', "\n", 'non-empty `action key`'],
]);

it('preserves valid direct feedback record values', function () {
    $record = new FeedbackRecord(
        ' saved ',
        ' Saved ',
        FeedbackTone::Success,
        true,
        ' Undo ',
        ' undo ',
    );

    expect($record->id)->toBe(' saved ')
        ->and($record->message)->toBe(' Saved ')
        ->and($record->actionLabel)->toBe(' Undo ')
        ->and($record->actionKey)->toBe(' undo ');
});

it('uses the exact supported semantic tones', function () {
    expect(FeedbackTone::cases())->toBe([
        FeedbackTone::Default,
        FeedbackTone::Success,
        FeedbackTone::Warning,
        FeedbackTone::Danger,
    ])->and(array_map(fn (FeedbackTone $tone) => $tone->value, FeedbackTone::cases()))->toBe([
        'default',
        'success',
        'warning',
        'danger',
    ]);
});

it('creates each feedback tone through the semantic manager methods', function (string $method, string $tone) {
    $store = new FeedbackStore;
    $feedback = new FeedbackManager($store);

    $feedback->{$method}('Status changed')->id($method)->send();

    expect($store->all()[0]->tone->value)->toBe($tone);
})->with([
    'default' => ['message', 'default'],
    'success' => ['success', 'success'],
    'warning' => ['warning', 'warning'],
    'danger' => ['danger', 'danger'],
]);

it('keeps builder instances immutable when applying feedback options', function () {
    $store = new FeedbackStore;
    $pending = (new FeedbackManager($store))->message('Original');

    $pending->id('first')->action('Retry', 'retry')->hold()->send();
    $pending->id('second')->send();

    expect($store->all())->toHaveCount(2)
        ->and($store->all()[0]->id)->toBe('first')
        ->and($store->all()[0]->actionLabel)->toBe('Retry')
        ->and($store->all()[0]->actionKey)->toBe('retry')
        ->and($store->all()[0]->hold)->toBeTrue()
        ->and($store->all()[1]->id)->toBe('second')
        ->and($store->all()[1]->actionLabel)->toBeNull()
        ->and($store->all()[1]->actionKey)->toBeNull()
        ->and($store->all()[1]->hold)->toBeFalse();
});

it('generates a unique id for each feedback item that omits one', function () {
    $store = new FeedbackStore;
    $feedback = new FeedbackManager($store);

    $first = $feedback->message('First')->send();
    $second = $feedback->message('Second')->send();

    expect($first)->not->toBe($second)
        ->and(array_map(fn ($item) => $item->id, $store->all()))->toBe([$first, $second]);
});

it('rejects blank authored feedback fields', function (Closure $author, string $message) {
    expect($author)->toThrow(InvalidArgumentException::class, $message);
})->with([
    'blank message' => [fn () => (new FeedbackManager(new FeedbackStore))->message(' '), 'non-empty `message`'],
    'blank id' => [fn () => (new FeedbackManager(new FeedbackStore))->message('Message')->id("\t"), 'non-empty `id`'],
    'blank action label' => [fn () => (new FeedbackManager(new FeedbackStore))->message('Message')->action(' ', 'retry'), 'non-empty `action label`'],
    'blank action key' => [fn () => (new FeedbackManager(new FeedbackStore))->message('Message')->action('Retry', "\n"), 'non-empty `action key`'],
]);

it('authors action labels and keys together while preserving nonblank authored text', function () {
    $store = new FeedbackStore;

    (new FeedbackManager($store))->message(' Saved ')->action(' Undo ', ' undo ')->send();

    expect($store->all()[0]->message)->toBe(' Saved ')
        ->and($store->all()[0]->actionLabel)->toBe(' Undo ')
        ->and($store->all()[0]->actionKey)->toBe(' undo ');
});

it('uses the exact dismissal reasons and event payloads', function () {
    $action = new FeedbackActionPressed('saved', 'undo');
    $dismissed = new FeedbackDismissed('saved', FeedbackDismissReason::Action);

    expect(FeedbackDismissReason::cases())->toBe([
        FeedbackDismissReason::Timeout,
        FeedbackDismissReason::Manual,
        FeedbackDismissReason::Action,
    ])->and(array_map(fn (FeedbackDismissReason $reason) => $reason->value, FeedbackDismissReason::cases()))->toBe([
        'timeout',
        'manual',
        'action',
    ])->and($action->id)->toBe('saved')
        ->and($action->actionKey)->toBe('undo')
        ->and($dismissed->id)->toBe('saved')
        ->and($dismissed->reason)->toBe(FeedbackDismissReason::Action);
});
