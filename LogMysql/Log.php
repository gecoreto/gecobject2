<?php

/**
 * Muestra el registro de las consultas de la libreria
 * @package Gecobject
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @link https://github.com/gecoreto/gecobject/blob/master/LogMysql/Log.php
 * @author David Garzon <stylegeco@gmail.com>
 */

namespace GecObject\LogMysql;

class Log {

    private static $log_data;

    static function writeLog($action, $msg) {
        $action.=sizeof(self::$log_data) + 1;
        self::$log_data[$action] = $msg;
    }

    static function showLog() {
        echo "</br>Registro:</br>";
        foreach (self::$log_data as $action => $msg) {
            echo "[$action]: " . $msg . "</br>";
        }
    }

}
