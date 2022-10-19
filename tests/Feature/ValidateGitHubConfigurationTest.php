<?php

namespace Tests\Feature;

use App\Actions\ValidateGitHubConfiguration;
use App\Configuration\InstallerConfiguration;
use App\ConsoleWriter;
use LaravelZero\Framework\Commands\Command;
use Tests\Feature\Fakes\FakeProcess;
use Tests\TestCase;

/**
 * @group git-and-github
 */
class ValidateGitHubConfigurationTest extends TestCase
{
    private $consoleWriter;

    private $console;

    /** @test */
    public function it_skips_gh_command_line_tool_validation()
    {
        config(['installer.store.tools.gh' => true]);
        config(['installer.store.tools.hub' => true]);

        config(['installer.store.initializeGitHub' => null]);
        app(ValidateGitHubConfiguration::class)();
        $this->assertFalse(config('installer.store.' . InstallerConfiguration::INITIALIZE_GITHUB));

        config(['installer.store.' . InstallerConfiguration::INITIALIZE_GITHUB => false]);
        app(ValidateGitHubConfiguration::class)();
        $this->assertFalse(config('installer.store.' . InstallerConfiguration::INITIALIZE_GITHUB));
    }

    /** @test */
    public function it_logs_a_warning_if_github_tooling_is_missing()
    {
        $this->consoleWriter = $this->mock(ConsoleWriter::class);
        $this->console = $this->mock(Command::class);

        config(['installer.store.' . InstallerConfiguration::INITIALIZE_GITHUB => true]);
        config(['installer.store.tools.gh' => false]);
        config(['installer.store.tools.hub' => false]);

        $this->shouldLogWarning();
        $this->shouldLogInstructions(ValidateGitHubConfiguration::INSTRUCTIONS_GITHUB_TOOLING_MISSING);
        $this->shouldAskToContinue();

        app(ValidateGitHubConfiguration::class)();

        $this->assertFalse(config('installer.store.' . InstallerConfiguration::INITIALIZE_GITHUB));
    }

    /** @test */
    public function configuration_is_valid_if_hub_is_installed()
    {
        $this->consoleWriter = $this->mock(ConsoleWriter::class);

        config(['installer.store.' . InstallerConfiguration::INITIALIZE_GITHUB => true]);
        config(['installer.store.tools.gh' => true]);
        config(['installer.store.tools.hub' => true]);

        $this->shouldLogChosenGitHubTool('hub');

        app(ValidateGitHubConfiguration::class)();

        $this->assertTrue(config('installer.store.' . InstallerConfiguration::INITIALIZE_GITHUB));
    }

    /** @test */
    public function configuration_is_valid_if_gh_is_installed_and_authenticated()
    {
        $this->consoleWriter = $this->mock(ConsoleWriter::class);

        config(['installer.store.' . InstallerConfiguration::INITIALIZE_GITHUB => true]);
        config(['installer.store.tools.gh' => true]);
        config(['installer.store.tools.hub' => false]);

        $this->shell->shouldReceive('execQuietly')
            ->with('gh auth status')
            ->andReturn(FakeProcess::success());

        $this->shouldLogChosenGitHubTool('gh');

        app(ValidateGitHubConfiguration::class)();

        $this->assertTrue(config('installer.store.' . InstallerConfiguration::INITIALIZE_GITHUB));
    }

    /** @test */
    public function it_logs_a_warning_if_gh_is_not_authenticated_with_github()
    {
        $this->consoleWriter = $this->mock(ConsoleWriter::class);
        $this->console = $this->mock(Command::class);

        config(['installer.store.' . InstallerConfiguration::INITIALIZE_GITHUB => true]);
        config(['installer.store.tools.gh' => true]);
        config(['installer.store.tools.hub' => false]);

        $this->shell->shouldReceive('execQuietly')
            ->with('gh auth status')
            ->andReturn(FakeProcess::fail('gh auth status'));

        $this->shouldLogWarning();
        $this->shouldLogInstructions(ValidateGitHubConfiguration::INSTRUCTIONS_GH_NOT_AUTHENTICATED);
        $this->shouldAskToContinue();

        app(ValidateGitHubConfiguration::class)();

        $this->assertFalse(config('installer.store.' . InstallerConfiguration::INITIALIZE_GITHUB));
    }

    private function shouldLogChosenGitHubTool(string $tool): void
    {
        $this->consoleWriter->shouldReceive('note')
            ->with(sprintf(ValidateGitHubConfiguration::SELECTED_GITHUB_TOOL_MESSAGE_PATTERN, $tool))
            ->globally()
            ->ordered();
    }

    private function shouldLogWarning(): void
    {
        $this->consoleWriter->shouldReceive('warn')
            ->with(ValidateGitHubConfiguration::WARNING_UNABLE_TO_CREATE_REPOSITORY)
            ->globally()
            ->ordered();
    }

    private function shouldAskToContinue(): void
    {
        $this->console->shouldReceive('confirm')
            ->with(ValidateGitHubConfiguration::QUESTION_SHOULD_CONTINUE)
            ->andReturnTrue()
            ->globally()
            ->ordered();
        $this->swap('console', $this->console);
    }

    private function shouldLogInstructions(array $instructions): void
    {
        $this->consoleWriter->shouldReceive('text')
            ->with($instructions)
            ->globally()
            ->ordered();
    }
}
