<?php

/**
 * Representa un campo de la tabla.
 * 
 * @package Database
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @author Juan Haro <juanharo@gmail.com>
 */
use GecObject\DataBase\Table;

namespace GecObject\DataBase;

class FieldTbl {

    /**
     * Valores del atributo NULL en MySQL.
     */
    const SQL_NOT_NULL_VALUE = "NO";
    const SQL_NULL_VALUE = "YES";

    /**
     * Valores del atributo Extra en MySQL.
     */
    const SQL_AUTO_INCREMENT_VALUE = "auto_increment";

    /**
     * Tipo y longitud por defecto.
     */
    const DEFAULT_TYPE = "varchar";
    const DEFAULT_LENGTH = 50;
    const DEFAULT_NULL_VALUE = self::SQL_NOT_NULL_VALUE;

    /**
     * Nombre del campo.
     * @var string 
     */
    private $name;

    /**
     * Formato del campo.
     * @var string 
     */
    private $type = self::DEFAULT_TYPE;

    /**
     * Extensión máxima del campo.
     * @var string 
     */
    private $length;

    /**
     * El campo debe contener un valor [true|false]
     * @var boolean 
     */
    private $null = self::DEFAULT_NULL_VALUE;

    /**
     * Es PRIMARY KEY de la tabla [true|false]
     * @var boolean 
     */
    private $isPrimaryKey = False;

    /**
     * Propiedades adicionales del campo (p. ej., AUTO_INCREMENT)
     * @var string 
     */
    private $extra;

    /**
     * Si el campo contiene un valor por defecto;
     * @var string 
     */
    private $default;

    /**
     *
     * @var string Nombre de la tabla a la que pertenece el campo
     */
    private $tableName;

    /**
     * 
     * @param array $valores Contine la descripción de cada campo
     */
    function __construct($valores = array(), $tableName = '') {
        $keysDefault = ['Field', 'Type', 'Null', 'Key', 'Default', 'Extra'];
        foreach ($valores as $key => $value) {
            if (in_array($key, $keysDefault)) {
                if ($key == "Key") {
                    $this->setIsPrimaryKey(($value == Table::SQL_PRIMARY_KEY) ? true : false);
                    continue;
                } else if ($key == "Type") {
                    $haveLength = strpos($value, "(");
                    if ($haveLength != false) {
                        $this->setType(substr($value, 0, strpos($value, "(")));
                        $this->setLength(substr($value, strpos($value, "(") + 1, -1));
                    } else {
                        $this->setType($value);
                    }
                    continue;
                }
                $method = ($key == "Field") ? "setName" : "set" . ucfirst($key);
                $this->{$method}($value);
            }
        }
        $this->tableName = $tableName;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getType() {
        return $this->type;
    }

    public function setType($type) {
        $this->type = $type;
    }

    public function getLength() {
        return $this->length;
    }

    public function setLength($length) {
        $this->length = $length;
    }

    public function getNull() {
        return $this->null;
    }

    public function setNull($nullValue) {
        if ($nullValue == self::SQL_NULL_VALUE)
            $this->null = TRUE;
        elseif ($nullValue == self::SQL_NOT_NULL_VALUE) {
            $this->null = FALSE;
        }
    }

    public function getIsPrimaryKey() {
        return $this->isPrimaryKey;
    }

    public function setIsPrimaryKey($isPrimaryKey) {
        $this->isPrimaryKey = $isPrimaryKey;
    }

    public function getExtra() {
        return $this->extra;
    }

    public function setExtra($extra) {
        $this->extra = $extra;
    }

    public function getDefault() {
        return $this->default;
    }

    public function setDefault($default) {
        $this->default = $default;
    }

    public function getTableName() {
        return $this->tableName;
    }

    public function setTableName($tableName) {
        $this->tableName = $tableName;
    }

}

?>
