<?php

namespace Tests\Feature;

use App\Actions\CustomizeDotEnv;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CustomizeDotEnvTest extends TestCase
{
    /** @test */
    public function it_saves_the_customized_dot_env_files()
    {
        config(['installer.store.project_name' => 'my-project']);
        config(['installer.store.database_name' => 'my_project']);
        config(['installer.store.project_url' => 'http://my-project.example.com']);
        config(['installer.store.database_username' => 'username']);
        config(['installer.store.database_password' => 'password']);
        config(['installer.store.project_path' => '/some/project/path']);

        $originalDotEnv = File::get(base_path('tests/Feature/Fixtures/.env.original'));
        $customizedDotEnv = File::get(base_path('tests/Feature/Fixtures/.env.customized'));

        File::shouldReceive('get')
            ->once()
            ->with('/some/project/path/.env.example')
            ->andReturn($originalDotEnv);

        File::shouldReceive('put')
            ->with('/some/project/path/.env.example', $customizedDotEnv);

        File::shouldReceive('put')
            ->with('/some/project/path/.env', $customizedDotEnv);

        app(CustomizeDotEnv::class)();
    }

    /** @test */
    public function it_replaces_static_strings()
    {
        config()->set('installer.store.database_username', 'root');

        $customizeDotEnv = app(CustomizeDotEnv::class);
        $contents = 'DB_USERNAME=previous';
        $contents = $customizeDotEnv->customize($contents);
        $this->assertEquals('DB_USERNAME=root', $contents);
    }

    /** @test */
    public function un_targeted_lines_are_unchanged()
    {
        config()->set('installer.store.database_username', 'root');

        $customizeDotEnv = app(CustomizeDotEnv::class);
        $contents = "DB_USERNAME=previous\nDONT_TOUCH_ME=cant_touch_me";
        $contents = $customizeDotEnv->customize($contents);
        $this->assertEquals("DB_USERNAME=root\nDONT_TOUCH_ME=cant_touch_me", $contents);
    }

    /** @test */
    public function lines_with_no_equals_are_unchanged()
    {
        $customizeDotEnv = app(CustomizeDotEnv::class);
        $contents = "SOME_VALUE=previous\nABCDEFGNOEQUALS";
        $contents = $customizeDotEnv->customize($contents);
        $this->assertEquals("SOME_VALUE=previous\nABCDEFGNOEQUALS", $contents);
    }

    /** @test */
    public function line_breaks_remain()
    {
        $customizeDotEnv = app(CustomizeDotEnv::class);
        $contents = "A=B\n\nC=D";
        $contents = $customizeDotEnv->customize($contents);
        $this->assertEquals("A=B\n\nC=D", $contents);
    }
}
