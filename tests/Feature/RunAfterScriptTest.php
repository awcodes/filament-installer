<?php

namespace Tests\Feature;

use App\Actions\RunAfterScript;
use App\InstallerException;
use Illuminate\Support\Facades\File;
use Tests\Feature\Fakes\FakeProcess;
use Tests\TestCase;

class RunAfterScriptTest extends TestCase
{
    /** @test */
    public function it_runs_the_after_script_if_one_exists()
    {
        config(['home_dir' => '/my/home/dir']);

        File::shouldReceive('isFile')
            ->with('/my/home/dir/.filament/after')
            ->andReturn(true)
            ->globally()
            ->ordered();

        $this->shell->shouldReceive('execInProject')
            ->with('sh /my/home/dir/.filament/after')
            ->once()
            ->andReturn(FakeProcess::success())
            ->globally()
            ->ordered();

        app(RunAfterScript::class)();
    }

    /** @test */
    public function it_throws_an_exception_if_the_after_script_fails()
    {
        config(['home_dir' => '/my/home/dir']);

        File::shouldReceive('isFile')
            ->with('/my/home/dir/.filament/after')
            ->andReturn(true)
            ->globally()
            ->ordered();

        $this->shell->shouldReceive('execInProject')
            ->with('sh /my/home/dir/.filament/after')
            ->once()
            ->andReturn(FakeProcess::fail('sh /my/home/dir/.filament/after'))
            ->globally()
            ->ordered();

        $this->expectException(InstallerException::class);

        app(RunAfterScript::class)();
    }
}
