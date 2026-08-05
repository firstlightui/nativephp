<?php

namespace FirstlightUI\Facades;

use FirstlightUI\Feedback\FeedbackManager;
use FirstlightUI\Feedback\PendingFeedback;
use Illuminate\Support\Facades\Facade;

/**
 * @method static PendingFeedback message(string $message)
 * @method static PendingFeedback success(string $message)
 * @method static PendingFeedback warning(string $message)
 * @method static PendingFeedback danger(string $message)
 * @method static bool dismiss(string $id)
 */
final class Feedback extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FeedbackManager::class;
    }
}
