<?php

namespace Tests\Feature;

use App\Actions\VerifyDependencies;
use App\InstallerException;
use Symfony\Component\Process\ExecutableFinder;
use Tests\TestCase;

class VerifyDependenciesTest extends TestCase
{
    private $executableFinder;

    public function setUp(): void
    {
        parent::setUp();
        $this->executableFinder = $this->mock(ExecutableFinder::class);
    }

    /** @test */
    public function it_checks_that_dependencies_are_available()
    {
        foreach (['composer', 'valet', 'git', 'hub', 'gh'] as $dependency) {
            $this->dependencyIsAvailable($dependency);
        }

        app(VerifyDependencies::class)();
        $this->assertTrue(config('installer.store.tools.gh'));
        $this->assertTrue(config('installer.store.tools.hub'));
    }

    /** @test */
    public function it_marks_optional_dependencies_as_missing()
    {
        foreach (['composer', 'valet', 'git'] as $dependency) {
            $this->dependencyIsAvailable($dependency);
        }

        $this->dependencyIsMissing('gh');
        $this->dependencyIsMissing('hub');

        app(VerifyDependencies::class)();
        $this->assertFalse(config('installer.store.tools.gh'));
        $this->assertFalse(config('installer.store.tools.hub'));
    }

    /** @test */
    public function it_throws_an_installer_exception_if_composer_is_missing()
    {
        foreach (['valet', 'git', 'hub', 'gh'] as $dependency) {
            $this->dependencyIsAvailable($dependency);
        }

        $this->dependencyIsMissing('composer');

        $this->expectException(InstallerException::class);

        app(VerifyDependencies::class)();
    }

    /** @test */
    public function it_throws_an_installer_exception_if_valet_is_missing()
    {
        foreach (['composer', 'git', 'hub', 'gh'] as $dependency) {
            $this->dependencyIsAvailable($dependency);
        }

        $this->dependencyIsMissing('valet');

        $this->expectException(InstallerException::class);

        app(VerifyDependencies::class)();
    }

    /** @test */
    public function it_throws_an_installer_exception_if_git_is_missing()
    {
        foreach (['composer', 'valet', 'hub', 'gh'] as $dependency) {
            $this->dependencyIsAvailable($dependency);
        }

        $this->dependencyIsMissing('git');

        $this->expectException(InstallerException::class);

        app(VerifyDependencies::class)();
    }

    private function dependencyIsAvailable(string $dependency, $isAvailable = true): void
    {
        $foo = $this->executableFinder
            ->shouldReceive('find')
            ->with($dependency);

        $isAvailable
            ? $foo->andReturn("/path/to/{$dependency}")
            : $foo->andReturnNull();
    }

    private function dependencyIsMissing(string $dependency)
    {
        $this->dependencyIsAvailable($dependency, false);
    }
}
