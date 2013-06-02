<?php
/**
 * QuarkPHP Framework
 * Copyright 2012-2013 Sahib Alejandro Jaramillo Leo
 *
 * @author Sahib J. Leo <sahib.alejandro@gmail.com>
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @link    http://quarkphp.com
 */

abstract class QuarkORM
{
  /**
   * Flag que nos dice si el objeto es nuevo o no, es decir, si ya se encuentra
   * en guardado en la tabla o no.
   * @var boolean
   */
  protected $is_new;

  /**
   * Instancia QuarkORMQueryBuilder, de uso interno.
   * @var QuarkORMQueryBuilder
   */
  protected $QueryBuilder;

  /**
   * Constructor del objeto ORM
   * 
   * @param string $table     Nombre de la tabla
   * @param string $conn_name Nombre de la conexión
   */
  public function __construct()
  {
    $ORMInfo = QuarkORMEngine::getORMInfo(get_class($this));
    
    // Crear las propiedades que representan las columnas de la tabla
    foreach($ORMInfo->columns as $Column){
      if( !isset($this->{$Column->Field}) ){
        $this->{$Column->Field} = null;
      }
    }

    /*
     * Crear un QueryBuilder que sera utilizado internamente en este objeto, en
     * PHP 5.3+ esto no sería necesario.
     */
    $this->QueryBuilder = new QuarkORMQueryBuilder( get_class($this) );

    // El objeto es nuevo si todos los valores de su primary key son null
    $this->is_new = true;
    foreach($this->getPkValues() as $value){
      if($value != null){
        $this->is_new = false;
        break;
      }
    }
  }
  
  /**
   * Guarda los datos del ORM en la tabla, si es un ORM nuevo lo inserta, de lo
   * contrario realiza UPDATE
   */
  public function save()
  {
    if( $this->validate() ){
      
      $ORMInfo = QuarkORMEngine::getORMInfo(get_class($this));

      // Obtener la lista de columnas que deben ser insertadas
      $fields = array();
      
      // Crear los fields para el metodo insert() del QueryBuilder        
      foreach($ORMInfo->columns as $Column){
        $fields[$Column->Field] = $this->{$Column->Field};
      }

      if( $this->is_new ){
        // Preparar query builder para INSERT
        $this->QueryBuilder->insert($fields);
      } else {
        // Preparar query builder para UPDATE
        $this->QueryBuilder->update($fields)->where($this->getPkValues());
      }

      // Ejecutar el query preparado
      $result = $this->QueryBuilder->exec();

      // Actualizar el campo AUTO_INCREMENT si existe y el objeto es nuevo
      // Esto es necesario para hacer el reload cuando los registros utilizan
      // un ID auto increment.
      if($ORMInfo->auto_increment != '' && $this->is_new){
        $PDO = QuarkORMEngine::getConnection($ORMInfo->connection);
        $this->{$ORMInfo->auto_increment} = $PDO->lastInsertId();
      }

      // Despues de guardado, el objeto ya no es nuevo :(
      $this->is_new = false;

      // Recargar los datos del objeto, para que coincidan con los datos almacenados
      // asi no tenemos problemas si una o varias de las columnas eran instancias
      // de QuarkSQLExpression antes de guardar.
      $this->reload();

      return $result;
    }
  }

  /**
   * Elimina el registro asociado al objeto actual.
   * Las propiedades del objeto siguen estando disponibles, y si se invoca save()
   * se re-inserta el registro como nuevo.
   * 
   * @return int Número de filas eliminadas, debe ser 1 fila.
   */
  public function delete()
  {
    if( $this->is_new ){
      // Es nuevo, no hay que borrar nada
      return 0;
    } else {
      // Borrar el registro utilizando su primary key en el WHERE
      $this->QueryBuilder->delete()->where($this->getPkValues())->limit(1)
        ->exec();

      // El objeto vuelve a ser nuevo
      $this->is_new = true;
    }
  }

  /**
   * Recarga los datos del objeto desde la tabla, seleccionando el registro por su
   * primary key
   */
  protected function reload()
  {
    // Brutal combo!
    $this->QueryBuilder
      ->select('*')
      ->where($this->getPkValues())
      ->limit(1)
      ->fetchInto($this)
      ->exec();
  }

  /**
   * Devuelve un array key/value pairs con los datos del primary key
   * 
   * @return array
   */
  protected function getPkValues()
  {
    $pk_values = array();
    $ORMInfo = QuarkORMEngine::getORMInfo(get_class($this));
    foreach($ORMInfo->pk_fields as $field){
      $pk_values[$field] = $this->$field;
    }
    return $pk_values;
  }

