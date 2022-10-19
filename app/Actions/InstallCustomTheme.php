<?php

namespace App\Actions;

use App\Actions\Concerns\InteractsWithComposer;
use App\Actions\Concerns\InteractsWithNpm;
use App\Actions\Concerns\InteractsWithStubs;
use App\Actions\Concerns\ReplaceInFile;
use App\ConsoleWriter;
use App\Shell;

class InstallCustomTheme
{
    use AbortsCommands;
    use InteractsWithComposer;
    use ReplaceInFile;
    use InteractsWithStubs;
    use InteractsWithNpm;

    private $shell;

    private $consoleWriter;

    public function __construct(Shell $shell, ConsoleWriter $consoleWriter)
    {
        $this->shell = $shell;
        $this->consoleWriter = $consoleWriter;
    }

    public function __invoke()
    {
        if (! config('installer.store.themed')) {
            return;
        }

        $this->consoleWriter->logStep('Installing Custom Theme Scaffolding');

        if (config('installer.store.mix')) {
            $this->installWithMix();
        } else {
            $this->installWithVite();
        }

        $this->consoleWriter->success('Successfully installed custom theme scaffolding.');
    }

    private function installWithMix(): void
    {
        $this->publishStub('package.json', 'theme/mix/package.json');
        $this->publishStub('tailwind.config.js', 'theme/mix/tailwind.config.js');
        $this->publishStub('webpack.mix.js', 'theme/mix/webpack.mix.js');
        $this->publishStub('resources/css/filament.css', 'theme/mix/filament.css');
        $this->publishStub('resources/js/bootstrap.js', 'theme/mix/bootstrap.js');

        $addToServiceProvider = $this->replaceInProjectFile(
            '{{ installer:theme }}',
            "Filament::registerTheme(mix('css/filament.css'));",
            '/app/Providers/FilamentServiceProvider.php'
        );

        $this->abortIf(! $addToServiceProvider, 'Could not add theme to Filament service provider.');
    }

    private function installWithVite(): void
    {
        $this->publishStub('package.json', 'theme/vite/package.json');
        $this->publishStub('tailwind.config.js', 'theme/vite/tailwind.config.js');
        $this->publishStub('postcss.config.js', 'theme/vite/postcss.config.js');
        $this->publishStub('resources/css/filament.css', 'theme/vite/filament.css');

        $addViteToProvider = $this->replaceInProjectFile(
            '{{ installer:theme_class }}',
            "use Illuminate\Foundation\Vite;",
            '/app/Providers/FilamentServiceProvider.php'
        );

        $addToServiceProvider = $this->replaceInProjectFile(
            '{{ installer:theme }}',
            "Filament::registerTheme(app(Vite::class)('resources/css/filament.css'));",
            '/app/Providers/FilamentServiceProvider.php'
        );

        $this->abortIf(! ($addToServiceProvider || $addViteToProvider), 'Could not add theme to Filament service provider.');

        $replaceInVite = $this->replaceInProjectFile(
            "input: ['resources/css/app.css', 'resources/js/app.js'],",
            "input: ['resources/css/app.css', 'resources/css/filament.css', 'resources/js/app.js'],",
            '/vite.config.js'
        );

        $this->abortIf(! $replaceInVite, 'Could not update vite.config.js.');

        $addFilementServiceProvider = $this->replaceInProjectFile(
            "App\Providers\RouteServiceProvider::class,",
            "App\Providers\RouteServiceProvider::class,\n\t\tApp\Providers\FilamentServiceProvider::class,",
            '/config/app.php'
        );

        $this->abortIf(! $addFilementServiceProvider, 'Could not add FilamentServiceProvider to app config.');
    }
}
