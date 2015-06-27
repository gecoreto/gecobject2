<?php

/**
 * @package DataBase
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @link https://github.com/gecoreto/gecobject
 * @author David Garzon <stylegeco@gmail.com>
 */

namespace GecObject\DataBase;

use GecObject\DataBase\DataBase as Db;

class Table {

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
    /** Nombre correspondiente al campo de la Primary Key
     * @var string $nameFieldPK
     */
    private $nameFieldPK;

    function __construct($table_name) {
        $this->db = Db::database();
        $this->table_name = $table_name;
        $this->db->query = "DESC $this->table_name";
        foreach ($this->db->get_results_from_query() as $campo) {
            if ($campo['Key'] == self::SQL_PRIMARY_KEY) {
                if (empty($this->nameFieldPK)) //Si tiene dos Primary Key selecciono la primera
                    $this->nameFieldPK = $campo['Field'];
            }
            $this->nameFields[] = $campo['Field'];
        }
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
        if (is_array($id)) {
            $this->db->query = "";
            foreach ($id as $value)
                $this->db->query .= "SELECT * FROM {$this->table_name} WHERE  $this->nameFieldPK='$value';";
            $this->db->execute_multi_query();
            foreach ($this->db->get_results_from_query() as $row)
                $filas[] = $this->setRow($row, $this->nameFieldPK);

            return $filas;
        }
        /* Busca una solo fila en la tabla */
        $this->db->query = "SELECT * FROM {$this->table_name} WHERE  $this->nameFieldPK='$id';";
        $result = $this->db->get_results_from_query();
        if (empty($result)) {
            return array();
        } else {
            return $this->setRow($result[0], $this->nameFieldPK);
        }
    }

    /**
     * Retorna el conjunto de registros de la tabla que cumpla con las condiciones
     * recibidas como parámetro. <br>
     * <pre>
     * Donde:
     * $where debe ser una cadena de condiciones 
     * en lenguaje de consultas MySQL 
     * (p.ej., WHERE campo = value and campo2 LIKE "%nombre%"). 
     * Si no recibe parametros, devolverá todos los registros de la tabla.</pre>
     * @param string $cols Columnas
     * @param string $where Condiciones
     * @param array $order Arreglo Asociativo
     *      <pre>
     * array(
     *              'type' => ASC | DESC
     *              'columns' => array('column1', 'column2')
     *          );
     *      </pre>
     * @param string $limit Limit
     * @return array objetos de la clase Row
     */
    public function findAll($cols = '*', $where = array(), $order = array(), $limit = '') {
        $this->rows = array();
        $orderby = "";
        if (array_key_exists('type', $order) && array_key_exists('columns', $order)) {
            $tipo = $order['type'];
            if (in_array($tipo, array('ASC', 'DESC'))) {
                $columns = implode(',', $order['columns']);
                $orderby = "ORDER BY $columns $tipo";
            }
        }
        if ($cols != "*")
            $cols = "{$this->nameFieldPK},$cols";
        $where = !empty($where) ? self::parseWhere($where) : '';
        $limit = !empty($limit) ? "LIMIT $limit " : '';
        $this->db->query = "SELECT $cols FROM {$this->table_name} $where $orderby $limit;";
        foreach ($this->db->get_results_from_query() as $row) {
            $this->rows[] = $this->setRow($row, $this->nameFieldPK);
        }
        return $this->rows;
    }

    /**
     * Retorna una unica fila de la tabla que cumpla con las condiciones
     * recibidas como parámetro. <br>
     * <pre>
     * Donde:
     * $where debe ser una cadena de condiciones 
     * en lenguaje de consultas MySQL 
     * (p.ej., campo = value and campo2 LIKE "%nombre%"). 
     * Si no recibe parametros, devolverá todos los registros de la tabla.</pre>
     * @param string $cols Columnas que desea seleccionar separadas por ","
     * @param string $where Condiciones
     * @return RowTbl objeto de la clase RowTbl
     */
    public function find($cols = '*', $where = array()) {
        if ($cols != "*")
            $cols = "{ $this->nameFieldPK},$cols";
        $where = !empty($where) ? self::parseWhere($where) : '';
        $this->db->query = "SELECT $cols FROM {$this->table_name} $where;";
        $row = array_shift($this->db->get_results_from_query());
        if (empty($row))
            return [];
        return $this->setRow($row, $this->nameFieldPK);
    }

    /**
     * Retorna el conjunto de registros de la tabla que cumpla con el query recibido como parametro. <br>
     * Mysql(p.ej., ORDER BY RAND() LIMIT 3). 
     * @param string $query Limit
     * @return array|object objetos de la clase RowTbl
     */
    public function findByQuery($query) {
        $rows = [];
        $this->db->query = "SELECT * FROM {$this->table_name} " . $query;
        foreach ($this->db->get_results_from_query() as $row) {
            $rows[] = $this->setRow($row, $this->nameFieldPK);
        }
        if (count($rows) == 1)
            return $row[0];
        return $rows;
    }

    /**
     * Crea una nueva fila de la tabla, representa la fila como un objeto
     * @param array $row array asociativo representa una fila en la tabla
     * @param string $fieldPk nombre de la columna asignada como primary key
     * @return Row Objeto de de la clase RowTbl 
     */
    private function setRow($row, $fieldPk) {
        $objeto = new RowTbl($this->table_name, $fieldPk, $this->nameFields);
        foreach ($row as $attribute => $value) {
            if (in_array($attribute, $this->nameFields))
                $objeto->__set($attribute, $value);
        }
        return $objeto;
    }

    /**
     * Crea una nueva isntancia de Table.
     * @param string $table_name nombre de la tabla para consultar
     * @return Table 
     */
    static function get($table_name) {
        $table = new self($table_name);
        return $table;
    }

    /**
     * Devuelve el nombre de la columna correspondiente a la clave primaria
     * @return string $fieldPk 
     */
    public function getNameFieldPk() {
        return $this->nameFieldPK;
    }

    /**
     * Retorna el where para la consulta sql. <br>
     * @param array $values 
     * @return String Devuelve un where para generar la consulta sql
     */
    private static function parseWhere($values) {
        $where = "WHERE ";
        foreach ($values as $column => $val) {
            switch (true) {
                case stripos($val, 'LIKE') === 0:
                    $compare = "LIKE ";
                    break;
                case stripos($val, '>') === 0:
                    $compare = ">";
                    break;
                case stripos($val, '<') === 0:
                    $compare = "<";
                    break;
                case stripos($val, '!=') === 0:
                    $compare = "!=";
                    break;
                default:
                    $compare = "=";
                    break;
            }
            // Elimina el comparador de la cadena $value
            $val = str_ireplace($compare, '', $val);
            $where .= "$column $compare '$val' AND ";
        }
        //Elimino el ultimo AND para no generar error en la sintaxis sql
        $where = substr($where, 0, -4);
        return $where;
    }

}
