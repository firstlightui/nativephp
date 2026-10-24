<?php

namespace FirstlightUI\Testing;

use FirstlightUI\Facades\Feedback;
use FirstlightUI\Feedback\FeedbackFake;
use FirstlightUI\Feedback\FeedbackRecord;
use FirstlightUI\Feedback\FeedbackTone;
use Generator;
use Native\Mobile\Testing\TestableComponent;
use PHPUnit\Framework\Assert;

/**
 * Firstlight vocabulary for NativePHP's screen test harness.
 *
 * Registers macros on `Native\Mobile\Testing\TestableComponent`, so they run
 * against the tree `Native::test()` published and dispatch through the same
 * callback ids and wire event types the Firstlight renderers send:
 *
 *     ScreenAssertions::register(); // tests/Pest.php
 *
 *     Native::test(SignupScreen::class)
 *         ->input('email', 'not-an-email')
 *         ->tap('Create account')
 *         ->assertFieldError('email', 'The email must be a valid email address.');
 */
final class ScreenAssertions
{
    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;
        self::registerMacros();
        self::registerExpectations();
    }

    private static function registerMacros(): void
    {
        /*
         * Macros run bound to the harness at TestableComponent scope, so they
         * reuse its tree, registries, and dispatch path instead of copying them.
         */

        TestableComponent::macro('field', function (string $target): array {
            /** @var TestableComponent $this */
            $node = $this->nodeByRef($this->tree(), $target);

            if ($node === null) {
                $ids = [];
                foreach ($this->componentRegistries() as $registry) {
                    foreach ($registry->expressions() as $expression => $id) {
                        if (str_starts_with($expression, "__syncProperty('{$target}'")) {
                            $ids[] = $id;
                        }
                    }
                }

                foreach (ScreenAssertions::nodes($this->tree()) as $candidate) {
                    if (array_intersect(ScreenAssertions::callbackIds($candidate), $ids) !== []) {
                        $node = $candidate;
                        break;
                    }
                }
            }

            Assert::assertNotNull(
                $node,
                "No element with ref or native:model binding [{$target}] found in the rendered tree."
            );

            return $node;
        });

        TestableComponent::macro('assertFieldError', function (string $target, ?string $message = null): TestableComponent {
            /** @var TestableComponent $this */
            $error = (string) ($this->field($target)['props']['error'] ?? '');

            Assert::assertNotSame('', $error, "Field [{$target}] shows no validation error.");

            if ($message !== null) {
                Assert::assertSame($message, $error, "Field [{$target}] shows a different validation error.");
            }

            return $this;
        });

        TestableComponent::macro('assertNoFieldError', function (string $target): TestableComponent {
            /** @var TestableComponent $this */
            $error = (string) ($this->field($target)['props']['error'] ?? '');

            Assert::assertSame('', $error, "Field [{$target}] unexpectedly shows [{$error}].");

            return $this;
        });

        TestableComponent::macro('assertHasErrors', function (string|array $keys): TestableComponent {
            /** @var TestableComponent $this */
            $bag = ScreenAssertions::errorBag($this->instance());

            foreach ((array) $keys as $key => $message) {
                if (is_int($key)) {
                    Assert::assertTrue($bag->has($message), "No validation error for [{$message}]. Errors: ".json_encode($bag->toArray()));

                    continue;
                }

                Assert::assertContains($message, $bag->get($key), "Validation errors for [{$key}] do not include [{$message}].");
            }

            return $this;
        });

        TestableComponent::macro('assertHasNoErrors', function (string|array|null $keys = null): TestableComponent {
            /** @var TestableComponent $this */
            $bag = ScreenAssertions::errorBag($this->instance());

            if ($keys === null) {
                Assert::assertTrue($bag->isEmpty(), 'Unexpected validation errors: '.json_encode($bag->toArray()));

                return $this;
            }

            foreach ((array) $keys as $key) {
                Assert::assertFalse($bag->has($key), "Unexpected validation error for [{$key}]: ".$bag->first($key));
            }

            return $this;
        });

        TestableComponent::macro('choose', function (string $target, string|int $value): TestableComponent {
            /** @var TestableComponent $this */
            $this->startInteraction();
            $node = $this->field($target);
            $props = $node['props'] ?? [];
            $wireValue = (string) $value;

            if (! isset($props['option_callbacks'])) {
                $callbackId = $props['on_change'] ?? null;
                Assert::assertIsInt($callbackId, "Element [{$target}] has no option or change callback.");

                return $this->dispatchUiEvent([
                    'type' => TestableComponent::EVENT_SELECT_CHANGE,
                    'callback_id' => $callbackId,
                    'value' => $wireValue,
                ]);
            }

            $index = array_search($wireValue, array_map('strval', $props['option_values'] ?? []), true);
            Assert::assertNotFalse($index, "Element [{$target}] has no option with value [{$wireValue}].");
            Assert::assertNotSame('0', (string) ($props['option_enabled'][$index] ?? '1'), "Option [{$wireValue}] of [{$target}] is disabled.");

            $callbackId = (int) ($props['option_callbacks'][$index] ?? 0);
            Assert::assertNotSame(0, $callbackId, "Option [{$wireValue}] of [{$target}] cannot be chosen: the control is disabled or the option is already selected.");

            return $this->dispatchUiEvent(['type' => TestableComponent::EVENT_PRESS, 'callback_id' => $callbackId]);
        });

        TestableComponent::macro('listItem', function (string $headline): array {
            /** @var TestableComponent $this */
            foreach (ScreenAssertions::nodes($this->tree()) as $node) {
                if (($node['type'] ?? null) === 'firstlight.list-item' && ($node['props']['headline'] ?? null) === $headline) {
                    return $node;
                }
            }

            Assert::fail("No List Item with headline [{$headline}] found. Headlines: ".implode(' | ', ScreenAssertions::listHeadlines($this->tree())));
        });

        TestableComponent::macro('assertListItem', function (string $headline, ?callable $matcher = null): TestableComponent {
            /** @var TestableComponent $this */
            $node = $this->listItem($headline);

            if ($matcher !== null) {
                Assert::assertTrue($matcher($node) === true, "List Item [{$headline}] does not match the given callback.");
            }

            return $this;
        });

        TestableComponent::macro('assertMissingListItem', function (string $headline): TestableComponent {
            /** @var TestableComponent $this */
            Assert::assertNotContains($headline, ScreenAssertions::listHeadlines($this->tree()), "Unexpected List Item [{$headline}] found.");

            return $this;
        });

        TestableComponent::macro('assertListItemCount', function (int $count): TestableComponent {
            /** @var TestableComponent $this */
            Assert::assertCount($count, ScreenAssertions::listHeadlines($this->tree()), 'Unexpected List Item count.');

            return $this;
        });

        TestableComponent::macro('pressListItem', function (string $headline, ?string $action = null): TestableComponent {
            /** @var TestableComponent $this */
            $this->startInteraction();
            $node = $this->listItem($headline);

            Assert::assertNotTrue($node['props']['disabled'] ?? false, "List Item [{$headline}] is disabled.");

            if ($action === null) {
                return $this->dispatchUiEvent(['type' => TestableComponent::EVENT_PRESS, 'callback_id' => $node['on_press']]);
            }

            $labels = [];
            foreach (['leading', 'trailing'] as $edge) {
                $json = $node['props']["firstlight_{$edge}_actions_json"] ?? null;
                foreach (is_string($json) ? json_decode($json, true, flags: JSON_THROW_ON_ERROR) : [] as $swipe) {
                    if ($swipe['label'] === $action) {
                        return $this->dispatchUiEvent(['type' => TestableComponent::EVENT_PRESS, 'callback_id' => $swipe['cb']]);
                    }
                    $labels[] = $swipe['label'];
                }
            }

            Assert::fail("List Item [{$headline}] has no swipe action [{$action}]. Actions: ".(implode(' | ', $labels) ?: '(none)'));
        });

        TestableComponent::macro('assertConfirmationDialog', function (?string $title = null): TestableComponent {
            /** @var TestableComponent $this */
            Assert::assertNotNull(
                ScreenAssertions::visibleConfirmationDialog($this->tree(), $title),
                'No visible Confirmation Dialog'.($title === null ? '' : " titled [{$title}]").' found.'
            );

            return $this;
        });

        TestableComponent::macro('assertNoConfirmationDialog', function (): TestableComponent {
            /** @var TestableComponent $this */
            $dialog = ScreenAssertions::visibleConfirmationDialog($this->tree(), null);

            Assert::assertNull($dialog, 'Unexpected visible Confirmation Dialog ['.($dialog['props']['title'] ?? '').'].');

            return $this;
        });

        $answerDialog = function (?string $title, string $key): TestableComponent {
            /** @var TestableComponent $this */
            $this->startInteraction();
            $dialog = ScreenAssertions::visibleConfirmationDialog($this->tree(), $title);

            Assert::assertNotNull($dialog, 'No visible Confirmation Dialog'.($title === null ? '' : " titled [{$title}]").' to answer.');

            // Both renderers send confirmation and cancellation as PRESS events.
            return $this->dispatchUiEvent([
                'type' => TestableComponent::EVENT_PRESS,
                'callback_id' => $dialog[$key] ?? $dialog['props'][$key],
            ]);
        };

        TestableComponent::macro('confirmDialog', function (?string $title = null) use ($answerDialog): TestableComponent {
            /** @var TestableComponent $this */
            return $answerDialog->call($this, $title, 'on_press');
        });

        TestableComponent::macro('cancelDialog', function (?string $title = null) use ($answerDialog): TestableComponent {
            /** @var TestableComponent $this */
            return $answerDialog->call($this, $title, 'on_dismiss');
        });

        TestableComponent::macro('assertFeedbackSent', function (string $message, ?FeedbackTone $tone = null): TestableComponent {
            /** @var TestableComponent $this */
            ScreenAssertions::feedbackFake()->assertSent(
                fn (FeedbackRecord $record): bool => $record->message === $message
                    && ($tone === null || $record->tone === $tone)
            );

            return $this;
        });

        TestableComponent::macro('assertNoFeedbackSent', function (): TestableComponent {
            /** @var TestableComponent $this */
            ScreenAssertions::feedbackFake()->assertNothingSent();

            return $this;
        });
    }

    private static function registerExpectations(): void
    {
        if (! function_exists('expect')) {
            return;
        }

        expect()->extend('toHaveFieldError', function (string $target, ?string $message = null) {
            $this->value->assertFieldError($target, $message);

            return $this;
        });

        expect()->extend('toHaveListItem', function (string $headline, ?callable $matcher = null) {
            $this->value->assertListItem($headline, $matcher);

            return $this;
        });
    }

    /**
     * Every node in a wire tree, depth-first.
     *
     * @internal
     *
     * @return Generator<int, array<string, mixed>>
     */
    public static function nodes(array $node): Generator
    {
        yield $node;

        foreach ($node['children'] ?? [] as $child) {
            yield from self::nodes($child);
        }
    }

    /**
     * Callback ids a node publishes: node-level and props `on_*` ids plus
     * Firstlight option callbacks.
     *
     * @internal
     *
     * @return list<int>
     */
    public static function callbackIds(array $node): array
    {
        $ids = [];

        foreach ([$node, $node['props'] ?? []] as $bag) {
            foreach ($bag as $key => $value) {
                if (is_string($key) && str_starts_with($key, 'on_') && is_int($value)) {
                    $ids[] = $value;
                }
            }
        }

        foreach ($node['props']['option_callbacks'] ?? [] as $id) {
            if ((int) $id !== 0) {
                $ids[] = (int) $id;
            }
        }

        return $ids;
    }

    /**
     * @internal
     *
     * @return list<string>
     */
    public static function listHeadlines(array $tree): array
    {
        $headlines = [];

        foreach (self::nodes($tree) as $node) {
            if (($node['type'] ?? null) === 'firstlight.list-item') {
                $headlines[] = (string) ($node['props']['headline'] ?? '');
            }
        }

        return $headlines;
    }

    /** @internal */
    public static function visibleConfirmationDialog(array $tree, ?string $title): ?array
    {
        foreach (self::nodes($tree) as $node) {
            if (($node['type'] ?? null) === 'firstlight.confirmation-dialog'
                && ($node['props']['visible'] ?? false) === true
                && ($title === null || ($node['props']['title'] ?? null) === $title)) {
                return $node;
            }
        }

        return null;
    }

    /** @internal */
    public static function errorBag(object $component): \Illuminate\Support\MessageBag
    {
        Assert::assertTrue(
            method_exists($component, 'getErrorBag'),
            get_class($component).' does not use FirstlightUI\Concerns\ValidatesFields.'
        );

        return $component->getErrorBag();
    }

    /** @internal */
    public static function feedbackFake(): FeedbackFake
    {
        $fake = Feedback::getFacadeRoot();

        Assert::assertInstanceOf(FeedbackFake::class, $fake, 'Call Feedback::fake() before asserting Feedback.');

        return $fake;
    }
}
