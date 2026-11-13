<?php

namespace FirstlightUI\Connectivity;

/**
 * The app's queue of writes made while offline, as Firstlight shows it.
 * Firstlight owns no queue: it reads these counts for the status line and
 * asks for one drain each time the device comes back online.
 */
interface PendingChanges
{
    /** Writes not yet sent, including those being sent now. */
    public function waitingCount(): int;

    /** Writes the server refused, which need the user. */
    public function failedCount(): int;

    public function isSyncing(): bool;

    /** Start one drain, such as an AsyncTask, and return without waiting for it. */
    public function sync(): void;
}
