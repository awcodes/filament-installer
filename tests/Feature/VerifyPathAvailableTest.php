<?php

namespace Tests\Feature;

use App\Actions\VerifyPathAvailable;
use App\InstallerException;
use Exception;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class VerifyPathAvailableTest extends TestCase
{
    /** @test */
    public function it_checks_if_the_required_directories_are_available()
    {
        config(['installer.store.root_path' => '/some/filesystem/path']);
        config(['installer.store.project_path' => '/some/filesystem/path/my-project']);

        File::shouldReceive('isDirectory')
            ->with('/some/filesystem/path')
            ->once()
            ->andReturn(true);

        File::shouldReceive('isDirectory')
            ->with('/some/filesystem/path/my-project')
            ->once()
            ->andReturn(false);

        app(VerifyPathAvailable::class)();
    }

    /** @test */
    public function it_throws_an_installer_exception_if_the_root_path_is_not_available()
    {
        config(['installer.store.root_path' => '/non/existent/filesystem/path']);

        File::shouldReceive('isDirectory')
            ->with('/non/existent/filesystem/path')
            ->once()
            ->andReturn(false);

        $this->expectException(InstallerException::class);

        app(VerifyPathAvailable::class)();
    }

    /** @test */
    public function it_throws_an_installer_exception_if_the_project_path_already_exists()
    {
        config(['installer.store.root_path' => '/some/filesystem/path']);
        config(['installer.store.project_path' => '/some/filesystem/path/existing-directory']);

        File::shouldReceive('isDirectory')
            ->with('/some/filesystem/path')
            ->once()
            ->andReturn(true)
            ->globally()
            ->ordered();

        File::shouldReceive('isDirectory')
            ->with('/some/filesystem/path/existing-directory')
            ->once()
            ->andReturn(true)
            ->globally()
            ->ordered();

        $this->expectException(InstallerException::class);

        app(VerifyPathAvailable::class)();
    }

    /** @test */
    public function it_ignores_a_pre_existing_directory_if_the_force_option_is_specified()
    {
        config(['installer.store.root_path' => '/some/filesystem/path']);
        config(['installer.store.project_path' => '/some/filesystem/path/existing-directory']);
        config(['installer.store.force_create' => true]);

        File::shouldReceive('isDirectory')
            ->with('/some/filesystem/path')
            ->once()
            ->andReturn(true)
            ->globally()
            ->ordered();

        File::shouldReceive('isDirectory')
            ->with('/some/filesystem/path/existing-directory')
            ->once()
            ->andReturn(true)
            ->globally()
            ->ordered();

        File::shouldReceive('deleteDirectory')
            ->with('/some/filesystem/path/existing-directory')
            ->once()
            ->andReturn(true)
            ->globally()
            ->ordered();

        app(VerifyPathAvailable::class)();
    }

    /** @test */
    public function it_throws_an_installer_exception_if_it_fails_to_delete_the_pre_existing_directory()
    {
        config(['installer.store.root_path' => '/some/filesystem/path']);
        config(['installer.store.project_path' => '/some/filesystem/path/existing-directory']);
        config(['installer.store.force_create' => true]);

        File::shouldReceive('isDirectory')
            ->with('/some/filesystem/path')
            ->once()
            ->andReturn(true)
            ->globally()
            ->ordered();

        File::shouldReceive('isDirectory')
            ->with('/some/filesystem/path/existing-directory')
            ->once()
            ->andReturn(true)
            ->globally()
            ->ordered();

        File::shouldReceive('deleteDirectory')
            ->with('/some/filesystem/path/existing-directory')
            ->once()
            ->andReturn(false)
            ->globally()
            ->ordered();

        $this->expectException(InstallerException::class);

        app(VerifyPathAvailable::class)();
    }

    /** @test */
    public function it_throws_an_exception_if_project_path_is_empty()
    {
        config(['installer.store.root_path' => '/some/filesystem/path']);
        config(['installer.store.project_path' => '']);

        File::shouldReceive('isDirectory')
            ->with('/some/filesystem/path')
            ->once()
            ->andReturn(true)
            ->globally()
            ->ordered();

        $this->expectException(Exception::class);

        app(VerifyPathAvailable::class)();
    }

    /** @test */
    public function it_throws_an_exception_if_project_path_is_null()
    {
        config(['installer.store.root_path' => '/some/filesystem/path']);
        config(['installer.store.project_path' => null]);

        File::shouldReceive('isDirectory')
            ->with('/some/filesystem/path')
            ->once()
            ->andReturn(true)
            ->globally()
            ->ordered();

        $this->expectException(Exception::class);

        app(VerifyPathAvailable::class)();
    }
}
