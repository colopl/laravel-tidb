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

namespace Colopl\TiDB\Schema;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint as BaseBluePrint;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Illuminate\Support\Fluent;

class Grammar extends MySqlGrammar
{
    /**
     * @var array
     */
    protected $modifiers = [
        'Unsigned', 'Charset', 'Collate', 'VirtualAs', 'StoredAs', 'Nullable',
        'Srid', 'Default', 'Increment', 'Comment', 'After', 'First',
        // TiDB Specific Modifiers
        'AutoRandom',
    ];

    /**
     * @param  Blueprint  $blueprint
     * @param  Fluent  $column
     * @return string|null
     */
    protected function modifyAutoRandom(Blueprint $blueprint, Fluent $column)
    {
        if ($column->autoRandom) {
            return ' auto_random'.(is_int($column->autoRandom) ? "({$column->autoRandom})" : '');
        }
    }

    /**
     * @param  Blueprint  $blueprint
     * @param  Fluent  $command
     * @param  Connection  $connection
     * @return string
     */
    public function compileCreate(BaseBluePrint $blueprint, Fluent $command, Connection $connection)
    {
        $sql = parent::compileCreate($blueprint, $command, $connection);

        $sql = $this->compileCreateShards($sql, $connection, $blueprint);

        return $sql;
    }

    /**
     * @param string $sql
     * @param Connection $connection
     * @param Blueprint $blueprint
     * @return string
     */
    protected function compileCreateShards(string $sql, Connection $connection, Blueprint $blueprint)
    {
        if ($blueprint->shardRowIdBits) {
            $sql.= " SHARD_ROW_ID_BITS={$blueprint->shardRowIdBits}";
        }

        if ($blueprint->preSplitRegions) {
            $sql.= " PRE_SPLIT_REGIONS={$blueprint->preSplitRegions}";
        }

        return $sql;
    }

}
