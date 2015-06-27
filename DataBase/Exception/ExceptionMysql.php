<?php

/**
 * @package Exception
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @link https://github.com/gecoreto/gecobject
 * @author David Garzon <stylegeco@gmail.com>
 */

namespace GecObject\DataBase\Exception;

class ExceptionMysql extends \Exception {

    /**
     * Mensaje de la exception lanzada
     * @var string $message
     */
    protected $message;

    /**
     * Codigo de error generado en la consulta Mysql
     * @var integer $code
     */
    protected $code;

    /**
     * Constructor inicializa los atributos de la clase
     * @param string $message Mensaje de la exception lanzada
     * @param integer $code Codigo de error generado en la consulta Mysql
     */
    public function __construct($message, $code, $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->message = $message;
        $this->code = $code;
        $this->changemessage();
        $this->log();
    }

    /**
     * Cambia el atributo $mensaje generado de acuerdo con el codigo de la exception.
     */
    private function changemessage() {
        switch ($this->code) {
            case 1062 :
                $this->message = "Ya se encuentran datos guardados con los valores introducidos, por favor intente de nuevo.";
                break;
            case 1264 :
                $this->message = "Algún dato fue dado fuera del rango establecido en la base de datos.";
                break;
            case 1064 :
                $this->message = "Error en la sintaxis.";
                break;
            default :
                //$this->message = "Código $this->code no encontrado";
                break;
        }
    }

    /**
     * Imprime los mensajes de error 
     */
    private function log() {
        echo("---------ERROR EN CONSULTA MYSQL---------");
        echo("<br>C&oacute;digo de error:\t $this->code");
        echo("<br>Mensaje del error:\t $this->message");
        echo("<br>-------------------FIN--------------------");
        //Si la constante global ERROR_EXCEPTION es true escribe los errores en un archivo de texto
        if (ERROR_EXCEPTION) {
            $ar = fopen("gecobject/LogMySql/error-mysql.txt", "a+") or exit($this->message);
            fputs($ar, "\n\n\n---------" . gmdate("D, Y/m/d H:i:s", time() - 18000) . "---------");
            fputs($ar, "\nCódigo de error:\t $this->code");
            fputs($ar, "\nMensaje del error:\t $this->message");
            fputs($ar, "\n---------Fin del error---------");
            fclose($ar);
        }
    }

}
