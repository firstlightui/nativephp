<?php

use FirstlightUI\Support\Chrome;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use InvalidArgumentException;

beforeEach(function () {
    $this->previousContainer = Container::getInstance();
    Container::setInstance(new Container);
});

afterEach(function () {
    Container::setInstance($this->previousContainer);
});

it('returns English chrome strings when no translator is bound', function () {
    expect(Chrome::string('confirm'))->toBe('Confirm')
        ->and(Chrome::string('cancel'))->toBe('Cancel')
        ->and(Chrome::string('ok'))->toBe('OK')
        ->and(Chrome::string('dismiss'))->toBe('Dismiss')
        ->and(Chrome::string('dismiss_feedback'))->toBe('Dismiss feedback')
        ->and(Chrome::string('clear'))->toBe('Clear')
        ->and(Chrome::string('clear_search'))->toBe('Clear search')
        ->and(Chrome::string('clear_text'))->toBe('Clear text')
        ->and(Chrome::string('skip'))->toBe('Skip')
        ->and(Chrome::string('done'))->toBe('Done')
        ->and(Chrome::string('crop'))->toBe('Crop')
        ->and(Chrome::string('zoom_in'))->toBe('Zoom in')
        ->and(Chrome::string('zoom_out'))->toBe('Zoom out')
        ->and(Chrome::string('choose_media'))->toBe('Choose media')
        ->and(Chrome::string('photo_library'))->toBe('Photo Library')
        ->and(Chrome::string('camera'))->toBe('Camera')
        ->and(Chrome::string('browse_files'))->toBe('Browse Files')
        ->and(Chrome::string('show_password'))->toBe('Show password')
        ->and(Chrome::string('hide_password'))->toBe('Hide password');
});

it('rejects unknown chrome keys', function () {
    Chrome::string('nope');
})->throws(InvalidArgumentException::class, 'Unknown Firstlight chrome string [nope]');

it('uses the bound translator for chrome strings', function () {
    $loader = new ArrayLoader;
    $loader->addMessages('fr', 'chrome', ['confirm' => 'Confirmer'], 'firstlight');
    $translator = new Translator($loader, 'fr');
    Container::getInstance()->instance('translator', $translator);

    expect(Chrome::string('confirm'))->toBe('Confirmer')
        ->and(Chrome::string('cancel'))->toBe('Cancel');
});

it('falls back to English when the translator returns a missing key', function () {
    $translator = new Translator(new ArrayLoader, 'de');
    Container::getInstance()->instance('translator', $translator);

    expect(Chrome::string('confirm'))->toBe('Confirm');
});

it('loads English chrome strings from the package lang file', function () {
    $path = dirname(__DIR__, 3).'/lang';
    $translator = new Translator(new FileLoader(new Filesystem, $path), 'en');
    $translator->addNamespace('firstlight', $path);

    foreach (array_keys(Chrome::STRINGS) as $key) {
        expect($translator->get('firstlight::chrome.'.$key))->toBe(Chrome::STRINGS[$key]);
    }
});

it('maps Laravel application locales onto BCP-47 tags', function (string $laravelLocale, string $expected) {
    $translator = new Translator(new ArrayLoader, $laravelLocale);
    Container::getInstance()->instance('translator', $translator);

    expect(Chrome::applicationLocale())->toBe($expected);
})->with([
    ['en', 'en'],
    ['en_AU', 'en-AU'],
    ['en-AU', 'en-AU'],
    ['zh_Hant_TW', 'zh-Hant-TW'],
    ['en_AU.UTF-8', 'en-AU'],
]);

it('omits invalid inherited application locales', function (string $laravelLocale) {
    $translator = new Translator(new ArrayLoader, $laravelLocale);
    Container::getInstance()->instance('translator', $translator);

    expect(Chrome::applicationLocale())->toBeNull();
})->with(['', 'en--AU', 'en_@@']);

it('returns null for application locale when no translator is bound', function () {
    expect(Chrome::applicationLocale())->toBeNull();
});

it('returns a valid inherited application timezone from config', function () {
    Container::getInstance()->instance('config', new class
    {
        public function get(string $key, mixed $default = null): mixed
        {
            return $key === 'app.timezone' ? 'Australia/Sydney' : $default;
        }
    });

    expect(Chrome::applicationTimezone())->toBe('Australia/Sydney');
});

it('omits invalid inherited application timezones', function (mixed $timezone) {
    Container::getInstance()->instance('config', new class($timezone)
    {
        public function __construct(private mixed $timezone) {}

        public function get(string $key, mixed $default = null): mixed
        {
            return $key === 'app.timezone' ? $this->timezone : $default;
        }
    });

    expect(Chrome::applicationTimezone())->toBeNull();
})->with(['', 'Mars/Olympus_Mons', 42, '+10:00']);
