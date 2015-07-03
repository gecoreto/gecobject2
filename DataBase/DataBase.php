<?php

/**
 * @package DataBase
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @link https://github.com/gecoreto/gecobject2
 * @author David Garzon <stylegeco@gmail.com>
 */

namespace GecObject\DataBase;

use GecObject\Log\Log;
use PDO;
use Exception;
use Closure;
use GecObject\DataBase\Exception\ExceptionG;
use PDOException;

final class DataBase {

    /**
     * Única instancia de la clase.
     * 
     * @var Session $_instance
     */
    protected static $_instance;

    /**
     * Gestiona la conexión con la base de datos.
     * 
     * @var PDO $connec
     */
    protected $connec;

    /**
     * Informa que lo sucecido con las consultas.
     * 
     * @var string $message
     */
    protected $message = '';

    /**
     * Obtiene el valor de la PRIMARY KEY después de un INSERT.
     * 
     * @var mixed $last_id
     */
    protected $last_id;

    /**
     * Numero de filas afectadas en una actualizacion o en una inserción
     * 
     * @var int affect_rows
     */
    protected $affect_rows;

    /**
     * Total de filas de una consulta Sql
     * 
     * @var int $num_count
     */
    protected $num_rows;

    /**
     * Modo "fetch mode" por default de ls connection.
     *
     * @var int
     */
    protected $fetchMode = PDO::FETCH_ASSOC;

    /**
     * El prefijo de las tablas para la  conexión.
     *
     * @var string
     */
    protected $tablePrefix = '';

    /**
     * Configuración de la conexión a la base de datos.
     *
     * @var array
     */
    protected $config = array();

    /**
     * Atributos que seran limpiados antes de ejecutar una sentencia SQL.
     *
     * @var array
     */
    protected $clearAttrs = [
        'affect_rows',
        'last_id',
        'message',
        'num_rows'
    ];

    /**
     * Obtiene un SQLSTATE asociado con la última operación en el manejador de la base de datos
     * 
     * @var boolean 
     */
    protected $error;

    /** Si es true valida que el dato ingresado corresponda con el tipo de la columna
     * 
     * @property boolean validateField
     */
    protected $validateField;

    /**
     * Conectar a bases de datos PostgreSql
     * @var string
     */
    const DRIVER_POSTGRESQL = 'pgsql';

    /**
     * Conectar a bases de datos MySql
     * @var string
     */
    const DRIVER_MYSQL = 'mysql';

    public function __construct($config = array()) {
        try {
            if (empty($config) && empty(getConfigDb()))
                throw new Exception("No se definieron las variables necesarias para conectar a la base de datos.");
            $this->config = (empty($config)) ? getConfigDb() : $config;
            $this->tablePrefix = $this->config['tablePrefix'];
            $this->validateField = $this->config['validateField'];
        } catch (Exception $exc) {
            echo $exc->getMessage();
        }
    }

    /**
     *  Al finalizar la ejecución se muestra el registro de acciones.
     */
    function __destruct() {
        if (!empty($this->config) && $this->config['log'] == true)
            Log::showLog();
    }

    /**
     * Obtiene la instancia de la base de datos.
     * 
     * @return Database
     */
    public static function database($config = array()) {
        if (!isset(self::$_instance)) {
            $className = __CLASS__;
            self::$_instance = new $className($config);
        }
        return self::$_instance;
    }

