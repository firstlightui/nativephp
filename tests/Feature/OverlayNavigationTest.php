<?php

use FirstlightUI\Elements\BottomSheet;
use FirstlightUI\Elements\Modal;
use FirstlightUI\NativeComponent;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\Elements\Column;
use Native\Mobile\Edge\Elements\Text;
use Native\Mobile\Testing\Native;

require_once __DIR__.'/../Support/ScreenHarness.php';

class OverlayNavigationScreen extends NativeComponent
{
    public bool $filtersVisible = true;

    public bool $detailsVisible = false;

    public int $dismissals = 0;

    public function closeFilters(): void
    {
        $this->filtersVisible = false;
        $this->dismissals++;
    }

    public function closeDetails(): void
    {
        $this->detailsVisible = false;
        $this->dismissals++;
    }

    public bool $navigateOnDismiss = false;

    public function closeFiltersThenNavigate(): void
    {
        $this->dismissals++;
        $this->navigate('/settings');
        $this->filtersVisible = false;
    }

    public function openNewPost(): void
    {
        $this->navigate('/posts/create');
    }

    public function closeFiltersAndOpenNewPost(): void
    {
        $this->filtersVisible = false;
        $this->navigate('/posts/create');
    }

    public function render(): Element
    {
        return $this->response(Column::make(
            Text::make('Screen'),
            BottomSheet::make(Text::make('Filters'))->visible($this->filtersVisible)->onDismiss($this->navigateOnDismiss ? 'closeFiltersThenNavigate' : 'closeFilters'),
            Modal::make(Text::make('Details'))->visible($this->detailsVisible)->onDismiss('closeDetails'),
        ));
    }
}

it('dismisses the overlays left open when the screen opens another', function () {
    Native::test(OverlayNavigationScreen::class)
        ->call('openNewPost')
        ->assertNavigatedTo('/posts/create')
        // Only the open sheet is dismissed; the closed modal's handler doesn't run.
        ->assertSet('filtersVisible', false)
        ->assertSet('dismissals', 1);
});

it('does not dismiss an overlay the handler already closed before navigating', function () {
    Native::test(OverlayNavigationScreen::class)
        ->call('closeFiltersAndOpenNewPost')
        ->assertNavigatedTo('/posts/create')
        ->assertSet('dismissals', 0);
});


it('runs each dismiss handler once even when it navigates', function () {
    Native::test(OverlayNavigationScreen::class)
        ->set('navigateOnDismiss', true)
        ->call('openNewPost')
        // The screen keeps the destination it was leaving for.
        ->assertNavigatedTo('/posts/create')
        ->assertSet('filtersVisible', false)
        ->assertSet('dismissals', 1);
});
