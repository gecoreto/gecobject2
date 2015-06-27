<?php

/**
 * @package GecObject\DataBase\Exception
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @link https://github.com/gecoreto/gecobject2
 * @author David Garzon <stylegeco@gmail.com>
 */
use GecObject\DataBase\FieldTbl;

namespace GecObject\DataBase\Exception;

class ExceptionField extends \Exception {

    /**
     *
     * @var \GecObject\DataBase\FieldTbl 
     */
    private $field;

    public function __construct($message, \GecObject\DataBase\FieldTbl $field) {
        parent::__construct($message);
        $this->message = $message;
        $this->field = $field;
        $this->log();
    }

    /**
     * Imprime los mensajes de error 
     */
    private function log() {
        echo("---------ERROR EN LA VALIDACIÓN DEL CAMPO---------");
        echo("<br>Nombre de la tabla:\t {$this->field->getTableName()}");
        echo("<br>Nombre del campo:\t {$this->field->getName()}");
        echo("<br>Mensaje del error:\t {$this->message}");
        echo("<br>Lanzado en:\t <b>{$this->file}</b> en la linea <b>{$this->line}</b>");
        echo("<br>--------------------------------FIN---------------------------------");
    }

}
