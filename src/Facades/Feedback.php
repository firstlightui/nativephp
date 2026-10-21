<?php

namespace FirstlightUI\Facades;

use FirstlightUI\Feedback\FeedbackFake;
use FirstlightUI\Feedback\FeedbackManager;
use FirstlightUI\Feedback\FeedbackRecord;
use FirstlightUI\Feedback\PendingFeedback;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static PendingFeedback message(string $message)
 * @method static PendingFeedback success(string $message)
 * @method static PendingFeedback warning(string $message)
 * @method static PendingFeedback danger(string $message)
 * @method static bool dismiss(string $id)
 * @method static void assertSent(callable $callback)
 * @method static void assertNotSent(callable $callback)
 * @method static void assertNothingSent()
 * @method static Collection<int, FeedbackRecord> sent(?callable $callback = null)
 */
final class Feedback extends Facade
{
    public static function fake(): FeedbackFake
    {
        self::swap($fake = new FeedbackFake);

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return FeedbackManager::class;
    }
}
