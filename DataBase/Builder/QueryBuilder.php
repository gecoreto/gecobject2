<?php

/**
 * @package GecObject\DataBase\Builder
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @link https://github.com/gecoreto/gecobject2
 * @author David Garzon <stylegeco@gmail.com>
 */
use GecObject\DataBase\DataBase as Db;
use GecObject\DataBase\Table;

namespace GecObject\DataBase\Builder;

/**
 * Description of QueryBuilder
 *
 * @author David
 */
class QueryBuilder {

    /**
     * La tabla que se desea obtener
     *
     * @var string
     */
    public $from;

    /**
     * Las columnas que deben ser devueltas.
     *
     * @var array
     */
    public $columns;

    /**
     * Los wheres para la consulta SQL
     *
     * @var array
     */
    public $wheres;

    /**
     * Las agrupaciones para el query.
     *
     * @var array
     */
    public $groups;

    /**
     * El ordenamiento del query.
     *
     * @var array
     */
    public $orders;

    /**
     * El número máximo de registros para retornar.
     *
     * @var int
     */
    public $limit;

    /**
     * Todos los operadores MYSQL disponibles.
     *
     * @var array
     */
    protected $operators = array(
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'like', 'not like', 'between', 'ilike',
        '&', '|', '^', '<<', '>>',
        'rlike', 'regexp', 'not regexp',
    );

    /**
     * query valores a comparar.
     *
     * @var array
     */
    protected $bindings = array(
        'select' => [],
        'join' => [],
        'where' => [],
        'having' => [],
        'order' => [],
    );

    /**
     * Indica si el query returna "distinct" resultadoss.
     *
     * @var bool
     */
    public $distinct = false;

    /**
     * Crea una nueva instancia de QueryBuilder.
     * @return void
     */
    public function __construct(DataBase $database = null) {
        $this->select();
    }

    /**
     * Establece las columnas que se van a seleccionar.
     *
     * @param  array  $columns
     * @return Table|$this
     */
    public function select($columns = array('*')) {
        $this->columns = (is_array($columns)) ? $columns : func_get_args();
        return $this;
    }

    /**
     * Agrega la tabla que se desea obtener
     *
     * @param  string  $table
     * @return Table
     */
    public function from($table) {
        $this->from = $table;
        return $this;
    }

    /**
     * Agrega una sentencia al query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  mixed   $value
     * @param  string  $boolean
     * @return Table|$this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and') {
        if (is_array($column)) {
            foreach ($column as $key => $value) {
                $this->where($key, '=', $value, $boolean);
            }
            return $this;
        }
        if ($column instanceof \Closure) {//Si es una funcion anonima anido los where 
            //Creo una nueva instancia de QueryBuilder para anidar los where EJ: (name = 'david' and edad > 13)
            $query = new QueryBuilder;
            $query->from($this->from);
            call_user_func($column, $query);
            $this->addNestedWhereQuery($query, $boolean);
            return $this;
        }
        if (func_num_args() == 2) {
            list($value, $operator) = array($operator, '=');
        }
        //Si el operador no corresponde se pone ppor defecto "="
        if (!in_array(strtolower($operator), $this->operators, true)) {
            list($value, $operator) = array($operator, '=');
        }
        $type = "Basic";
        $this->wheres[] = compact('type', 'column', 'operator', 'value', 'boolean');
        $this->addBindings($value, 'where');
        return $this;
    }

    /**
     * Agrega un QueryBuilder anido where al query actual.
     *
     * @param  \GecObject\DataBase\Builder\QueryBuilder $query
     * @param  string  $boolean
     * @return $this
     */
    public function addNestedWhereQuery($query, $boolean = 'and') {
        if (count($query->wheres)) {
            $type = 'Nested';

            $this->wheres[] = compact('type', 'query', 'boolean');

            $this->bindings = array_merge_recursive($this->bindings, $query->bindings);
        }

        return $this;
    }

    /**
     * Agrega una clausula "or where" al query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  mixed   $value
     * @return Table|$this
     */
    public function orWhere($column, $operator = null, $value = null) {
        return $this->where($column, $operator, $value, 'or');
    }

    /**
     * Agrega una clausula where between al query.
     *
     * @param  string  $column
     * @param  array   $values
     * @param  string  $boolean
     * @param  bool  $not
     * @return Table|$this
     */
    public function whereBetween($column, array $value, $boolean = 'and', $not = false) {
        $type = 'between';
        $this->wheres[] = compact('column', 'type', 'boolean', 'not', 'value');

        $this->addBindings($value, 'where');

        return $this;
    }

    /**
     * Agrega una clausula "or where between" al query.
     *
     * @param  string  $column
     * @param  array   $values
     * @return Table|$this
     */
    public function orWhereBetween($column, array $values) {
        return $this->whereBetween($column, $values, 'or');
    }

    /**
     * Agrega una clausula "where not between" al query.
     *
     * @param  string  $column
     * @param  array   $values
     * @param  string  $boolean
     * @return Table|$this
     */
    public function whereNotBetween($column, array $values, $boolean = 'and') {
        return $this->whereBetween($column, $values, $boolean, true);
    }

    /**
     * Agrega una clausula "or where not between" al query.
     *
     * @param  string  $column
     * @param  array   $values
     * @return Table|$this
     */
    public function orWhereNotBetween($column, array $values) {
        return $this->whereNotBetween($column, $values, 'or');
    }

    /**
     * Agrega una clausula "order by" al query.
     *
     * @param  string  $column
     * @param  string  $direction
     * @return Table|$this
     */
    public function orderBy($column, $direction = 'asc') {
        $direction = strtolower($direction) == 'asc' ? 'asc' : 'desc';
        $this->orders[] = compact('column', 'direction');
        return $this;
    }

    /**
     * Agrega una clausula "group by" al query.
     *
     * @param  array|string  $column,...
     * @return Table|$this
     */
    public function groupBy() {
        foreach (func_get_args() as $arg) {
            $this->groups = array_merge((array) $this->groups, is_array($arg) ? $arg : [$arg]);
        }
        return $this;
    }

    /**
     * Agrega una clausula "having" al query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  string  $value
     * @param  string  $boolean
     * @return Table|$this
     */
    public function having($column, $operator = null, $value = null, $boolean = 'and') {
        $type = 'basic';

        $this->havings[] = compact('type', 'column', 'operator', 'value', 'boolean');

        $this->addBindings($value, 'having');

        return $this;
    }

    /**
     * Agrega una clausula "or having" al query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  string  $value
     * @return Table|$this
     */
    public function orHaving($column, $operator = null, $value = null) {
        return $this->having($column, $operator, $value, 'or');
    }

    /**
     * Agrega una clausula "offset" al query.
     *
     * @param  int  $value
     * @return Table|$this
     */
    public function offset($value) {
        $this->offset = max(0, $value);
        return $this;
    }

    /**
     * Setea el valor del "limit" para el query.
     *
     * @param  int  $value
     * @return Table|$this
     */
    public function limit($value) {
        if ($value > 0)
            $this->limit = $value;

        return $this;
    }

    protected function addBindings($val, $type) {
        $this->bindings[$type][] = $val;
    }

}
