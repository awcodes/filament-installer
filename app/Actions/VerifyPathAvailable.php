<?php

namespace App\Actions;

use App\InstallerException;
use Illuminate\Support\Facades\File;

class VerifyPathAvailable
{
    use AbortsCommands;

    private $consoleWriter;

    public function __invoke()
    {
        app('console-writer')->logStep('Verifying path availability');

        $rootPath = config('installer.store.root_path');

        if (! File::isDirectory($rootPath)) {
            throw new InstallerException("{$rootPath} is not a directory.");
        }

        $projectPath = config('installer.store.project_path');

        if (empty($projectPath)) {
            throw new InstallerException("Configuration 'installer.store.project_path' cannot be null or an empty string.");
        }

        if (File::isDirectory($projectPath)) {
            if (! config('installer.store.force_create')) {
                throw new InstallerException("{$projectPath} is already a directory.");
            }

            if (! File::deleteDirectory($projectPath)) {
                throw new InstallerException("{$projectPath} is already a directory and, although the force option was specified, deletion failed.");
            }
        }

        app('console-writer')->success("Directory <span class=\"text-sky-600\">'{$projectPath}'</span> is available.");
    }
}
