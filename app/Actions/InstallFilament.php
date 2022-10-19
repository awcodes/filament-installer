<?php

namespace App\Actions;

use App\Actions\Concerns\InteractsWithComposer;
use App\Actions\Concerns\ReplaceInFile;
use App\ConsoleWriter;
use App\Shell;

class InstallFilament
{
    use AbortsCommands;
    use InteractsWithComposer;
    use ReplaceInFile;

    private $shell;

    private $consoleWriter;

    public function __construct(Shell $shell, ConsoleWriter $consoleWriter)
    {
        $this->shell = $shell;
        $this->consoleWriter = $consoleWriter;
    }

    public function __invoke()
    {
        $this->consoleWriter->logStep('Installing Filament');

        $this->composerRequire('filament/filament');
        $this->installFilament();

        $this->consoleWriter->success('Successfully installed Filament.');
    }

    protected function installFilament(): void
    {
        $process = $this->shell->execInProject(sprintf(
            'php artisan vendor:publish --tag="filament-config"%s',
            config('installer.store.with_output') ? '' : ' --quiet'
        ));

        if (! $process->isSuccessful()) {
            app('final-steps')->add('php artisan vendor:publish --tag="filament-config"');
            $this->warn('Failed to publish the Filament config file.');
        }

        $process = $this->shell->execInProject(sprintf(
            'php artisan storage:link %s',
            config('installer.store.with_output') ? '' : ' --quiet'
        ));

        if (! $process->isSuccessful()) {
            app('final-steps')->add('Run <info>php artisan storage:link</info>');
            $this->warn('Failed to create storage symlink.');
        }

        $replace = $this->replaceInProjectFile(
            '"@php artisan vendor:publish --tag=laravel-assets --ansi --force"',
            "\"@php artisan vendor:publish --tag=laravel-assets --ansi --force\",\n\t\t\t\"@php artisan filament:upgrade\"",
            '/composer.json'
        );

        if (! $replace) {
            $this->warn('Failed to add Filament upgrade to composer.json.');
        }

        if (! (config('installer.store.shield') || config('installer.store.sentry'))) {
            app('final-steps')->add('Set up your database .env settings');
            app('final-steps')->add('Run <info>php artisan migrate</info>');
            app('final-steps')->add('Run <info>php artisan make:filament-user</info>');
        }
    }
}
