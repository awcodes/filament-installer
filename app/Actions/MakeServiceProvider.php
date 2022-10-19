<?php

namespace App\Actions;

use App\Actions\Concerns\InteractsWithComposer;
use App\Actions\Concerns\InteractsWithNpm;
use App\Actions\Concerns\InteractsWithStubs;
use App\Actions\Concerns\ReplaceInFile;
use App\ConsoleWriter;
use App\Shell;

class MakeServiceProvider
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
        if (config('installer.store.themed')) {
            config()->set('installer.store.filament_provider', true);
        }

        if (! config('installer.store.filament_provider')) {
            return;
        }

        $this->consoleWriter->logStep('Making FilamentServiceProvider');

        $this->copyStubToApp(
            'FilamentServiceProvider',
            '/app/Providers/FilamentServiceProvider.php',
        );

        $addFilementServiceProvider = $this->replaceInProjectFile(
            "App\Providers\RouteServiceProvider::class,",
            "App\Providers\RouteServiceProvider::class,\n\t\tApp\Providers\FilamentServiceProvider::class,",
            '/config/app.php'
        );

        $this->abortIf(! $addFilementServiceProvider, 'Could not add FilamentServiceProvider to app config.');

        if (! config('installer.store.themed')) {
            $this->replaceInProjectFile(
                '{{ installer:theme_class }}',
                '',
                '/app/Providers/FilamentServiceProvider.php'
            );

            $this->replaceInProjectFile(
                '{{ installer:theme }}',
                '//',
                '/app/Providers/FilamentServiceProvider.php'
            );
        }

        $this->consoleWriter->success('Filament Service Provider successfully created.');
    }
}
