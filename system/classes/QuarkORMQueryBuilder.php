<?php
class QuarkORMQueryBuilder
{
  /**
   * Nombre de la clase QuarkORM vinculada con este query builder.
   * 
   * @var string
   */
  private $_orm_class_name;

  /**
   * Instancia de un objeto QuarkORM sobre el cual se hara el fetch de un resultado
   * obtenido con findOne()->exec().
   * 
   * @var QuarkORM
   */
  private $_ObjFetchTarget;

  /**
   * Partes del string SQL que será ejecutado en exec().
   * 
   * @var array
   */
  private $_sql = array();

  /**
   * Array asociativo con los argumentos que serán enviados al PDOStatment en exec().
   * Este array es inflado mediante los metodos CRUD.
   * 
   * @var array
   */
  private $_arguments = array();

  /**
   * Flag para saber si se invoco findOne() o selectOne(), es utilizado en el
   * metodo exec().
   * 
   * @var boolean
   */
  private $_just_one = false;

  /**
   * Constructor del QueryBuilder
   * @param string $orm_class_name Nombre del ORM al que pertenece
   */
  public function __construct($orm_class_name)
  {
    $this->_orm_class_name = $orm_class_name;
  }

  /**
   * Prepara el query para realizar un insert
   * @return QuarkORMQueryBuilder
   */
  public function insert($key_value_pairs)
  {
    $ORMInfo = QuarkORMEngine::getORMInfo($this->_orm_class_name);
    $this->expandPlaceholders($key_value_pairs, $placeholders, $arguments);
    $placeholders = implode(',', $placeholders);
    $keys = implode(',', $this->quoteFields(array_keys($key_value_pairs)));
    $this->appendSQL('insert'
      , "INSERT INTO `{$ORMInfo->table}`($keys)VALUES($placeholders)"
      , $arguments);

    return $this;
  }

  /**
   * Prepara el query para realizar un select
   * @return QuarkORMQueryBuilder
   */
  public function find()
  {
    $ORMInfo = QuarkORMEngine::getORMInfo($this->_orm_class_name);
    $this->appendSQL('find', "SELECT * FROM `{$ORMInfo->table}`");
    return $this;
  }

  /**
   * Prepara el query para seleccionar solo un elemento
   * @param  string $fields
   * @return QuarkORMQueryBuilder
   */
  public function findOne()
  {
    // Encender flag para solo devolver 1 objeto
    $this->_just_one = true;
    return $this->find();
  }

  /**
   * Funciona igual que find() pero exec() no devolverá un array de instancias
   * de objetos QuarkORM, en su lugar devolverá un array de objetos anonimos con los
   * campos solicitados en los argumentos de entrada.
   * 
   * Devuelve la instancia del QuarkORMQueryBuilder actual.
   * 
   * @param  string $fields Campos
   * @return QuarkORMQueryBuilder
   */
  public function select($fields)
  {
    $ORMInfo = QuarkORMEngine::getORMInfo($this->_orm_class_name);
    $this->_fields = func_get_args();

    if(count($this->_fields) == 1 && $this->_fields[0] == '*'){
      $fields = '*';
    } else {
      $fields = implode(',', $this->quoteFields($this->_fields));
    }
    $this->appendSQL('select', "SELECT $fields FROM `{$ORMInfo->table}`");

    return $this;
  }

  /**
   * Funciona igual que select() pero exec() devolverá solo 1 objeto anonimo
   * en lugar de un array de objetos anonimos.
   *
   * Devuelve la instancia del QuarkORMQueryBuilder actual.
   * 
   * @param  string $fields Campos
   * @return QuarkORMQueryBuilder
   */
  public function selectOne($fields)
  {
    // Encender flag para solo devolver 1 objeto
    $this->_just_one = true;
    $args = func_get_args();
    return call_user_func_array(array($this, 'select'), $args);
  }

  /**
   * Prepara el query para realizar un UPDATE
   * @return QuarkORMQueryBuilder
   */
  public function update($key_value_pairs)
  {
    $ORMInfo = QuarkORMEngine::getORMInfo($this->_orm_class_name);
    $this->expandPlaceholders($key_value_pairs, $placeholders, $arguments, true);
    $placeholders = implode(',', $placeholders);
    $this->appendSQL('update', "UPDATE `{$ORMInfo->table}` SET $placeholders"
      , $arguments);

    return $this;
  }

  /**
   * Prepara el query para realizar un DELETE
   * @return QuarkORMQueryBuilder
   */
  public function delete()
  {
    $ORMInfo = QuarkORMEngine::getORMInfo($this->_orm_class_name);
    $this->appendSQL('delete', "DELETE FROM `{$ORMInfo->table}`");
    return $this;
  }

