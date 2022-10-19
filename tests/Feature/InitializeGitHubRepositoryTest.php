<?php

namespace Tests\Feature;

use App\Actions\Concerns\InteractsWithGitHub;
use App\Actions\InitializeGitHubRepository;
use App\Configuration\InstallerConfiguration;
use App\ConsoleWriter;
use App\InstallerException;
use Tests\Feature\Fakes\FakeProcess;
use Tests\TestCase;

/**
 * @group git-and-github
 */
class InitializeGitHubRepositoryTest extends TestCase
{
    use InteractsWithGitHub;

    protected $toolConfigurations = [
        ['gh' => true, 'hub' => true],
        ['gh' => true, 'hub' => false],
        ['gh' => false, 'hub' => true],
        ['gh' => false, 'hub' => false],
    ];

    protected $gitHubConfigurations = [
        [
            InstallerConfiguration::GITHUB_PUBLIC => false,
            InstallerConfiguration::GITHUB_DESCRIPTION => null,
            InstallerConfiguration::GITHUB_HOMEPAGE => null,
            InstallerConfiguration::GITHUB_ORGANIZATION => null,
        ],
        [
            InstallerConfiguration::GITHUB_PUBLIC => false,
            InstallerConfiguration::GITHUB_DESCRIPTION => null,
            InstallerConfiguration::GITHUB_HOMEPAGE => null,
            InstallerConfiguration::GITHUB_ORGANIZATION => 'org',
        ],
        [
            InstallerConfiguration::GITHUB_PUBLIC => false,
            InstallerConfiguration::GITHUB_DESCRIPTION => null,
            InstallerConfiguration::GITHUB_HOMEPAGE => 'https://example.com',
            InstallerConfiguration::GITHUB_ORGANIZATION => null,
        ],
        [
            InstallerConfiguration::GITHUB_PUBLIC => false,
            InstallerConfiguration::GITHUB_DESCRIPTION => null,
            InstallerConfiguration::GITHUB_HOMEPAGE => 'https://example.com',
            InstallerConfiguration::GITHUB_ORGANIZATION => 'org',
        ],
        [
            InstallerConfiguration::GITHUB_PUBLIC => false,
            InstallerConfiguration::GITHUB_DESCRIPTION => 'My awesome project',
            InstallerConfiguration::GITHUB_HOMEPAGE => null,
            InstallerConfiguration::GITHUB_ORGANIZATION => null,
        ],
        [
            InstallerConfiguration::GITHUB_PUBLIC => false,
            InstallerConfiguration::GITHUB_DESCRIPTION => 'My awesome project',
            InstallerConfiguration::GITHUB_HOMEPAGE => null,
            InstallerConfiguration::GITHUB_ORGANIZATION => 'org',
        ],
        [
            InstallerConfiguration::GITHUB_PUBLIC => false,
            InstallerConfiguration::GITHUB_DESCRIPTION => 'My awesome project',
            InstallerConfiguration::GITHUB_HOMEPAGE => 'https://example.com',
            InstallerConfiguration::GITHUB_ORGANIZATION => null,
        ],
        [
            InstallerConfiguration::GITHUB_PUBLIC => false,
            InstallerConfiguration::GITHUB_DESCRIPTION => 'My awesome project',
            InstallerConfiguration::GITHUB_HOMEPAGE => 'https://example.com',
            InstallerConfiguration::GITHUB_ORGANIZATION => 'org',
        ],
        [
            InstallerConfiguration::GITHUB_PUBLIC => true,
            InstallerConfiguration::GITHUB_DESCRIPTION => null,
            InstallerConfiguration::GITHUB_HOMEPAGE => null,
            InstallerConfiguration::GITHUB_ORGANIZATION => null,
        ],
        [
            InstallerConfiguration::GITHUB_PUBLIC => true,
            InstallerConfiguration::GITHUB_DESCRIPTION => null,
            InstallerConfiguration::GITHUB_HOMEPAGE => null,
            InstallerConfiguration::GITHUB_ORGANIZATION => 'org',
        ],
        [
            InstallerConfiguration::GITHUB_PUBLIC => true,
            InstallerConfiguration::GITHUB_DESCRIPTION => null,
            InstallerConfiguration::GITHUB_HOMEPAGE => 'https://example.com',
            InstallerConfiguration::GITHUB_ORGANIZATION => null,
        ],
        [
            InstallerConfiguration::GITHUB_PUBLIC => true,
            InstallerConfiguration::GITHUB_DESCRIPTION => null,
            InstallerConfiguration::GITHUB_HOMEPAGE => 'https://example.com',
            InstallerConfiguration::GITHUB_ORGANIZATION => 'org',
        ],
        [
            InstallerConfiguration::GITHUB_PUBLIC => true,
            InstallerConfiguration::GITHUB_DESCRIPTION => 'My awesome project',
            InstallerConfiguration::GITHUB_HOMEPAGE => null,
            InstallerConfiguration::GITHUB_ORGANIZATION => null,
        ],
        [
            InstallerConfiguration::GITHUB_PUBLIC => true,
            InstallerConfiguration::GITHUB_DESCRIPTION => 'My awesome project',
            InstallerConfiguration::GITHUB_HOMEPAGE => null,
            InstallerConfiguration::GITHUB_ORGANIZATION => 'org',
        ],
        [
            InstallerConfiguration::GITHUB_PUBLIC => true,
            InstallerConfiguration::GITHUB_DESCRIPTION => 'My awesome project',
            InstallerConfiguration::GITHUB_HOMEPAGE => 'https://example.com',
            InstallerConfiguration::GITHUB_ORGANIZATION => null,
        ],
        [
            InstallerConfiguration::GITHUB_PUBLIC => true,
            InstallerConfiguration::GITHUB_DESCRIPTION => 'My awesome project',
            InstallerConfiguration::GITHUB_HOMEPAGE => 'https://example.com',
            InstallerConfiguration::GITHUB_ORGANIZATION => 'org',
        ],
    ];

