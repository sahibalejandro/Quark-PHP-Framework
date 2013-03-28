<?php
final class QuarkDBQuery
{
  /**
   * Nombre de la clase QuarkDBObject
   * @var string
   */
  private $class;

  /**
   * Tipo de consulta
   * @see Constantes QUERY_TYPE_*
   * @var int
   */
  private $query_type; 

  /**
   * Tipo de resultado
   * @see Constantes FETCH_TYPE_*
   * @var int
   */
  private $fetch_type;

  /**
   * Define si los resultados serán arrays o instancias de QuarkDBObject
   * @var bool
   */
  private $results_as_array;

  /**
   * Lista de columnas para SELECT
   * @var array
   */
  private $select_columns;

  /**
   * Lista de columnas para INSERT
   * @var array
   */
  private $insert_columns;

  /**
   * Lista de columnas para UPDATE
   * @var array
   */
  private $update_columns;

  /**
   * Lista de joins
   * @var array
   */
  private $joins;

  /**
   * Lista de condiciones WHERE
   * @var array
   */
  private $where;

  /**
   * Lista de condiciones para el ORDER BY
   * @var array
   */
  private $order;

  /**
   * Limite de la consulta
   * @var array(offset, limit)
   */
  private $limit;

  /**
   * Lista de parametros que serán enviados a la consulta
   * @var array
   */
  private $params;

  /**
   * Operador logico AND
   * @var string
   */
  const LOGIC_OP_AND = 'AND';

  /**
   * Operador logico OR
   * @var string
   */
  const LOGIC_OP_OR  = 'OR';

  /**
   * Tipo INNER JOIN
   * @var string
   */
  const JOIN_TYPE_INNER = 'INNER';

  /**
   * Tipo LEFT JOIN
   * @var string
   */
  const JOIN_TYPE_LEFT = 'LEFT';

  /**
   * Tipo RIGHT JOIN
   * @var string
   */
  const JOIN_TYPE_RIGHT = 'RIGHT';

  /**
   * Tipo de consulta SELECT
   * @var int
   */
  const QUERY_TYPE_SELECT = 1;

  /**
   * Tipo de consulta INSERT
   * @var int
   */
  const QUERY_TYPE_INSERT = 2;

  /**
   * Tipo de consulta UPDATE
   * @var int
   */
  const QUERY_TYPE_UPDATE = 4;

  /**
   * Tipo de consulta DELETE
   * @var int
   */
  const QUERY_TYPE_DELETE = 8;

  /**
   * Tipo de consulta SELECT COUNT(*)
   * @var int
   */
  const QUERY_TYPE_COUNT = 16;

  /**
   * Tipo de consulta SELECT MAX(...) o SELECT MIN(...)
   * @var int
   */
  const QUERY_TYPE_MIN_MAX = 32;

  /**
   * Tipo de extracción de resultados: Un solo objeto o null si no existe.
   * @var int
   */
  const FETCH_TYPE_SINGLE = 1;

  /**
   * Tipo de extraccion de resultados: un array
   * @var int
   */
  const FETCH_TYPE_MANY = 2;

  public function __construct($class)
  {
    $this->class = $class;
    $this->clear();
  }

  /**
   * Reinicia la configuración del objeto a configuración nueva.
   * 
   * @return QuarkDBQuery
   */
  public function clear()
  {
    $this->query_type       = 0; 
    $this->fetch_type       = 0;
    $this->results_as_array = false;
    $this->select_columns   = array();
    $this->insert_columns   = array();
    $this->update_columns   = array();
    $this->joins            = array();
    $this->where            = array();
    $this->order            = array();
    $this->limit            = array();
    $this->params           = array();
    return $this;
  }

  /**
   * Prepara la consulta para realizar un SELECT
   * 
   * @param mixed $columns Array de columnas o string de columnas separadas por coma
   * @param int $fetch_type Tipo de fetch para el resultado
   * @return QuarkDBQuery
   */
  public function select($columns = null, $fetch_type = self::FETCH_TYPE_MANY)
  {
    $this->query_type = self::QUERY_TYPE_SELECT;
    $this->fetch_type = $fetch_type;
    $this->addSelectColumns($columns, $this->class);
    return $this;
  }

  /**
   * Prepara la consulta para realizar un SELECT de un unico elemento
   * 
   * @param mixed $columns Array de columnas o string de columnas separadas por coma
   * @return QuarkDBQuery
   */
  public function selectOne($columns = null)
  {
    return $this->select($columns, self::FETCH_TYPE_SINGLE)->limit(1);
  }

