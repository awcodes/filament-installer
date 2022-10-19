<?php

namespace App\Actions;

use App\ConsoleWriter;
use App\Shell;
use Illuminate\Support\Facades\File;

class RunAfterScript
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
        if (config('installer.store.shield')) {
            $this->consoleWriter->logStep('Finishing Filament Shield installation.');

            $shieldInstall = $this->shell->execInProject('php artisan shield:generate');

            if (! $shieldInstall->isSuccessful()) {
                app('final-steps')->add('Run <span class="text-green-500">php aritsan shield:generate</span>');
                $this->consoleWriter->warn('Failed to finish installing Filament Shield.');
            } else {
                $this->consoleWriter->success('Filament Shield installation completed.');
            }
        }

        $afterScriptPath = config('home_dir') . '/.filament/after';
        if (! File::isFile($afterScriptPath)) {
            return;
        }

        $this->consoleWriter->logStep('Running after script');

        $process = $this->shell->execInProject('sh ' . $afterScriptPath);
        $this->abortIf(! $process->isSuccessful(), 'After file did not complete successfully', $process);

        $this->consoleWriter->success('After script has completed.');
    }
}
