<?php

namespace App\Commands;

use App\Actions\DisplayHelpScreen;
use App\Actions\DisplayInstallerWelcome;

class HelpCommand extends InstallerCommand
{
    protected $signature = 'help-screen';

    protected $description = 'Show help';

    public function handle()
    {
        app(DisplayInstallerWelcome::class)();
        app(DisplayHelpScreen::class)();
    }
}