  /**
   * Devuelve una instancia de QuarkDBObject que cumple con la condicion del
   * primary key $pk (columnas de primary key), si no se encuentra el registro
   * devuelve null.
   * 
   * @param array $pk
   * @return QuarkDBObject|null
   */
  public function selectByPk($pk)
  {
    return $this->selectOne()->where($pk)->exec();
  }

  /**
   * Devuelve una instancia de QuarkBDObject donde el campo "id" tenga el valor $id
   * Si el registro no se encuentra devuelve null
   * 
   * @param mixed $id ID del registro
   * @return QuarkDBObject|null
   */
  public function selectById($id)
  {
    return $this->selectByPk(array('id' => $id));
  }

  /**
   * Agrega un JOIN a la consulta
   * 
   * @param string $class Nombre de clase B
   * @param mixed $columns Nombres de columnas a seleccionar (array o string)
   * @param mixed $condition Condicion del join (igual que en where())
   * @param array $params Parametros de condición (igual que en where())
   * @param string $type Tipo de join (INNER, LEFT o RIGHT)
   * @return QuarkDBQuery
   * @throws QuarkDBException
   */
  public function join(
    $class,
    $columns   = null,
    $condition = null,
    $params    = array(),
    $type      = self::JOIN_TYPE_INNER
  ) {
    // Generar los nombres de clase A y B
    $class_a = $class_b = '';
    $classes = explode('.', $class);
    $classes = array_map('trim', $classes);

    switch(count($classes)) {
      case 1:
        $class_a = $this->class;
        $class_b = $classes[0];
        break;
      case 2:
        list($class_a, $class_b) = $classes;
        break;
      default:
        throw new QuarkDBException(
          __METHOD__.'() Class name only can have one or two class names',
          QuarkDBException::ERROR_BAD_CLASS_NAME
        );
        break;
    }

    // Agregar las columnas de la clase B a la lista de columnas de seleccion
    $this->addSelectColumns($columns, $class_b);

    /* Generar condicion automatica si no esta definida, usando los campos relacionados
     * en las dos tablas */
    if ($condition === null) {
      $conditions = array();
      foreach (QuarkDBUtils::getPrimaryKey($class_b) as $pk) {
        $conditions[] = $class_b.'.'.$pk.'='.$class_a.'.'.$class_b::TABLE.'_'.$pk;
      }
      $condition = implode(' '.self::LOGIC_OP_AND.' ', $conditions);
    }

    // Generar condición a partir de un array si es necesario
    if (is_array($condition)) {
      QuarkDBUtils::prepareCondition(
        $condition,
        self::LOGIC_OP_AND,
        $class_b,
        $condition,
        $params
      );
    }

    // Generar la condicion SQL corecta y sus parametros
    QuarkDBUtils::buildCondition($condition, $params);
    $this->addParams($params);

    $this->joins[] = array(
      'class_a'   => $class_a, // Necesaria para buildResultRow()
      'class_b'   => $class_b,
      'condition' => $condition,
      'type'      => $type,
    );

    return $this;
  }

  /**
   * Alias de join() pero genera un LEFT JOIN
   * 
   * @param string $class Nombre de clase B
   * @param mixed $columns Nombres de columnas a seleccionar (array o string)
   * @param mixed $condition Condicion del join (igual que en where())
   * @param array $params Parametros de condición (igual que en where())
   * @return QuarkDBQuery
   */
  public function leftJoin($class, $columns = null, $condition = null, $params = array())
  {
    return $this->join($class, $columns, $condition, $params, self::JOIN_TYPE_LEFT);
  }

  /**
   * Alias de join() pero genera un RIGHT JOIN
   * 
   * @param string $class Nombre de clase B
   * @param mixed $columns Nombres de columnas a seleccionar (array o string)
   * @param mixed $condition Condicion del join (igual que en where())
   * @param array $params Parametros de condición (igual que en where())
   * @return QuarkDBQuery
   */
  public function rightJoin($class, $columns = null, $condition = null, $params = array())
  {
    return $this->join($class, $columns, $condition, $params, self::JOIN_TYPE_RIGHT);
  }

