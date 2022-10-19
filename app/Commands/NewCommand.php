<?php

namespace App\Commands;

use App\Actions\ActivateDarkMode;
use App\Actions\CreateDatabase;
use App\Actions\CustomizeDotEnv;
use App\Actions\DisplayHelpScreen;
use App\Actions\DisplayInstallerWelcome;
use App\Actions\EditConfigFile;
use App\Actions\GenerateAppKey;
use App\Actions\InitializeGitHubRepository;
use App\Actions\InitializeGitRepository;
use App\Actions\InstallBreezy;
use App\Actions\InstallCustomTheme;
use App\Actions\InstallFilament;
use App\Actions\InstallLaravel;
use App\Actions\InstallNpmDependencies;
use App\Actions\InstallSentry;
use App\Actions\InstallShield;
use App\Actions\MakeServiceProvider;
use App\Actions\MigrateDatabase;
use App\Actions\OpenInBrowser;
use App\Actions\OpenInEditor;
use App\Actions\PushToGitHub;
use App\Actions\RevertToMix;
use App\Actions\RunAfterScript;
use App\Actions\RunShieldInstall;
use App\Actions\UpgradeSavedConfiguration;
use App\Actions\ValetLink;
use App\Actions\ValetSecure;
use App\Actions\ValidateGitHubConfiguration;
use App\Actions\VerifyDependencies;
use App\Actions\VerifyPathAvailable;
use App\Configuration\CommandLineConfiguration;
use App\Configuration\InstallerConfiguration;
use App\Configuration\SavedConfiguration;
use App\Configuration\SetConfig;
use App\Configuration\ShellConfiguration;
use App\ConsoleWriter;
use App\FinalSteps;
use App\InstallerException;
use App\Options;

class NewCommand extends InstallerCommand
{
    use Debug;

    protected $signature;

    protected $description = 'Creates a fresh Filament application';

    protected $consoleWriter;

    public function __construct()
    {
        $this->signature = $this->buildSignature();

        parent::__construct();

        app()->bind('console', function () {
            return $this;
        });

        app()->singleton('final-steps', function () {
            return new FinalSteps();
        });
    }

    public function buildSignature()
    {
        return collect((new Options())->all())->reduce(
            function ($carry, $option) {
                return $carry . $this->buildSignatureOption($option);
            },
            "new\n{projectName? : Name of the Filament project}"
        );
    }

    public function buildSignatureOption($option): string
    {
        $commandlineOption = isset($option['short']) ? ($option['short'] . '|' . $option['long']) : $option['long'];

        if (isset($option['param_description'])) {
            $commandlineOption .= '=' . ($option['default'] ?? '');
        }

        return "\n{--{$commandlineOption} : {$option['cli_description']}}";
    }

    public function handle()
    {
        app(DisplayInstallerWelcome::class)();

        if (! $this->argument('projectName')) {
            app(DisplayHelpScreen::class)();
            exit;
        }

        $this->setConsoleWriter();
        $this->setConfig();

        if (app(UpgradeSavedConfiguration::class)()) {
            $this->consoleWriter->newLine();
            $this->consoleWriter->note('Your Filament configuration (~/.filament/config) has been updated.');
            $this->consoleWriter->note('Please review the changes then run filament again.');
            if ($this->confirm(sprintf('Review the changes now in %s?', config('installer.store.editor')))) {
                app(EditConfigFile::class)('config');
            }

            return;
        }

        app('final-steps')->add('cd ' . config('installer.store.project_path'));

        sleep(1);

        try {
            $this->consoleWriter->panel('Dependencies');
            app(VerifyDependencies::class)();
            app(ValidateGitHubConfiguration::class)();
            app(VerifyPathAvailable::class)();

            $this->consoleWriter->panel('Laravel');
            app(InstallLaravel::class)();
            app(CustomizeDotEnv::class)();
            app(GenerateAppKey::class)();
            app(RevertToMix::class)();

            $this->consoleWriter->panel('Filament');
            app(InstallFilament::class)();
            app(ActivateDarkMode::class)();
            app(MakeServiceProvider::class)();
            app(InstallCustomTheme::class)();
            app(InstallSentry::class)();
            app(InstallBreezy::class)();
            app(InstallShield::class)();

            $this->consoleWriter->panel('Finishing Up');
            app(InstallNpmDependencies::class)();
            app(CreateDatabase::class)();
            app(MigrateDatabase::class)();
            app(InitializeGitRepository::class)();
            app(RunAfterScript::class)();
            app(InitializeGitHubRepository::class)();
            app(PushToGitHub::class)();
            app(ValetLink::class)();
            app(ValetSecure::class)();
            app(RunShieldInstall::class)();
            app(OpenInEditor::class)();
            app(OpenInBrowser::class)();
        } catch (InstallerException $e) {
            $this->consoleWriter->exception($e->getMessage());
            exit;
        }

        $this->consoleWriter->newLine();
        $this->consoleWriter->panel('New Filament project installed! <em>Make something great.</em>', 'success');

        if (count(app('final-steps')->all()) > 1) {
            $this->consoleWriter->note('Some items need to be set manually', 'Next Steps');
            $this->consoleWriter->listing(app('final-steps')->all());
        }

        $this->consoleWriter->newLine();

        return self::SUCCESS;
    }

    protected function setConsoleWriter()
    {
        $this->consoleWriter = app(ConsoleWriter::class);
    }

