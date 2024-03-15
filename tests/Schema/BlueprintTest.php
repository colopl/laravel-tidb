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

namespace Colopl\TiDB\Tests\Schema;

use Colopl\TiDB\Schema\Blueprint;
use Colopl\TiDB\Tests\TestCase;

class BlueprintTest extends TestCase
{
    protected function tearDown(): void
    {
        $conn = $this->getConnection();
        $conn->getSchemaBuilder()->dropIfExists('Test');

        parent::tearDown();
    }

    public function testAddColumn()
    {
        $conn = $this->getConnection();
        $schema = $conn->getSchemaBuilder();

        $schema->create('Test', function(Blueprint $table) {
            $table->id();
        });

        $schema->table('Test', function(Blueprint $table) {
            $table->string('str', 5);
            $table->integer('num');
        });

        $columnInfos = $conn->select('SHOW COLUMNS FROM Test');
        $columns = array_map(static fn($col) => $col->Field, $columnInfos);

        self::assertContains('str', $columns);
        self::assertContains('num', $columns);
    }

    public function testDropColumn()
    {
        $conn = $this->getConnection();
        $schema = $conn->getSchemaBuilder();

        $schema->create('Test', function(Blueprint $table) {
            $table->id();
            $table->string('str', 5);
            $table->integer('num');
        });

        $schema->table('Test', function(Blueprint $table) {
            $table->dropColumn('str', 'num');
        });

        $columnInfos = $conn->select('SHOW COLUMNS FROM Test');
        $columns = array_map(static fn($col) => $col->Field, $columnInfos);

        self::assertNotContains('str', $columns);
        self::assertNotContains('num', $columns);
    }

    public function testCreateTableWithIndex()
    {
        $conn = $this->getConnection();
        $schema = $conn->getSchemaBuilder();

        $schema->create('Test', function(Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->string('str1', 5);
            $table->string('str2', 5);

            $table->unique(['str1', 'str2']);
        });

        $indexInfos = $conn->select('SHOW INDEX FROM Test');

        self::assertEquals('Test', $indexInfos[0]->Table);
        self::assertEquals('PRIMARY', $indexInfos[0]->Key_name);
        self::assertEquals('id', $indexInfos[0]->Column_name);
        self::assertEquals('Test', $indexInfos[1]->Table);
        self::assertEquals('test_str1_str2_unique', $indexInfos[1]->Key_name);
        self::assertEquals('str1', $indexInfos[1]->Column_name);
        self::assertEquals('0', $indexInfos[1]->Non_unique);
        self::assertEquals('Test', $indexInfos[2]->Table);
        self::assertEquals('test_str1_str2_unique', $indexInfos[2]->Key_name);
        self::assertEquals('str2', $indexInfos[2]->Column_name);
        self::assertEquals('0', $indexInfos[2]->Non_unique);
    }

}
