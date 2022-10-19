<?php

namespace App\Actions;

use App\ConsoleWriter;
use App\Shell;
use Symfony\Component\Process\Process;

class RunShieldInstall
{
    use AbortsCommands;

    protected $shell;

    protected $consoleWriter;

    public function __construct(Shell $shell, ConsoleWriter $consoleWriter)
    {
        $this->shell = $shell;
        $this->consoleWriter = $consoleWriter;
    }

    public function __invoke()
    {
        if (! (config('installer.store.shield') || config('installer.store.sentry'))) {
            return;
        }

        if (! config('installer.store.migrate_database')) {
            app('final-steps')->add('Set up your database .env settings');
            app('final-steps')->add('Run <info>php artisan shield:install --fresh</info>');

            return;
        }

        $this->consoleWriter->logStep('Running Filament Shield Setup');

        $cwd = getcwd();
        chdir(config('installer.store.project_path'));

        $process = Process::fromShellCommandline('php artisan shield:install --fresh');

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            try {
                $process->setTty(true);
            } catch (RuntimeException $e) {
                $output->writeln('  <bg=yellow;fg=black> WARN </> ' . $e->getMessage() . PHP_EOL);
            }
        }

        $process->run(function ($type, $line) {
            $this->consoleWriter->write('    ' . $line);
        });

        $this->abortIf(! $process->isSuccessful(), 'Filament Shield setup failed.', $process);

        chdir($cwd);
    }
}
