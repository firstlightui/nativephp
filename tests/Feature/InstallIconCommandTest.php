<?php

use FirstlightUI\Console\InstallIconCommand;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Native\Mobile\Icon\Svg\SvgParser;
use Symfony\Component\Console\Tester\CommandTester;

const LUCIDE_COMMIT = '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M12 3v6"/><circle cx="12" cy="12" r="3"/><path d="M12 15v6"/></g></svg>';

const FA_ROCKET = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M160 288L0 288l96-160 122 0L160 288z"/></svg>';

final class IconInstallApplication extends Container
{
    public function __construct(private readonly string $base) {}

    public function runningUnitTests(): bool
    {
        return true;
    }

    public function resourcePath(string $path = ''): string
    {
        return $this->base.'/resources'.($path === '' ? '' : '/'.$path);
    }
}

beforeEach(function () {
    $this->base = sys_get_temp_dir().'/firstlight-icons-'.bin2hex(random_bytes(8));
    $this->app = new IconInstallApplication($this->base);
    $this->http = new Factory;
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->base);
    putenv('FA_API_TOKEN');
    unset($_ENV['FA_API_TOKEN'], $_SERVER['FA_API_TOKEN']);
});

function installFirstlightIcons(Container $app, Factory $http, array $input): CommandTester
{
    $command = new InstallIconCommand(new Filesystem, $http);
    $command->setLaravel($app);
    $tester = new CommandTester($command);
    $tester->execute($input);

    return $tester;
}

function useFontAwesomeToken(string $token): void
{
    putenv("FA_API_TOKEN={$token}");
    $_ENV['FA_API_TOKEN'] = $_SERVER['FA_API_TOKEN'] = $token;
}

it('installs an Iconify icon as a custom icon the NativePHP build accepts', function () {
    $this->http->fake([
        'api.iconify.design/*' => $this->http->response('<?xml version="1.0" encoding="UTF-8"?>'."\n".LUCIDE_COMMIT),
    ]);

    $tester = installFirstlightIcons($this->app, $this->http, ['icons' => ['lucide:git-commit-vertical']]);
    $path = $this->base.'/resources/icons/git-commit-vertical.svg';

    expect($tester->getStatusCode())->toBe(0)
        ->and($tester->getDisplay())->toContain('custom:git-commit-vertical')
        ->and(file_get_contents($path))
        ->toStartWith('<!-- lucide:git-commit-vertical from Iconify.')
        ->not->toContain('<?xml');

    SvgParser::parse(file_get_contents($path), 'resources/icons/git-commit-vertical.svg');

    $this->http->assertSent(fn (Request $request) => $request->url() === 'https://api.iconify.design/lucide/git-commit-vertical.svg');
});

it('skips an installed icon without downloading it, and replaces it with --force', function () {
    (new Filesystem)->ensureDirectoryExists($this->base.'/resources/icons');
    file_put_contents($this->base.'/resources/icons/bell.svg', 'mine');
    $this->http->fake(['api.iconify.design/*' => $this->http->response(LUCIDE_COMMIT)]);

    $skipped = installFirstlightIcons($this->app, $this->http, ['icons' => ['heroicons:bell']]);

    expect($skipped->getStatusCode())->toBe(0)
        ->and($skipped->getDisplay())->toContain('Already installed')
        ->and(file_get_contents($this->base.'/resources/icons/bell.svg'))->toBe('mine');
    $this->http->assertNothingSent();

    $replaced = installFirstlightIcons($this->app, $this->http, ['icons' => ['heroicons:bell'], '--force' => true]);

    expect($replaced->getStatusCode())->toBe(0)
        ->and(file_get_contents($this->base.'/resources/icons/bell.svg'))->toContain('<path d="M12 3v6"/>');
});

it('rejects an SVG the build cannot convert without stopping the rest of the batch', function () {
    $this->http->fake([
        'api.iconify.design/mdi/spin.svg' => $this->http->response('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path transform="rotate(45)" d="M0 0h24v24H0z"/></svg>'),
        'api.iconify.design/lucide/missing.svg' => $this->http->response('Not found', 404),
        'api.iconify.design/lucide/git-commit-vertical.svg' => $this->http->response(LUCIDE_COMMIT),
    ]);

    $tester = installFirstlightIcons($this->app, $this->http, [
        'icons' => ['mdi:spin', 'lucide:missing', 'lucide:git-commit-vertical'],
    ]);
    $display = preg_replace('/\s+/', ' ', $tester->getDisplay());

    expect($tester->getStatusCode())->toBe(1)
        ->and($display)->toContain('resources/icons/spin.svg')->toContain('transform')
        ->and($display)->toContain('Iconify has no [missing] icon in the [lucide] set')
        ->and(file_exists($this->base.'/resources/icons/spin.svg'))->toBeFalse()
        ->and(file_exists($this->base.'/resources/icons/missing.svg'))->toBeFalse()
        ->and(file_exists($this->base.'/resources/icons/git-commit-vertical.svg'))->toBeTrue();
});

