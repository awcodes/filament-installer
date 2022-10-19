<?php

namespace App\Actions;

use App\ConsoleWriter;
use App\Shell;

class InstallLaravel
{
    use AbortsCommands;

    protected $shell;

    protected $consoleWriter;

    public function __construct(Shell $shell, ConsoleWriter $consoleWriter)
    {
        $this->shell = $shell;
        $this->consoleWriter = $consoleWriter;
    }

    public function __invoke()
    {
        $this->consoleWriter->logStep('Creating a new Laravel project');

        $process = $this->shell->execInRoot(sprintf(
            'composer create-project laravel/laravel %s%s --remove-vcs --prefer-dist %s',
            config('installer.store.project_name'),
            config('installer.store.dev') ? ' dev-master' : '',
            config('installer.store.with_output') ? '' : '--quiet'
        ));

        $this->abortIf(! $process->isSuccessful(), 'The Laravel installer did not complete successfully.', $process);

        $this->consoleWriter->success(sprintf(
            "A new application '%s' has been created from the %s branch.",
            config('installer.store.project_name'),
            config('installer.store.dev') ? 'develop' : 'release'
        ));
    }
}
