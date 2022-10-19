<?php

namespace App\Configuration;

class ShellConfiguration extends InstallerConfiguration
{
    protected function getSettings(): array
    {
        return $_SERVER;
    }
}