it('installs a local SVG under a valid name', function () {
    $source = $this->base.'/downloads/Rocket Launch.svg';
    (new Filesystem)->ensureDirectoryExists(dirname($source));
    file_put_contents($source, FA_ROCKET);

    $unnamed = installFirstlightIcons($this->app, $this->http, ['icons' => [$source]]);

    expect($unnamed->getStatusCode())->toBe(1)
        ->and($unnamed->getDisplay())->toContain('--as=');

    $named = installFirstlightIcons($this->app, $this->http, ['icons' => [$source], '--as' => 'launch']);

    expect($named->getStatusCode())->toBe(0)
        ->and(file_get_contents($this->base.'/resources/icons/launch.svg'))->toContain('Source: Rocket Launch.svg');

    $many = installFirstlightIcons($this->app, $this->http, ['icons' => [$source, 'lucide:rocket'], '--as' => 'launch']);

    expect($many->getStatusCode())->toBe(1);
    $this->http->assertNothingSent();
});

it('needs FA_API_TOKEN for Font Awesome icons', function () {
    $this->http->fake();

    $tester = installFirstlightIcons($this->app, $this->http, ['icons' => ['fontawesome:light/rocket-launch']]);

    expect($tester->getStatusCode())->toBe(1)
        ->and(preg_replace('/\s+/', ' ', $tester->getDisplay()))->toContain('Set FA_API_TOKEN');
    $this->http->assertNothingSent();
});

it('installs Font Awesome icons in the requested family and style with one token exchange', function () {
    useFontAwesomeToken('api-token');
    $this->http->fake(function (Request $request) {
        if ($request->url() === 'https://api.fontawesome.com/token') {
            return $this->http->response(['access_token' => 'access-token', 'expires_in' => 3600]);
        }

        $svgs = str_contains($request['query'], 'icon(name: "unicorn-wing")') ? [] : [['html' => FA_ROCKET]];

        return $this->http->response(['data' => ['release' => ['version' => '7.3.1', 'icon' => ['svgs' => $svgs]]]]);
    });

    $tester = installFirstlightIcons($this->app, $this->http, [
        'icons' => ['fontawesome:light/rocket-launch', 'fontawesome:sharp-duotone/thin/rocket', 'fontawesome:solid/unicorn-wing'],
    ]);

    expect($tester->getStatusCode())->toBe(1)
        ->and(file_get_contents($this->base.'/resources/icons/rocket-launch.svg'))
        ->toStartWith('<!-- Font Awesome 7.3.1 classic/light/rocket-launch.')
        ->and(file_exists($this->base.'/resources/icons/rocket.svg'))->toBeTrue()
        ->and(file_exists($this->base.'/resources/icons/unicorn-wing.svg'))->toBeFalse()
        ->and(preg_replace('/\s+/', ' ', $tester->getDisplay()))->toContain('has no classic/solid SVG');

    $queries = $this->http->recorded(fn (Request $request) => $request->url() === 'https://api.fontawesome.com')->values();
    $tokenExchanges = $this->http->recorded(fn (Request $request) => $request->url() === 'https://api.fontawesome.com/token')->values();

    expect($tokenExchanges)->toHaveCount(1)
        ->and($tokenExchanges[0][0]->header('Authorization'))->toBe(['Bearer api-token'])
        ->and($queries)->toHaveCount(3)
        ->and($queries[0][0]->header('Authorization'))->toBe(['Bearer access-token'])
        ->and($queries[0][0]['query'])->toContain('family: CLASSIC, style: LIGHT')
        ->and($queries[1][0]['query'])->toContain('icon(name: "rocket")')->toContain('family: SHARP_DUOTONE, style: THIN');
});

it('rejects Font Awesome paths that could change the query', function () {
    useFontAwesomeToken('api-token');
    $this->http->fake();

    $tester = installFirstlightIcons($this->app, $this->http, ['icons' => ['fontawesome:solid/rocket") { id } #'], '--as' => 'rocket']);

    expect($tester->getStatusCode())->toBe(1)
        ->and($tester->getDisplay())->toContain('fontawesome:style/name');
    $this->http->assertNothingSent();
});
