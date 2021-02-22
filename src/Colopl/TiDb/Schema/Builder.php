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

use Closure;
use Illuminate\Database\Schema\Builder as BaseBuilder;

class Builder extends BaseBuilder
{
    /**
     * @param  string  $table
     * @param  Closure|null  $callback
     * @return Blueprint
     */
    protected function createBlueprint($table, Closure $callback = null)
    {
        $prefix = $this->connection->getConfig('prefix_indexes')
            ? $this->connection->getConfig('prefix')
            : '';

        if (isset($this->resolver)) {
            return call_user_func($this->resolver, $table, $callback, $prefix);
        }

        return new Blueprint($table, $callback, $prefix);
    }

}
