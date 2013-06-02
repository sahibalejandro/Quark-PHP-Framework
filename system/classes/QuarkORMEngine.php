<?php
/**
 * QuarkPHP Framework
 * Copyright 2012-2013 Sahib Alejandro Jaramillo Leo
 *
 * @author Sahib J. Leo <sahib.alejandro@gmail.com>
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @link    http://quarkphp.com
 */

class QuarkORMEngine{

  /**
   * Array de objetos PDO, uno para cada conexión.
   *
   * @static
   * @access private
   * @var array
   */
  private static $_connections = array();

  /**
   * Informacion de las clases ORM que se van utilizando
   * @var array
   */
  private static $_orms_info = array();

  /**
   * Devuelve el objeto PDO relacionado a la conexión $connection
   *
   * @static
   * @access public
   * @throws QuarkORMException
   * @var string $connection
   * @return PDO
   */
  public static function getConnection($connection)
  {

    if( !isset(self::$_connections[$connection]) ) {

      // Cargar la configuración de las bases de datos que ya fue leida en bigBang()
      $db_config = Quark::getDBConfig();

      // Verificar si existen los datos de conexión
      if( !isset($db_config[$connection]) ) {
        throw new QuarkORMException(
          'Datos para conexión $connection no definidos.',
          QuarkORMException::ERR_NO_CONNECTION_INFO);
      } else {
        // Crear nueva conexión

        $PDO = new PDO(
          'mysql:host='. $db_config[$connection]['host']
            . ';dbname='. $db_config[$connection]['database']
          , $db_config[$connection]['user']
          , $db_config[$connection]['password']
          , $db_config[$connection]['options']
        );

        $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Set charset
        $PDO->query(
          "SET NAMES '" . $db_config[$connection]['charset'] . "';"
        );

        self::$_connections[$connection] = $PDO;
      }
    }
    return self::$_connections[$connection];
  }

  public static function getORMInfo($orm_class_name)
  {
    if( !class_exists($orm_class_name) ){
      throw new QuarkORMException("No existe la clase ORM $orm_class_name"
        , QuarkORMException::ERR_ORM_NOT_FOUND);
    } else {
      // Accedemos a las propiedades estaticas de la clase ORM
      $table      = eval("return $orm_class_name::\$table;");
      $connection = eval("return $orm_class_name::\$connection;");

      if( !isset(self::$_orms_info[$orm_class_name]) ){

        // Obtener la lista de columnas
        $St = self::query("SHOW COLUMNS FROM `$table`;", null, $connection);
        $columns = $St->fetchAll(PDO::FETCH_OBJ);

        // Obtener la lista de campos que forman el primary key, y el campo que tenga
        // AUTO_INCREMENT
        $pk_fields = array();
        $field_auto_increment = '';

        foreach($columns as $Column){
          if($Column->Key == 'PRI'){
            $pk_fields[] = $Column->Field;
          }
          if($Column->Extra == 'auto_increment'){
            $field_auto_increment = $Column->Field;
          }
        }

        // Guardar la información
        self::$_orms_info[$orm_class_name] = (object)array(
          'table'      => $table,
          'connection' => $connection,
          'columns'    => $columns,
          'pk_fields'  => $pk_fields,
          'auto_increment' => $field_auto_increment,
        );
      }

      return self::$_orms_info[$orm_class_name];
    }
  }

  /**
   * Ejecuta la consulta $sql sobre la conexión $connection, utilizando los
   * argumentos $arguments
   *
   * @static
   * @access public
   * @throws QuarkORMException
   * @var string $sql
   * @var array $arguments
   * @var string $connection
   * @return PDOStatement
   */
  public static function query($sql, $arguments = null, $connection)
  {
    try {
      $St = self::getConnection($connection)->prepare($sql);
      $St->execute($arguments);
      return $St;
    } catch( PDOException $PDOException ) {
      throw new QuarkORMException(
        'PDOException: '. $PDOException->getMessage()
        , QuarkORMException::ERR_PDO_EXCEPTION
        , $PDOException
      );
    }
  }
}
