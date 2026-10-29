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

require_once __DIR__.'/../Support/ScreenHarness.php';

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
        $this->dispatchUiEvent($event);
    }
}

class ValidationBladeScreen extends ValidatingScreen
{
    public function render(): \Illuminate\View\View
    {
        return view('native.validation');
    }

    public function validationFrame(string $method): \Native\Mobile\Edge\Element
    {
        return in_array($method, ['view', 'partial'], true)
            ? $this->{$method}('validation')
            : $this->{$method}(view('native.validation'));
    }
}

class DeferredValidationScreen extends ValidatingScreen
{
    public function render(): \Native\Mobile\Edge\Element
    {
        return $this->response(\FirstlightUI\Elements\Button::make('Validate')->onPress('deferValidation'));
    }

    public function deferValidation(): void
    {
        $this->dispatch('validate-email')->self();
    }

    #[\Native\Mobile\Attributes\On('validate-email')]
    public function checkEmail(): void
    {
        parent::checkEmail();
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

it('handles deferred listener validation without losing unrelated errors or its announcement', function () {
    $state = bootFirstlightScreenHarness(new \Illuminate\Container\Container, __DIR__);
    ElementRegistry::register('firstlight.checkbox', Checkbox::class);

    try {
        $screen = \Native\Mobile\Testing\Native::test(DeferredValidationScreen::class);
        $screen->instance()->addError('accepted', 'Keep this');

        $screen->press('deferValidation');
        [$checkbox, $email] = renderAlertFrame($screen->instance(), signupLeaves())['children'];

        expect($screen->instance()->getErrorBag()->first('accepted'))->toBe('Keep this')
            ->and($email['props']['error'])->toBe('The email field is required.')
            ->and($checkbox['props']['validation_announcement'])->toBe('There are 2 errors. Keep this The email field is required.');
    } finally {
        tearDownFirstlightScreenHarness($state);
    }
});

it('handles validation thrown directly from a drag-end callback', function () {
    $screen = new class extends ValidatingScreen
    {
        public function finishDrag(float $x, float $y): void
        {
            throw new ValidationException($this->standaloneValidatorFactory()->make(
                [], ['email' => 'required'], ['email.required' => "Invalid position: {$x}, {$y}."],
            ));
        }
    };
    $id = $screen->bootCallbacks()->register('finishDrag', 'drag_end');

    $screen->dispatchEvent(['callback_id' => $id, 'type' => 2, 'text' => '12.5,-8']);
    [, $email] = renderAlertFrame($screen, signupLeaves())['children'];

    expect($email['props']['error'])->toBe('Invalid position: 12.5, -8.')
        ->and($email['props']['validation_announcement'])->toBe('Invalid position: 12.5, -8.')
        ->and($email['props'])->not->toHaveKey('validation_focus');
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

function renderAlertFrame(ValidatingScreen $screen, callable $leaves): array
{
    return (new ReflectionClass($screen))
        ->getMethod('withFieldErrorBag')
        ->invoke($screen, function () use ($leaves): array {
            $leaves();

            return NativeElementCollector::collect()->toArray(new CallbackRegistry);
        }, true);
}

function signupLeaves(bool $summaryFirst = false): callable
{
    return function () use ($summaryFirst): void {
        NativeElementCollector::open('column', []);
        if ($summaryFirst) {
            NativeElementCollector::leaf('firstlight.callout', ['error-summary' => true]);
        }
        NativeElementCollector::leaf('firstlight.checkbox', ['label' => 'I agree', 'native:model' => 'accepted', 'value' => false]);
        NativeElementCollector::leaf('firstlight.text-field', ['label' => 'Email', 'native:model.blur' => 'email']);
        NativeElementCollector::close();
    };
}

it('announces every message and focuses the first invalid field in render order after a failed press', function () {
    ElementRegistry::register('firstlight.callout', FirstlightUI\Elements\Callout::class);
    $screen = new ValidatingScreen;
    $screen->dispatchEvent(['callback_id' => $screen->bootCallbacks()->register('save'), 'type' => 0]);

    [$checkbox, $email] = renderAlertFrame($screen, signupLeaves())['children'];

    // Rules list email first; the checkbox renders first, so it takes focus.
    expect($checkbox['props'])->toMatchArray([
        'validation_alert' => $checkbox['props']['validation_alert'],
        'validation_focus' => true,
        'validation_announcement' => 'There are 2 errors. The email field is required. The accepted must be accepted.',
    ])
        ->and($checkbox['props']['validation_alert'])->toBeInt()->toBeGreaterThan(0)
        ->and($email['props'])->not->toHaveKeys(['validation_alert', 'validation_focus', 'validation_announcement'])
        ->and($email['props']['error'])->toBe('The email field is required.');

    // NativePHP may render a frame more than once: every render until the
    // next interaction carries the same revision, so natives act only once.
    [$again] = renderAlertFrame($screen, signupLeaves())['children'];

    expect($again['props'])->toMatchArray([
        'validation_alert' => $checkbox['props']['validation_alert'],
        'validation_focus' => true,
    ]);

    // The next interaction clears it even when it adds no message.
    $screen->email = 'clinician@example.com';
    $screen->dispatchEvent(['callback_id' => $screen->bootCallbacks()->register('checkEmail'), 'type' => 2]);
    [$after] = renderAlertFrame($screen, signupLeaves())['children'];

    expect($after['props'])->not->toHaveKey('validation_alert')
        ->and($after['props']['error'])->toBe('The accepted must be accepted.');
});

it('lets a leading summary Callout carry the announcement while the first field takes focus', function () {
    ElementRegistry::register('firstlight.callout', FirstlightUI\Elements\Callout::class);
    $screen = new ValidatingScreen;
    $screen->dispatchEvent(['callback_id' => $screen->bootCallbacks()->register('save'), 'type' => 4]);

    [$summary, $checkbox] = renderAlertFrame($screen, signupLeaves(summaryFirst: true))['children'];

    expect($summary['props'])->toMatchArray([
        'message' => "There are 2 errors.\nThe email field is required.\nThe accepted must be accepted.",
        'tone' => 'danger',
        'validation_announcement' => 'There are 2 errors. The email field is required. The accepted must be accepted.',
    ])
        ->and($summary['props'])->not->toHaveKey('validation_focus')
        ->and($checkbox['props']['validation_focus'])->toBeTrue()
        ->and($checkbox['props']['validation_alert'])->toBe($summary['props']['validation_alert'])
        ->and($checkbox['props'])->not->toHaveKey('validation_announcement');
});

it('announces only the field a property sync failed, without moving focus', function () {
    $screen = new ValidatingScreen;
    $screen->addError('accepted', 'Earlier message');
    $screen->dispatchEvent(['callback_id' => $screen->bootCallbacks()->register('checkEmail'), 'type' => 2]);

    [$checkbox, $email] = renderAlertFrame($screen, signupLeaves())['children'];

    // The earlier checkbox error renders first but is not what just failed.
    expect($checkbox['props']['error'])->toBe('Earlier message')
        ->and($checkbox['props'])->not->toHaveKey('validation_alert')
        ->and($email['props'])->toMatchArray(['validation_announcement' => 'The email field is required.'])
        ->and($email['props'])->not->toHaveKey('validation_focus');
});

it('raises a new alert for a repeated identical failure and none for a passing interaction', function () {
    $screen = new ValidatingScreen;
    $save = $screen->bootCallbacks()->register('save');

    $screen->dispatchEvent(['callback_id' => $save, 'type' => 0]);
    $first = renderAlertFrame($screen, signupLeaves())['children'][0]['props']['validation_alert'];
    $screen->dispatchEvent(['callback_id' => $save, 'type' => 0]);
    $second = renderAlertFrame($screen, signupLeaves())['children'][0]['props']['validation_alert'];

    $screen->email = 'clinician@example.com';
    $screen->accepted = true;
    $screen->dispatchEvent(['callback_id' => $save, 'type' => 0]);
    $passed = renderAlertFrame($screen, signupLeaves())['children'][0]['props'];

    expect($second)->toBeGreaterThan($first)
        ->and($passed)->not->toHaveKey('validation_alert')
        ->and($passed['error'])->toBe('');
});

it('alerts for addError from a press, such as a server-side check after validation', function () {
    $screen = new class extends ValidatingScreen
    {
        public function register(): void
        {
            $this->addError('email', 'This account is already registered.');
        }
    };
    $screen->dispatchEvent(['callback_id' => $screen->bootCallbacks()->register('register'), 'type' => 0]);

    [, $email] = renderAlertFrame($screen, signupLeaves())['children'];

    expect($email['props'])->toMatchArray([
        'validation_focus' => true,
        'validation_announcement' => 'This account is already registered.',
    ]);
});

it('binds Firstlight fields to upstream validation scopes', function () {
    $tree = \Native\Mobile\Edge\FieldErrors::using(new MessageBag(['email' => 'Upstream message']),
        fn () => collectBoundField('firstlight.text-field', ['label' => 'Email', '_change' => "__syncProperty('email')"]));

    expect($tree['props']['error'])->toBe('Upstream message')
        ->and(FieldErrorBag::current())->toBeNull();
});

it('binds upstream controls to the Firstlight validation scope', function () {
    ElementRegistry::register('outlined_text_input', \Native\Mobile\UI\Elements\OutlinedTextInput::class);

    $tree = FieldErrorBag::using(new MessageBag(['email' => 'Firstlight message']),
        fn () => collectBoundField('outlined_text_input', ['label' => 'Email', '_change' => "__syncProperty('email')"]));

    expect($tree['props'])->toMatchArray(['is_error' => true, 'supporting' => 'Firstlight message'])
        ->and(\Native\Mobile\Edge\FieldErrors::current())->toBeNull();
});

it('restores the parent bag without leaking its announcement into an upstream child scope', function () {
    $screen = new ValidatingScreen;
    $screen->dispatchEvent(['callback_id' => $screen->bootCallbacks()->register('save'), 'type' => 0]);

    $tree = renderAlertFrame($screen, function () {
        \Native\Mobile\Edge\FieldErrors::using(new MessageBag(['email' => 'Child error']), function () {
            NativeElementCollector::open('column', []);
            NativeElementCollector::leaf('firstlight.text-field', ['label' => 'Child', 'error-for' => 'email']);
        });
        NativeElementCollector::leaf('firstlight.text-field', ['label' => 'Parent', 'error-for' => 'email']);
        NativeElementCollector::close();
    });

    expect($tree['children'][0]['props']['error'])->toBe('Child error')
        ->and($tree['children'][0]['props'])->not->toHaveKey('validation_alert')
        ->and($tree['children'][1]['props']['error'])->toBe('The email field is required.')
        ->and($tree['children'][1]['props']['validation_focus'])->toBeTrue();
});

it('shares validation errors across Blade fields, directives, and legacy views', function (string $method) {
    $directory = sys_get_temp_dir().'/firstlight-validation-'.bin2hex(random_bytes(8));
    $files = new \Illuminate\Filesystem\Filesystem;
    $files->makeDirectory($directory.'/native', 0755, true);
    $files->put($directory.'/native/validation.blade.php', <<<'BLADE'
        <native:column>
            <firstlight:text-field label="Firstlight email" native:model="email" />
            <native:outlined-text-input label="Native email" native:model="email" />
            @error('accepted')
                <native:text :text="$message" />
            @enderror
            <native:text :text="$errorBag->first('email')" />
            <firstlight:button label="Save" @press="save" />
        </native:column>
        BLADE);
    $state = bootFirstlightScreenHarness(new \Illuminate\Container\Container, $directory);

    try {
        app('blade.compiler')->component('native-outlined-text-input', \Native\Mobile\UI\Components\OutlinedTextInput::class);
        ElementRegistry::register('outlined_text_input', \Native\Mobile\UI\Elements\OutlinedTextInput::class);
        $screen = \Native\Mobile\Testing\Native::test(ValidationBladeScreen::class)->press('save');
        $tree = $screen->instance()->validationFrame($method)->toArray(new CallbackRegistry);

        expect($tree['children'][0]['props']['error'])->toBe('The email field is required.')
            ->and($tree['children'][1]['props'])->toMatchArray(['is_error' => true, 'supporting' => 'The email field is required.'])
            ->and($tree['children'][2]['props']['text'])->toBe('The accepted must be accepted.')
            ->and($tree['children'][3]['props']['text'])->toBe('The email field is required.')
            ->and(\Native\Mobile\Edge\FieldErrors::current())->toBeNull();
    } finally {
        tearDownFirstlightScreenHarness($state);
        $files->deleteDirectory($directory);
    }
})->with(['view', 'fromView', 'partial', 'fromViewPartial']);
