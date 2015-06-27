<?php

/**
 * @package Database
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @link https://github.com/gecoreto/gecobject
 * @author David Garzon <stylegeco@gmail.com>
 */

namespace GecObject\DataBase;

/**
 * @uses Db representa la clase DataBase
 */
use GecObject\DataBase\DataBase as Db;
use GecObject\DataBase\Table;

class RowTbl {

    /** Array para establecer propiedades dinámicas 
     * @var array() $dynamicFields 
     */
    protected $dynamicFields;

    /** Array para acceder a las Closures 
     * @var array() $closures 
     */
    protected $closures;

    /**
     * Nombre de la tabla en la base de datos 
     * @var string $table_name
     */
    private $table_name;

    /** Nombre de la columna correspondiente a la primary key
     * @var String $fieldPk
     */
    private $fieldPk;

    /** Instancia de la clase Db 
     * @var Db $db
     */
    private $db;

    /**
     */
    protected $nameFields = array();

    /**
     * Constructor de la clase
     * @param string $table_name Nombre de la tabla.
     * @param string $fieldPk Nombre del campo primary Key.
     * @return boolean true si está asignado como atributo dinámico, false en otro caso (comportamiento de isset).
     * @throws Exception
     */
    function __construct() {
        $args = func_get_args();
        $nargs = func_num_args();
        if ($nargs == 0) {
            try {
                if (empty($args[0]))
                    throw new \Exception("Es necesario el parametro TableName en el constructor de la clase " . __CLASS__);
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
        }
        switch ($nargs) {
            case 1:
                self::__construct1($args[0]);
                break;
            case 3:
                self::__construct3($args[0], $args[1], $args[2]);
                break;
        }
    }

    private function __construct1($table_name) {
        $this->db = Db::database();
        $this->table_name = $table_name;
        $this->db->query = "DESC $table_name";
        foreach ($this->db->get_results_from_query() as $value) {
            if ($value['Key'] == "PRI")
                $this->fieldPk = $value['Field'];
            $this->nameFields[] = $value['Field'];
        }
    }

    private function __construct3($table_name, $fieldPk, $nameFieldPK) {
        $this->fieldPk = $fieldPk;
        $this->db = Db::database();
        $this->table_name = $table_name;
        $this->nameFields = $nameFieldPK;
    }

    /**
     * Método mágico: comprueba si está asignado el atributo en tiempo de ejecución.
     * @param string $name Nombre del atributo.
     * @return boolean true si está asignado como atributo dinámico, false en otro caso (comportamiento de isset).
     */
    public function __isset($name) {
        return (isset($this->dynamicFields[$name]) or isset($this->closure[$name]));
    }

    /**
     * Método mágico: devuelve un atributo asignado en tiempo de ejecución.
     * @param string $name Nombre del atributo.
     * @return mixed Valor del atributo.
     */
    public function __get($name) {
        if (isset($this->dynamicFields[$name]))
            return $this->dynamicFields[$name];
        if (isset($this->closure[$name]))
            return $this->closure[$name];
        return null;
    }

    /**
     * Método mágico: asigna un atributo dinámico en tiempo de ejecución.
     * @param string $name Nombre del atributo.
     * @param string $value Valor del atributo.
     */
    public function __set($name, $value) {
        if ($value instanceof Closure) {
            $this->closures[$name] = $value;
        } else {
            //solo permite agregar los atributos que coincidan con los campos de la tabla
            if (in_array($name, $this->nameFields))
                $this->dynamicFields[$name] = $value;
        }
    }

    /**
     * Método mágico: ejecuta unset sobre un atributo asignado en tiempo de ejecución.
     * @param $name Nombre del atributo.
     */
    public function __unset($name) {
        if (isset($this->dynamicFields[$name]))
            unset($this->dynamicFields[$name]);
        if (isset($this->closure[$name]))
            unset($this->closure[$name]);
    }

    /**
     * Método mágico: agrega metodos dinamicamente a la clase
     * La clase no tiene más que un método para añadir métodos desde clausuras.
     * 
      public function __call($name, $arguments) {
      if (isset($this->closures[$name]) && $this->$name instanceof Closure) {
      return call_user_func_array($this->$name, $arguments);
      }
      } */

    /**
     * Devuelve los valores de la fila como array.
     * <pre>
     * Ejemplo:
     * array(
     *        'id' => 1
     *        'nombre' => Antonio
     *      );
     * </pre>
     * @return array Array con los valores del objeto.
     * */
    public function getAsArray() {
        $values = array();
        // Si los atributos dinámicos no existen, devolvemos un array vacío
        if (!is_array($this->dynamicFields) or count($this->dynamicFields) == 0)
            return $values;
        // Para cada atributo dinámico, lo añadimos en el array
        foreach ($this->dynamicFields as $key => $value) {
            $values[$key] = $value;
        }
        return $values;
    }

    /**
     * Actualiza la información de la fila en la DB y guarda su contenido
     * @return boolean retorna TRUE si todo salio bien o FALSE si hay un error
     */
    public function save() {
        if (!isset($this->getAsArray()[$this->fieldPk]))
            return false;
        $atributos = $this->getAsArray();
        foreach ($atributos as $col => $value) {
            $vals[] = $col . ' = "' . $value . '"';
        }
        $this->db->query = "UPDATE $this->table_name SET " . implode(',', $vals) . " WHERE {$this->fieldPk}='{$atributos[$this->fieldPk]}';";
        $this->db->execute_single_query(true);
        if ($this->db->getAffectedRows() === null) {
            return false;
        }
        return true;
    }

    /**
     * Inserta un nuevo registro en la tabla correspondiente 
     * @return mixed FALSE on Error Or lastID on OK
     */
    public function add() {

        $cols = $values = array();
        $atributos = $this->getAsArray();
        foreach ($atributos as $col => $value) {
            $cols[] = $col;
            $values[] = "'$value'";
        }
        $this->db->query = "INSERT INTO {$this->table_name} " . " (" . implode(",", $cols) . ") " . " VALUES " . " (" . implode(",", $values) . ") ";
        $this->db->execute_single_query(true);
        if ($this->db->getAffectedRows() === null) {
            return false;
        }
        $this->__set($this->fieldPk, $this->db->getLastId());
        return $this->db->getLastId();
    }

    /**
     * Elimina la fila de la tabla correspondiente
     * @return boolean 
     */
    public function delete() {
        $this->db->query = "DELETE FROM {$this->table_name} WHERE {$this->fieldPk}='{$this->__get($this->fieldPk)}';";
        $this->db->execute_single_query(true);
        if ($this->db->getAffectedRows() === null) {
            return false;
        }
        $this->dynamicFields = array();
        return true;
    }

    /**
     * Retorna el nombre de la tabla a la que pertenece este objeto
     * @return String TableName 
     */
    public function getTableName() {
        return $this->table_name;
    }

}
