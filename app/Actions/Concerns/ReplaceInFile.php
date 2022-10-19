<?php

namespace App\Actions\Concerns;

trait ReplaceInFile
{
    protected function replaceInProjectFile(string $search, string $replace, string $file)
    {
        $projectPath = config('installer.store.project_path');
        $file = $projectPath . '/' . ltrim($file, '/');

        return file_put_contents(
            $file,
            str_replace($search, $replace, file_get_contents($file))
        );
    }
}
