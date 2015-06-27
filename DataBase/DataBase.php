<?php

/**
 * @package DataBase
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @link https://github.com/gecoreto/gecobject
 * @author David Garzon <stylegeco@gmail.com>
 */

namespace GecObject\DataBase;

use GecObject\DataBase\Exception\ExceptionMysql;
use GecObject\LogMysql\Log;

final class DataBase {

    /**
     * Única instancia de la clase.
     * 
     * @var Session $_instance
     */
    private static $_instance;

    /**
     * Host
     * 
     * @var string $db_host
     */
    private static $db_host = DB_HOST;

    /**
     * Usuario de la base de datos
     * 
     * @var string $db_user
     */
    private static $db_user = DB_USER;

    /**
     * Password del usuario de la base de datos.
     * 
     * @var string $db_pass
     */
    private static $db_pass = DB_PASSWORD;

    /**
     * Nombre de la base de datos.
     * 
     * @var string $db_name
     */
    protected $db_name = DB_NAME;

    /**
     * Consultas MySql 
     * 
     * @var string $query
     */
    public $query;

    /**
     * Gestiona la conexión con la base de datos.
     * 
     * @var Mysqli $connec
     */
    private $connec;

    /**
     * Informa que lo sucecido con las consultas.
     * 
     * @var string $message
     */
    protected $message = '';

    /**
     * Obtiene el valor de la PRIMARY KEY después de un INSERT.
     * 
     * @var string $last_id
     */
    private $last_id;

    /**
     * Numero de filas afectadas en una actualizacion o en una inserción
     * 
     * @var int affect_rows
     */
    private $affect_rows;

    /**
     * Total de filas de una consulta Sql
     * 
     * @var int $num_count
     */
    private $num_rows;

    /**
     *  Al finalizar la ejecución se muestra el registro de acciones.
     */
    function __destruct() {
        if (LOG)
            Log::showLog();
    }

    /**
     * Obtiene la instancia de la base de datos.
     * 
     * @return Session
     */
    public static function database() {
        try {
            if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_PASSWORD') || !defined('DB_NAME'))
                throw new \Exception("No se definieron las constantes necesarias para conectar a la base de datos.");
        } catch (Exception $exc) {
            echo $exc->getMessage();
        }
        if (!isset(self::$_instance)) {
            $className = __CLASS__;
            self::$_instance = new $className;
        }
        return self::$_instance;
    }

    /**
     * Abre la conexión con la base de datos.<br>
     * Si $message es true escribe un mensaje de Log
     * 
     * @param Boolean $message
     */
    private function open_connection($message = false) {
        $this->last_id = null;
        $this->affect_rows = null;
        $this->connec = new \mysqli(self::$db_host, self::$db_user, self::$db_pass, $this->db_name);
        /* verificar conexión */
        if (mysqli_connect_errno()) {
            $this->errores("Errores en la Conexión: " . mysqli_connect_error($this->connec));
            Log::writeLog(__FUNCTION__, "Conexion Fallida: " . mysqli_connect_error($this->connec));
            exit();
        }
        if ($message)
            Log::writeLog(__FUNCTION__, "Conexion Establecida: " . $this->connec->host_info);
    }

    /**
     * Cierra la conexión con la base de datos.
     * Si $message es true escribe un mensaje de Log
     * 
     * @param Boolean $message
     */
    private function close_connection($message = false) {
        $this->connec->close();
        if ($message)
            Log::writeLog(__FUNCTION__, "Conexion Cerrada: " . self::$db_host);
    }

    /**
     * Ejecuta de un query (INSERT - UPDATE - DELETE)
     * Si $message es true escribe un mensaje de Log
     * 
     * @param Boolean $message
     */
    public function execute_single_query($message = false) {
        $this->open_connection($message);
        $this->connec->query($this->query) or ($this->errores("Error"));
        $this->last_id = mysqli_insert_id($this->connec);
        $this->affect_rows = mysqli_affected_rows($this->connec);
        if ($message)
            Log::writeLog(__FUNCTION__, "Consulta ejecutada: {$this->query}");
        $this->close_connection($message);
    }

