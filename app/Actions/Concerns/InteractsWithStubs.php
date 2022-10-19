<?php

namespace App\Actions\Concerns;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait InteractsWithStubs
{
    protected function publishStub(string $file, string $stub): void
    {
        $projectPath = config('installer.store.project_path');

        File::put($projectPath . '/' . ltrim($file, '/'), File::get(base_path('stubs/' . ltrim($stub, '/'))));
    }

    protected function copyStubToApp(string $stub, string $targetPath, array $replacements = []): void
    {
        $filesystem = new Filesystem();
        $projectPath = config('installer.store.project_path');

        if (! $this->fileExists($stubPath = base_path("stubs/{$stub}.stub"))) {
            $stubPath = __DIR__ . "/../../../stubs/{$stub}.stub";
        }

        $stub = Str::of($filesystem->get($stubPath));

        foreach ($replacements as $key => $replacement) {
            $stub = $stub->replace("{{ {$key} }}", is_array($replacement) ? json_encode($replacement) : $replacement);
        }

        $stub = (string) $stub;

        $this->writeFile($projectPath . $targetPath, $stub);
    }

    protected function fileExists(string $path): bool
    {
        $filesystem = new Filesystem();

        return $filesystem->exists($path);
    }

    protected function writeFile(string $path, string $contents): void
    {
        $filesystem = new Filesystem();

        $filesystem->ensureDirectoryExists(
            (string) Str::of($path)
                ->beforeLast(DIRECTORY_SEPARATOR),
        );

        $filesystem->put($path, $contents);
    }
}