  /**
   * Agrega una condición al WHERE, usando $logic_op_condition para unir las columnas
   * y $logic_op_where para unir toda la condicion con las demás condiciones.
   * 
   * @param mixed $condition Condicion, como string o array de columnas
   * @param array $params Parametros de condicion cuando $condition es string
   * @param string $logic_op_condition Operador logico para unir columnas cuando
   *                                   $condition es array
   * @param string $logic_op_where Operador logico para unir esta condicion
   *                               con las definidas anteriorimente.
   * @return QuarkDBQuery
   */
  public function where(
    $condition,
    $params = array(),
    $logic_op_condition = self::LOGIC_OP_AND,
    $logic_op_where = self::LOGIC_OP_AND
  ) {

    // Generar cadena de condicion y parametros a partir de un array
    if (is_array($condition)) {
      QuarkDBUtils::prepareCondition(
        $condition,
        $logic_op_condition,
        $this->class,
        $condition,
        $params
      );
    }

    // Generar la condicion SQL y ajustar los placeholders
    QuarkDBUtils::buildCondition($condition, $params);

    // Agregar parametros
    $this->addParams($params);

    // Agregar la condicion a la lista
    $this->where[] = array(
      'condition' => $condition,
      'logic_op'  => $logic_op_where,
    );

    return $this;
  }

  /**
   * Agrega una condición al WHERE, usando AND para unir las columnas y OR para unir
   * toda la condicion con las demás condiciones.
   * 
   * @param mixed $condition Condicion, como string o array de columnas
   * @param array $params Parametros de condicion cuando $condition es string
   * @return QuarkDBQuery
   */
  public function orWhere($condition, $params = array())
  {
    return $this->where($condition, $params, self::LOGIC_OP_AND, self::LOGIC_OP_OR);
  }

  /**
   * Agrega una condición al WHERE, usando OR para unir las columnas y AND para unir
   * toda la condicion con las demás condiciones.
   * 
   * @param mixed $condition Condicion, como string o array de columnas
   * @param array $params Parametros de condicion cuando $condition es string
   * @return QuarkDBQuery
   */
  public function whereOr($condition, $params = array())
  {
    return $this->where($condition, $params, self::LOGIC_OP_OR, self::LOGIC_OP_AND);
  }

  /**
   * Agrega una condición al WHERE, usando OR para unir las columnas y OR para unir
   * toda la condicion con las demás condiciones.
   * 
   * @param mixed $condition Condicion, como string o array de columnas
   * @param array $params Parametros de condicion cuando $condition es string
   * @return QuarkDBQuery
   */
  public function orWhereOr($condition, $params = array())
  {
    return $this->where($condition, $params, self::LOGIC_OP_OR, self::LOGIC_OP_OR);
  }

  /**
   * Agrega una condición al ORDER BY
   * 
   * @param mixed $columns Array de columnas o string de columnas separadas por coma.
   * @param string $type Tipo de orden (ASC, DESC, RAND)
   */
  public function orderBy($columns, $type = 'asc')
  {
    // Generar array de columnas a partir del string
    if (is_string($columns)) {
      $columns = QuarkDBUtils::splitColumns($columns);
    }

    // Obtener los scope de las columnas para generar su forma SQL
    $order_columns = array();
    foreach ($columns as $column) {
      QuarkDBUtils::buildColumnScope($column, $this->class, $column, $class);
      $order_columns[] = QuarkDBUtils::buildColumnSQL($column, $class);
    }

    // Agregar el order a la lista de orders
    $this->order[] = implode(',', $order_columns).' '.strtoupper($type);

    return $this;
  }

  /**
   * Define el limite de la consulta
   * 
   * @param int $offset
   * @param int $limit Si no es definido se usa $offset como limit
   * @return QuarkDBQuery
   */
  public function limit($offset, $limit = null)
  {
    $this->limit = array('offset' => $offset, 'limit'=> $limit);
    return $this;
  }