  /**
   * Genera el WHERE para un query preparado, si WHERE es invocado varias veces se
   * irán concatenando las condicionas utilizando AND, si quiere concatenar al WHERE
   * utilizando OR definalo en el argumento $logic_operator o utilice el metodo or();
   * 
   * @param string|array Condicion
   * @param array $arguments Argumentos para la condicion
   * @param string $logic_operator Operador logico para unir con condiciones ya
   *                               definidas
   */
  public function where($where, $arguments = null, $logic_operator = 'AND')
  {
    if( is_array($where) ) {
      $this->expandPlaceholders($where, $placeholders, $arguments, true);
      $where = implode(" AND ", $placeholders);
    }

    // Crear o completar el where
    $this->appendSQL('where'
      , !isset($this->_sql['where']) ? "WHERE ($where)"
        : $this->_sql['where'] . " $logic_operator ($where)"
      , $arguments);

    return $this;
  }

  /**
   * Agrega una condicion al WHERE utilizando el operar logico OR
   * @param  string|array $where
   * @param  array $arguments
   * @return QuarkORMQueryBuilder
   */
  public function orWhere($where, $arguments = null)
  {
    return $this->where($where, $arguments, 'OR');
  }

  /**
   * Agrega una condicion al WHERE utilizando el operar logico AND
   * @param  string|array $where
   * @param  array $arguments
   * @return QuarkORMQueryBuilder
   */
  public function andWhere($where, $arguments = null)
  {
    return $this->where($where, $arguments, 'AND');
  }

  /**
   * Establece el LIMIT para un query preparado
   */
  public function limit($skip, $limit = 0)
  {
    $this->appendSQL('limit', "LIMIT $skip" . ($limit > 0 ? ",$limit" : null) );
    return $this;
  }

  /**
   * Establece el ORDER BY para un query preparado
   */
  public function order($order_by, $type = 'ASC')
  {
    // Crear o completar el ORDER BY
    $this->appendSQL('orderby',
      !isset($this->_sql['orderby']) ? "ORDER BY $order_by $type"
      : $this->_sql['orderby'] . ", $order_by $type");
    return $this;
  }

  /**
   * Busca un registro por primary key y devuelve su ORM
   * @param  mixed|array $pk Valor o valores de primary key
   * @return QuarkORM
   */
  public function findByPk($pk)
  {
    if( !is_array($pk) ){
      // En este caso se asume que el primary key esta formado solo por un campo.
      $ORMInfo = QuarkORMEngine::getORMInfo($this->_orm_class_name);
      $pk = array(
        $ORMInfo->pk_fields[0] => $pk
      );
    }

    // Super combo!
    return $this->findOne()->where($pk)->exec();
  }

  /**
   * Prepara el query builder para hacer un select count
   * @return [type] [description]
   */
  public function count()
  {
    $ORMInfo = QuarkORMEngine::getORMInfo($this->_orm_class_name);
    $this->appendSQL('count', "SELECT COUNT(*) FROM `{$ORMInfo->table}`");

    return $this;
  }

  protected function _selectFunction($func, $fields)
  {
    $func_fields = array();
    $ORMInfo = QuarkORMEngine::getORMInfo($this->_orm_class_name);
    foreach($fields as $field){
      $func_fields[] = "$func(`$field`) AS `$field`";
    }
    $func_fields = implode(',', $func_fields);
    $this->appendSQL('select_function'
      , "SELECT $func_fields FROM `{$ORMInfo->table}`");
    return $this;
  }

  public function sum($fields)
  {
    $fields = func_get_args();
    return $this->_selectFunction('SUM', $fields);
  }

  public function min($fields)
  {
    $fields = func_get_args();
    return $this->_selectFunction('MIN', $fields);
  }

  public function max($fields)
  {
    $fields = func_get_args();
    return $this->_selectFunction('MAX', $fields);
  }

  /**
   * Define un objeto donde se hará el fetch al seleccionar 1 registro
   * 
   * @param  object $Object Objeto destino
   * @return QuarkORMQueryHandler
   */
  public function fetchInto($Object)
  {
    $this->_ObjFetchTarget = $Object;

    return $this;
  }

  /**
   * Expande un array key/value pairs $fields a place holders y argumentos
   * para ser utilizados en un query generado.
   */
  protected function expandPlaceholders($fields, &$placeholders, &$arguments,
    $assignment = false)
  {
    // Inicializar variables de salida
    $placeholders = $arguments = array();

    foreach($fields as $key => $value)
    {
      if(!($value instanceof QuarkSQLExpression)){
        if($assignment == true) {
          $placeholders[] = "`$key`=:$key";
        } else {
          $placeholders[] = ":$key";
        }
        $arguments[":$key"] = $value;
      } else {
        if($assignment == true){
          $placeholders[] = "`$key`=" . $value->getExpression();
        } else {
          $placeholders[] = $value->getExpression();
        }
        $arguments = array_merge($arguments, $value->getArguments());
      }
    }
  }

