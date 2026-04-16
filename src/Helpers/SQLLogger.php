<?php

namespace Core\Helpers;

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

class SQLLogger
{
    public static function init($db, $table)
    {
        return function ($info) use ($db, $table) {
            /**
             * $info it is array of 4 elements
             * ['machine' => ip, 'date' => date, 'level' => int, 'message' => string]
             */
            //  for production we'll be store only important messages
            if ($info['level'] <= 4) {
                $info['message'] = 'PROD: '.$info['message'];
                /* @var $db \Medoo\Medoo */
                $db->insert($table, $info);
            }
        };
    }
}
