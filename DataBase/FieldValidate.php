<?php

/**
 * Métodos para la validación de campos. Comprueba si el valor asignado a 
 * un campo se ajusta a las restricciones de tipo de columna y longitud.
 * 
 * NOTA: Por el momento solo funciona con bases de datos Mysql
 * 
 * @package Database
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @author Juan Haro <juanharo@gmail.com> Modificado by David Garzon <stylegeco@gmail.com>
 */
use GecObject\DataBase\FieldTbl;

namespace GecObject\DataBase;

class FieldValidate {

    /**
     * Tipos de datos numéricos de MySQL.
     * @var array
     */
    public static $numericFields = array(
        "tinyint", "int", "smallint", "mediumint", "bigint", "decimal", "float", "double", "numeric", "integer"
    );

    /**
     * Tipos de datos de cadena de caracteres de MySQL.
     * @var array
     */
    public static $textFields = array(
        "char", "varchar", "binary", "varbinary", "blob", "text", "enum", "set", "tinytext", "mediumtext", "longtext"
    );

    /**
     * Tipos de datos de fecha y hora de MySQL.
     * @var array
     */
    public static $timeFields = array(
        "date", "datetime", "timestamp", "year", "time"
    );

    /**
     * Valida, campo a campo, un conjunto de campos. Comprueba campos sin valor, si el valor de los campos
     * se ajusta a la longitud y tipo permitido.
     * 
     * @param array $fields
     * @param array $tableRowData
     * @throws ExceptionField
     */
    static function validateAllFields($fields, $tableRowData) {
        foreach ($fields as $field) {
            // Validación identificador
            if ($field->getIsPrimaryKey() == True) {
                //....
            }
            // El campo no está incluido en los datos del registro.
            if (!key_exists($field->getName(), $tableRowData))
                throw new Exception\ExceptionField("El campo no esta incluido en los campos de la tabla.", $field);
            // Validación de tipo y longitud            
            self::validateField($field, $tableRowData[$field->getName()]);
        }
    }

    /**
     * Comprueba si el valor del campo se ajusta al tipo de datos permitido y
     * si no excede la longitud máxima.
     * @param \GecObject\DataBase\FieldTbl $field
     * @param mixed $fieldValue Valor asignado al campo
     * @throws ExceptionField | El valor del campo no se ajusta al tipo permitido.
     */
    public static function validateField(FieldTbl $field, $fieldValue) {
        self::validateNull($field, $fieldValue);
        switch ($field->getType()) {
            case in_array($field->getType(), self::$numericFields):
                if (!is_numeric($fieldValue) && ($field->getNull() == false))
                    throw new Exception\ExceptionField("El valor del campo debe ser numérico.", $field);
                break;
            case in_array($field->getType(), self::$textFields):
                if (!is_string($fieldValue) && ($field->getNull() == false))
                    throw new Exception\ExceptionField("El valor del campo debe ser de tipo cadena.", $field);
                self::validateLength($field, $fieldValue);
                break;
            case in_array($field->getType(), self::$timeFields):
                if (!empty($fieldValue))
                    self::validateTimeFields($field, $fieldValue);
                break;
        }
    }

    /**
     * Comprueba si el valor del campo se ajusta al tipo de datos permitidos.
     * @param \GecObject\DataBase\FieldTbl $field
     * @param mixed $fieldValue Valor asignado al campo
     * @throws ExceptionField | El valor del campo no se ajusta al tipo permitido.
     */
    static function validateTimeFields(FieldTbl $field, $fieldValue) {
        switch (true) {
            case ($field->getType() == 'date'):
                if (preg_match("/(19|20)[0-9]{2}[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])/", trim($fieldValue)) == 0)
                    throw new Exception\ExceptionField("El valor del campo debe ser en formato 'YYYY-MM-DD'.", $field);
                break;
            case ($field->getType() == 'datetime' || $field->getType() == 'timestamp'):
                $date = $fullhour = 0;
                $date = split("-", substr($fieldValue, 0, 10));
                $fullhour = split(":", substr($fieldValue, 11));
                if ((count($date) != 3) || (count($fullhour) != 3))
                    throw new Exception\ExceptionField("El valor del campo debe ser en formato 'YYYY-MM-DD HH:MM:SS'.", $field);
                $year = $date[0];
                $month = $date[1];
                $day = $date[2];
                $hour = $fullhour[0];
                $mim = $fullhour[1];
                $second = $fullhour[2];
                if (!self::isValidDate($year, $month, $day) || !self::isValidTime($hour, $mim, $second))
                    throw new Exception\ExceptionField("El valor del campo debe ser en formato 'YYYY-MM-DD HH:MM:SS'.", $field);
                break;
            case ($field->getType() == 'time'):
                $date = $fullhour = 0;
                $fullhour = split(":", substr($fieldValue, 0, 10));
                if (count($fullhour) != 3)
                    throw new Exception\ExceptionField("El valor del campo debe ser en formato 'HH:MM:SS'.", $field);
                $hour = $fullhour[0];
                $mim = $fullhour[1];
                $second = $fullhour[2];
                if (!self::isValidTime($hour, $mim, $second))
                    throw new Exception\ExceptionField("El valor del campo debe ser en formato 'HH:MM:SS'.", $field);
                break;
        }
    }

    /**
     * Comprueba si el valor del campo excede la longitud permitida.
     * @param \GecObject\DataBase\FieldTbl $field
     * @param type $fieldValue
     * @throws ExceptionField | Longitud mayor que la permitida.
     */
    static function validateLength(FieldTbl $field, $fieldValue) {
        if (strlen($fieldValue) > $field->getLength() && !empty($field->getLength()))
            throw new Exception\ExceptionField("El valor del campo {$field->getName()} tiene una longitud mayor que la permitida ({$field->getLength()}).", $field);
    }

    static function validateNull(FieldTbl $field, $fieldValue) {
        if ($field->getNull() == false) {
            //si no es auto increment no puede estar vacio el campo
            if ($field->getExtra() != FieldTbl::SQL_AUTO_INCREMENT_VALUE && empty($fieldValue))
                throw new Exception\ExceptionField("No se ha especificado el valor del campo.", $field);
        }
    }

    /**
     * Checks to see if the year, month, day are valid combination.
     * @param integer $y year
     * @param integer $m month
     * @param integer $d day
     * @return boolean true if valid date, semantic check only.
     */
    public static function isValidDate($y, $m, $d) {
        if (!is_numeric($d) || !is_numeric($m) || !is_numeric($y))
            return false;
        return checkdate($m, $d, $y);
    }

    /**
     * Verifica si las horas, minutos y segundos son validos.
     * @param integer $h hora
     * @param integer $m minuto
     * @param integer $s segundo
     * @param boolean $hs24 Si las horas deben ser de 0 a 23 (por defecto) o 1 a 12.
     * @return boolean true si es valida la hora
     */
    public static function isValidTime($h, $m, $s, $hs24 = true) {
        if (!is_numeric($h) || !is_numeric($m) || !is_numeric($s))
            return false;
        if ($hs24 && ($h < 0 || $h > 23) || !$hs24 && ($h < 1 || $h > 12))
            return false;
        if ($m > 59 || $m < 0)
            return false;
        if ($s > 59 || $s < 0)
            return false;
        return true;
    }

}

?>