  /**
   * Devuelve un QuarkORMQueryBuilder preparado para obtener a los hijos del ORM
   * actual.
   * 
   * @param  string $orm_class_name Nombre del ORM hijo
   * @return QuarkORMQueryBuilder Query builder preparado
   */
  public function getChilds($orm_class_name, $just_one = false)
  {
    $ORMInfo = QuarkORMEngine::getORMInfo(get_class($this));

    // Creamos un query builder para la clase $orm_class_name
    $QueryBuilder = $this->createQueryBuilderFor($orm_class_name);

    // Crear los parent fields para el where
    $parent_fields = array();
    foreach($this->getPkValues() as $key => $val){
      $parent_fields[$ORMInfo->table . "_$key"] = $val;
    }

    // Devolvemos el QueryBuilder preparado para que hagan exec() / puff()
    if($just_one){
      $QueryBuilder->findOne();
    } else {
      $QueryBuilder->find();
    }
    return $QueryBuilder->where($parent_fields);
  }

  /**
   * Igual que getChilds pero solo devuelve un objeto.
   * @param  string $orm_class_name
   * @return QuarkORMQueryBuilder
   */
  public function getChild($orm_class_name)
  {
    return $this->getChilds($orm_class_name, true);
  }

  /**
   * Devuelve una instancia de QuarkORM que representa al registro padre del
   * ORM actual basado en los parent primary keys
   * @param  string $orm_class_name Nombre de la clase ORM padre
   * @return QuarkORM
   */
  public function getParent($orm_class_name)
  {
    $ParentORMInfo = QuarkORMEngine::getORMInfo($orm_class_name);
    
    // Crear los fields que representan el primary key de padre
    $where_fields = array();
    foreach($ParentORMInfo->pk_fields as $parent_field){
      $child_field = $ParentORMInfo->table . "_{$parent_field}";
      $where_fields[$parent_field] = $this->$child_field;
    }

    // Crear el query builder para buscar al parent orm
    $QueryBuilder = $this->createQueryBuilderFor($orm_class_name);
    // Buscar al parent orm y devolver su resultado
    return $QueryBuilder->findOne()->where($where_fields)->exec();
  }

  /**
   * Prepara un QuarkORMQueryBuilder para hacer un count de los hijos del orm actual
   * que sean de la clase $orm_class_name
   * 
   * @param  string $orm_class_name Nombre del ORM
   * @return QuarkORMQueryBuilder
   */
  public function countChilds($orm_class_name)
  {
    $ORMInfo = QuarkORMEngine::getORMInfo(get_class($this));
    $QueryBuilder = $this->createQueryBuilderFor($orm_class_name);

    // Crear los parent fields para el where
    $parent_fields = array();
    foreach($this->getPkValues() as $key => $val){
      $parent_fields[$ORMInfo->table . "_$key"] = $val;
    }

    // Devolvemos el Query Builder preparado para hacer puffy truff!
    return $QueryBuilder->count()->where($parent_fields);
  }

  /**
   * Asigna los valores a las propiedades que pertenecen a los campos de un registro
   * padre.
   * 
   * @param QuarkORM $Parent Instancia del objeto padre
   */
  public function setParent(QuarkORM $Parent)
  {
    $parent_class_name = get_class($Parent);
    if( $Parent->isNew() ){
      throw new QuarkORMException(
        "No se puede utilizar un objeto QuarkORM ($parent_class_name) "
        . 'nuevo en setParent()'
        , QuarkORMException::ERR_NEW_PARENT);
    } else {
      $ParentORMInfo = QuarkORMEngine::getORMInfo( $parent_class_name );
      foreach($ParentORMInfo->pk_fields as $field){
        $this->{$ParentORMInfo->table . "_$field"} = $Parent->$field;
      }
    }
  }

  /**
   * Crea un QuarkORMQueryBuilder para un QuarkORM especificado en $orm_class_name
   * 
   * @param  string $orm_class_name Nombre del ORM
   * @return QuarkORMQueryBuilder
   */
  protected function createQueryBuilderFor($orm_class_name)
  {
    // Instanciamos un objeto para obtener su nombre de tabla y conexión
    $ORMInfo = QuarkORMEngine::getORMInfo($orm_class_name);
    return new QuarkORMQueryBuilder($orm_class_name, $ORMInfo->table
      , $ORMInfo->connection);
  }

  /**
   * Metodo para validar las propiedades (campos) antes de realizar save()
   * No es necesario que el usuario invoque este metodo, es invocado dentro de save()
   * Si devuelve true se ejecuta el query para guardar datos, de lo contrario no.
   * 
   * @return boolean
   */
  protected function validate()
  {
    return true;
  }

  /**
   * Devuelve true si el objeto es nuevo (no guardado en base de datos), de lo
   * contrario devuelve false
   * 
   * @return boolean
   */
  public function isNew()
  {
    return $this->is_new;
  }

  /**
   * Metodo que devuelve un QuarkORMQueryBuilder preparado para realizar consultas
   * utilizando la tabla y la conexión asociadas al objeto ORM
   * 
   * @return QuarkORMQueryBuilder
   */
  public static function query()
  {
    return null;
  }
}
