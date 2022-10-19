<?php

namespace Tests\Feature;

use App\Actions\InstallNpmDependencies;
use App\InstallerException;
use Tests\Feature\Fakes\FakeProcess;
use Tests\TestCase;

class InstallNpmDependenciesTest extends TestCase
{
    /** @test */
    public function it_installs_npm_dependencies()
    {
        config(['installer.store.with_output' => false]);

        $this->shell->shouldReceive('execInProject')
            ->with('npm install --silent')
            ->once()
            ->andReturn(FakeProcess::success());

        $this->shell->shouldReceive('execInProject')
            ->with('npm run build --silent')
            ->once()
            ->andReturn(FakeProcess::success());

        app(InstallNpmDependencies::class)();
    }

    /** @test */
    public function it_installs_npm_dependencies_and_shows_console_output()
    {
        config(['installer.store.with_output' => true]);

        $this->shell->shouldReceive('execInProject')
            ->with('npm install')
            ->once()
            ->andReturn(FakeProcess::success());

        $this->shell->shouldReceive('execInProject')
            ->with('npm run build')
            ->once()
            ->andReturn(FakeProcess::success());

        app(InstallNpmDependencies::class)();
    }

    /** @test */
    public function it_throws_an_exception_if_npm_install_fails()
    {
        config(['installer.store.with_output' => false]);

        $this->shell->shouldReceive('execInProject')
            ->with('npm install --silent')
            ->once()
            ->andReturn(FakeProcess::fail('npm install --silent'));

        $this->expectException(InstallerException::class);

        app(InstallNpmDependencies::class)();
    }
}
