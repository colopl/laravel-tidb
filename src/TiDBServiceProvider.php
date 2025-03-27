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

use Colopl\TiDB\Schema\Blueprint as TiDbBlueprint;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class TiDBServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('db.connector.tidb', function() {
            return new Connector;
        });

        $this->app->bind(Blueprint::class, function($app, $args) {
            return new TiDbBlueprint(...$args);
        });

        Connection::resolverFor('tidb', function ($pdo, $database, $prefix, $config) {
            return new Connection($pdo, $database, $prefix, $config);
        });
    }
}
