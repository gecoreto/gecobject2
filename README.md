# gecobject2
Libreria desarrollada en PHP para facilitar los procesos del CRUD, representa tablas y filas de Mysql como objetos de php 

Requerimientos
=========

- Servidor web con soporte php 5.3 o superior y Mysql

## License

This software is licenced under the [ licencia MIT.](http://opensource.org/licenses/MIT). Please read LICENSE for information on the
software availability and distribution.

## Instalación & configuración 

Descarga gecObject clonándolo  desde tu pc. Si no estás familiarizado con GIT o simplemente quieres el archivo comprimido has click en “Donwload zip” en la parte derecha de la pantalla.

Luego copia la carpeta gecobject y su contenido en la raíz de tu proyecto php. 

Finalmente un ` require 'gecobject/config.php'; ` para cargar la librería y todo debería funcionar!

### Configuración

Define la configuración para conectarse a la base de datos en el archivo  [config.php](config.php)

```php
<?php

/** Nombre del host en la base de datos 
     * @property type $name Description string DB_HOST
     */
    'db_host' => 'localhost',
    /** Nombre de la base de datos 
     * @property string DB_NAME
     */
    'database' => 'nombre de la base de datos',
    /** Usuario de la base de datos 
     * @property string DB_USER
     */
    'db_user' => 'root',
    /** Password para acceder a la base de datos 
     * @property string DB_PASSWORD
     */
    'db_pass' => '',
    /** Si es true Imprime el registro de mensajes
     * @property boolean LOG
     */
    'log' => false,
    /** Si es true guarda las excepsiones generadas en consultas
     *  mysql en un archivo de texto ubicado en LogMysql/error-mysql.txt
     * 
     * @property boolean ERROR_EXCEPTION
     */
    'error_exception' => false,
    /** Si es true valida que el dato ingresado corresponda con el tipo de la columna
     * 
     * @property boolean validateField
     */
    'validateField' => false,
    'driver' => 'mysql',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'tablePrefix' => '',

?>
```

## Documentación

Esta en desarrollo una documentación mas amplia, si tienes algun comentario no dudes en comunicarmelo, envialo al correo [stylegeco@gmail.com](stylegeco@gmail.com) o en twitter [@stylegeco](https://twitter.com/stylegeco).

## Contribuir

Si quieres ayudarme en el desarrollo de este proyecto no lo dudes, tienes a tu disposición todos los script.

## Sugerencias

- [stylegeco@gmail.com](stylegeco@gmail.com)
- [@stylegeco](https://twitter.com/stylegeco)
