<?php

namespace Tests\Feature;

use App\Actions\CreateDatabase;
use App\Tools\Database;
use Tests\TestCase;

class CreateDatabaseTest extends TestCase
{
    private $database;

    public function setUp(): void
    {
        parent::setUp();

        $this->database = $this->mock(Database::class);
    }

    /** @test */
    public function it_creates_a_mysql_database()
    {
        $fakeStore = [
            'create_database' => true,
            'database_host' => 'example.test',
            'database_port' => 3306,
            'database_username' => 'user',
            'database_password' => 'password',
            'database_name' => 'foo',
        ];

        $this->database->shouldReceive('fillFromInstallerStore')
            ->with($fakeStore)
            ->once()
            ->andReturnSelf();

        $this->database->shouldReceive('create')
            ->once()
            ->globally()
            ->andReturnTrue()
            ->ordered();

        config(['installer.store' => $fakeStore]);

        app(CreateDatabase::class)();
    }

    /** @test */
    public function it_skips_database_creation()
    {
        $spy = $this->spy(Database::class);

        config(['installer.store.create_database' => false]);

        config(['installer.store.database_host' => 'example.test']);
        config(['installer.store.database_port' => 3306]);
        config(['installer.store.database_username' => 'user']);
        config(['installer.store.database_password' => 'password']);
        config(['installer.store.database_name' => 'foo']);

        app(CreateDatabase::class)();

        $spy->shouldNotHaveReceived('find');
        $spy->shouldNotHaveReceived('createSchema');
    }
}