    /** @test */
    public function it_manages_new_repository_initialization()
    {
        foreach ([true, false] as $initializeGitHub) {
            foreach ($this->toolConfigurations as $toolConfiguration) {
                foreach ($this->gitHubConfigurations as $gitHubConfiguration) {
                    config(['installer.store.project_name' => 'name']);
                    config(['installer.store.' . InstallerConfiguration::INITIALIZE_GITHUB => $initializeGitHub]);
                    config(['installer.store.push_to_github' => false]);
                    config(['installer.store.tools' => $toolConfiguration]);
                    config(['installer.store' => array_merge(config('installer.store'), $gitHubConfiguration)]);

                    if ($this->shouldCreateRepository()) {
                        $this->shell->shouldReceive('execInProject', [$this->getGitHubCreateCommand()])
                            ->andReturn(FakeProcess::success());
                    }

                    if (! $this->gitHubToolingInstalled()) {
                        $this->expectException(InstallerException::class);
                    }

                    app(InitializeGitHubRepository::class)();

                    if ($this->shouldCreateRepository()) {
                        $this->assertTrue(config('installer.store.push_to_github'));
                    }
                }
            }
        }
    }

    /** @test */
    public function it_warns_the_user_if_repository_creation_fails()
    {
        $consoleWriter = $this->mock(ConsoleWriter::class);
        $consoleWriter->shouldReceive('logStep');

        config(['installer.store.project_name' => 'name']);
        config(['installer.store.' . InstallerConfiguration::INITIALIZE_GITHUB => true]);
        config(['installer.store.push_to_github' => false]);
        config(['installer.store.tools.gh' => true]);

        $failedCommandOutput = 'Failed command output';

        $this->shell->shouldReceive('execInProject')
            ->with($this->getGitHubCreateCommand())
            ->once()
            ->andReturn(FakeProcess::fail($this->getGitHubCreateCommand())->withErrorOutput($failedCommandOutput));

        $consoleWriter->shouldReceive('warn')
            ->with(InitializeGitHubRepository::WARNING_FAILED_TO_CREATE_REPOSITORY)
            ->globally()
            ->ordered();

        $consoleWriter->shouldReceive('warnCommandFailed')
            ->with($this->getGitHubCreateCommand())
            ->globally()
            ->ordered();

        $consoleWriter->shouldReceive('showOutputErrors')
            ->with($failedCommandOutput)
            ->globally()
            ->ordered();

        app(InitializeGitHubRepository::class)();
    }
}
