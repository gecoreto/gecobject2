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

    /**
     * Metodo encargado de almacenar los mensajes generados al realizar una consulta a la BD
     * 
     * @param string $action Nombre de la función donde se ejecuto la consulta SQL
     * @param string $msg Mensaje que se quiere mostrar 
     */
    static function writeLog($action, $msg) {
        $action.=sizeof(self::$log_data) + 1;
        self::$log_data[$action] = $msg;
    }

    /**
     * 
     * @param boolean $return Si $return es false hara "echo" de los mensajes
     * @return string 
     */
    static function showLog($return = false) {
        $registros = "";
        if (!empty(self::$log_data)):
            $registros .= "</br>Registro:</br>";
            foreach (self::$log_data as $action => $msg) {
                $registros .= "[$action]: " . $msg . "</br>";
            }
        endif;
        if ($return)
            return $registros;
        else
            echo $registros;
    }

}
