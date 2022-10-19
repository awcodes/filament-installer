<?php

namespace Tests\Feature;

use App\Actions\OpenInEditor;
use App\InstallerException;
use Tests\Feature\Fakes\FakeProcess;
use Tests\TestCase;

class OpenInEditorTest extends TestCase
{
    /** @test */
    public function it_opens_the_project_folder_in_the_specified_editor()
    {
        config(['installer.store.editor' => 'my-editor']);

        $this->shell->shouldReceive('withTTY')
            ->once()
            ->andReturnSelf();

        $this->shell->shouldReceive('execInProject')
            ->with('my-editor .')
            ->once()
            ->andReturn(FakeProcess::success());

        app(OpenInEditor::class)();
    }

    /** @test */
    public function it_throws_an_exception_if_it_fails_to_open_the_editor()
    {
        config(['installer.store.editor' => 'my-editor']);

        $this->shell->shouldReceive('withTTY')
            ->once()
            ->andReturnSelf();

        $this->shell->shouldReceive('execInProject')
            ->with('my-editor .')
            ->once()
            ->andReturn(FakeProcess::fail('my-editor .'));

        $this->expectException(InstallerException::class);

        app(OpenInEditor::class)();
    }
}
