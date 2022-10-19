<?php

namespace App\Actions;

use function Termwind\{render};

class DisplayInstallerWelcome
{
    protected $installerLogo = '
    ______  __                           __
   / ____(_) /___   ___ __   ___  ____  / /_
  / /_  /\/ / __ `/ __ `__ \/ _ \/ __ \/ __/
 / __/ /\/ / /_/ / / / / / /  __/ / / / /_
/_/   /\/_/\__,_/_/ /_/ /_/\___/_/ /_/\__/';
    
    public function __invoke()
    {
        foreach (explode("\n", $this->installerLogo) as $line) {
            // Extra space on the end fixes an issue with console when it ends with backslash
            app('console-writer')->text("<fg=#eab308;bg=default>{$line} </>");
        }

        render(<<<'HTML'
            <div class="py-1 ml-1">
                <div class="px-1 bg-yellow-500 text-black">Filament Installer</div>
                <em class="ml-1">
                    Quickly spin up a new Filament powered application.
                </em>
            </div>
        HTML);
    }
}
