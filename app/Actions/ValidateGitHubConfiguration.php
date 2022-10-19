<?php

namespace App\Actions;

use App\Actions\Concerns\InteractsWithGitHub;
use App\Configuration\InstallerConfiguration;
use App\ConsoleWriter;
use App\Shell;

class ValidateGitHubConfiguration
{
    use InteractsWithGitHub;

    public const WARNING_UNABLE_TO_CREATE_REPOSITORY = 'Unable to create a new GitHub repository';

    public const INSTRUCTIONS_GITHUB_TOOLING_MISSING = [
        'For Filament Installer to initialize a new repository on GitHub it requires either the',
        'official GitHub command line tool or the unofficial hub tool, but neither',
        'are installed. You can find more information about each tool by visiting:',
        '    - <fg=blue;options=underscore>https://github.com/cli/cli#installation</>',
        '    - <fg=blue;options=underscore>https://github.com/github/hub</>',
    ];

    public const INSTRUCTIONS_GH_NOT_AUTHENTICATED = [
        'You are not logged into GitHub. Please run <comment>gh auth login</comment>.',
        'For more information, please visit, <fg=blue;options=underscore>https://cli.github.com/manual/gh_auth_login</>',
    ];

    public const QUESTION_SHOULD_CONTINUE = 'Would you like Filament Installer to continue without GitHub repository creation?';

    public const SELECTED_GITHUB_TOOL_MESSAGE_PATTERN = "Using the '%s' command for GitHub configuration.";

    private $consoleWriter;

    private $shell;

    public function __construct(Shell $shell, ConsoleWriter $consoleWriter)
    {
        $this->consoleWriter = $consoleWriter;
        $this->shell = $shell;
    }

    public function __invoke()
    {
        if (! static::gitHubInitializationRequested()) {
            config(['installer.store.' . InstallerConfiguration::INITIALIZE_GITHUB => false]);

            return;
        }

        if (! static::gitHubToolingInstalled()) {
            $this->consoleWriter->warn(self::WARNING_UNABLE_TO_CREATE_REPOSITORY);
            $this->consoleWriter->text(self::INSTRUCTIONS_GITHUB_TOOLING_MISSING);
            $this->askToContinueWithoutGitHubSetup();

            return;
        }

        if (static::hubInstalled()) {
            $this->consoleWriter->note(sprintf(self::SELECTED_GITHUB_TOOL_MESSAGE_PATTERN, 'hub'));

            return;
        }

        if (! $this->ghAuthenticated()) {
            $this->consoleWriter->warn(self::WARNING_UNABLE_TO_CREATE_REPOSITORY);
            $this->consoleWriter->text(self::INSTRUCTIONS_GH_NOT_AUTHENTICATED);
            $this->askToContinueWithoutGitHubSetup();

            return;
        }

        $this->consoleWriter->note(sprintf(self::SELECTED_GITHUB_TOOL_MESSAGE_PATTERN, 'gh'));
    }

    private function askToContinueWithoutGitHubSetup()
    {
        config(['installer.store.' . InstallerConfiguration::INITIALIZE_GITHUB => false]);

        if (! app('console')->confirm(self::QUESTION_SHOULD_CONTINUE)) {
            exit;
        }
    }

    private function ghAuthenticated(): bool
    {
        $process = $this->shell->execQuietly('gh auth status');

        return $process->isSuccessful();
    }
}
