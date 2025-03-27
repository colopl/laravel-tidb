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
     * @return array
     */
    public function compileCreate(BaseBluePrint $blueprint, Fluent $command, Connection $connection)
    {
        $res = parent::compileCreate($blueprint, $command, $connection);
        $res[0] = $this->compileCreateShards($res[0], $connection, $blueprint);
        return $res;
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
     * @param BaseBluePrint $blueprint
     * @param Fluent $command
     * @param Connection $connection
     * @return string
     */
    protected function compileCreateTable($blueprint, $command, $connection)
    {
        return trim(sprintf('%s table %s (%s)',
            $blueprint->temporary ? 'create temporary' : 'create',
            $this->wrapTable($blueprint),
            implode(', ', array_merge($this->getColumns($blueprint), $this->getIndexes($blueprint))),
        ));
    }

    /**
     * @param BaseBluePrint $blueprint
     * @return array
     */
    protected function getIndexes(BaseBluePrint $blueprint)
    {
        $indexes = [];

        if ($blueprint instanceof Blueprint) {
            $blueprint->useAndNullifyIndexCommands(function($command) use (&$indexes) {
                if ($command->name === 'primary') {
                    $str = 'primary key';
                }
                else if ($command->name === 'unique') {
                    $str = 'unique index';
                }
                else if ($command->name === 'spatialIndex') {
                    $str = 'spatial index';
                }
                else {
                    $str = 'index';
                }

                if ($command->name !== 'primary') {
                    $str.= ' '.$this->wrap($command->index);
                }

                $str.= $command->algorithm ? ' using '.$command->algorithm : '';

                $str.= ' ('.$this->columnize($command->columns).')';

                $indexes[]= $str;
            });
        }

        return $indexes;
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
