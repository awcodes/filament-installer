<?php

namespace App\Commands;

use App\Actions\EditConfigFile;
use App\Configuration\CommandLineConfiguration;
use App\Configuration\InstallerConfiguration;
use App\Configuration\SavedConfiguration;
use App\Configuration\SetConfig;
use App\Configuration\ShellConfiguration;
use App\InstallerException;

class EditAfter extends InstallerCommand
{
    protected $signature = 'edit-after {--editor= : Open the config file in the specified <info>EDITOR</info> or the system default if none is specified.}';

    protected $description = 'Edit Config File. A new config file is created if one does not already exist.';

    public function handle()
    {
        app()->bind('console', function () {
            return $this;
        });

        $commandLineConfiguration = new CommandLineConfiguration([
            'editor' => InstallerConfiguration::EDITOR,
        ]);

        $savedConfiguration = new SavedConfiguration([
            'CODEEDITOR' => InstallerConfiguration::EDITOR,
        ]);

        $shellConfiguration = new ShellConfiguration([
            'EDITOR' => InstallerConfiguration::EDITOR,
        ]);

        (new SetConfig(
            $commandLineConfiguration,
            $savedConfiguration,
            $shellConfiguration,
            app('console-writer'),
            $this->input
        ))([
            InstallerConfiguration::EDITOR => 'nano',
        ]);

        try {
            app(EditConfigFile::class)('after');
        } catch (InstallerException $e) {
            app('console-writer')->exception($e->getMessage());
        }
    }
}