    /**
     * Abre la conexión con la base de datos.<br>
     * Si $message es true escribe un mensaje de Log
     * 
     * @param Boolean $message
     */
    protected function open_connection($message = false) {
        $this->clear();
        try {
            $this->connec = new PDO("{$this->config['driver']}:dbname={$this->config['database']};host={$this->config['db_host']}", $this->config['db_user'], $this->config['db_pass'], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . $this->config['charset'])
            );
            $this->connec->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            Log::writeLog(__FUNCTION__, "Conexion Fallida: " . $e->getMessage());
            throw new ExceptionG("ERRORES EN LA CONEXIÓN: " . $e->getMessage(), $e->getCode(), $this->config);
            exit();
        }
        if ($message)
            Log::writeLog(__FUNCTION__, "Conexion Establecida: " . $this->connec->getAttribute(PDO::ATTR_CONNECTION_STATUS));
    }

    /**
     * Cierra la conexión con la base de datos.
     * Si $message es true escribe un mensaje de Log
     * 
     * @param Boolean $message
     */
    protected function close_connection($message = false) {
        if ($message)
            Log::writeLog(__FUNCTION__, "Conexion Cerrada: " . $this->connec->getAttribute(PDO::ATTR_CONNECTION_STATUS));
        $this->error = $this->connec->errorCode();
        $this->last_id = $this->connec->lastInsertId();
        $this->connec = NULL;
    }

    /**
     * Ejecuta una sentencia SQL 
     *
     * @param  string    $query
     * @param  array     $bindings
     * @param  \Closure  $callback
     * @return mixed
     *
     * @throws Exception
     */
    protected function ejecutar($query, $bindings, Closure $callback, $action = __FUNCTION__) {
        $this->clear();
        $this->open_connection(true);
        try {
            Log::writeLog($action, $query);
            if (!empty($bindings))
                Log::writeLog("Bindings", implode(" || ", $bindings));
            $res = $callback($this, $query, $bindings);
        } catch (PDOException $e) {
            throw new ExceptionG($e->errorInfo[2], $e->errorInfo[1], $this->config);
            exit();
        }
        $this->close_connection(true);
        return $res;
    }

    /**
     * Ejecuta una instrucción SQL
     *
     * @param  string    $query
     * @param  array     $bindings
     * @return bool
     *
     */
    public function stmt($query, $bindings = array(), $action = __FUNCTION__) {
        return $this->ejecutar($query, $bindings, function($me, $query, $bindings) {
                    return $me->connec->prepare($query)
                                    ->execute($bindings); //array(':calories' => $calorías, ':colour' => $color)                    
                }, $action);
    }

    /**
     * Ejecuta una instrucción SQL de insercción, actualización o eliminación
     *
     * @param  string    $query
     * @param  array     $bindings
     * @return int
     *
     */
    public function affectedStmt($query, $bindings = array(), $action = __FUNCTION__) {
        return $this->ejecutar($query, $bindings, function($me, $query, $bindings) {
                    $stmt = $me->connec->prepare($query);
                    $stmt->execute($bindings);
                    return $me->affect_rows = $stmt->rowCount();
                }, $action);
    }

    /**
     * Ejecuta una instrucción select en la base de datos.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return array
     */
    public function select($query, $bindings = array(), $action = __FUNCTION__) {

        return $this->ejecutar($query, $bindings, function($me, $query, $bindings) {
                    $stmt = $me->connec->prepare($query);
                    $stmt->execute($bindings);
                    $me->num_rows = $stmt->rowCount();
                    return $stmt->fetchAll($me->fetchMode);
                }, $action);
    }

    /**
     * Ejecuta una instrucción select en la base de datos devolviendo un unico resultado.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return mixed
     */
    public function selectOne($query, $bindings = array(), $action = __FUNCTION__) {
        $rows = $this->select($query, $bindings, $action);
        if (count($rows) > 0) {
            $this->num_rows = 1;
            return reset($rows);
        } else {
            return null;
        }
    }

    /**
     * Ejecuta varias instrucciones select en la base de datos.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return array
     */
    public function selectMultiQuery($query, $bindings = array(), $action = __FUNCTION__) {
        return $this->ejecutar($query, $bindings, function($me, $query, $bindings) {
                    $resultSet = array();
                    $stmt = $me->connec->prepare($query);
                    $stmt->execute($bindings);
                    do {
                        $resultSet[] = $stmt->fetchAll($me->fetchMode);
                        $me->num_rows = $me->num_rows + $stmt->rowCount();
                    } while ($stmt->nextRowset());
                    return $resultSet;
                }, $action);
    }

    /**
     * Ejecuta una instrucción de inserción en la base de datos.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return bool
     */
    public function insert($query, $bindings = array(), $action = __FUNCTION__) {
        return $this->stmt($query, $bindings, $action);
    }

    /**
     * Ejecuta una instrucción de actualización en la base de datos.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return int
     */
    public function update($query, $bindings = array(), $action = __FUNCTION__) {
        return $this->affectedStmt($query, $bindings, $action);
    }

    /**
     *  Ejecuta una instrucción de borrado en la base de datos.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return int
     */
    public function delete($query, $bindings = array(), $action = __FUNCTION__) {
        return $this->affectedStmt($query, $bindings, $action);
    }

    /**
     * El prefijo de las tablas para la  connexión.
     * 
     * @param string $tablePrefix
     */
    public function setTablePrefix($tablePrefix) {
        $this->tablePrefix = $tablePrefix;
    }

    /**
     * El prefijo de las tablas para la  connexión.
     * 
     * @return string
     */
    public function getTablePrefix() {
        return $this->tablePrefix;
    }

    /**
     * Informa que lo sucecido con las consultas.
     * 
     * @return type
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * Obtiene el valor de la PRIMARY KEY después de un INSERT.
     * 
     * @var mixed $last_id
     */
    public function getLastId() {
        return $this->last_id;
    }

    /**
     * Numero de filas afectadas en una actualizacion o en una inserción
     * 
     * @return int 
     */
    public function getAffectedRows() {

        return ($this->affect_rows == null) ? 0 : $this->affect_rows;
    }

    /**
     * Obtiene cuantos registros se obtubieron durante la consulta.
     * 
     * @return int 
     */
    public function getNumRows() {

        return ($this->num_rows == null) ? 0 : $this->num_rows;
    }

    /**
     * Modo "fetch mode" por default de la connection.
     * 
     * @param int $fetchMode
     */
    public function setFetchMode($fetchMode) {
        $this->fetchMode = $fetchMode;
    }

    /**
     * Modo "fetch mode" por default de la connection.
     * 
     * @return int
     */
    public function getFetchMode() {
        return $this->fetchMode;
    }

    /**
     * Limpia los atributos suministrados antes de ejecutar una sentencia SQL.
     */
    protected function clear() {
        foreach ($this->clearAttrs as $attr) {
            $this->{$attr} = null;
        }
    }

    /**
     * Obtiene un SQLSTATE asociado con la última operación en el manejador de la base de datos
     * 
     * @return boolean true si hubo error de lo contrario retorna false 
     */
    public function getError() {
        return ($this->error == 00000) ? false : true;
    }

    /** Si es true valida que el dato ingresado corresponda con el tipo de la columna
     * 
     * @return boolean validateField
     */
    public function getValidateField() {
        return $this->validateField;
    }

}
