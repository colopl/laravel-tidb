laravel-tidb
================

Laravel database driver for [TiDB](https://github.com/pingcap/tidb)

## Requirements

- PHP >= 7.4
- Laravel >= 6
- TiDB >= 4.0

## Installation

Install via composer

```sh
composer require colopl/laravel-tidb
```

That's all. You can use database connection as usual.


## Features

- Added `autoRandom($shard_bits = null)` to `ColumnDefinition`
- When user defines `$table->id()` in the migration file, it will add `PRIMARY KEY AUTO_RANDOM` to the schema instead of `PRIMARY KEY AUTO_INCREMENT` so that data gets distributed evenly.
- Added Support for nested transactions (MySQL driver will throw an exception)
- Added Support for adding/dropping multiple columns (MySQL driver will throw an exception)

## Unsupported features

1. Nesting transactions and then rolling them back will always rollback to the first transaction since `SAVEPOINT` is not supported by TiDB. In other words, rolling back with `$connection->rollBack()` will always rollback level to `0`.
2. Adding and dropping multiple columns atomically is not supported. Ex: `$table->dropColumn('title', 'content')`
3. Defining multiple columns in migrations is supported but will be executed one by one and will not be atomic.

For unsupported features for TiDB itself, please see [MySQL Compatibility](https://docs.pingcap.com/tidb/stable/mysql-compatibility).


### Testing
You can run tests on docker by the following command.

```sh
make test
```


## License
Apache 2.0 - See [LICENSE](./LICENSE) for more information.
