<?php

namespace Tests\Feature;

use App\Actions\ValetLink;
use App\InstallerException;
use Tests\Feature\Fakes\FakeProcess;
use Tests\TestCase;

class ValetLinkTest extends TestCase
{
    /** @test */
    public function it_runs_valet_link()
    {
        config(['installer.store.valet_link' => true]);

        $this->shell->shouldReceive('execInProject')
            ->with('valet link')
            ->once()
            ->andReturn(FakeProcess::success());

        app(ValetLink::class)();
    }

    /** @test */
    public function it_throws_an_exception_if_valet_link_fails()
    {
        config(['installer.store.valet_link' => true]);

        $command = 'valet link';
        $this->shell->shouldReceive('execInProject')
            ->with($command)
            ->once()
            ->andReturn(FakeProcess::fail($command));

        $this->expectException(InstallerException::class);

        app(ValetLink::class)();
    }
}
