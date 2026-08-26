<?php

namespace FirstlightUI;

use FirstlightUI\Feedback\FeedbackManager;
use FirstlightUI\Feedback\FeedbackStore;
use FirstlightUI\NativeComponents\FeedbackCenter;
use Illuminate\Support\ServiceProvider;
use Native\Mobile\Edge\ChromeContributorRegistry;
use Native\Mobile\Edge\ComponentRegistry;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\Layouts\NativeLayout;
use Native\Mobile\Edge\NativeComponent;

class FirstlightServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FeedbackStore::class);
        $this->app->singleton(FeedbackManager::class);

        ComponentRegistry::register('firstlight-feedback-center', FeedbackCenter::class);
    }

    public function boot(): void
    {
        $this->loadFirstlightTranslations();
        $this->loadFirstlightViews();

        ChromeContributorRegistry::register(
            fn (NativeComponent $screen, ?NativeLayout $layout, callable $renderPartial): Element => $renderPartial(
                view('firstlight::native.feedback-center')
            )
        );

        $this->app->make('blade.compiler')->prepareStringsForCompilationUsing(
            new FirstlightTagPrecompiler
        );
    }

    private function loadFirstlightTranslations(): void
    {
        $path = __DIR__.'/../lang';

        if ($this->app->bound('translator')) {
            $this->loadTranslationsFrom($path, 'firstlight');
        } else {
            $this->app->afterResolving('translator', function (): void {
                $this->loadTranslationsFrom(__DIR__.'/../lang', 'firstlight');
            });
        }

        if (method_exists($this->app, 'langPath')) {
            $this->publishes([
                $path => $this->app->langPath('vendor/firstlight'),
            ], 'firstlight-lang');
        }
    }

    private function loadFirstlightViews(): void
    {
        $config = $this->app->bound('config')
            ? $this->app->make('config')
            : null;

        if (is_array($config) || $config instanceof \ArrayAccess) {
            $this->loadViewsFrom(__DIR__.'/../resources/views', 'firstlight');

            return;
        }

        $register = fn ($view) => $view->addNamespace(
            'firstlight',
            __DIR__.'/../resources/views',
        );

        $this->app->afterResolving('view', $register);

        if ($this->app->resolved('view')) {
            $register($this->app->make('view'));
        }
    }
}
