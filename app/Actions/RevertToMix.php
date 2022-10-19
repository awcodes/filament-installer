<?php

namespace App\Actions;

use App\Actions\Concerns\InteractsWithComposer;
use App\Actions\Concerns\InteractsWithNpm;
use App\Actions\Concerns\InteractsWithStubs;
use App\Actions\Concerns\ReplaceInFile;
use App\ConsoleWriter;
use App\Shell;
use Illuminate\Support\Facades\File;

class RevertToMix
{
    use AbortsCommands;
    use InteractsWithComposer;
    use ReplaceInFile;
    use InteractsWithStubs;
    use InteractsWithNpm;

    private $shell;

    private $consoleWriter;

    public function __construct(Shell $shell, ConsoleWriter $consoleWriter)
    {
        $this->shell = $shell;
        $this->consoleWriter = $consoleWriter;
    }

    public function __invoke()
    {
        if (! config('installer.store.mix')) {
            return;
        }

        $this->consoleWriter->logStep('Reverting to Laravel Mix');

        $this->publishStub('package.json', 'mix/package.json');
        $this->publishStub('webpack.mix.js', 'mix/webpack.mix.js');
        $this->publishStub('resources/js/bootstrap.js', 'mix/bootstrap.js');

        if (File::exists(config('installer.store.project_path') . '/.env.example')) {
            $this->replaceInProjectFile('VITE_', 'MIX_', '/.env.example');
        }

        if (File::exists(config('installer.store.project_path') . '/.env')) {
            $this->replaceInProjectFile('VITE_', 'MIX_', '/.env');
        }

        $process = $this->shell->execInProject('rm vite.config.js');

        if (! $process->isSuccessful()) {
            app('final-steps')->add('Delete vite.config.js');
            $this->warn('Failed to delete vite.config.js.');
        }

        $this->consoleWriter->success('Successfully reverted to Laravel Mix.');
    }
}
