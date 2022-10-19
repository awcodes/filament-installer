<?php

namespace App\Actions;

use App\ConsoleWriter;
use Symfony\Component\Process\ExecutableFinder;

class VerifyDependencies
{
    use AbortsCommands;

    protected $finder;

    protected $consoleWriter;

    private $dependencies = [
        [
            'command' => 'composer',
            'label' => 'Composer',
            'instructions_url' => 'https://getcomposer.org',
        ],
        [
            'command' => 'valet',
            'label' => 'Laravel valet',
            'instructions_url' => 'https://laravel.com/docs/valet',
        ],
        [
            'command' => 'git',
            'label' => 'Git version control',
            'instructions_url' => 'https://git-scm.com',
        ],
    ];

    private $optionalDependencies = [
        [
            'command' => 'hub',
            'label' => 'Unofficial GitHub command line tool',
            'instructions_url' => 'https://github.com/github/hub',
        ],
        [
            'command' => 'gh',
            'label' => 'Official GitHub command line tool',
            'instructions_url' => 'https://cli.github.com/',
        ],
    ];

    public function __construct(ExecutableFinder $finder, ConsoleWriter $consoleWriter)
    {
        $this->finder = $finder;
        $this->consoleWriter = $consoleWriter;
    }

    public function __invoke()
    {
        $this->consoleWriter->logStep('Verifying dependencies');

        $this->consoleWriter->sectionSubTitle('Optional dependencies');

        foreach ($this->optionalDependencies as $optionalDependency) {
            [$command, $label, $instructionsUrl] = array_values($optionalDependency);

            if (($installedDependency = $this->finder->find($command)) === null) {
                if ($command === 'hub' && $this->finder->find('gh') !== null) {
                    continue;
                }

                $this->consoleWriter->note("{$label}, an optional dependency, is missing. You can find installation instructions at: <a class=\"text-sky-500\" href=\"{$instructionsUrl}\">{$instructionsUrl}</a>");
                config(["installer.store.tools.{$command}" => false]);
            } else {
                $this->consoleWriter->success("{$label} found at: <span class=\"text-sky-600\">{$installedDependency}</span>");
                config(["installer.store.tools.{$command}" => true]);
            }
        }

        $this->consoleWriter->sectionSubTitle('Required dependencies');

        $this->abortIf(
            collect($this->dependencies)->reduce(function ($carry, $dependency) {
                [$command, $label, $instructionsUrl] = array_values($dependency);
                if (($installedDependency = $this->finder->find($command)) === null) {
                    $this->consoleWriter->warn("{$label} is missing. You can find installation instructions at: <a class=\"text-sky-500\" href=\"{$instructionsUrl}\">{$instructionsUrl}</a>");

                    return true;
                }
                $this->consoleWriter->success("{$label} found at: <span class=\"text-sky-600\">{$installedDependency}</span>");

                return $carry ?? false;
            }),
            'Please install missing dependencies and try again.'
        );
    }
}
