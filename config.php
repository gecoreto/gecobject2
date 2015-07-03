<?php

/**
 * Carga los script necesarios y define las constantes para hacer la conexion en la base de datos
 * @package Gecobject
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @link https://github.com/gecoreto/gecobject2/config.php
 * @author David Garzon <stylegeco@gmail.com>
 */
//
//  Incluye los archivos de las clases necesarios automaticamente 
require 'autoload.php';

$config_db = array(
    /** Nombre del host en la base de datos 
     * @property type $name Description string DB_HOST
     */
    'db_host' => 'localhost',
    /** Nombre de la base de datos 
     * @property string DB_NAME
     */
    'database' => 'nombre de la base de datos',
    /** Usuario de la base de datos 
     * @property string DB_USER
     */
    'db_user' => 'root',
    /** Password para acceder a la base de datos 
     * @property string DB_PASSWORD
     */
    'db_pass' => '',
    /** Si es true Imprime el registro de mensajes 
     * @property boolean LOG
     */
    'log' => false,
    /** Si es true guarda las excepsiones generadas en consultas
     *  mysql en un archivo de texto ubicado en Log/error-mysql.txt o Log/error-postgresql.txt
     * 
     * @property boolean ERROR_EXCEPTION
     */
    'error_exception' => false,
    /** Si es true valida que el dato ingresado corresponda con el tipo de la columna
     * NOTA: Por el momento solo valida campos en bases de datos Mysql
     * @property boolean validateField
     */
    'validateField' => false,
    //Conectar a Mysql
    'driver' => 'mysql',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'tablePrefix' => '',

    /**
     * Conectar a PostgreSql
      'driver' => 'pgsql',
      'port' => '5432',
     */
    /**
     *  Ejemplo consultas POSTGRESQL:
            require '../config.php';
            $db = Db::database();
            $rows = $db->select("SELECT * FROM usuarios LIMIT 10;");
            foreach ($rows as $user) {
                echo "{$user['nombre']}<br>";
            }
     */
);

function getConfigDb() {
    global $config_db;
    return $config_db;
}

?>
