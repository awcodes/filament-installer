<?php

namespace Tests\Feature;

use App\Actions\InstallLaravel;
use App\InstallerException;
use Tests\Feature\Fakes\FakeProcess;
use Tests\TestCase;

class InstallLaravelTest extends TestCase
{
    /** @test */
    public function it_installs_laravel()
    {
        collect([
            ['installer.store.dev' => false, 'installer.store.with_output' => false],
            ['installer.store.dev' => false, 'installer.store.with_output' => true],
            ['installer.store.dev' => true, 'installer.store.with_output' => false],
            ['installer.store.dev' => true, 'installer.store.with_output' => true],
        ])->each(function ($options) {
            config(['installer.store.project_name' => 'my-project']);
            config(['installer.store.dev' => $options['installer.store.dev']]);
            config(['installer.store.with_output' => $options['installer.store.with_output']]);
            $this->shell->shouldReceive('execInRoot')
                ->with(sprintf(
                    'composer create-project laravel/laravel %s%s --remove-vcs --prefer-dist %s',
                    config('installer.store.project_name'),
                    config('installer.store.dev') ? ' dev-master' : '',
                    config('installer.store.with_output') ? '' : '--quiet'
                ))
                ->once()
                ->andReturn(FakeProcess::success());

            app(InstallLaravel::class)();
        });
    }

    /** @test */
    public function it_throws_an_exception_if_laravel_fails_to_install()
    {
        config(['installer.store.project_name' => 'my-project']);
        config(['installer.store.dev' => false]);
        config(['installer.store.with_output' => false]);

        $this->shell->shouldReceive('execInRoot')
            ->andReturn(FakeProcess::fail('failed command'));

        $this->expectException(InstallerException::class);

        app(InstallLaravel::class)();
    }
}
