<?php

/**
 * @package Database
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @link https://github.com/gecoreto/gecobject2
 * @author David Garzon <stylegeco@gmail.com>
 */

namespace GecObject\DataBase;

/**
 * @uses Db representa la clase DataBase
 */
use GecObject\DataBase\DataBase as Db;
use GecObject\DataBase\Table;
use GecObject\DataBase\FieldTbl;
use GecObject\DataBase\FieldValidate;

class RowTbl {

    const NO_CHANGE_FIELD = 'NO_CHANGE_FIELD_';

    /** String para difirenciar una funcion en el valor de un campo
     * @const String $SEPARADOR
     */
    const SEPARADOR = "__";

    /** Array para establecer propiedades dinámicas 
     * @var array() $dynamicFields 
     */
    protected $dynamicFields;

    /** Array representa cada campo de la db mediante la clase FieldTbl
     * @var array() $fields 
     */
    protected $fields;

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
     * @var array Contiene los nombres de las columnas pertenecientes a la tabla
     */
    protected $nameFields = array();

    /**
     * @var array Contiene los nombres de las columnas modificadas para hacer un UPDATE
     */
    protected $changeFields = array();

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
            case 4:
                self::__construct4($args[0], $args[1], $args[2], $args[3]);
                break;
        }
    }

    private function __construct1($table_name) {
        $this->db = Db::database();
        $this->table_name = $table_name;
        $this->db->query = "DESC $table_name";
        foreach ($this->db->select("DESCRIBE $this->table_name") as $value) {
            if ($value['Key'] == Table::SQL_PRIMARY_KEY)
                $this->fieldPk = $value['Field'];
            $this->nameFields[] = $value['Field'];
            $this->fields[$value['Field']] = new FieldTbl($value, $this->table_name);
        }
    }

    private function __construct4($table_name, $fieldPk, $nameFields, $fields) {
        $this->fieldPk = ($fieldPk == NULL) ? $this->fieldPk : $fieldPk;
        $this->db = Db::database();
        $this->table_name = ($table_name == NULL) ? $this->table_name : $table_name;
        $this->nameFields = ($nameFields == NULL) ? $this->nameFields : $nameFields;
        $this->fields = ($fields == NULL) ? $this->fields : $fields;
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
        $addChanges = false;
        if ($value instanceof Closure) {
            $this->closures[$name] = $value;
        } else {
            if (stripos($name, self::NO_CHANGE_FIELD) === 0) {//si el atributo se agrega desde la clase Table
                $name = str_ireplace(self::NO_CHANGE_FIELD, '', $name); //eliminar el diferenciador 'NO_CHANGE_FIELD_' de $name
            } else
                $addChanges = true;
            //solo permite agregar los atributos que coincidan con los campos de la tabla
            if (in_array($name, $this->nameFields)) {
                //validar tipo del campo
                // FieldValidate::validateField($this->fields[$name], $value);
                $this->dynamicFields[$name] = $value;
                if ($addChanges)
                    array_push($this->changeFields, $name);
            }
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
            if (in_array($key, $this->nameFields))//si el atributo dinamico creado corresponde a una columna de la base de datos
                $values[$key] = $value;
            else {
                unset($this->dynamicFields[$key]);
            }
        }
        return $values;
    }

    /**
     * Actualiza la información de la fila en la DB y guarda su contenido
     * @return boolean retorna TRUE si todo salio bien o FALSE si hay un error
     */
    public function save() {
        $vals = $input_parameters = array();
        $atributos = $this->getAsArray();
        if ($this->db->getValidateField())//Validar el tipo de dato
            FieldValidate::validateAllFields($this->fields, $atributos);
        if (!isset($atributos[$this->fieldPk]))
            return false;
        else if (empty($this->changeFields))//si no se ha modificado ninguna columna omito la clausula del update
            return true;
        foreach ($this->changeFields as $col) {
            $value = $atributos[$col];
            if (!empty($value)) {
                if ((substr($value, 0, 2) == self::SEPARADOR && substr($value, -2, 2) == self::SEPARADOR)) {//SI es una funcion de mysql se asigna asi Ej: __NOW__
                    $value = substr($value, 2, -2);
                    $vals[] = $col . ' = ' . $value;
                } else {
                    $vals[] = $col . ' = :' . $col;
                    $input_parameters[':' . $col] = $value;
                }
            }
        }
        $input_parameters[":" . $this->fieldPk] = $atributos[$this->fieldPk];
        $query = "UPDATE $this->table_name SET " . implode(',', $vals) . " WHERE {$this->fieldPk}=:{$this->fieldPk};";
        $this->db->update($query, $input_parameters, __FUNCTION__);
        return ($this->db->getError()) ? false : true;
    }

    /**
     * Inserta un nuevo registro en la tabla correspondiente 
     * @return mixed FALSE on Error Or lastID on OK
     */
    public function add() {
        $cols = $values = $input_parameters = array();
        $atributos = $this->getAsArray();
        if ($this->db->getValidateField())//Validar el tipo de dato
            FieldValidate::validateAllFields($this->fields, $atributos);
        foreach ($atributos as $col => $value) {
            $cols[] = $col;
            //SI es una funcion de mysql se asigna asi Ej: __NOW__
            if ((substr($value, 0, 2) == self::SEPARADOR && substr($value, -2, 2) == self::SEPARADOR)) {
                $values[] = substr($value, 2, -2);
            } else {
                $values[] = ":" . $col;
                $input_parameters[":" . $col] = $value;
            }
        }
        $query = "INSERT INTO {$this->table_name} " . " (" . implode(",", $cols) . ") " . " VALUES " . " (" . implode(",", $values) . ") ";
        $this->db->insert($query, $input_parameters, __FUNCTION__);
        if ($this->db->getError()) {
            return false;
        }
        $this->__set(RowTbl::NO_CHANGE_FIELD . $this->fieldPk, $this->db->getLastId()); //Agrego 'NO_CHANGE_FIELD_' al nombre de atributo para saber que no lo debo guardar en $changeFields de RowTbl
        return $this->db->getLastId();
    }

    /**
     * Elimina la fila de la tabla correspondiente
     * @return boolean 
     */
    public function delete() {
        $query = "DELETE FROM {$this->table_name} WHERE {$this->fieldPk}=:id;";
        $this->db->delete($query, [':id' => $this->__get($this->fieldPk)], __FUNCTION__);
        if ($this->db->getAffectedRows() === 0) {
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

    public function table() {
        return Table::get($this->getTableName());
    }

    public function has_many($table_name, $foreignKey) {
        return Table::get($table_name)->where($foreignKey, $this->{$this->fieldPk})->findAll();
    }

}
