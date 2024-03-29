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

namespace Colopl\TiDB;

use Colopl\TiDB\Query\Grammar as QueryGrammar;
use Colopl\TiDB\Schema\Builder as SchemaBuilder;
use Colopl\TiDB\Schema\Grammar as SchemaGrammar;
use Illuminate\Database\Grammar;
use Illuminate\Database\MySqlConnection;
use function method_exists;

class Connection extends MySqlConnection
{
    /**
     * @return Grammar
     */
    protected function getDefaultQueryGrammar()
    {
        $grammar = new QueryGrammar;
        if (method_exists($grammar, 'setConnection')) {
            $grammar->setConnection($this);
        }

        return $this->withTablePrefix($grammar);
    }

    /**
     * @return Grammar
     */
    protected function getDefaultSchemaGrammar()
    {
        $grammar = new SchemaGrammar;
        if (method_exists($grammar, 'setConnection')) {
            $grammar->setConnection($this);
        }

        return $this->withTablePrefix($grammar);
    }

    /**
     * @return SchemaBuilder
     */
    public function getSchemaBuilder()
    {
        if ($this->schemaGrammar === null) {
            $this->useDefaultSchemaGrammar();
        }

        return new SchemaBuilder($this);
    }

    /**
     * @param int|null $toLevel
     * @return void
     */
    public function rollBack($toLevel = null)
    {
        // TiDB does not support savepoint so always rollback the base transaction.
        parent::rollBack(0);
    }
}
