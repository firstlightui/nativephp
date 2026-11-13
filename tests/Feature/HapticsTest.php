<?php

use FirstlightUI\Elements\Button;
use FirstlightUI\Elements\IconButton;
use FirstlightUI\Elements\Segmented;
use FirstlightUI\Elements\SwitchControl;
use FirstlightUI\Facades\Feedback;
use FirstlightUI\Facades\Haptics;
use FirstlightUI\Feedback\FeedbackManager;
use FirstlightUI\Feedback\FeedbackStore;
use FirstlightUI\Haptics\Haptic;
use FirstlightUI\Haptics\HapticsManager;
use FirstlightUI\NativeComponent;
use Illuminate\Container\Container;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Testing\FakeBridge;
use PHPUnit\Framework\AssertionFailedError;

require_once __DIR__.'/../../vendor/nativephp/mobile/src/jump_bridge_functions.php';

class HapticScreen extends NativeComponent
{
    public string $email = '';

    /** @var array<string, string> */
    protected array $rules = ['email' => 'required|email'];

    public function save(): void
    {
        Feedback::success('Saved')->send();
    }

    public function saveAndFail(): void
    {
        Feedback::success('Draft kept')->send();
        $this->validate();
    }

    public function failQuietly(): void
    {
        Feedback::danger('Not saved')->withoutHaptic()->send();
    }

    public function inform(): void
    {
        Feedback::message('Nothing changed')->send();
    }

    public function warnTwice(): void
    {
        Haptics::warning();
        Haptics::success();
        Haptics::warning();
    }

    public function tapKey(): void
    {
        Haptics::play('keypress');
        Haptics::success();
    }