    /**
     * Ejecuta varios querys al tiempo (INSERT - UPDATE - DELETE)<br>
     * Ejemplo:<br>
     * $this->query  = "SELECT CURRENT_USER();";<br>
     * $this->query .= "SELECT Name FROM City ORDER BY ID LIMIT 20, 5"; 
     *  Si $message es true escribe un mensaje de Log
     * 
     * @param Boolean $message
     */
    public function execute_multi_query($message = false) {
        $this->open_connection($message);
        $this->connec->multi_query($this->query) or ($this->errores());
        $this->last_id = mysqli_insert_id($this->connec);
        $this->affect_rows = mysqli_affected_rows($this->connec);
        if ($message)
            Log::writeLog(__FUNCTION__, "Consulta ejecutada: {$this->query}");
        $this->close_connection($message);
    }

    /**
     * Escape strings 
     * 
     * @param   mixed  String to escape 
     * @return  string Escaped string, ready for SQL insertion 
     */
    public function escape($data) {
        switch (gettype($data)) {
            case 'string':
                $data = "'" . mysql_real_escape_string($data) . "'";
                break;
            case 'boolean':
                $data = (int) $data;
                break;
            case 'double':
                $data = sprintf('%F', $data);
                break;
            default:
                $data = ($data === null) ? 'null' : $data;
        }
        return (string) $data;
    }

    /**
     * Retorna un result de un query.
     * 
     * @param Boolean $object
     * @return Object | Array
     */
    public function get_results_from_query($object = false) {
        $this->open_connection(true);
        //Si se ejecutan multiples consultas
        if ($this->connec->multi_query($this->query)) {
            Log::writeLog(__FUNCTION__, "Consulta ejecutada: {$this->query}");
            $resultSet = array();
            do {
                /* almacenar primer juego de resultados */
                if ($result = $this->connec->store_result()) {
                    while ($row = $result->fetch_assoc()) {
                        $resultSet[] = $row;
                    }
                    $result->free();
                }
            } while ($this->connec->next_result());
        }
        //si solo una consulta es ejecutada
        else {
            Log::writeLog(__FUNCTION__, "Consulta ejecutada: {$this->query}");
            $result = $this->connec->query($this->query) or ($this->errores());
            while ($rows[] = $result->fetch_assoc());
            array_pop($rows);
            $this->num_rows = count($rows);
            if ($object) {
                $resultSet = (object) $rows;
            } else {
                $resultSet = $rows;
            }
            $result->close();
        }
        $this->close_connection(true);
        return $resultSet;
    }

    /**
     * Obtiene cuantos registros se obtubieron durante la consulta.
     * 
     * @return int 
     */
    public function num_rows() {
        return $this->num_rows;
    }

    /**
     * 
     * @return type
     */
    public function getAffectedRows() {
        return $this->affect_rows;
    }

    /**
     * 
     * @return type
     */
    public function getLastId() {
        return $this->last_id;
    }

    /**
     * Genera un archivo informando de un error en algunos de los procesos del CRUD
     * 
     * @param string $error
     */
    private function errores($error = NULL) {
        if(empty($error))
            $error = mysqli_error($this->connec);
        throw new ExceptionMysql($error, mysqli_errno($this->connec));
    }

    /**
     * Inicia la transacción y actualiza a FALSE el autocommit.
     *
     * @return boolean
     */
    protected function begin() {
        mysqli_query($this->connec, "BEGIN");
        return true;
    }

    /**
     * Guarda los cambios.
     *
     * @return void
     */
    protected function commit() {
        return mysqli_query($this->connec, "COMMIT");
    }

    /**
     * Ignora los cambios.
     *
     * @return void
     */
    protected function rollBack() {
        return mysqli_query($this->connec, "ROLLBACK");
    }

    protected function set(&$value) {
        $this->open_connection();
        $value = mysqli_escape_string($this->connec, $value);
        $this->close_connection();
    }

    /**
     * LLama un procedimiento almacenado de la base de datos.
     * 
     * @param string $procedure Nombre del procedure
      protected function callProcedure($procedure) {

      }
     */
}

