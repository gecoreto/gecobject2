<?php

/**
 * @package GecObject\DataBase\Builder
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @link https://github.com/gecoreto/gecobject2
 * @author David Garzon <stylegeco@gmail.com>
 */
use GecObject\DataBase\Table;
use GecObject\DataBase\Builder\QueryBuilder as Query;

namespace GecObject\DataBase\Builder;

/**
 * Description of QueryCompiler
 *
 * @author David
 */
class QueryCompiler {

    /**
     * Los componentes que conforman la clausula SQL
     *
     * @var array
     */
    protected $selectComponents = array(
        'columns',
        'from',
        'wheres',
        'groups',
        'havings',
        'orders',
        'limit',
        'offset',
        'lock',
    );

    /**
     * Compila los componentes necesarios para una clausula select.
     *
     * @param  GecObject\DataBase\Builder\QueryBuilder $query
     * @return array
     */
    protected function compileComponents(QueryBuilder $query) {
        $sql = array();
        foreach ($this->selectComponents as $component) {
            if (!empty($query->$component)) {
                $method = 'compile' . ucfirst($component);
                $sql[$component] = $this->$method($query, $query->$component);
            }
        }

        return $sql;
    }

    /**
     * Concatena un array de segmentos, removiendo los vacios.
     *
     * @param  array   $segments
     * @return string
     */
    protected function concatenate($segments) {
        return implode(' ', array_filter($segments, function($value) {
                    return (string) $value !== '';
                }));
    }

    /**
     * Compila un "select query" en SQL.
     *
     * @param  GecObject\DataBase\Builder\QueryBuilder $query
     * @return string
     */
    public function compileSelect(QueryBuilder $query) {
        if (is_null($query->columns))
            $query->columns = array('*');

        return trim($this->concatenate($this->compileComponents($query)));
    }

    /**
     * Compila un porcion "select *" del query.
     *
     * @param  GecObject\DataBase\Builder\QueryBuilder $query
     * @param  array  $columns
     * @return string
     */
    protected function compileColumns(QueryBuilder $query, $columns) {

        $select = $query->distinct ? 'select distinct ' : 'select ';

        return $select . $this->columnize($columns);
    }

    /**
     * Compila la porcion del "from" para el query.
     *
     * @param  GecObject\DataBase\Builder\QueryBuilder $query
     * @param  string  $table
     * @return string
     */
    protected function compileFrom(QueryBuilder $query, $table) {
        return 'from ' . $table;
    }

    /**
     * Compila la porcion where "where" del query.
     *
     * @param  \GecObject\DataBase\Builder\QueryBuilder $query
     * @return string
     */
    protected function compileWheres(QueryBuilder $query) {
        $sql = array();

        if (is_null($query->wheres))
            return '';

        foreach ($query->wheres as $where) {
            $method = "where{$where['type']}";

            $sql[] = $where['boolean'] . ' ' . $this->$method($query, $where);
        }

        if (count($sql) > 0) {
            $sql = implode(' ', $sql);

            return 'where ' . preg_replace('/and |or /', '', $sql, 1);
        }

        return '';
    }

    /**
     * Compila una clausula where basica.
     *
     * @param  \GecObject\DataBase\Builder\QueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereBasic(QueryBuilder $query, $where) {
        $value = '?';
        // $value = "'{$where['value']}'";
        return $where['column'] . ' ' . $where['operator'] . ' ' . $value;
    }

    /**
     * Compila una clausula where anidada.
     *
     * @param  \GecObject\DataBase\Builder\QueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNested(QueryBuilder $query, $where) {
        $nested = $where['query'];
        return '(' . substr($this->compileWheres($nested), 6) . ')';
    }

    /**
     * Compila una clausula where "between".
     *
     * @param  \GecObject\DataBase\Builder\QueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereBetween(QueryBuilder $query, $where) {
        $between = $where['not'] ? 'not between' : 'between';

        return $where['column'] . ' ' . $between . ' ? and ?';
    }

    /**
     * Compila una clausula "group by" al query.
     *
     * @param \GecObject\DataBase\Builder\QueryBuilder  $query
     * @param  array  $groups
     * @return string
     */
    protected function compileGroups(QueryBuilder $query, $groups) {
        return 'group by ' . $this->columnize($groups);
    }

    /**
     * Compila una clausula "order by" al query.
     *
     * @param  \GecObject\DataBase\Builder\QueryBuilder  $query
     * @param  array  $orders
     * @return string
     */
    protected function compileOrders(QueryBuilder $query, $orders) {
        return 'order by ' . implode(', ', array_map(function($order) {
                            if (isset($order['sql']))
                                return $order['sql'];

                            return $order['column'] . ' ' . $order['direction'];
                        }
                                , $orders));
    }

    /**
     * Compila las clausulas "having" al query.
     *
     * @param  \GecObject\DataBase\Builder\QueryBuilder  $query
     * @param  array  $havings
     * @return string
     */
    protected function compileHavings(QueryBuilder $query, $havings) {
        $sql = implode(' ', array_map(array($this, 'compileHaving'), $havings));

        return 'having ' . preg_replace('/and |or /', '', $sql, 1);
    }

    /**
     * Compila una simple clausula "having".
     *
     * @param  array   $having
     * @return string
     */
    protected function compileHaving(array $having) {
        $column = $having['column'];
        $parameter = '?';
        //$parameter = "'{$having['value']}'";  
        return $having['boolean'] . ' ' . $column . ' ' . $having['operator'] . ' ' . $parameter;
    }

    /**
     * Compila una clausula "offset" al query.
     *
     * @param  \GecObject\DataBase\Builder\QueryBuilder $query
     * @param  int  $offset
     * @return string
     */
    protected function compileOffset(QueryBuilder $query, $offset) {
        return 'offset ' . (int) $offset;
    }

    /**
     * Compila una clausula "limit" al query.
     *
     * @param  \GecObject\DataBase\Builder\QueryBuilder $query
     * @param  int  $limit
     * @return string
     */
    protected function compileLimit(QueryBuilder $query, $limit) {
        return 'limit ' . (int) $limit;
    }

    /**
     * Convierte un array de columnas de nombres en un string delimitado por ", ".
     *
     * @param  array   $columns
     * @return string
     */
    public function columnize(array $columns) {
        return implode(', ', $columns);
    }

    /**
     * Obtiene cada binding del array de bindings en un array consecutivo de estos.
     *
     * @param  array   $bindings
     * @return array
     */
    public function getBindings($bindings = array()) {
        $return = array();

        array_walk_recursive($bindings, function($x) use (&$return) {
            $return[] = $x;
        });

        return $return;
    }

}
