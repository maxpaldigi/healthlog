<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class CheckConstraint
{
    public static function add(string $table, string $name, string $expression): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK ({$expression})");

            return;
        }

        if ($driver === 'sqlite') {
            $newExpression = self::prefixNewColumns($expression);

            foreach (['insert', 'update'] as $event) {
                DB::unprepared("
                    CREATE TRIGGER {$name}_{$event}
                    BEFORE ".strtoupper($event)." ON {$table}
                    FOR EACH ROW
                    WHEN NOT ({$newExpression})
                    BEGIN
                        SELECT RAISE(ABORT, 'CHECK constraint failed: {$name}');
                    END;
                ");
            }
        }
    }

    private static function prefixNewColumns(string $expression): string
    {
        $keywords = ['between', 'and', 'or', 'is', 'null', 'in', 'not'];

        return preg_replace_callback(
            "/(?<![\\w.'])([a-z_][a-z0-9_]*)(?![\\w'])/i",
            function (array $matches) use ($keywords): string {
                if (in_array(strtolower($matches[1]), $keywords, true)) {
                    return $matches[1];
                }

                return 'NEW.'.$matches[1];
            },
            $expression,
        ) ?? $expression;
    }
}
