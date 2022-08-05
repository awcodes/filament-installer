<?php

namespace Filament\Installer\Console;

use RuntimeException;
use function Termwind\{render};
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class NewCommand extends Command
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('new')
            ->setDescription('Create a new Filament application')
            ->addArgument('name', InputArgument::REQUIRED)
            ->addOption('dev', null, InputOption::VALUE_NONE, 'Installs the latest "development" release')
            ->addOption('git', null, InputOption::VALUE_NONE, 'Initialize a Git repository')
            ->addOption('branch', null, InputOption::VALUE_REQUIRED, 'The branch that should be created for a new repository', $this->defaultBranch())
            ->addOption('github', null, InputOption::VALUE_OPTIONAL, 'Create a new repository on GitHub', false)
            ->addOption('organization', null, InputOption::VALUE_REQUIRED, 'The GitHub organization to create the new repository for')
            ->addOption('dark', null, InputOption::VALUE_NONE, 'Default Filament to be dark mode enabled')
            ->addOption('themed', null, InputOption::VALUE_NONE, 'Install custom theme scaffolding')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Forces install even if the directory already exists')
            ->addOption('breezy', null, InputOption::VALUE_NONE, 'Installs Filament Breezy Plugin')
            ->addOption('shield', null, InputOption::VALUE_NONE, 'Installs Filament Shield Plugin')
            ->addOption('sentry', null, InputOption::VALUE_NONE, 'Installs Filament Sentry Plugin (combines Breezy, Shield and User management');
    }

    /**
     * Execute the command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $output->write(PHP_EOL.'  <fg=yellow>   ______  __                           __
    / ____(_) /___   ___ __   ___  ____  / /_
   / /_  /\/ / __ `/ __ `__ \/ _ \/ __ \/ __/
  / __/ /\/ / /_/ / / / / / /  __/ / / / /_
 /_/   /\/_/\__,_/_/ /_/ /_/\___/_/ /_/\__/
        </>'.PHP_EOL);

        $installDarkMode = $input->getOption('dark') === true
            ? (bool) $input->getOption('dark')
            : $io->confirm('Would you like to use dark mode with Filament?', false);

        $installCustomTheme = $input->getOption('themed') === true
            ? (bool) $input->getOption('themed')
            : $io->confirm('Would you like to use a custom theme with Filament?', false);

        $installBreezyPlugin = $input->getOption('breezy') === true
            ? (bool) $input->getOption('breezy')
            : (!($input->getOption('sentry') === true) ? $io->confirm('Would you like to install the Filament Breezy Plugin for Authentication?', false) : false);

        $installShieldPlugin = $input->getOption('shield') === true
            ? (bool) $input->getOption('shield')
            : (!($input->getOption('sentry') === true) ? $io->confirm('Would you like to install the Filament Shield Plugin for Authorization?', false) : false);

        $installSentryPlugin = $input->getOption('sentry') === true
            ? (bool) $input->getOption('sentry')
            : (!($installBreezyPlugin || $installShieldPlugin) ? $io->confirm('Would you like to install the Filament Sentry Plugin for Authentication, Authorization and User Management?', false) : false);

        sleep(1);

        $name = $input->getArgument('name');

        $directory = $name !== '.' ? getcwd().'/'.$name : '.';

        $version = $this->getVersion($input);

        if (! $input->getOption('force')) {
            $this->verifyApplicationDoesntExist($directory);
        }

        if ($input->getOption('force') && $directory === '.') {
            throw new RuntimeException('Cannot use --force option when using current directory for installation!');
        }

        $composer = $this->findComposer();

        $commands = [
            $composer." create-project laravel/laravel \"$directory\" $version --remove-vcs --prefer-dist --quiet",
        ];

        if ($directory != '.' && $input->getOption('force')) {
            if (PHP_OS_FAMILY == 'Windows') {
                array_unshift($commands, "(if exist \"$directory\" rd /s /q \"$directory\")");
            } else {
                array_unshift($commands, "rm -rf \"$directory\"");
            }
        }

        if (PHP_OS_FAMILY != 'Windows') {
            $commands[] = "chmod 755 \"$directory/artisan\"";
        }

        render('<div class="text-green-500">Installing Laravel...</div>');

        if (($process = $this->runCommands($commands, $input, $output))->isSuccessful()) {

            if ($name !== '.') {
                $this->replaceInFile(
                    'APP_URL=http://localhost',
                    'APP_URL=http://'.$name.'.test',
                    $directory.'/.env'
                );

                $this->replaceInFile(
                    'DB_DATABASE=laravel',
                    'DB_DATABASE='.str_replace('-', '_', strtolower($name)),
                    $directory.'/.env'
                );

                $this->replaceInFile(
                    'DB_DATABASE=laravel',
                    'DB_DATABASE='.str_replace('-', '_', strtolower($name)),
                    $directory.'/.env.example'
                );
            }

            render('<div class="text-green-500">Installing Filament...</div>');

            $this->installFilament($directory, $input, $output);

            if ($installDarkMode) {
                render('<div class="text-green-500">Setting up Dark Mode...</div>');
                $this->installDarkMode($directory, $input, $output);
            }

            if ($installCustomTheme) {
                render('<div class="text-green-500">Setting up Custom Theme...</div>');
                $this->installCustomTheme($directory, $input, $output);
            }

            if ($installSentryPlugin) {
                render('<div class="text-green-500">Installing Filament Sentry...</div>');
                $this->installSentryPlugin($directory, $input, $output);
            } else {
                if ($installBreezyPlugin) {
                    render('<div class="text-green-500">Installing Filament Breezy...</div>');
                    $this->installBreezyPlugin($directory, $input, $output);
                }

                if ($installShieldPlugin) {
                    render('<div class="text-green-500">Installing Filament Shield...</div>');
                    $this->installShieldPlugin($directory, $input, $output);
                }
            }

            /**
             * Commit and push to Github
             */
            if ($input->getOption('git') || $input->getOption('github') !== false) {
                $this->createRepository($directory, $input, $output);
            }

            if ($input->getOption('github') !== false) {
                $this->pushToGitHub($name, $directory, $input, $output);
                $output->writeln('');
            }

            render('<div class="bg-green-300 px-1 mt-1 font-bold text-green-900">New Filament project installed!</div>');

            render('<div class="mt-1 text-green-500">Next Steps</div>');

            if ($installShieldPlugin || $installSentryPlugin) {
                render(<<<HTML
                    <ol class="pl-2">
                        <li>cd {$name}</li>
                        <li>php artisan shield:install</li>
                        <li>Login at <a href="http://{$name}.test/admin/login">http://{$name}.test/admin/login</a></li>
                    </ol>
                HTML);
            } else {
                render(<<<HTML
                    <ol class="pl-2">
                        <li>cd {$name}</li>
                        <li>php artisan migrate</li>
                        <li>php artisan make:filament-user</li>
                        <li>Login at <a href="http://{$name}.test/admin/login">http://{$name}.test/admin/login</a></li>
                    </ol>
                HTML);
            }
        }

        return $process->getExitCode();
    }

    /**
     * Install Filament into the application.
     *
     * @param  string  $directory
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function installFilament(string $directory, InputInterface $input, OutputInterface $output)
    {
        chdir($directory);

        $commands = array_filter([
            $this->findComposer().' require filament/filament --quiet',
            PHP_BINARY.' artisan vendor:publish --ansi --tag=filament-config --quiet',
            PHP_BINARY.' artisan storage:link --quiet',
        ]);

        if ($this->runCommands($commands, $input, $output)->isSuccessful()) {
            $this->replaceInFile(
                '"@php artisan vendor:publish --tag=laravel-assets --ansi --force"',
                "\"@php artisan vendor:publish --tag=laravel-assets --ansi --force\",\n\t\t\t\"@php artisan filament:upgrade\"",
                $directory.'/composer.json'
            );
        }

        $this->commitChanges('Install Filament', $directory, $input, $output);
    }

    /**
     * Activate Dark Mode in Filament.
     *
     * @param  string  $directory
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function installDarkMode(string $directory, InputInterface $input, OutputInterface $output)
    {
        $this->replaceInFile(
            "'dark_mode' => false,",
            "'dark_mode' => true,",
            $directory.'/config/filament.php'
        );

        $this->commitChanges('Activate Dark Mode in Filament', $directory, $input, $output);
    }

    /**
     * Install Filament custom theme scaffolding into the application.
     *
     * @param  string  $directory
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function installCustomTheme(string $directory, InputInterface $input, OutputInterface $output)
    {
        copy(__DIR__ . '/../stubs/tailwind.config.js', $directory . '/tailwind.config.js');
        copy(__DIR__ . '/../stubs/postcss.config.js', $directory . '/postcss.config.js');
        copy(__DIR__ . '/../stubs/filament.css', $directory . '/resources/css/filament.css');
        copy(__DIR__ . '/../stubs/FilamentServiceProvider.php', $directory . '/app/Providers/FilamentServiceProvider.php');

        chdir($directory);

        $commands = array_filter([
            "npm install --save-dev &>/dev/null autoprefixer @tailwindcss/forms @tailwindcss/typography tippy.js",
        ]);

        if (PHP_OS_FAMILY == 'Windows') {
            array_push($commands, "(if exist package-lock.json rd /s /q package-lock.json)");
        } else {
            array_push($commands, "rm package-lock.json");
        }

        if ($this->runCommands($commands, $input, $output)->isSuccessful()) {

            $this->replaceInFile(
                "input: ['resources/css/app.css', 'resources/js/app.js'],",
                "input: ['resources/css/app.css', 'resources/css/filament.css', 'resources/js/app.js'],",
                $directory.'/vite.config.js'
            );

            $this->replaceInFile(
                "App\Providers\RouteServiceProvider::class,",
                "App\Providers\RouteServiceProvider::class,\n\t\tApp\Providers\FilamentServiceProvider::class,",
                $directory.'/config/app.php'
            );

            $this->runCommands([
                "npm install &>/dev/null",
                "npm run build &>/dev/null",
            ], $input, $output);

            $this->commitChanges('Custom Theme scaffolding installed.', $directory, $input, $output);
        }
    }

    /**
     * Install Filament Breezy plugin into the application.
     *
     * @param  string  $directory
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function installBreezyPlugin(string $directory, InputInterface $input, OutputInterface $output)
    {
        chdir($directory);

        $commands = array_filter([
            $this->findComposer().' require jeffgreco13/filament-breezy --quiet',
            PHP_BINARY.' artisan vendor:publish --ansi --tag=filament-breezy-config --quiet',
        ]);

        if ($this->runCommands($commands, $input, $output)->isSuccessful()) {
            $this->replaceInFile(
                "\Filament\Http\Livewire\Auth\Login::class",
                "\JeffGreco13\FilamentBreezy\Http\Livewire\Auth\Login::class",
                $directory.'/config/filament.php'
            );
        }

        $this->commitChanges('Install Filament Breezy', $directory, $input, $output);
    }

    /**
     * Install Filament Shield plugin into the application.
     *
     * @param  string  $directory
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function installShieldPlugin(string $directory, InputInterface $input, OutputInterface $output)
    {
        chdir($directory);

        $commands = array_filter([
            $this->findComposer().' require bezhansalleh/filament-shield --quiet',
            PHP_BINARY.' artisan vendor:publish --ansi --tag=filament-shield-config --quiet',
        ]);

        if ($this->runCommands($commands, $input, $output)->isSuccessful()) {
            $this->replaceInFile(
                "use Laravel\Sanctum\HasApiTokens;",
                "use Laravel\Sanctum\HasApiTokens;\nuse BezhanSalleh\FilamentShield\Traits\HasFilamentShield;",
                $directory.'/app/Models/User.php'
            );

            $this->replaceInFile(
                "use HasApiTokens, HasFactory, Notifiable;",
                "use HasApiTokens, HasFactory, Notifiable, HasFilamentShield;",
                $directory.'/app/Models/User.php'
            );
        }

        $this->commitChanges('Install Filament Shield', $directory, $input, $output);
    }

    /**
     * Install Filament Sentry plugin into the application.
     *
     * @param  string  $directory
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function installSentryPlugin(string $directory, InputInterface $input, OutputInterface $output)
    {
        chdir($directory);

        $commands = array_filter([
            $this->findComposer().' require awcodes/filament-sentry --quiet',
            PHP_BINARY.' artisan vendor:publish --ansi --tag=filament-sentry-config --quiet',
            PHP_BINARY.' artisan vendor:publish --ansi --tag=filament-shield-config --quiet',
        ]);

        if ($this->runCommands($commands, $input, $output)->isSuccessful()) {
            $this->replaceInFile(
                "use Laravel\Sanctum\HasApiTokens;",
                "use Laravel\Sanctum\HasApiTokens;\nuse BezhanSalleh\FilamentShield\Traits\HasFilamentShield;",
                $directory.'/app/Models/User.php'
            );

            $this->replaceInFile(
                "use HasApiTokens, HasFactory, Notifiable;",
                "use HasApiTokens, HasFactory, Notifiable, HasFilamentShield;",
                $directory.'/app/Models/User.php'
            );
        }

        $this->commitChanges('Install Filament Shield', $directory, $input, $output);
    }

    /**
     * Return the local machine's default Git branch if set or default to `main`.
     *
     * @return string
     */
    protected function defaultBranch()
    {
        $process = new Process(['git', 'config', '--global', 'init.defaultBranch']);

        $process->run();

        $output = trim($process->getOutput());

        return $process->isSuccessful() && $output ? $output : 'main';
    }

    /**
     * Create a Git repository and commit the base Laravel skeleton.
     *
     * @param  string  $directory
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function createRepository(string $directory, InputInterface $input, OutputInterface $output)
    {
        chdir($directory);

        $branch = $input->getOption('branch') ?: $this->defaultBranch();

        $commands = [
            'git init -q',
            'git add .',
            'git commit -q -m "Set up a fresh Laravel app"',
            "git branch -M {$branch}",
        ];

        $this->runCommands($commands, $input, $output);
    }

    /**
     * Commit any changes in the current working directory.
     *
     * @param  string  $message
     * @param  string  $directory
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function commitChanges(string $message, string $directory, InputInterface $input, OutputInterface $output)
    {
        if (! $input->getOption('git') && $input->getOption('github') === false) {
            return;
        }

        chdir($directory);

        $commands = [
            'git add .',
            "git commit -q -m \"$message\"",
        ];

        $this->runCommands($commands, $input, $output);
    }

    /**
     * Create a GitHub repository and push the git log to it.
     *
     * @param  string  $name
     * @param  string  $directory
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function pushToGitHub(string $name, string $directory, InputInterface $input, OutputInterface $output)
    {
        $process = new Process(['gh', 'auth', 'status']);
        $process->run();

        if (! $process->isSuccessful()) {
            $output->writeln('  <bg=yellow;fg=black> WARN </> Make sure the "gh" CLI tool is installed and that you\'re authenticated to GitHub. Skipping...'.PHP_EOL);

            return;
        }

        chdir($directory);

        $name = $input->getOption('organization') ? $input->getOption('organization')."/$name" : $name;
        $flags = $input->getOption('github') ?: '--private';
        $branch = $input->getOption('branch') ?: $this->defaultBranch();

        $commands = [
            "gh repo create {$name} --source=. --push {$flags}",
        ];

        $this->runCommands($commands, $input, $output, ['GIT_TERMINAL_PROMPT' => 0]);
    }

    /**
     * Verify that the application does not already exist.
     *
     * @param  string  $directory
     * @return void
     */
    protected function verifyApplicationDoesntExist($directory)
    {
        if ((is_dir($directory) || is_file($directory)) && $directory != getcwd()) {
            throw new RuntimeException('Application already exists!');
        }
    }

    /**
     * Get the version that should be downloaded.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @return string
     */
    protected function getVersion(InputInterface $input)
    {
        if ($input->getOption('dev')) {
            return 'dev-master';
        }

        return '';
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposer()
    {
        $composerPath = getcwd().'/composer.phar';

        if (file_exists($composerPath)) {
            return '"'.PHP_BINARY.'" '.$composerPath;
        }

        return 'composer';
    }

    /**
     * Run the given commands.
     *
     * @param  array  $commands
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  array  $env
     * @return \Symfony\Component\Process\Process
     */
    protected function runCommands($commands, InputInterface $input, OutputInterface $output, array $env = [])
    {
        if (! $output->isDecorated()) {
            $commands = array_map(function ($value) {
                if (substr($value, 0, 5) === 'chmod') {
                    return $value;
                }

                return $value.' --no-ansi';
            }, $commands);
        }

        if ($input->getOption('quiet')) {
            $commands = array_map(function ($value) {
                if (substr($value, 0, 5) === 'chmod') {
                    return $value;
                }

                return $value.' --quiet';
            }, $commands);
        }

        $process = Process::fromShellCommandline(implode(' && ', $commands), null, $env, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            try {
                $process->setTty(true);
            } catch (RuntimeException $e) {
                $output->writeln('  <bg=yellow;fg=black> WARN </> '.$e->getMessage().PHP_EOL);
            }
        }

        $process->run(function ($type, $line) use ($output) {
            $output->write('    '.$line);
        });

        return $process;
    }

    /**
     * Replace the given string in the given file.
     *
     * @param  string  $search
     * @param  string  $replace
     * @param  string  $file
     * @return void
     */
    protected function replaceInFile(string $search, string $replace, string $file)
    {
        file_put_contents(
            $file,
            str_replace($search, $replace, file_get_contents($file))
        );
    }
}