  /**
   * Ejecuta el query generado y devuelve el resultado esperado
   * 
   * @param QuarkORM Instancia donde se hara el fetch into en caso de ser select one
   * @return mixed Puede devolver un QuarkORM, un array(QuarkORM), el numero de
   *               filas afectadas, false o un array vacio.
   */
  public function exec()
  {
    $ORMInfo = QuarkORMEngine::getORMInfo($this->_orm_class_name);
    // Formar el SQL concatenando las sentencias SQL en el orden correcto.
    $sql =
        (isset($this->_sql['find'])    ? $this->_sql['find']   : null)
      . (isset($this->_sql['select'])  ? $this->_sql['select'] : null)
      . (isset($this->_sql['select_function'])
          ? $this->_sql['select_function'] : null)

      . (isset($this->_sql['insert'])  ? $this->_sql['insert'] : null)
      . (isset($this->_sql['update'])  ? $this->_sql['update'] : null)
      . (isset($this->_sql['delete'])  ? $this->_sql['delete'] : null)
      . (isset($this->_sql['count'])   ? $this->_sql['count']  : null)

      . (isset($this->_sql['where'])   ? ' ' . $this->_sql['where']   : null)
      . (isset($this->_sql['orderby']) ? ' ' . $this->_sql['orderby'] : null)
      . (isset($this->_sql['limit'])   ? ' ' . $this->_sql['limit']   : null)
    ;

    $St = QuarkORMEngine::query($sql, $this->_arguments, $ORMInfo->connection);

    /*
     * El resultado varia dependiendo de la configuración del query builder.
     */

    if( is_object($this->_ObjFetchTarget) ){
      /*
       * Resultado extraido sobre un objeto definido con fetchInto()
       */
      $St->setFetchMode(PDO::FETCH_INTO, $this->_ObjFetchTarget);
      $result = $St->fetch();
    } elseif( isset($this->_sql['find']) ){
      /*
       * Resultados como nuevas instancias del ORM actual
       */
      $St->setFetchMode(PDO::FETCH_CLASS, $this->_orm_class_name);

      if($this->_just_one) {
        $result = $St->fetch();
      } else {
        $result = $St->fetchAll();
      }
    } elseif( isset($this->_sql['select']) ){
      /*
       * Resultados como objetos anonimos
       */
      if($this->_just_one) {
        $result = $St->fetch(PDO::FETCH_OBJ);
      } else {
        $result = $St->fetchAll(PDO::FETCH_OBJ);
      }
    } elseif( isset($this->_sql['select_function']) ){
      /*
       * Select de funciones como SUM, MAX, etc.
       */
      $result = $St->fetch(PDO::FETCH_OBJ);
    } elseif( isset($this->_sql['count']) ) {
      /*
       * Resultado de un SELECT COUNT(*)
       */
      $result = $St->fetchColumn(0);
    } else {
      /*
       * Resultado con el numero de filas afectadas, esto puede ser para
       * update, insert o delete.
       */
      $result = $St->rowCount();
    }

    // Limpiar los datos del query builder, para no interferir en futuras consultas
    $this->_sql       = array();
    $this->_arguments = array();
    $this->_just_one  = false;
    $this->_ObjFetchTarget = null;

    return $result;
  }

  /**
   * Alias de exec()
   * @return mixed
   */
  public function puff()
  {
    return $this->exec();
  }

  /**
   * Agrega apostrofes a los campos $fields.
   * $fields puede ser un string con campos separados por comas o un array de
   * campos. Devuelve un array con los campos con apostrofes
   *
   * @static
   * @access public
   * @param mixed $fields
   * @return array(string)
   */
  protected function quoteFields($fields)
  {
    // Convertir cadena en array de campos
    if( is_string($fields) ){
      $fields = explode(',', $fields);
    }
    $fields = array_map('trim', $fields);

    return array_map(create_function('$e', 'return "`$e`";'), $fields);
  }

  /**
   * Agrega partes de una sentencia SQL al query builder, con su lista de argumentos
   * opcional.
   * 
   * @param  string $sql
   * @param  array $arguments
   */
  protected function appendSQL($tag, $sql, $arguments = null)
  {
    $this->_sql[$tag] = $sql;

    if( is_array($arguments) ) {
      $this->_arguments = array_merge($this->_arguments, $arguments);
    }
  }
}
