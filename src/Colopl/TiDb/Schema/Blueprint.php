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

use Illuminate\Database\Schema\Blueprint as BaseBluePrint;

class Blueprint extends BaseBluePrint
{
    /**
     * @var int
     */
    public $shardRowIdBits;

    /**
     * @var int
     */
    public $preSplitRegions;

    /**
     * @param string $column
     * @return ColumnDefinition
     */
    public function id($column = 'id')
    {
        return $this->bigInteger($column)->autoRandom();
    }

    /**
     * @param string $column
     * @param false $autoIncrement
     * @param false $unsigned
     * @return ColumnDefinition
     */
    public function bigInteger($column, $autoIncrement = false, $unsigned = false)
    {
        return parent::bigInteger($column, $autoIncrement, $unsigned);
    }

    /**
     * @param  string  $type
     * @param  string  $name
     * @param  array  $parameters
     * @return ColumnDefinition
     */
    public function addColumn($type, $name, array $parameters = [])
    {
        $this->columns[] = $column = new ColumnDefinition(
            array_merge(compact('type', 'name'), $parameters)
        );

        return $column;
    }

    /**
     * @param int $bits
     */
    public function shardRowIdBits(int $bits)
    {
        $this->shardRowIdBits = $bits;
    }

    /**
     * @param int $regions
     */
    public function preSplitRegions(int $regions)
    {
        $this->preSplitRegions = $regions;
    }
}
