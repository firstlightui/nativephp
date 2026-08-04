<?php

namespace FirstlightUI;

use Illuminate\Support\ServiceProvider;

class FirstlightServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->app->make('blade.compiler')->prepareStringsForCompilationUsing(
            new FirstlightTagPrecompiler
        );
    }
}
