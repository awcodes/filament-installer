<?php

namespace Tests\Feature;

use App\Actions\MigrateDatabase;
use App\Shell;
use App\Tools\Database;
use Tests\Feature\Fakes\FakeProcess;
use Tests\TestCase;

class MigrateDatabaseTest extends TestCase
{
    private $database;

    public function setUp(): void
    {
        parent::setUp();
        $this->database = $this->mock(Database::class);
    }

    /** @test */
    public function it_migrates_the_database()
    {
        $fakeStore = [
            'migrate_database' => true,
            'database_host' => 'example.test',
            'database_port' => 3306,
            'database_username' => 'user',
            'database_password' => 'password',
            'database_name' => 'foo',
        ];

        config(['installer.store' => $fakeStore]);

        $this->database->shouldReceive('fillFromInstallerStore')
            ->with($fakeStore)
            ->once()
            ->andReturnSelf();

        $this->database->shouldReceive('ensureExists')
            ->once()
            ->andReturnTrue();

        $this->shell->shouldReceive('execInProject')
            ->with('php artisan migrate --quiet')
            ->once()
            ->andReturn(FakeProcess::success());

        app(MigrateDatabase::class)();
    }

    /** @test */
    public function failed_migrations_do_not_halt_execution()
    {
        $fakeStore = [
            'migrate_database' => true,
            'database_host' => 'example.test',
            'database_port' => 3306,
            'database_username' => 'user',
            'database_password' => 'password',
            'database_name' => 'foo',
        ];

        config(['installer.store' => $fakeStore]);

        $this->database->shouldReceive('fillFromInstallerStore')
            ->with($fakeStore)
            ->once()
            ->andReturnSelf();

        $this->database->shouldReceive('ensureExists')
            ->once()
            ->andReturnTrue();

        $this->shell->shouldReceive('execInProject')
            ->with('php artisan migrate --quiet')
            ->once()
            ->andReturn(FakeProcess::fail('php artisan migrate --quiet'));

        app(MigrateDatabase::class)();
    }

    /** @test */
    public function it_skips_migrations()
    {
        $databaseSpy = $this->spy(Database::class);
        $shellSpy = $this->spy(Shell::class);

        // Mock the Database->url() so that if it is called it
        // returns properly.
        $databaseSpy->shouldReceive('url')->andReturnSelf();

        config(['installer.store.migrate_database' => false]);

        config(['installer.store.database_host' => 'example.test']);
        config(['installer.store.database_port' => 3306]);
        config(['installer.store.database_username' => 'user']);
        config(['installer.store.database_password' => 'password']);
        config(['installer.store.database_name' => 'foo']);

        app(MigrateDatabase::class)();

        $databaseSpy->shouldNotHaveReceived('fillFromInstallerStore');
        $databaseSpy->shouldNotHaveReceived('ensureExists');
        $shellSpy->shouldNotHaveReceived('execInProject');
    }
}
