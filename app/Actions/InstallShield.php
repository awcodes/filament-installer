<?php

namespace App\Actions;

use App\Actions\Concerns\InteractsWithComposer;
use App\Actions\Concerns\ReplaceInFile;
use App\ConsoleWriter;
use App\Shell;

class InstallShield
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
        if (config('installer.store.shield') === false || config('installer.store.sentry') === true) {
            return;
        }

        $this->consoleWriter->logStep('Installing Filament Shield');

        $this->composerRequire('bezhansalleh/filament-shield');
        $this->installShield();

        $this->consoleWriter->success('Successfully installed Filament Shield.');
    }

    protected function installShield(): void
    {
        $process = $this->shell->execInProject(sprintf(
            'php artisan vendor:publish --tag="filament-shield-config" && php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"%s',
            config('installer.store.with_output') ? '' : ' --quiet'
        ));

        if (! $process->isSuccessful()) {
            app('final-steps')->add('Run <info>php artisan vendor:publish --tag="filament-shield-config"</info>');
            $this->warn('Failed to publish Filament Shield config.');
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
