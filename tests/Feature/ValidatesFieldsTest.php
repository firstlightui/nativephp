<?php

use FirstlightUI\Elements\Checkbox;
use FirstlightUI\Elements\TextField;
use FirstlightUI\NativeComponent;
use FirstlightUI\Validation\FieldErrorBag;
use FirstlightUI\Validation\FieldErrorBinder;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;

class StoreSignupRequest
{
    public function rules(): array
    {
        return ['email' => 'required|email'];
    }

    public function messages(): array
    {
        return ['email.required' => 'Need an email'];
    }

    public function attributes(): array
    {
        return ['email' => 'work email'];
    }
}

class ValidatingScreen extends NativeComponent
{
    public string $email = '';

    public bool $accepted = false;

    public string $startsOn = '2026-08-24';

    public int $quantity = 3;

    /** @var array<string, string> */
    protected array $rules = [
        'email' => 'required|email',
        'accepted' => 'accepted',
        'startsOn' => 'date',
        'quantity' => 'integer|min:1',
    ];

    public function save(): void
    {
        $this->validate();
    }

    public function checkEmail(): void
    {
        $this->validateOnly('email');
    }

    public function explode(): void
    {
        throw new RuntimeException('boom');
    }

    public function bootCallbacks(): CallbackRegistry
    {
        $this->nativeCallbacks = new CallbackRegistry;

        return $this->nativeCallbacks;
    }

    public function dispatchEvent(array $event): void
    {
        $this->dispatch($event);
    }
}

beforeEach(function () {
    FieldErrorBag::reset();
    NativeElementCollector::reset();
    ElementRegistry::reset();
    ElementRegistry::register('firstlight.text-field', TextField::class);
    ElementRegistry::register('firstlight.checkbox', Checkbox::class);
});

