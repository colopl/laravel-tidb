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
}
