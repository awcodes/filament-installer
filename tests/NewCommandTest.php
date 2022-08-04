<?php

namespace Filament\Installer\Console\Tests;

use Filament\Installer\Console\NewCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class NewCommandTest extends TestCase
{
    public function test_it_can_scaffold_a_new_filament_app()
    {
        $scaffoldDirectoryName = 'tests-output/my-app';
        $scaffoldDirectory = __DIR__.'/../'.$scaffoldDirectoryName;

        if (file_exists($scaffoldDirectory)) {
            if (PHP_OS_FAMILY == 'Windows') {
                exec("rd /s /q \"$scaffoldDirectory\"");
            } else {
                exec("rm -rf \"$scaffoldDirectory\"");
            }
        }

        $app = new Application('Filament Installer');
        $app->add(new NewCommand);

        $tester = new CommandTester($app->find('new'));

        $statusCode = $tester->execute(['name' => $scaffoldDirectoryName]);

        $this->assertSame(0, $statusCode);

        // assert laravel is installed
        $this->assertDirectoryExists($scaffoldDirectory.'/vendor');
        $this->assertFileExists($scaffoldDirectory.'/.env');

        // assert filament is installed
        $this->assertDirectoryExists($scaffoldDirectory.'/vendor/filament');
        $this->assertFileExists($scaffoldDirectory.'/config/filament.php');
    }
}
