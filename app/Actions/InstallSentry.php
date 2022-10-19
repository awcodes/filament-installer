<?php

namespace App\Actions;

use App\Actions\Concerns\InteractsWithComposer;
use App\Actions\Concerns\ReplaceInFile;
use App\ConsoleWriter;
use App\Shell;

class InstallSentry
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
        if (config('installer.store.sentry') === false) {
            return;
        }

        $this->consoleWriter->logStep('Installing Filament Sentry');

        $this->composerRequire('awcodes/filament-sentry');
        $this->installShield();

        $this->consoleWriter->success('Successfully installed Filament Sentry.');
    }

    protected function installShield(): void
    {
        $process = $this->shell->execInProject(sprintf(
            'php artisan vendor:publish --tag="filament-sentry-config"%s',
            config('installer.store.with_output') ? '' : ' --quiet'
        ));

        if (! $process->isSuccessful()) {
            app('final-steps')->add('Run <info>php artisan vendor:publish --tag="filament-sentry-config"</info>');
            $this->warn('Failed to publish Filament Sentry config.');
        } else {
            $addUserTrait = $this->replaceInProjectFile(
                "use Laravel\Sanctum\HasApiTokens;",
                "use Laravel\Sanctum\HasApiTokens;\nuse BezhanSalleh\FilamentShield\Traits\HasFilamentShield;",
                '/app/Models/User.php'
            );

            $addUserUseTrait = $this->replaceInProjectFile(
                'use HasApiTokens, HasFactory, Notifiable;',
                'use HasApiTokens, HasFactory, Notifiable, HasFilamentShield;',
                '/app/Models/User.php'
            );

            if (! ($addUserTrait || $addUserUseTrait)) {
                app('final-steps')->add('Add necessary traits to User Model. See <a class="text-sky-500" href="https://github.com/bezhanSalleh/filament-shield/tree/main">https://github.com/bezhanSalleh/filament-shield/tree/main</a>');
                $this->warn('Failed to update User class with necessary traits.');
            }
        }
    }
}