  /**
   * Prepara la consulta para realizar un INSERT sobre la tabla
   * 
   * @param string $columns Columnas a insertar
   * @return QuarkDBQuery
   */
  public function insert($columns)
  {
    $params           = array();
    $this->query_type = self::QUERY_TYPE_INSERT;

    // Generar columnas para la sentencia INSERT
    foreach ($columns as $column => $value) {

      // Agregar la columna a las columnas para INSERT
      $class = '';
      QuarkDBUtils::buildColumnScope($column, $this->class, $column, $class);
      $this->insert_columns[] = QuarkDBUtils::buildColumnSQL($column, $class);

      // Obtener el posible valor adecuado para esta columna
      $value = QuarkDBUtils::getPossibleValue($column, $class, $value);

      // Agregar el parametro a la lista de parametros
      if ($value === null) {
        $placeholder = 'NULL';
      } elseif ($value instanceof QuarkSQLExpression) {
        $placeholder = $value->getExpression();
        $params      = array_merge($params, $value->getParams());
      } else {
        $placeholder          = ':'.QuarkDBUtils::getPlaceholderId();
        $params[$placeholder] = $value;
      }

      // Agregar el placeholder para esta columna a la lista de placeholders de insert
      $this->insert_placeholders[] = $placeholder;
    }

    $this->addParams($params);

    return $this;
  }

  /**
   * Prepara la consulta para hacer UPDATE
   * 
   * @param array $columns Columnas para actualizar
   * @return QuarkDBQuery
   */
  public function update($columns)
  {
    $params           = array();
    $this->query_type = self::QUERY_TYPE_UPDATE;

    foreach ($columns as $column => $value) {
      // Obtener el nombre SQL de la columna
      $class = '';
      QuarkDBUtils::buildColumnScope($column, $this->class, $column, $class);
      $sql_column = QuarkDBUtils::buildColumnSQL($column, $class); 

      // Obtener el posible valor para la columna
      $value = QuarkDBUtils::getPossibleValue($column, $class, $value);

      // Crear la sentencia SQL de asignación para la columna
      if ($value === null) {
        $this->update_columns[] = $sql_column.'=NULL';
      } else {
        if ($value instanceof QuarkSQLExpression) {
          $placeholder = $value->getExpression();
          $params      = array_merge($params, $value->getParams());
        } else {
          $placeholder = ':'.QuarkDBUtils::getPlaceholderId();
          $params[$placeholder] = $value;
        }

        $this->update_columns[] = $sql_column.'='.$placeholder;
      }
    }
    $this->addParams($params);
    return $this;
  }

  /**
   * Prepara la consulta para realizar un DELETE
   * 
   * @return QuarkDBQuery
   */
  public function delete()
  {
    $this->query_type = self::QUERY_TYPE_DELETE;
    return $this;
  }

  /**
   * Prepara la consulta para realizar un SELECT COUNT(*)
   * 
   * @return QuarkDBQuery
   */
  public function count()
  {
    $this->query_type = self::QUERY_TYPE_COUNT;
    $this->fetch_type = self::FETCH_TYPE_SINGLE;

    $this->addSelectColumns(array(
      new QuarkSQLExpression('COUNT(*)', null, 'select_count')
    ), $this->class);
    return $this->asArray();
  }

  /**
   * Prepara la consulta para realizar un MAX() o MIN() sobre las columnas deseadas.
   * 
   * @param mixed $columns Nombre de columas separadas por coma o array de columnas
   * @return QuarkDBQuery
   */
  private function maxMin($columns, $func)
  {
    $this->query_type = self::QUERY_TYPE_MIN_MAX;
    $this->fetch_type = self::FETCH_TYPE_SINGLE;

    if (is_string($columns)) {
      $columns = QuarkDBUtils::splitColumns($columns);
    }

    $select_columns = array();
    foreach ($columns as $column) {
      QuarkDBUtils::buildColumnScope($column, $this->class, $column, $class_scope);
      $select_columns[] = new QuarkSQLExpression(
        $func.'('.QuarkDBUtils::buildColumnSQL($column, $class_scope).')',
        null,
        $column
      );
    }
    $this->addSelectColumns($select_columns, $this->class);
    return $this->asArray();
  }

  public function max($columns)
  {
    return $this->maxMin($columns, 'MAX');
  }

  public function min($columns)
  {
    return $this->maxMin($columns, 'MIN');
  }

  /**
   * Devuelve la ultima fila insertada en la tabla, como un array asociativo.
   * Solo funciona si la tabla tiene por lo menos una columna AUTO_INCREMENT o
   * TIMESTAMP, devuelve null si no se puede obtener la ultima fila o no es
   * encontrada.
   * 
   * @return array
   */
  public function getLastRow()
  {
    // Buscar la columna usable para ordenar
    $usable_column = null;
    foreach(QuarkDBUtils::getColumnsInfo($this->class) as $ColumnInfo) {
      if (strtoupper($ColumnInfo->Extra) == 'AUTO_INCREMENT'
        || QuarkDBUtils::isType(QuarkDBUtils::TYPE_TIMESTAMP, $ColumnInfo)
      ) {
        $usable_column = $ColumnInfo->Field;
        break;
      }
    }

    if ($usable_column == null) {
      // No se puede buscar la ultima fila
      return null;
    } else {
      return $this->selectOne()->asArray()->orderBy($usable_column, 'DESC')->exec();
    }
  }

