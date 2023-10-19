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

use Illuminate\Database\Connectors\MySqlConnector;
use PDO;

class Connector extends MySqlConnector
{
    /**
     * This override allows change in replica read mode by setting options.replica_read
     * @see https://docs.pingcap.com/tidb/stable/follower-read
     *
     * @param array $config
     * @return PDO
     */
    public function connect(array $config)
    {
        $pdo = parent::connect($config);

        if (isset($config['replica_read'])) {
            $mode = preg_replace('/[^a-zA-Z0-9_-]+/', '', $config['replica_read']);
            $pdo->exec("set @@tidb_replica_read='{$mode}'");
        }

        return $pdo;
    }
}
