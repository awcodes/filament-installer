<?php

namespace App\Configuration;

use App\Commands\Debug;
use App\Commands\NewCommand;
use App\ConsoleWriter;
use App\InstallerException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SetConfig
{
    use Debug;

    protected $consoleWriter;

    protected $fullFlags = [
        InstallerConfiguration::CREATE_DATABASE,
        InstallerConfiguration::MIGRATE_DATABASE,
        InstallerConfiguration::VALET_LINK,
        InstallerConfiguration::VALET_SECURE,
    ];

    protected $options;

    private $commandLineConfiguration;

    private $savedConfiguration;

    private $shellConfiguration;

    private $commandLineInput;

    public function __construct(
        CommandLineConfiguration $commandLineConfiguration,
        SavedConfiguration $savedConfiguration,
        ShellConfiguration $shellConfiguration,
        ConsoleWriter $consoleWriter,
        InputInterface $commandLineOptions
    ) {
        $this->commandLineConfiguration = $commandLineConfiguration;
        $this->savedConfiguration = $savedConfiguration;
        $this->shellConfiguration = $shellConfiguration;
        $this->consoleWriter = $consoleWriter;

        $this->commandLineInput = array_filter($commandLineOptions->getOptions(), function ($value, $key) use ($commandLineOptions) {
            return $commandLineOptions->hasParameterOption("--{$key}");
        }, ARRAY_FILTER_USE_BOTH);
    }

    public function __invoke($defaultConfiguration)
    {
        foreach ($defaultConfiguration as $configurationKey => $default) {
            $methodName = 'get' . Str::of($configurationKey)->studly();
            if (method_exists($this, $methodName)) {
                config(["installer.store.{$configurationKey}" => $this->$methodName($configurationKey, $default)]);
            } else {
                config(["installer.store.{$configurationKey}" => $this->get($configurationKey, $default)]);
            }
        }

        // If we're in the "new" command, generate a few config items which
        // require others to be set above first.
        if (config('installer.store.command') === NewCommand::class) {
            $projectPath = config('installer.store.root_path') . '/' . config('installer.store.project_name');
            config(['installer.store.project_path' => $projectPath]);
            config(['installer.store.project_url' => $this->getProjectURL()]);
        }

        if (config('installer.store.full')) {
            foreach ($this->fullFlags as $fullFlag) {
                config(["installer.store.{$fullFlag}" => true]);
            }
        }
    }

    private function get(string $configurationKey, $default)
    {
        if (isset($this->commandLineConfiguration->$configurationKey)) {
            return $this->commandLineConfiguration->$configurationKey;
        }

        if (isset($this->savedConfiguration->$configurationKey)) {
            return $this->savedConfiguration->$configurationKey;
        }

        if (isset($this->shellConfiguration->$configurationKey)) {
            return $this->shellConfiguration->$configurationKey;
        }

        return $default;
    }

    private function getTld(): string
    {
        $valetConfig = config('home_dir') . '/.config/valet/config.json';
        $legacyValetConfig = config('home_dir') . '/.valet/config.json';

        if (File::isFile($valetConfig)) {
            return json_decode(File::get($valetConfig))->tld;
        }

        if (File::isFile($legacyValetConfig)) {
            return json_decode(File::get($legacyValetConfig))->domain;
        }

        throw new InstallerException(
            implode(PHP_EOL, [
                'Unable to find valet domain (tld) configuration.',
                'No Valet configuration located at either of the following locations:',
                "- {$valetConfig}",
                "- {$legacyValetConfig}",
            ])
        );
    }

    private function getRootPath(string $key, $default)
    {
        $configuredKeyValue = $this->get($key, $default);

        return ($configuredKeyValue === $default)
            ? $default
            : str_replace('~', config('home_dir'), $configuredKeyValue);
    }

    private function getDatabaseName(string $key, $default)
    {
        return str_replace('-', '_', $this->get($key, $default));
    }

    private function getProjectURL(): string
    {
        return sprintf(
            'http%s://%s.%s',
            config('installer.store.valet_secure') ? 's' : '',
            config('installer.store.project_name'),
            config('installer.store.tld')
        );
    }

    private function getMigrateDatabase(string $key, $default)
    {
        if ($this->commandLineConfiguration->inertia || $this->commandLineConfiguration->livewire) {
            return true;
        }

        return $this->get($key, $default);
    }

    private function getWithOutput(string $key, $default): bool
    {
        if ($this->consoleWriter->getVerbosity() > SymfonyStyle::VERBOSITY_NORMAL) {
            return true;
        }

        return $this->get($key, $default);
    }
}
