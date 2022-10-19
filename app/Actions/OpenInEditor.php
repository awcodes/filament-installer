<?php

namespace App\Actions;

use App\ConsoleWriter;
use App\Shell;

class OpenInEditor
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
        $this->consoleWriter->logStep('Opening In Editor');

        $process = $this->shell->withTTY()->execInProject(sprintf('%s .', config('installer.store.editor')));
        $this->abortIf(! $process->isSuccessful(), sprintf('Failed to open editor %s', config('installer.store.editor')), $process);

        $this->consoleWriter->success('Opening your project in ' . config('installer.store.editor'));
    }
}
