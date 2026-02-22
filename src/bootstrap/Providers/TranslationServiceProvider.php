<?php

namespace Bootstrap\Providers;

use Core\Translation\Internal\Translator;

class TranslationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('translator', function () {
            $langPath = $this->app->basePath('resources' . DIRECTORY_SEPARATOR . 'lang');
            $locale   = config('app.locale', 'en');
            $fallback = config('app.fallback_locale', 'en');

            return new Translator($langPath, $locale, $fallback);
        });
    }
}
