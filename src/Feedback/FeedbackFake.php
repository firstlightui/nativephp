<?php

namespace FirstlightUI\Feedback;

use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert;

final class FeedbackFake
{
    private readonly FeedbackManager $manager;

    /** @var list<FeedbackRecord> */
    private array $records = [];

    public function __construct()
    {
        $this->manager = new FeedbackManager(new FeedbackStore(function (FeedbackRecord $record): void {
            $this->records[] = $record;
        }));
    }

    public function message(string $message): PendingFeedback
    {
        return $this->manager->message($message);
    }

    public function success(string $message): PendingFeedback
    {
        return $this->manager->success($message);
    }

    public function warning(string $message): PendingFeedback
    {
        return $this->manager->warning($message);
    }

    public function danger(string $message): PendingFeedback
    {
        return $this->manager->danger($message);
    }

    public function dismiss(string $id): bool
    {
        return $this->manager->dismiss($id);
    }

    /** @return Collection<int, FeedbackRecord> */
    public function sent(?callable $callback = null): Collection
    {
        $records = new Collection($this->records);

        return $callback === null ? $records : $records->filter($callback)->values();
    }

    /** @param callable(FeedbackRecord): bool $callback */
    public function assertSent(callable $callback): void
    {
        Assert::assertNotEmpty($this->sent($callback), 'The expected feedback was not sent.');
    }

    /** @param callable(FeedbackRecord): bool $callback */
    public function assertNotSent(callable $callback): void
    {
        Assert::assertEmpty($this->sent($callback), 'Unexpected feedback was sent.');
    }

    public function assertNothingSent(): void
    {
        Assert::assertEmpty($this->records, 'Feedback was sent unexpectedly.');
    }
}
