<?php

/**
 * @package DataBase
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @link https://github.com/gecoreto/gecobject22
 * @author David Garzon <stylegeco@gmail.com>
 */

namespace GecObject\DataBase;

use GecObject\DataBase\DataBase as Db;
use GecObject\DataBase\Builder\QueryBuilder as Query;
use GecObject\DataBase\Builder\QueryCompiler as Compiler;
use GecObject\DataBase\FieldTbl;

class Table extends Query {

    const NAME_TABLEROW = 'GecObject\DataBase\RowTbl';

    /** Identifica la clave primaria en Mysql
     * @const SQL_PRIMARY_KEY
     */
    const SQL_PRIMARY_KEY = "PRI";

    /** nombre de la tabla en la base de datos 
     * @var string $table_name
     */
    protected $table_name;

    /** Instancia de la clase Db 
     * @var Db $db
     */
    protected $db;

    /** Array de filas en la tabla representada cada una por un objeto de la clase RowTbl
     * @var array $rows
     */
    private $rows = array();

    /** Array con los nombres correspondientes a los campos de la tabla
     * @var array $rows
     */
    private $nameFields = array();

    /** Array representa cada campo de la db mediante la clase FieldTbl
     * @var array() $fields 
     */
    protected $fields = array();

    /** Nombre correspondiente al campo de la Primary Key
     * @var string $nameFieldPK
     */
    private $nameFieldPK;

    /** Array de instancias de las tablas previamente llamadas
     * @var array $loadedTables
     */
    private static $loadedTables = array();

    function __construct($table_name) {
        $this->db = Db::database();
        $this->table_name = $this->db->getTablePrefix() . $table_name;
        $this->from($table_name);
        foreach ($this->db->select("DESCRIBE $this->table_name") as $campo) {
            if ($campo['Key'] == self::SQL_PRIMARY_KEY) {
                if (empty($this->nameFieldPK)) //Si tiene dos Primary Key selecciono la primera
                    $this->nameFieldPK = $campo['Field'];
            }
            $this->nameFields[] = $campo['Field'];
            $this->fields[$campo['Field']] = new FieldTbl($campo, $this->table_name);
        }
        if (!array_key_exists($table_name, self::$loadedTables))
            self::$loadedTables[$table_name] = $this;
    }

    /**
     * Retorna un unico registro de la tabla que coincida con el $id 
     * @param mixed $id es el valor correspondiente a la Primary Key de la tabla o un array con varias primary keys.
     * <pre>
     * array(
     *        pk1,
     *        pk2,
     *        pk3
     *      );
     * </pre>
     * @return mixed fila seleccionada o array de filas seleccionadas
     */
    public function findByPk($id) {
        /* Busca varias filas en la tabla */
        if (is_array($id)) {// $id: array(0 => id1, 1 => id2...)
            $query = "";
            foreach ($id as $value)
                $query .= "SELECT * FROM " . "{$this->table_name} WHERE  $this->nameFieldPK=?; ";
            $rows = array();
            foreach ($this->db->selectMultiQuery($query, $id) as $conjunto) {
                $rows[] = $this->setRow(reset($conjunto), $this->nameFieldPK);
            }
            return $rows;
        }

        /* Busca una solo fila en la tabla */
        $result = $this->db->selectOne("SELECT * FROM " . "{$this->table_name} WHERE  $this->nameFieldPK=:id;", [":id" => $id]);
        if (empty($result)) {
            return array();
        } else {
            return $this->setRow($result, $this->nameFieldPK);
        }
    }

    /**
     * Retorna el conjunto de registros de la tabla que cumpla con las condiciones
     * recibidas. 
     * @return array objetos de la clase Row
     */
    public function findAll() {
        $this->rows = [];
        $compiler = new Compiler();
        $sql = $compiler->compileSelect($this);
        $rows = $this->db->select($sql, $compiler->getBindings($this->bindings), __FUNCTION__);
        foreach ($rows as $row) {
            $this->rows[] = $this->setRow($row, $this->nameFieldPK);
        }
        return $this->rows;
    }

    /**
     * Retorna una unica fila de la tabla que cumpla con las condiciones
     * de busqueda. 
     * 
     * @return RowTbl objeto de la clase RowTbl
     */
    public function find() {
        $compiler = new Compiler();
        $sql = $compiler->compileSelect($this);
        $row = $this->db->selectOne($sql, $compiler->getBindings($this->bindings), __FUNCTION__);
        if (empty($row))
            return [];
        return $this->setRow($row, $this->nameFieldPK);
    }

    /**
     * Establece las columnas que se van a seleccionar.
     *
     * @param  array  $columns
     * @return Table|$this
     */
    public function select($columns = array('*')) {

        $this->columns = (is_array($columns)) ? $columns : func_get_args();
        $this->columns[] = $this->nameFieldPK; //Agrego siempre la columna correspondiente a la primaryKey
        $this->columns = array_unique($this->columns); //Si la columna primaryKey ya ha sido llamada la elimino
        return $this;
    }

    /**
     * Agrega la tabla que se desea obtener
     * NOTA: Para la clase table el nombre de la tabla no puede ser modificado
     * 
     * @param  string  $table
     * @return Table
     */
    public function from($table) {
        return parent::from($this->table_name);
    }

    /**
     * Crea una nueva fila de la tabla, representa la fila como un objeto
     * @param array $row array asociativo representa una fila en la tabla
     * @param string $fieldPk nombre de la columna asignada como primary key
     * @return Row Objeto de de la clase RowTbl 
     */
    private function setRow($row, $fieldPk) {
        $objeto = new RowTbl($this->table_name, $fieldPk, $this->nameFields, $this->fields);
        foreach ($row as $attribute => $value) {
            if (in_array($attribute, $this->nameFields))
                $objeto->__set(RowTbl::NO_CHANGE_FIELD . $attribute, $value); //Agrego 'NO_CHANGE_FIELD_' al nombre de atributo para saber que no lo debo guardar en $changeFields de RowTbl
        }
        return $objeto;
    }

    /**
     * Crea o retorna una nueva isntancia de Table.
     * @param string $table_name nombre de la tabla para consultar
     * @return Table 
     */
    static function get($table_name) {
        if (empty($table_name))
            throw new \Exception("Es necesario el parametro TableName en el constructor de la clase " . __CLASS__);
        //   if (array_key_exists($table_name, self::$loadedTables))
        //     return self::$loadedTables[$table_name];
        return new self($table_name);
    }

    /**
     * Devuelve el nombre de la columna correspondiente a la clave primaria
     * @return string $fieldPk 
     */
    public function getNameFieldPk() {
        return $this->nameFieldPK;
    }

}
