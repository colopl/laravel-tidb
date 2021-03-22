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
use Colopl\TiDB\Connector;

class ConnectionTest extends TestCase
{
    public function testConnect()
    {
        $conn = $this->getConnection();
        self::assertInstanceOf(Connection::class, $conn);
        self::assertNotEmpty($conn->getName());
        $conn->disconnect();
    }

    public function testSelectQuery()
    {
        $conn = $this->getConnection();
        self::assertEquals(1, $conn->selectOne('SELECT 1')->{"1"});
    }

    public function testStatementQuery()
    {
        $conn = $this->getConnection();
        self::assertTrue($conn->statement('SELECT 1'));
    }

    public function testNestedTransactions()
    {
        $name = 'tester1';

        $conn = $this->getConnection();
        $conn->beginTransaction();
        $conn->beginTransaction();
        $conn->query()->from('User')->insert(compact('name'));
        $conn->commit();
        $conn->commit();

        $data = $conn->selectOne('SELECT * FROM User');

        self::assertEquals($data->name, $name);
    }

    public function testNestedTransactionWithRollback()
    {
        $name = 'tester1';

        $conn = $this->getConnection();
        $conn->beginTransaction();
        $conn->query()->from('User')->insert(compact('name'));
        $conn->beginTransaction();
        $conn->rollBack();
        $conn->commit();

        $data = $conn->selectOne('SELECT * FROM User');
        self::assertNull($data);
    }

    /**
     * @group test
     */
    public function testReplicaReads()
    {
        $mode = 'leader-and-follower';
        $config = config('database.connections.main');
        $config['replica_read'] = $mode;

        $pdo = (new Connector)->connect($config);
        $data = $pdo->query('SELECT @@tidb_replica_read')->fetch(\PDO::FETCH_ASSOC);

        self::assertEquals($data['@@tidb_replica_read'], $mode);
    }

    public function testReplicaReadsWithWrongName()
    {
        $this->expectExceptionMessage("SQLSTATE[42000]: Syntax error or access violation: 1231 Variable 'tidb_replica_read' can't be set to the value of 'random_name'");

        $config = config('database.connections.main');
        $config['replica_read'] = 'random_name';
        (new Connector)->connect($config);
    }
}
