<?php
/**
 * Copyright 2019 Colopl Inc. All Rights Reserved.
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

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Support\Carbon;

class ConnectionTest extends TestCase
{
    protected const TEST_DB_REQUIRED = true;

    public function testConnect()
    {
        $conn = $this->getDefaultConnection();
        $this->assertInstanceOf(Connection::class, $conn);
        $this->assertNotEmpty($conn->getName());
        $conn->disconnect();
    }

    public function testReconnect()
    {
        $conn = $this->getDefaultConnection();
        $this->assertInstanceOf(Connection::class, $conn);
        $conn->reconnect();
        $this->assertEquals(12345, $conn->selectOne('SELECT 12345')[0]);
    }

    public function testQueryLog()
    {
        $conn = $this->getDefaultConnection();
        $conn->enableQueryLog();

        $conn->select('SELECT 1');
        $this->assertCount(1, $conn->getQueryLog());

        $conn->select('SELECT 2');
        $this->assertCount(2, $conn->getQueryLog());
    }

    public function testQueryExecutedEvent()
    {
        $conn = $this->getDefaultConnection();

        $executedCount = 0;
        $this->app['events']->listen(QueryExecuted::class, function (QueryExecuted $ev) use (&$executedCount) {
            $executedCount++;
        });

        $conn->selectOne('SELECT 1');
        $conn->select('SELECT 1');
        $tableName = self::TABLE_NAME_USER;
        $uuid = $this->generateUuid();
        $name = 'test';
        $conn->insert("INSERT INTO ${tableName} (`userId`, `name`) VALUES ('${uuid}', '${name}')");
        $afterName = 'test2';
        $conn->update("UPDATE ${tableName} SET `name` = '${afterName}' WHERE `userId` = '${uuid}'");
        $conn->delete("DELETE FROM ${tableName} WHERE `userId` = '${uuid}'");

        $this->assertEquals(5, $executedCount);
    }

    public function testEventListenOrder()
    {
        $receivedEventClasses = [];
        $this->app['events']->listen(TransactionBeginning::class, function () use (&$receivedEventClasses) { $receivedEventClasses[] = TransactionBeginning::class; });
        $this->app['events']->listen(QueryExecuted::class, function () use (&$receivedEventClasses) { $receivedEventClasses[] = QueryExecuted::class; });
        $this->app['events']->listen(TransactionCommitted::class, function () use (&$receivedEventClasses) { $receivedEventClasses[] = TransactionCommitted::class; });

        $conn = $this->getDefaultConnection();

        $tableName = self::TABLE_NAME_USER;
        $uuid = $this->generateUuid();
        $name = 'test';
        $conn->insert("INSERT INTO ${tableName} (`userId`, `name`) VALUES ('${uuid}', '${name}')");

        $this->assertCount(3, $receivedEventClasses);
        $this->assertEquals(TransactionBeginning::class, $receivedEventClasses[0]);
        $this->assertEquals(QueryExecuted::class, $receivedEventClasses[1]);
        $this->assertEquals(TransactionCommitted::class, $receivedEventClasses[2]);
    }
}
