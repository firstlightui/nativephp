<?php

namespace FirstlightUI\Feedback;

final class FeedbackStore
{
    /** @var array<string, FeedbackRecord> */
    private array $records = [];

    /** @var array<string, int> */
    private array $publicationGenerations = [];

    public function put(FeedbackRecord $record): string
    {
        $this->records[$record->id] = $record;
        $this->publicationGenerations[$record->id] = ($this->publicationGenerations[$record->id] ?? 0) + 1;

        return $record->id;
    }

    public function publicationGeneration(string $id): int
    {
        return $this->publicationGenerations[$id] ?? 0;
    }

    public function remove(string $id): ?FeedbackRecord
    {
        $record = $this->records[$id] ?? null;

        if ($record !== null) {
            unset($this->records[$id], $this->publicationGenerations[$id]);
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
        $this->publicationGenerations = [];
    }
}
