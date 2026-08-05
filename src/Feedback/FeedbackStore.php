<?php

namespace FirstlightUI\Feedback;

final class FeedbackStore
{
    /** @var array<string, FeedbackRecord> */
    private array $records = [];

    public function put(FeedbackRecord $record): string
    {
        $this->records[$record->id] = $record;

        return $record->id;
    }

    public function remove(string $id): ?FeedbackRecord
    {
        $record = $this->records[$id] ?? null;

        if ($record !== null) {
            unset($this->records[$id]);
        }

        return $record;
    }

    /** @return array<int, FeedbackRecord> */
    public function all(): array
    {
        return array_values($this->records);
    }

    public function reset(): void
    {
        $this->records = [];
    }
}
