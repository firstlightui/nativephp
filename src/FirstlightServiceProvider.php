<?php

namespace FirstlightUI;

use FirstlightUI\Feedback\FeedbackManager;
use FirstlightUI\Feedback\FeedbackStore;
use Illuminate\Support\ServiceProvider;

class FirstlightServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FeedbackStore::class);
        $this->app->singleton(FeedbackManager::class);
    }

    public function boot(): void
    {
        $this->app->make('blade.compiler')->prepareStringsForCompilationUsing(
            new FirstlightTagPrecompiler
        );
    }
}
