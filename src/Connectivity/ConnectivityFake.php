<?php

namespace FirstlightUI\Connectivity;

/**
 * Connectivity for tests, from Connectivity::fake(). It never reads the
 * bridge, and drives what the device events would: going offline and online
 * and returning from the background.
 */
class ConnectivityFake extends ConnectivityManager
{
    private ConnectivityState $faked = ConnectivityState::Online;

    public function goOffline(): static
    {
        return $this->go(ConnectivityState::Offline);
    }

    /** From offline, asks the app's queue for a drain and revalidates the screen on display. */
    public function goOnline(): static
    {
        return $this->go(ConnectivityState::Online);
    }

    public function goUnknown(): static
    {
        return $this->go(ConnectivityState::Unknown);
    }

    /** Return from the background after $secondsAway, as AppForegrounded does. */
    public function foreground(int $secondsAway): static
    {
        $this->foregrounded((object) ['secondsAway' => $secondsAway]);

        return $this;
    }

    protected function read(): ConnectivityState
    {
        return $this->faked;
    }

    private function go(ConnectivityState $state): static
    {
        $this->faked = $state;
        $this->checkReconnect(revalidate: true);

        return $this;
    }
}
