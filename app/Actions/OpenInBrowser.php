<?php

namespace App\Actions;

use App\Environment;
use App\Shell;

class OpenInBrowser
{
    use AbortsCommands;

    protected $shell;

    protected $consoleWriter;

    protected $environment;

    public function __construct(Shell $shell, Environment $environment)
    {
        $this->shell = $shell;
        $this->environment = $environment;
    }

    public function __invoke()
    {
        if (config('installer.store.no_browser') || count(app('final-steps')->all()) > 1) {
            return;
        }

        app('console-writer')->logStep('Opening in Browser');

        if ($this->environment->isMac() && $this->browser()) {
            $this->shell->execInProject(sprintf(
                'open -a "%s" "%s"',
                $this->browser(),
                config('installer.store.project_url') . '/admin/login'
            ));

            return;
        }

        $this->shell->execInProject('valet open');
    }

    public function browser()
    {
        return config('installer.store.browser');
    }
}
