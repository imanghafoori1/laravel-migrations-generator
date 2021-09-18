<?php

namespace MigrationsGenerator\Repositories;

use MigrationsGenerator\MigrationsGeneratorSetting;
use MigrationsGenerator\Schema\MySQL\ShowColumn;

class MySQLRepository extends Repository
{
    /**
     * @return array [
     *  'charset' => string,
     *  'collation' => string
     * ]
     */
    public function getDatabaseCollation(): array
    {
        $setting = app(MigrationsGeneratorSetting::class);
        $columns = $setting->getConnection()->select("SELECT @@character_set_database, @@collation_database");
        return [
            'charset' => $columns[0]->{'@@character_set_database'}, 'collation' => $columns[0]->{'@@collation_database'}
        ];
    }

    public function showColumn(string $table, string $column): ?ShowColumn
    {
        $setting = app(MigrationsGeneratorSetting::class);
        $column = $setting->getConnection()
            ->selectOne("SHOW COLUMNS FROM `${table}` where Field = '${column}'");
        if ($column !== null) {
            return new ShowColumn($column);
        }
        return null;
    }

    public function getEnumPresetValues(string $table, string $columnName): array
    {
        $setting = app(MigrationsGeneratorSetting::class);
        $columns = $setting->getConnection()->select("SHOW COLUMNS FROM `${table}` where Field = '${columnName}' AND Type LIKE 'enum(%'");
        if (count($columns) > 0) {
            $showColumn = new ShowColumn($columns[0]);
            $value = substr(
                str_replace('enum(\'', '', $showColumn->getType()),
                0,
                -2
            );
            return explode("','", $value);
        }
        return [];
    }

    public function getSetPresetValues(string $table, string $columnName): array
    {
        $setting = app(MigrationsGeneratorSetting::class);
        $columns = $setting->getConnection()->select("SHOW COLUMNS FROM `${table}` where Field = '${columnName}' AND Type LIKE 'set(%'");
        if (count($columns) > 0) {
            $showColumn = new ShowColumn($columns[0]);
            $value = substr(
                str_replace('set(\'', '', $showColumn->getType()),
                0,
                -2
            );
            return explode("','", $value);
        }

        return [];
    }

    public function useOnUpdateCurrentTimestamp(string $table, string $columnName): bool
    {
        $setting = app(MigrationsGeneratorSetting::class);

        // MySQL5.7 shows "on update CURRENT_TIMESTAMP"
        // MySQL8 shows "DEFAULT_GENERATED on update CURRENT_TIMESTAMP"
        $column = $setting->getConnection()
            ->select(
                "SHOW COLUMNS FROM `${table}`
                WHERE Field = '${columnName}'
                    AND Type = 'timestamp'
                    AND EXTRA LIKE '%on update CURRENT_TIMESTAMP%'"
            );
        if (count($column) > 0) {
            return true;
        }

        return false;
    }
}