  /**
   * Alias de getLastRow() pero devuelve una instancia QuarkDBObject
   * 
   * @return QuarkDBObject
   */
  public function getLastObject()
  {
    $row = $this->getLastRow();
    if ($row == null) {
      return null;
    } else {
      $QuarkDBObject = new $this->class();
      $QuarkDBObject->fillFromArray($row);
      return $QuarkDBObject;
    }
  }

  /**
   * Agrega columnas a la lista de columnas de seleccion
   * 
   * @param mixed $columns Array de nombres de columnas o string (no sql)
   * @param string $class Nombre de clase para las columnas que no tienen scope
   */
  private function addSelectColumns($columns, $class)
  {
    // Se seleccionan todas las columnas si no se especifica
    if ($columns === null) {
      $columns = QuarkDBUtils::getColumns($class);
    }
    
    /* Las columnas pueden estar en un string separadas por coma, hay que generar
     * un array a partir de ese string */
    if (is_string($columns)) {
      $columns = QuarkDBUtils::splitColumns($columns);
    }

    foreach ($columns as $key => $column) {
      if ($column instanceof QuarkSQLExpression) {
        $this->select_columns[] = $column;
      } else {
        $class_out = '';
        QuarkDBUtils::buildColumnScope($column, $class, $column, $class_out);

        /* Necesitamos mantener separado 'column' y 'class' para poder generar el
         * alias de la columna (column AS column_alias) en el metodo getSQL() */
        $this->select_columns[] = array(
          'column' => $column,
          'class'  => $class_out,
        );
      }
    }
  }

  /**
   * Ejecuta la consulta generada
   * @return mixed
   * @throws QuarkDBException
   */
  public function exec()
  {
    $class = $this->class;
    try {
      // Preparar y ejecutar la consulta
      $PDO = QuarkDBUtils::getPDO($class::CONNECTION);
      $PDOSt = $PDO->prepare($this->getSQL());
      $PDOSt->execute($this->params);
      
      // Valor de retorno
      $return = null;

      switch ($this->query_type) {
        case self::QUERY_TYPE_SELECT:
          // Extrar las filas como array asociativo
          $rows = $PDOSt->fetchAll(PDO::FETCH_ASSOC);

          // Separar las columnas para cada fila
          foreach ($rows as &$row) {
            $row = $this->buildResultRow($row, $this->class, $this->results_as_array);
          }

          // El resultado puede ser un solo objeto o un array de objetos
          switch($this->fetch_type) {
            case self::FETCH_TYPE_MANY:
              $return = $rows;
              break;
            case self::FETCH_TYPE_SINGLE:
              if(count($rows) == 0) {
                $return = null;
              } else {
                $return = $rows[0];
              }
              break;
            default:
              throw new QuarkDBException(
                __METHOD__.'() QuarkDBQuery Undefined fetch type?',
                QuarkDBException::ERROR_UNDEFINED_FETCH_TYPE
              );
              break;
          }

          break;
        case self::QUERY_TYPE_COUNT:
          // Solo devolver el numero devuelto por COUNT(*)
          $return = (int)$PDOSt->fetchColumn(0);
          break;
        case self::QUERY_TYPE_MIN_MAX:
          // Obtener la fila como un array asociativo
          $return = $PDOSt->fetch(PDO::FETCH_ASSOC);
          // Si la fila solo tiene un elemento, devolver solo ese elemento
          if (sizeof($return) == 1) {
            $return = array_values($return);
            $return = array_shift($return);
          } else {
            /* La fila tiene varios elementos, filtramos las columnas para remover
             * el prefijo de la tabla y devolvemos el array de columnas */
            $return = self::filterColumns($return, $this->class);
          }
          break;
        case self::QUERY_TYPE_INSERT:
        case self::QUERY_TYPE_UPDATE:
        case self::QUERY_TYPE_DELETE:
          $return = $PDOSt->rowCount();
          break;
      }

      // Limpiar la configuración para poder re-utilizar el objeto como nuevo.
      $this->clear();

      return $return;

    } catch (PDOException $e) {
      throw new QuarkDBException(
        __METHOD__.'() Fail to execute query.',
        QuarkDBException::ERROR_QUERY,
        $e
      );
    }
  }

