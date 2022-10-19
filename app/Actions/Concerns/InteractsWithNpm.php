<?php

namespace App\Actions\Concerns;

use App\Actions\AbortsCommands;
use App\Shell;

trait InteractsWithNpm
{
    use AbortsCommands;

    /**
     * @throws \App\InstallerException
     */
    protected function npmInstall(string $packages, bool $forDev = true): void
    {
        $command = $this->getNpmInstallCommand($packages, $forDev);
        $npmProcess = app(Shell::class)->execInProject($command);
        $this->abortIf(! $npmProcess->isSuccessful(), 'Installation of npm dependencies did not complete successfully.', $npmProcess);
    }

    protected function getNpmInstallCommand(string $packages, bool $forDev): string
    {
        return sprintf(
            'npm install %s%s%s',
            $packages,
            $forDev ? ' --save-dev' : '',
            config('installer.store.with_output') ? '' : ' --silent'
        );
    }

    /**
     * @throws \App\InstallerException
     */
    protected function installAndCompileNodeDependencies(): void
    {
        $this->installNodeDependencies();
        $this->compileNodeDependencies();
    }

    /**
     * @throws \App\InstallerException
     */
    public function installNodeDependencies(): void
    {
        $process = app(Shell::class)->execInProject('npm install' . (config('installer.store.with_output') ? '' : ' --silent'));
        $this->abortIf(! $process->isSuccessful(), 'Installation of npm dependencies did not complete successfully', $process);
    }

    /**
     * @throws \App\InstallerException
     */
    protected function compileNodeDependencies(): void
    {
        $process = app(Shell::class)->execInProject('npm run' . (config('installer.store.mix') ? ' production' : ' build') . (config('installer.store.with_output') ? '' : ' --silent'));
        $this->abortIf(! $process->isSuccessful(), 'Compilation of project assets did not complete successfully', $process);
    }
}
