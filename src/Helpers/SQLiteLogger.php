<?php

namespace App\Helpers;

/**
 * Send the log message to the specified table in a
 * SQLite database.
 *
 * Usage:
 *
 *     Analog::handler (Helpesr/SQLiteLogger::init (
 *         'db',    // Medoo DB object
 *         'table', // database name
 *     ));
 */

class SQLiteLogger
{
    public static function init($db, $table)
    {
        return function ($info) use ($db, $table) {
            /**
             * $info it is array of 4 elements
             * ['machine' => ip, 'date' => date, 'level' => int, 'message' => string]
             */
            /* @var $db \Medoo\Medoo */
            $db->insert($table, $info);
        };
    }
}
