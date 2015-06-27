<?php
/**
 * Incluye los archivos de las clases necesarios automaticamente 
 * @package Gecobject
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @link https://github.com/gecoreto/gecobject2/autoload.php
 * @author David Garzon <stylegeco@gmail.com>
 */

/**
 * La funcion "__autoload" se basa en los 'namespace' e incluye los siguientes archivos automaticamente
 * require 'DataBase/Table.php';
 * require 'DataBase/RowTbl.php';
 * require 'DataBase/DataBase.php';
 * require 'LogMysql/Log.php';
 * require 'DataBase/Exception/ExceptionMysql.php';
 * require 'GecObject\DataBase\Builder\QueryBuilder';
 * require 'GecObject\DataBase\Builder\QueryCompiler';
 * @param string $classname es igual al namespace o nombre de clase de un archivo
 */
function __autoload($classname) {
    //example: $classname = "GecObject\DataBase\Table"
    $classname = ltrim($classname, '\\');
    $filename = '';
    $namespace = '';
    if ($lastnspos = strripos($classname, '\\')) {
        $namespace = substr($classname, 0, $lastnspos);
        $classname = substr($classname, $lastnspos + 1);
        $filename = str_replace('\\', '/', $namespace) . '/';
    }
    $filename .= str_replace('_', '/', $classname) . '.php';
    if (!file_exists($filename)) {
        $filename = str_replace("GecObject/", "", $filename);
    }
    /* Carga los script necesarios segun se vaya instaciando las clases o llamando algun script
      require_once 'DataBase/Table.php';
      require_once 'DataBase/RowTbl.php';
      require_once 'DataBase/DataBase.php';
      require_once 'LogMysql/Log.php';
      require_once 'DataBase/Exception/ExceptionMysql.php';
      require_once 'GecObject\DataBase\Builder\QueryBuilder';
      require_once 'GecObject\DataBase\Builder\QueryCompiler';
     */
    require_once $filename;
}

?>