    public function checkEmail(): void
    {
        $this->validateOnly('email');
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

/** Dispatches a press (type 0), live change (type 2) or submit (type 4) to $method. */
function hapticInteraction(HapticScreen $screen, string $method, int $type = 0): void
{
    $id = $screen->bootCallbacks()->register($method);
    $screen->dispatchEvent(['type' => $type, 'callback_id' => $id, 'text' => '']);
}

beforeEach(function () {
    $this->previousContainer = Container::getInstance();
    $container = new Container;
    Container::setInstance($container);
    Haptics::setFacadeApplication($container);
    Feedback::setFacadeApplication($container);
    Haptics::clearResolvedInstances();
    $container->singleton(HapticsManager::class);
    $container->instance(FeedbackManager::class, new FeedbackManager(new FeedbackStore));
    $this->bridge = FakeBridge::enable();
});

afterEach(function () {
    FakeBridge::disable();
    Haptics::clearResolvedInstances();
    Container::setInstance($this->previousContainer);
    Haptics::setFacadeApplication($this->previousContainer);
    Feedback::setFacadeApplication($this->previousContainer);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function playedHaptics(FakeBridge $bridge): array
{
    return array_map(fn (array $call): array => $call['params'], $bridge->callsTo(HapticsManager::PLAY));
}

it('sends each haptic outside an interaction straight to the bridge', function () {
    Haptics::success();
    Haptics::play(Haptic::Keypress);
    Haptics::play('threshold-activate');
    Haptics::play('none');
    Haptics::impact('heavy', 0.4);

    expect(playedHaptics($this->bridge))->toBe([
        ['haptic' => 'success'],
        ['haptic' => 'keypress'],
        ['haptic' => 'threshold-activate'],
        ['impact' => 'heavy', 'intensity' => 0.4],
    ]);
});

it('rejects unknown haptics, impact weights and intensities', function (Closure $call, string $message) {
    expect($call)->toThrow(InvalidArgumentException::class, $message);
    expect(playedHaptics($this->bridge))->toBe([]);
})->with([
    'unknown haptic' => [fn () => Haptics::play('buzz'), 'Unknown haptic [buzz]'],
    'unknown weight' => [fn () => Haptics::impact('huge'), 'Unknown impact weight [huge]'],
    'intensity above 1' => [fn () => Haptics::impact('light', 1.5), 'between 0 and 1'],
    'negative intensity' => [fn () => Haptics::impact('light', -0.1), 'between 0 and 1'],
]);

it('plays the tone of Feedback a tap sends, once the interaction ends', function () {
    hapticInteraction(new HapticScreen, 'save');

    expect(playedHaptics($this->bridge))->toBe([['haptic' => 'success']]);
});

it('keeps Feedback silent when no user interaction sent it', function () {
    Feedback::success('Back online')->send();
    Feedback::danger('Sync failed')->send();

    expect(playedHaptics($this->bridge))->toBe([]);
});

it('plays nothing for a neutral message or a Feedback sent without its haptic', function (string $method) {
    hapticInteraction(new HapticScreen, $method);

    expect(playedHaptics($this->bridge))->toBe([]);
})->with(['inform', 'failQuietly']);

it('plays one haptic per interaction, the strongest outcome', function () {
    $screen = new HapticScreen;

    // A success Feedback, then a failed validate() on submit: error wins.
    hapticInteraction($screen, 'saveAndFail', type: 4);
    // Warning outranks success no matter the order.
    hapticInteraction($screen, 'warnTwice');

    expect(playedHaptics($this->bridge))->toBe([['haptic' => 'error'], ['haptic' => 'warning']]);
});

it('plays touch haptics at once and holds only outcomes for the end', function () {
    hapticInteraction(new HapticScreen, 'tapKey');

    expect(playedHaptics($this->bridge))->toBe([['haptic' => 'keypress'], ['haptic' => 'success']]);
});

it('buzzes a rejected submission but not live revalidation while typing', function () {
    $screen = new HapticScreen;

    hapticInteraction($screen, 'checkEmail', type: 2);
    expect(playedHaptics($this->bridge))->toBe([]);

    hapticInteraction($screen, 'checkEmail', type: 0);
    expect(playedHaptics($this->bridge))->toBe([['haptic' => 'error']]);
});

it('treats a test harness call() as an interaction', function () {
    (new HapticScreen)->__invokeInteraction('save', []);

    expect(playedHaptics($this->bridge))->toBe([['haptic' => 'success']]);
});

it('stores the on or off choice natively', function () {
    Haptics::setEnabled(false);

    expect($this->bridge->callsTo(HapticsManager::SET_ENABLED))->toBe([
        ['method' => HapticsManager::SET_ENABLED, 'params' => ['enabled' => false]],
    ]);
});

it('records haptics with the fake, after the same interaction rules', function () {
    $fake = Haptics::fake();

    hapticInteraction(new HapticScreen, 'saveAndFail', type: 4);
    Haptics::impact('soft', 0.5);
    Haptics::setEnabled(true);

    $fake->assertPlayed(Haptic::Error, times: 1);
    $fake->assertNotPlayed('success');
    expect($fake->played())->toBe([['haptic' => 'error'], ['impact' => 'soft', 'intensity' => 0.5]])
        ->and($fake->enabledSetting())->toBeTrue()
        ->and(playedHaptics($this->bridge))->toBe([])
        ->and(fn () => $fake->assertNothingPlayed())->toThrow(AssertionFailedError::class)
        ->and(fn () => $fake->assertPlayed('warning'))->toThrow(AssertionFailedError::class);
});

it('publishes the haptic a button plays on press', function (string $class, array $attributes) {
    ElementRegistry::register('firstlight.haptic-subject', $class);
    NativeElementCollector::leaf('firstlight.haptic-subject', $attributes + ['haptic' => 'keypress']);

    expect(NativeElementCollector::collect()->toArray(new CallbackRegistry)['props']['haptic'])->toBe('keypress');
})->with([
    'button' => [Button::class, ['label' => '1', '_press' => 'pressDigit']],
    'icon button' => [IconButton::class, ['icon' => 'backspace', 'a11y-label' => 'Delete last digit', '_press' => 'backspace']],
]);

it('lets controls with a default haptic only turn it off', function () {
    expect(SwitchControl::make()->label('Alerts')->haptic('none')->toArray(new CallbackRegistry)['props']['haptic'])->toBe('none')
        ->and(fn () => SwitchControl::make()->haptic('success'))
        ->toThrow(InvalidArgumentException::class, '<firstlight:switch> plays its platform\'s own haptics; `haptic` accepts only `none`.')
        ->and(fn () => (new Segmented)->applyAttributes(['haptic' => 'tick', 'options' => ['a' => 'A']]))
        ->toThrow(InvalidArgumentException::class, '<firstlight:segmented>')
        ->and(fn () => Button::make('Go')->haptic('rumble'))
        ->toThrow(InvalidArgumentException::class, 'Unknown haptic [rumble]');
});
