<?php

namespace App\Actions;

use App\Actions\Concerns\InteractsWithComposer;
use App\Actions\Concerns\ReplaceInFile;
use App\ConsoleWriter;
use App\Shell;

class ActivateDarkMode
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
        if (! config('installer.store.dark')) {
            return;
        }

        $this->consoleWriter->logStep('Activating Dark Mode');

        $replace = $this->replaceInProjectFile(
            "'dark_mode' => false,",
            "'dark_mode' => true,",
            '/config/filament.php'
        );

        if (! $replace) {
            $this->warn('Failed to activate Dark Mode.');
        } else {
            $this->consoleWriter->success('Successfully activated dark mode.');
        }
    }
}
