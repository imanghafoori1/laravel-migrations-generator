<?php

namespace Tests\Feature\MySQL57;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;
use Tests\Feature\FeatureTestCase;

abstract class MySQL57TestCase extends FeatureTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('database.default', 'mysql57');
        $app['config']->set('database.connections.mysql57', [
            'driver'         => 'mysql',
            'url'            => null,
            'host'           => env('MYSQL57_HOST'),
            'port'           => env('MYSQL57_PORT'),
            'database'       => env('MYSQL57_DATABASE'),
            'username'       => env('MYSQL57_USERNAME'),
            'password'       => env('MYSQL57_PASSWORD'),
            'unix_socket'    => env('DB_SOCKET', ''),
            'charset'        => 'utf8mb4',
            'collation'      => 'utf8mb4_general_ci',
            'prefix'         => '',
            'prefix_indexes' => true,
            'strict'         => true,
            'engine'         => null,
            'options'        => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ]);
    }

    protected function dumpSchemaAs(string $destination): void
    {
        $password = (!empty(config('database.connections.mysql57.password')) ?
            '-p\''.config('database.connections.mysql57.password').'\'' :
            '');
        $command  = sprintf(
            'mysqldump -h %s -u %s '.$password.' %s --compact --no-data > %s',
            config('database.connections.mysql57.host'),
            config('database.connections.mysql57.username'),
            config('database.connections.mysql57.database'),
            $destination
        );
        exec($command);
    }

    protected function dropAllTables(): void
    {
        $tables = DB::select('SHOW TABLES');
        foreach ($tables as $table) {
            Schema::connection('mysql57')->dropIfExists(
                substr(
                    $table->{'Tables_in_'.config('database.connections.mysql57.database')},
                    strlen(config('database.connections.mysql57.prefix'))
                )
            );
        }
    }
}