    private function setConfig(): void
    {
        config(['installer.store' => []]); // @todo remove if debug code is removed.

        $commandLineConfiguration = new CommandLineConfiguration([
            'editor' => InstallerConfiguration::EDITOR,
            'message' => InstallerConfiguration::COMMIT_MESSAGE,
            'path' => InstallerConfiguration::ROOT_PATH,
            'browser' => InstallerConfiguration::BROWSER,
            'frontend' => InstallerConfiguration::FRONTEND_FRAMEWORK,
            'dbhost' => InstallerConfiguration::DATABASE_HOST,
            'dbport' => InstallerConfiguration::DATABASE_PORT,
            'dbname' => InstallerConfiguration::DATABASE_NAME,
            'dbuser' => InstallerConfiguration::DATABASE_USERNAME,
            'dbpassword' => InstallerConfiguration::DATABASE_PASSWORD,
            'create-db' => InstallerConfiguration::CREATE_DATABASE,
            'force' => InstallerConfiguration::FORCE_CREATE,
            'migrate-db' => InstallerConfiguration::MIGRATE_DATABASE,
            'link' => InstallerConfiguration::VALET_LINK,
            'secure' => InstallerConfiguration::VALET_SECURE,
            'with-output' => InstallerConfiguration::WITH_OUTPUT,
            'dev' => InstallerConfiguration::USE_DEVELOP_BRANCH,
            'full' => InstallerConfiguration::FULL,
            'github' => InstallerConfiguration::INITIALIZE_GITHUB,
            'gh-public' => InstallerConfiguration::GITHUB_PUBLIC,
            'gh-description' => InstallerConfiguration::GITHUB_DESCRIPTION,
            'gh-homepage' => InstallerConfiguration::GITHUB_HOMEPAGE,
            'gh-org' => InstallerConfiguration::GITHUB_ORGANIZATION,
            'projectName' => InstallerConfiguration::PROJECT_NAME,
            'breezy' => InstallerConfiguration::BREEZY,
            'shield' => InstallerConfiguration::SHIELD,
            'sentry' => InstallerConfiguration::SENTRY,
            'dark' => InstallerConfiguration::DARK,
            'themed' => InstallerConfiguration::THEMED,
            'silent_exec' => InstallerConfiguration::SILENT_EXEC,
            'mix' => InstallerConfiguration::MIX,
            'provider' => InstallerConfiguration::FILAMENT_PROVIDER,
        ]);

        $savedConfiguration = new SavedConfiguration([
            'PROJECTPATH' => InstallerConfiguration::ROOT_PATH,
            'MESSAGE' => InstallerConfiguration::COMMIT_MESSAGE,
            'DEVELOP' => InstallerConfiguration::USE_DEVELOP_BRANCH,
            'CODEEDITOR' => InstallerConfiguration::EDITOR,
            'BROWSER' => InstallerConfiguration::BROWSER,
            'DB_HOST' => InstallerConfiguration::DATABASE_HOST,
            'DB_PORT' => InstallerConfiguration::DATABASE_PORT,
            'DB_NAME' => InstallerConfiguration::DATABASE_NAME,
            'DB_USERNAME' => InstallerConfiguration::DATABASE_USERNAME,
            'DB_PASSWORD' => InstallerConfiguration::DATABASE_PASSWORD,
            'CREATE_DATABASE' => InstallerConfiguration::CREATE_DATABASE,
            'MIGRATE_DATABASE' => InstallerConfiguration::MIGRATE_DATABASE,
            'LINK' => InstallerConfiguration::VALET_LINK,
            'SECURE' => InstallerConfiguration::VALET_SECURE,
        ]);

        $shellConfiguration = new ShellConfiguration([
            'EDITOR' => InstallerConfiguration::EDITOR,
        ]);

        (new SetConfig(
            $commandLineConfiguration,
            $savedConfiguration,
            $shellConfiguration,
            $this->consoleWriter,
            $this->input
        ))([
            InstallerConfiguration::COMMAND => self::class,
            InstallerConfiguration::EDITOR => 'nano',
            InstallerConfiguration::COMMIT_MESSAGE => 'Initial commit',
            InstallerConfiguration::ROOT_PATH => getcwd(),
            InstallerConfiguration::BROWSER => null,
            InstallerConfiguration::DATABASE_HOST => '127.0.0.1',
            InstallerConfiguration::DATABASE_PORT => 3306,
            InstallerConfiguration::DATABASE_NAME => $this->argument('projectName'),
            InstallerConfiguration::DATABASE_USERNAME => 'root',
            InstallerConfiguration::DATABASE_PASSWORD => '',
            InstallerConfiguration::CREATE_DATABASE => false,
            InstallerConfiguration::FORCE_CREATE => false,
            InstallerConfiguration::MIGRATE_DATABASE => false,
            InstallerConfiguration::VALET_LINK => false,
            InstallerConfiguration::VALET_SECURE => false,
            InstallerConfiguration::WITH_OUTPUT => false,
            InstallerConfiguration::USE_DEVELOP_BRANCH => false,
            InstallerConfiguration::FULL => false,
            InstallerConfiguration::INITIALIZE_GITHUB => false,
            InstallerConfiguration::GITHUB_PUBLIC => false,
            InstallerConfiguration::PROJECT_NAME => null,
            InstallerConfiguration::GITHUB_DESCRIPTION => null,
            InstallerConfiguration::GITHUB_HOMEPAGE => null,
            InstallerConfiguration::GITHUB_ORGANIZATION => null,
            InstallerConfiguration::BREEZY => false,
            InstallerConfiguration::SHIELD => false,
            InstallerConfiguration::SENTRY => false,
            InstallerConfiguration::DARK => false,
            InstallerConfiguration::THEMED => false,
            InstallerConfiguration::TLD => null,
            InstallerConfiguration::SILENT_EXEC => true,
            InstallerConfiguration::MIX => false,
            InstallerConfiguration::FILAMENT_PROVIDER => false,
        ]);

        if ($this->consoleWriter->isDebug()) {
            $this->debugReport();
        }
    }
}
