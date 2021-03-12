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
     * OVERRIDDEN
     * @param  BaseBlueprint  $blueprint
     * @param  Fluent  $column
     * @return string|null
     */
    protected function modifyAutoRandom(BaseBlueprint $blueprint, Fluent $column)
    {
        if ($column->autoRandom) {
            return ' primary key auto_random'.(is_int($column->autoRandom) ? "({$column->autoRandom})" : '');
        }
    }

    /**
     * OVERRIDDEN
     * @param  BaseBlueprint  $blueprint
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
     * @param BaseBlueprint $blueprint
     * @return string
     */
    protected function compileCreateShards(string $sql, Connection $connection, BaseBlueprint $blueprint)
    {
        if ($blueprint instanceof Blueprint) {
            if ($blueprint->shardRowIdBits) {
                $sql.= " SHARD_ROW_ID_BITS={$blueprint->shardRowIdBits}";
            }

            if ($blueprint->preSplitRegions) {
                $sql.= " PRE_SPLIT_REGIONS={$blueprint->preSplitRegions}";
            }
        }
        return $sql;
    }

    /**
     * OVERRIDDEN
     * TiDB has limited support for foreign key constraints.
     * @see https://docs.pingcap.com/tidb/stable/constraints#foreign-key
     *
     * @param BaseBluePrint $blueprint
     * @param Fluent $command
     * @return string
     */
    public function compileForeign(BaseBluePrint $blueprint, Fluent $command)
    {
        $ddl = parent::compileForeign($blueprint, $command);

        logger()->warning('TiDB does not perform constraint checking on foreign keys. DDL:'.$ddl, [
            'result' => $ddl,
        ]);

        return $ddl;
    }

    /**
     * OVERRIDDEN
     * TiDB does not support multiple schema changes
     *
     * @param BaseBlueprint $blueprint
     * @param Fluent $command
     * @return array|string
     */
    public function compileAdd(BaseBlueprint $blueprint, Fluent $command)
    {
        $columns = $this->prefixArray('add', $this->getColumns($blueprint));
        return array_map(fn($column) => 'alter table '.$this->wrapTable($blueprint).' '.$column, $columns);
    }

    /**
     * OVERRIDDEN
     * TiDB does not support multiple schema changes
     *
     * @param BaseBlueprint $blueprint
     * @param Fluent $command
     * @return string|array
     */
    public function compileDropColumn(BaseBlueprint $blueprint, Fluent $command)
    {
        $columns = $this->prefixArray('drop', $this->wrapArray($command->columns));
        return array_map(fn($column) => 'alter table '.$this->wrapTable($blueprint).' '.$column, $columns);
    }
}
