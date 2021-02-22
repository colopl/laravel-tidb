<?php
/**
 * Copyright 2021 Colopl Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Colopl\TiDB\Tests;

use Colopl\TiDB\Connection;
use Colopl\TiDB\Schema\Blueprint;
use Colopl\TiDB\TiDBServiceProvider;
use Illuminate\Database\Connectors\MySqlConnector;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected static $databasePrepared = false;

    protected function tearDown(): void
    {
        $this->cleanupDatabaseRecords();
    }

    protected function cleanupDatabaseRecords()
    {
        /** @var Connection $conn */
        foreach ($this->app['db']->getConnections() as $conn) {

        }
    }

    protected function getConnection($connection = null)
    {
        if (!static::$databasePrepared) {
            $config = config('database.connections.main');
            $database = $config['database'];
            $config['database'] = null;
            $pdo = (new MySqlConnector)->connect($config);
            $_conn = new Connection($pdo, null, '', $config);
            $_conn->select('CREATE DATABASE IF NOT EXISTS '.$database);

            $conn = parent::getConnection();

            $builder = $conn->getSchemaBuilder();

            $builder->create('User', function(Blueprint $table) {
                $table->id();
                $table->string('name', 255);
            });

            register_shutdown_function(function() use ($builder, $conn, $database) {
                $conn->select('DROP DATABASE '.$database);
            });

            static::$databasePrepared = true;

            return $conn;
        }

        return parent::getConnection();
    }

    /**
     * @return string[]
     */
    protected function getTestDatabaseDDLs(): array
    {
        $ddlFile = __DIR__ . '/test.ddl';
        return collect(explode(';', file_get_contents($ddlFile)))
            ->map(function($ddl) { return trim($ddl); })
            ->filter()
            ->all();
    }

    protected function getPackageProviders($app)
    {
        return [TiDBServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $dbConfig = require __DIR__ . '/config.php';
        $app['config']->set('database', $dbConfig);
    }
}