afterEach(function () {
    FieldErrorBag::reset();
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function collectBoundField(string $type, array $attributes): array
{
    NativeElementCollector::leaf($type, $attributes);

    return NativeElementCollector::collect()->toArray(new CallbackRegistry);
}

it('maps the first MessageBag message from native:model and compiled sync callbacks', function (array $attrs, string $expected) {
    $tree = FieldErrorBag::using(new MessageBag([
        'email' => ['Enter a valid email.', 'Second message'],
        'accepted' => ['The terms must be accepted.'],
    ]), fn () => collectBoundField('firstlight.text-field', [
        'label' => 'Email',
        ...$attrs,
    ]));

    expect($tree['props']['error'])->toBe($expected);
})->with([
    'native:model' => [['native:model' => 'email'], 'Enter a valid email.'],
    'native:model.live' => [['native:model.live' => 'email'], 'Enter a valid email.'],
    'native:model.blur' => [['native:model.blur' => 'email'], 'Enter a valid email.'],
    'native:model.lazy' => [['native:model.lazy' => 'email'], 'Enter a valid email.'],
    'native:model.debounce' => [['native:model.debounce.300ms' => 'email'], 'Enter a valid email.'],
    'compiled sync' => [['_change' => "__syncProperty('email')"], 'Enter a valid email.'],
    'error-for' => [['error-for' => 'email'], 'Enter a valid email.'],
]);

it('lets error-for select a different rule key than the model name', function () {
    $tree = FieldErrorBag::using(new MessageBag([
        'contact_email' => ['Need a work email.'],
        'email' => ['Wrong key'],
    ]), fn () => collectBoundField('firstlight.text-field', [
        'label' => 'Email',
        'native:model' => 'email',
        'error-for' => 'contact_email',
    ]));

    expect($tree['props']['error'])->toBe('Need a work email.');
});

it('lets an authored error win over the MessageBag', function () {
    $tree = FieldErrorBag::using(new MessageBag([
        'email' => ['From the bag'],
    ]), fn () => collectBoundField('firstlight.text-field', [
        'label' => 'Email',
        'native:model' => 'email',
        'error' => 'Authored message',
    ]));

    expect($tree['props']['error'])->toBe('Authored message');
});

it('fills an empty authored error from the bag', function () {
    $tree = FieldErrorBag::using(new MessageBag([
        'email' => ['From the bag'],
    ]), fn () => collectBoundField('firstlight.text-field', [
        'label' => 'Email',
        'native:model' => 'email',
        'error' => '',
    ]));

    expect($tree['props']['error'])->toBe('From the bag');
});

it('binds bag messages onto checkbox from native:model', function () {
    $tree = FieldErrorBag::using(new MessageBag([
        'accepted' => ['The accepted must be accepted.'],
    ]), fn () => collectBoundField('firstlight.checkbox', [
        'label' => 'I agree',
        'native:model' => 'accepted',
        'value' => false,
    ]));

    expect($tree['props']['error'])->toBe('The accepted must be accepted.');
});

it('leaves the error slot empty when no bag or field name is available', function () {
    expect(FieldErrorBinder::resolveFieldName(['label' => 'Email']))->toBeNull()
        ->and(collectBoundField('firstlight.text-field', [
            'label' => 'Email',
            'native:model' => 'email',
        ])['props']['error'])->toBe('');
});

it('validates public properties and throws a stored ValidationException', function () {
    $screen = new ValidatingScreen;

    expect(fn () => $screen->validate())->toThrow(ValidationException::class)
        ->and($screen->hasError('email'))->toBeTrue()
        ->and($screen->getErrorBag()->first('email'))->toBe('The email field is required.')
        ->and($screen->hasError('accepted'))->toBeTrue();

    $screen->email = 'not-an-email';

    expect(fn () => $screen->validate())->toThrow(ValidationException::class)
        ->and($screen->getErrorBag()->first('email'))->toBe('The email must be a valid email address.');
});

it('returns validated data and clears those keys on success', function () {
    $screen = new ValidatingScreen;
    $screen->addError('email', 'stale');
    $screen->email = 'clinician@example.com';
    $screen->accepted = true;

    $validated = $screen->validate();

    expect($validated)->toMatchArray([
        'email' => 'clinician@example.com',
        'accepted' => true,
        'startsOn' => '2026-08-24',
        'quantity' => 3,
    ])
        ->and($screen->hasError('email'))->toBeFalse()
        ->and($screen->getErrorBag()->isEmpty())->toBeTrue();
});

it('replaces only the requested key with validateOnly', function () {
    $screen = new ValidatingScreen;
    $screen->addError('accepted', 'Keep this');

    expect(fn () => $screen->validateOnly('email'))->toThrow(ValidationException::class)
        ->and($screen->getErrorBag()->first('email'))->toBe('The email field is required.')
        ->and($screen->getErrorBag()->first('accepted'))->toBe('Keep this');

    $screen->email = 'clinician@example.com';
    $screen->validateOnly('email');

    expect($screen->hasError('email'))->toBeFalse()
        ->and($screen->getErrorBag()->first('accepted'))->toBe('Keep this');
});

it('passes boolean accepted and date or number public properties through as PHP types', function () {
    $screen = new ValidatingScreen;
    $screen->email = 'clinician@example.com';
    $screen->accepted = true;
    $screen->startsOn = '2026-01-15';
    $screen->quantity = 8;

    expect($screen->validate())->toMatchArray([
        'email' => 'clinician@example.com',
        'accepted' => true,
        'startsOn' => '2026-01-15',
        'quantity' => 8,
    ]);

    $screen->startsOn = 'not-a-date';
    $screen->quantity = 0;

    expect(fn () => $screen->validate())->toThrow(ValidationException::class)
        ->and($screen->hasError('startsOn'))->toBeTrue()
        ->and($screen->hasError('quantity'))->toBeTrue();
});

it('sources rules and messages from a Form Request class name', function () {
    $screen = new ValidatingScreen;

    expect(fn () => $screen->validate(StoreSignupRequest::class))->toThrow(ValidationException::class)
        ->and($screen->getErrorBag()->first('email'))->toBe('Need an email');

    $screen->email = 'clinician@example.com';

    expect($screen->validate(StoreSignupRequest::class))->toMatchArray([
        'email' => 'clinician@example.com',
    ]);
});

it('supports addError, hasError, and resetValidation', function () {
    $screen = new ValidatingScreen;
    $screen->addError('email', 'Nope');

    expect($screen->hasError('email'))->toBeTrue()
        ->and($screen->getErrorBag()->first('email'))->toBe('Nope');

    $screen->resetValidation('email');

    expect($screen->hasError('email'))->toBeFalse();

    $screen->addError('email', 'Again');
    $screen->addError('accepted', 'Also');
    $screen->resetValidation();

    expect($screen->getErrorBag()->isEmpty())->toBeTrue();
});

it('swallows ValidationException from dispatch and stores the bag', function () {
    $screen = new ValidatingScreen;
    $registry = $screen->bootCallbacks();
    $id = $registry->register('save');

    $screen->dispatchEvent(['callback_id' => $id, 'type' => 1]);

    expect($screen->hasError('email'))->toBeTrue()
        ->and($screen->getErrorBag()->first('email'))->toBe('The email field is required.');
});

it('rethrows a non-validation exception from dispatch', function () {
    $screen = new ValidatingScreen;
    $registry = $screen->bootCallbacks();
    $id = $registry->register('explode');

    expect(fn () => $screen->dispatchEvent(['callback_id' => $id, 'type' => 1]))
        ->toThrow(RuntimeException::class, 'boom');
});

it('exposes the current bag to fields while view hooks run', function () {
    $screen = new ValidatingScreen;
    $screen->addError('email', 'Enter a valid email.');

    $tree = (new ReflectionClass($screen))
        ->getMethod('withFieldErrorBag')
        ->invoke($screen, fn () => collectBoundField('firstlight.text-field', [
            'label' => 'Email',
            'native:model.blur' => 'email',
        ]));

    expect($tree['props']['error'])->toBe('Enter a valid email.')
        ->and(FieldErrorBag::current())->toBeNull();
});
