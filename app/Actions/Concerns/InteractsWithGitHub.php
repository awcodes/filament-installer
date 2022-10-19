<?php

namespace App\Actions\Concerns;

use App\Configuration\InstallerConfiguration;
use App\InstallerException;

trait InteractsWithGitHub
{
    protected static function shouldCreateRepository(): bool
    {
        return static::gitHubInitializationRequested() && static::gitHubToolingInstalled();
    }

    protected static function gitHubInitializationRequested(): bool
    {
        return config('installer.store.' . InstallerConfiguration::INITIALIZE_GITHUB) === true;
    }

    protected static function getDescription(): string
    {
        $description = config('installer.store.' . InstallerConfiguration::GITHUB_DESCRIPTION);

        if (is_null($description)) {
            return '';
        }

        return sprintf(' --description="%s"', $description);
    }

    protected static function getHomepage(): string
    {
        $homepage = config('installer.store.' . InstallerConfiguration::GITHUB_HOMEPAGE);

        if (is_null($homepage)) {
            return '';
        }

        return sprintf(' --homepage="%s"', $homepage);
    }

    /**
     * @throws InstallerException
     */
    protected static function getGitHubCreateCommand(): string
    {
        if (static::ghInstalled()) {
            return sprintf(
                'gh repo create%s --confirm %s%s%s',
                static::getRepositoryName(),
                config('installer.store.github_public') ? ' --public' : ' --private',
                static::getDescription(),
                static::getHomepage(),
            );
        }

        if (static::hubInstalled()) {
            return sprintf(
                'hub create %s%s%s%s',
                config('installer.store.github_public') ? '' : '--private ',
                static::getDescription(),
                static::getHomepage(),
                static::getRepositoryName()
            );
        }

        throw new InstallerException("Missing tool. Expected one of 'gh' or 'hub' to be installed but none found.");
    }

    protected static function getRepositoryName(): string
    {
        $name = config('installer.store.project_name');
        $organization = config('installer.store.' . InstallerConfiguration::GITHUB_ORGANIZATION);

        return $organization ? " {$organization}/{$name}" : " {$name}";
    }

    protected static function ghInstalled(): bool
    {
        return config('installer.store.tools.gh') === true;
    }

    protected static function hubInstalled(): bool
    {
        return config('installer.store.tools.hub') === true;
    }

    protected static function gitHubToolingInstalled(): bool
    {
        return static::ghInstalled() || static::hubInstalled();
    }
}
