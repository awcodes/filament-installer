<?php

namespace App\Actions;

use App\ConsoleWriter;
use App\Shell;
use App\Tools\Database;
use PDOException;

class CreateDatabase
{
    use AbortsCommands;

    protected $shell;

    protected $database;

    protected $consoleWriter;

    public function __construct(Shell $shell, Database $database, ConsoleWriter $consoleWriter)
    {
        $this->shell = $shell;
        $this->database = $database;
        $this->consoleWriter = $consoleWriter;
    }

    public function __invoke()
    {
        if (! config('installer.store.create_database')) {
            return;
        }

        $this->consoleWriter->logStep('Creating database');

        $db_name = config('installer.store.database_name');

        try {
            $databaseCreated = $this->database
                ->fillFromInstallerStore(config('installer.store'))
                ->create($db_name);

            if (! $databaseCreated) {
                $this->consoleWriter->warn($this->failureToCreateError($db_name));

                return;
            }
        } catch (PDOException $e) {
            $this->consoleWriter->warn($e->getMessage());
            $this->consoleWriter->warn($this->failureToCreateError($db_name));

            return;
        }

        $this->consoleWriter->success("Created a new database '{$db_name}'");
    }

    protected function failureToCreateError(string $db_name): string
    {
        return sprintf(
            "Failed to create database '%s' using credentials <span class=\"text-amber-500\">mysql://%s:****@%s:%s</span>\nYou will need to create the database manually.",
            $db_name,
            config('installer.store.database_username'),
            config('installer.store.database_host'),
            config('installer.store.database_port')
        );
    }
}