  /**
   * Alias de exec()
   */
  public function truff()
  {
    return $this->exec();
  }

  /**
   * Infla los resultados
   */
  private function buildResultRow($columns, $class, $as_array)
  {
    $row = self::filterColumns($columns, $class);

    // Anidar las columnas de los JOIN
    foreach ($this->joins as $join) {
      if ($join['class_a'] == $class) {
        $row[$join['class_b']] = self::buildResultRow($columns, $join['class_b'], $as_array);
      }
    }

    // Crear la instancia de QuarkDBObject si es necesario
    if (!$as_array) {
      $QuarkDBObject = new $class();
      $QuarkDBObject->fillFromArray($row);
      $row = $QuarkDBObject;
    }

    return $row;
  }

  /**
   * Genera y devuelve el string SQL que será ejecutado por exec()
   * 
   * @return string
   * @throws QuarkDBException
   */
  public function getSQL()
  {
    // Generar SQL
    $sql = '';

    // Copiar nombre de clase para usar paamayim nekudotayim sin error de sintaxis.
    $class = $this->class;

    switch($this->query_type) {
      case self::QUERY_TYPE_SELECT:
      case self::QUERY_TYPE_COUNT:
      case self::QUERY_TYPE_MIN_MAX:
        // Generar SELECT FROM
        $sql = 'SELECT';
        $sql .= ' '.QuarkDBUtils::buildSelectColumns($this->select_columns, $class);
        $sql .= ' FROM `'.$class::TABLE.'`';

        // Generar JOIN
        if (count($this->joins) > 0) {
          foreach ($this->joins as $join) {
            $sql .= ' '.$join['type'].' JOIN `'.$join['class_b']::TABLE
              .'` ON ('.$join['condition'].')';
          }
        }
        break;
      case self::QUERY_TYPE_INSERT:
        $sql = 'INSERT INTO `'.$class::TABLE.'`('
          .implode(',', $this->insert_columns)
          .')VALUES('.implode(',', $this->insert_placeholders).')';
        break;
      case self::QUERY_TYPE_UPDATE:
        $sql = 'UPDATE `'.$class::TABLE.'` SET '.implode(',', $this->update_columns);
        break;
      case self::QUERY_TYPE_DELETE:
        $sql = 'DELETE FROM `'.$class::TABLE.'`';
        break;
        $sql = 'SELECT COUNT(*) FROM `'.$class::TABLE.'`';
        break;
      default:
        throw new QuarkDBException(
          __METHOD__.'() Query type not defined.',
          QuarkDBException::ERROR_NO_QUERY_TYPE
        );
        break;
    }

    // Generar WHERE
    if (count($this->where) > 0) {
      $sql .= ' WHERE';
      foreach ($this->where as $i => $where) {
        if ($i > 0) {
          $sql .= ' '.$where['logic_op'];
        }
        $sql .= ' ('.$where['condition'].')';
      }
    }

    // Generar ORDER
    if (count($this->order) > 0) {
      $sql .= ' ORDER BY '.implode(',', $this->order);
    }

    // Generar LIMIT
    if (count($this->limit) > 0) {
      $sql .= ' LIMIT '.$this->limit['offset'];
      if ($this->limit['limit'] != null) {
        $sql .= ','.$this->limit['limit'];
      }
    }

    return $sql.';';
  }

  /**
   * Agrega parametros para la consulta
   * 
   * @param array $params Array asociativo de parametros (placeholder=>value)
   */
  private function addParams($params)
  {
    $this->params = array_merge($this->params, $params);
  }

  private function filterColumns($columns, $class)
  {
    $table_prefix = $class::TABLE.'_';

    $row = array();
    foreach ($columns as $column => $value) {
      // Filtrar las columnas que pertenecen solo a la tabla de $class
      if (strpos($column, $table_prefix) === 0) {
        $column = str_replace($table_prefix, '', $column);
        $row[$column] = $value;
      }
    }
    
    return $row;
  }

  /**
   * Devuelve los parametros para la consulta
   * 
   * @return array
   */
  public function getParams()
  {
    return $this->params;
  }

  public function asArray()
  {
    $this->results_as_array = true;
    return $this;
  }
}
