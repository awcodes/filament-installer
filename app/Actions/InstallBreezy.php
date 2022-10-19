<?php

namespace App\Actions;

use App\Actions\Concerns\InteractsWithComposer;
use App\Actions\Concerns\ReplaceInFile;
use App\ConsoleWriter;
use App\Shell;

class InstallBreezy
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
        if (config('installer.store.breezy') === false || config('installer.store.sentry') === true) {
            return;
        }

        $this->consoleWriter->logStep('Installing Filament Breezy');

        $this->composerRequire('jeffgreco13/filament-breezy');
        $this->installBreezy();

        $this->consoleWriter->success('Successfully installed Filament Breezy.');
    }

    protected function installBreezy(): void
    {
        $process = $this->shell->execInProject(sprintf(
            'php artisan vendor:publish --tag="filament-breezy-config"%s',
            config('installer.store.with_output') ? '' : ' --quiet'
        ));

        if (! $process->isSuccessful()) {
            app('final-steps')->add('Run <info>php artisan vendor:publish --tag="filament-breezy-config"</info>');
            $this->warn('Failed to publish Filament Breezy config.');
        } else {
            $replaceLoginClass = $this->replaceInProjectFile(
                "\Filament\Http\Livewire\Auth\Login::class",
                "\JeffGreco13\FilamentBreezy\Http\Livewire\Auth\Login::class",
                '/config/filament.php'
            );

            if (! $replaceLoginClass) {
                app('final-steps')->add('Replace <info>\Filament\Http\Livewire\Auth\Login::class</info> in the Filament config with <info>\JeffGreco13\FilamentBreezy\Http\Livewire\Auth\Login::class</info>');
                $this->warn('Failed to update Login class in Filament config.');
            }
        }
    }
}
