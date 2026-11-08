<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/** Replay additive November DDL after a MySQL implicit commit. */
final class MigrationSchema
{
    private static function mysql(): bool
    {
        return in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);
    }

    public static function create(string $table, Closure $define): void
    {
        if (! self::mysql() || ! Schema::hasTable($table)) {
            Schema::create($table, $define);

            return;
        }
        self::resume($table, $define, true);
    }

    public static function table(string $table, Closure $define): void
    {
        if (! self::mysql()) {
            Schema::table($table, $define);

            return;
        }
        self::resume($table, $define, false);
    }

    private static function mismatch(string $object): never
    {
        throw new RuntimeException("Esquema incompatible en {$object}. Revisa SHOW CREATE TABLE antes de continuar.");
    }

    private static function resume(string $table, Closure $define, bool $existingCreate): void
    {
        $blueprint = new Blueprint(Schema::getConnection(), $table);
        if ($existingCreate) {
            $blueprint->create();
        }
        $define($blueprint);
        // Expand fluent indexes (e.g. ->unique()) without executing SQL.
        $blueprint->toSql();
        $actual = collect(Schema::getColumns($table))->keyBy('name');
        $missing = [];
        foreach ($blueprint->getColumns() as $column) {
            if (! Schema::hasColumn($table, $column->name)) {
                // MySQL creates all base columns in one CREATE. A missing one denotes a different table.
                if ($existingCreate || $column->change) {
                    self::mismatch("$table.$column->name (columna ausente)");
                }
                $missing[] = $column;
            } elseif ($column->change) {
                $previousType = "enum('por_hora','fraccion','diaria','nocturna')";
                if ($table === 'tarifas' && $column->name === 'tipo_tarifa' && $actual[$column->name]['type'] === $previousType) {
                    $previous = new ColumnDefinition(['name' => 'tipo_tarifa', 'type' => 'enum', 'allowed' => ['por_hora', 'fraccion', 'diaria', 'nocturna'], 'default' => 'por_hora']);
                    self::checkColumn($table, $previous, $actual[$column->name]);
                    $missing[] = $column;
                } else {
                    self::checkColumn($table, $column, $actual[$column->name]);
                }
            } else {
                self::checkColumn($table, $column, $actual[$column->name]);
            }
        }
        foreach ($missing as $column) {
            Schema::table($table, fn (Blueprint $t) => $t->addColumn($column->type, $column->name, $column->getAttributes()));
        }
        if ($existingCreate && collect($blueprint->getColumns())->contains(fn ($c) => $c->autoIncrement)) {
            $primary = collect(Schema::getIndexes($table))->firstWhere('primary', true);
            if (($primary['columns'] ?? []) !== ['id']) {
                self::mismatch("$table.PRIMARY");
            }
        }
        foreach ($blueprint->getCommands() as $command) {
            if (in_array($command->name, ['index', 'unique'], true)) {
                self::index($table, $command->index, $command->columns, $command->name === 'unique');
            } elseif ($command->name === 'foreign') {
                self::foreign($table, $command);
            } elseif (! in_array($command->name, ['create', 'add', 'change', 'AutoIncrementStartingValues'], true)) {
                self::mismatch("$table (operación no prevista: $command->name)");
            }
        }
    }

    private static function checkColumn(string $table, ColumnDefinition $c, array $actual): void
    {
        $type = match ($c->type) {
            'string' => "varchar($c->length)", 'decimal' => "decimal($c->total,$c->places)",
            'bigInteger' => 'bigint', 'integer' => 'int', 'smallInteger' => 'smallint',
            'tinyInteger' => 'tinyint', 'boolean' => 'tinyint(1)',
            'dateTime' => 'datetime', 'timestamp' => 'timestamp', 'json' => 'json',
            'enum' => "enum('".implode("','", $c->allowed)."')",
            default => throw new RuntimeException("Tipo no previsto: $c->type"),
        };
        if ($c->unsigned) {
            $type .= ' unsigned';
        }
        $stored = strtolower($actual['type']);
        if ($c->type !== 'boolean') {
            $stored = preg_replace('/^(bigint|int|smallint|tinyint)\(\d+\)/', '$1', $stored);
        }
        $expected = $c->useCurrent ? 'current_timestamp' : $c->default;
        $default = $actual['default'];
        $sameDefault = ($expected === null || $default === null) ? $expected === $default
            : (is_numeric($expected) || is_bool($expected) ? is_numeric($default) && (float) $default === (float) $expected
                : strtolower(rtrim((string) $default, '()')) === strtolower((string) $expected));
        if ($stored !== $type || $actual['nullable'] !== (bool) $c->nullable || ! $sameDefault
            || $actual['auto_increment'] !== (bool) $c->autoIncrement || $actual['generation'] !== null) {
            self::mismatch("$table.$c->name (se esperaba $type)");
        }
    }

    private static function index(string $table, string $name, array $columns, bool $unique): void
    {
        $indexes = collect(Schema::getIndexes($table));
        $named = $indexes->firstWhere('name', strtolower($name));
        $matches = fn ($i) => $i['columns'] === $columns && $i['unique'] === $unique && $i['type'] === 'btree';
        if ($named && ! $matches($named)) {
            self::mismatch("$table.$name");
        }
        if ($indexes->contains($matches)) {
            return;
        }
        Schema::table($table, fn (Blueprint $t) => $unique ? $t->unique($columns, $name) : $t->index($columns, $name));
    }

    private static function foreign(string $table, $command): void
    {
        $keys = collect(Schema::getForeignKeys($table))->filter(fn ($k) => $k['name'] === $command->index || $k['columns'] === $command->columns);
        foreach ($keys as $key) {
            $restrict = fn ($rule) => in_array(strtolower($rule ?? 'restrict'), ['restrict', 'no action'], true) ? 'restrict' : strtolower($rule);
            if ($key['columns'] !== $command->columns || $key['foreign_table'] !== (string) $command->on
                || $key['foreign_schema'] !== Schema::getConnection()->getDatabaseName()
                || $key['foreign_columns'] !== (array) $command->references
                || $restrict($key['on_delete']) !== $restrict($command->onDelete)
                || $restrict($key['on_update']) !== $restrict($command->onUpdate)) {
                self::mismatch("$table.$command->index");
            }
        }
        if ($keys->isEmpty()) {
            Schema::table($table, function (Blueprint $t) use ($command) {
                $key = $t->foreign($command->columns, $command->index)->references($command->references)->on($command->on);
                if ($command->onDelete) {
                    $key->onDelete($command->onDelete);
                }
                if ($command->onUpdate) {
                    $key->onUpdate($command->onUpdate);
                }
            });
        }
    }

    public static function allowMultiplePayments(string $column, string $parent): void
    {
        $name = "pagos_{$column}_unique";
        if (self::mysql()) {
            self::table('pagos', fn (Blueprint $t) => $t->foreignId($column)->nullable()->constrained($parent)->restrictOnDelete());
        }
        $indexes = collect(Schema::getIndexes('pagos'));
        $old = $indexes->firstWhere('name', $name);
        if ($old && (! $old['unique'] || $old['columns'] !== [$column])) {
            self::mismatch("pagos.$name");
        }
        if ($indexes->contains(fn ($i) => $i['name'] !== $name && $i['unique'] && $i['columns'] === [$column])) {
            self::mismatch("pagos.$column (otro índice único)");
        }
        if (self::mysql() && ! $indexes->contains(fn ($i) => ! $i['unique'] && ($i['columns'][0] ?? null) === $column)) {
            self::index('pagos', "pagos_{$column}_lookup", [$column], false);
        }
        if ($old) {
            Schema::table('pagos', fn (Blueprint $t) => $t->dropUnique($name));
        }
    }
}
