<?php
final class QuarkDBUtils
{
  /**
   * Informacion de las columnas de las tablas
   * @see getColumnInfo()
   * @var array
   */
  private static $columns_info = array();

  /**
   * Contador para generar identificadores de placeholders
   * @see getPlaceholderId()
   * @var int
   */
  private static $placeholder_id = 0;

  /**
   * Coleccion de objetos PDO, uno por cada conección
   * 
   * @var array(PDO)
   */
  private static $pdo_objects = array();

  // Tipos de columna
  const TYPE_NUMBER      = 'a';
  const TYPE_INTEGER     = 'b';
  const TYPE_TINY_INT    = 'c';
  const TYPE_SMALL_INT   = 'd';
  const TYPE_MEDIUM_INT  = 'e';
  const TYPE_INT         = 'f';
  const TYPE_BIG_INT     = 'g';
  const TYPE_DECIMAL     = 'h';
  const TYPE_FLOAT       = 'i';
  const TYPE_DOUBLE      = 'j';
  const TYPE_STRING      = 'k';
  const TYPE_CHAR        = 'l';
  const TYPE_VARCHAR     = 'm';
  const TYPE_TINY_TEXT   = 'n';
  const TYPE_TEXT        = 'o';
  const TYPE_MEDIUM_TEXT = 'p';
  const TYPE_LONG_TEXT   = 'q';
  const TYPE_ENUM        = 'r';
  const TYPE_SET         = 's';
  const TYPE_DATE        = 't';
  const TYPE_DATE_TIME   = 'u';
  const TYPE_TIME        = 'v';
  const TYPE_YEAR        = 'w';
  const TYPE_TIMESTAMP   = 'x';

  /**
   * Devuelve una instancia de PDO configurado para la conección $connection definida
   * en el archivo config.php.
   * Si no existe la conección solicitada, es creada.
   * 
   * @param string $connection Nombre de conección
   * @return PDO
   */
  public static function getPDO($connection)
  {
    if (!isset(self::$pdo_objects[$connection])) {

      $db_config = Quark::getDBConfig();

      if (!isset($db_config[$connection])) {
        throw new QuarkDBException(
          'Connection "'.$connection.'" not defined in config file.',
          QuarkDBException::ERROR_UNDEFINED_CONNECTION
        );
      } else {
        // Crear la instancia PDO
        try {
          $PDO = new PDO('mysql:'
              .'host='.Quark::getDBConfig($connection, 'host')
              .';dbname='.Quark::getDBConfig($connection, 'database'),
            Quark::getDBConfig($connection, 'user'),
            Quark::getDBConfig($connection, 'password'),
            array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
          );
        } catch (PDOException $e) {
          throw new QuarkDBException(
            'Fail to create connection "'.$connection.'".',
            QuarkDBException::ERROR_CONNECTION,
            $e
          );
        }

        try {
          $PDOSt = $PDO->prepare('SET NAMES :names;');
          $PDOSt->bindValue(':names',
            Quark::getDBConfig($connection, 'charset'),
            PDO::PARAM_STR
          );
          $PDOSt->execute();
        } catch (PDOException $e) {
          throw new QuarkDBException(
            'Fail to SET NAMES',
            QuarkDBException::ERROR_SET_NAMES,
            $e
          );
        }

        self::$pdo_objects[$connection] = $PDO;
      }
    }

    return self::$pdo_objects[$connection];
  }

  /**
   * Devuelve el posible valor para la columna $column de la tabla $class::TABLE
   * en base a su valor actual $value.
   * 
   * @param string $column Nombre de columna
   * @param string $class Nombre de clase
   * @param mixed $value Valor
   * @return mixed Posible valor
   */
  public static function getPossibleValue($column, $class, $value)
  {
    $ColumnInfo = self::getColumnInfo($column, $class);

    if ($value !== null) {
      /* Cuando el tipo de columna es date, datetime, time o year y el valor
       * del campo es un número entero, se asume que se utiliza un UNIX timestamp
       * y se hace la conversión con date() para obtener el valor correcto */
      if (is_int($value)) {
        if (self::isType(self::TYPE_DATE, $ColumnInfo)) {
          $value = date('Y-m-d', $value);
        } elseif (self::isType(self::TYPE_DATE_TIME, $ColumnInfo)) {
          $value = date('Y-m-d H:i:s', $value);
        } elseif (self::isType(self::TYPE_TIME, $ColumnInfo)) {
          $value = date('H:i:s', $value);
        } elseif (self::isType(self::TYPE_YEAR, $ColumnInfo)) {
          $value = date('Y', $value);
        }
      }
    } else {
      
      // Cuando el valor del campo es php null
      
      /* Cuando el campo de la tabla NO acepta nulos agregamos su valor default
       * El valor puede continuar siendo php null cuando:
       *
       * a) Sea un campo AUTO_INCREMENT
       * b) Sea un campo timestamp con default "CURRENT_TIMESTAMP" y/o
       *    extra "on update CURRENT_TIMESTAMP"
       */
      if ($ColumnInfo->Null == 'NO'
        && (
            $ColumnInfo->Extra != 'auto_increment'
            /**
             * TODO:
             * Verificar si se puede usar isType(TYPE_TIMESTAMP)
             */
            && $ColumnInfo->Extra != 'on update CURRENT_TIMESTAMP'
            && $ColumnInfo->Default != 'CURRENT_TIMESTAMP'
           )
      ) {
        // Asignar el valor por default
        if ($ColumnInfo->Default != null) {
          $value = $ColumnInfo->Default;
        } elseif (self::isType(self::TYPE_NUMBER, $ColumnInfo)) {
          $value = 0;
        } elseif (self::isType(self::TYPE_STRING, $ColumnInfo)) {
          $value = '';
        } elseif (self::isType(self::TYPE_SET, $ColumnInfo)) {
          $value = '';
        } elseif (self::isType(self::TYPE_YEAR, $ColumnInfo)) {
          $value = '0000';
        } elseif (self::isType(self::TYPE_TIME, $ColumnInfo)) {
          $value = '00:00:00';
        } elseif (self::isType(self::TYPE_DATE, $ColumnInfo)) {
          $value = '0000-00-00';
        } elseif (self::isType(self::TYPE_DATE_TIME, $ColumnInfo)) {
          $value = '0000-00-00 00:00:00';
        } elseif (self::isType(self::TYPE_ENUM, $ColumnInfo)) {
          $tmp = explode('(', $ColumnInfo->Type);
          $tmp = explode(',', $tmp[1]);
          $value = str_replace("'", '', $tmp[0]);
        }
      }
    }

    return $value;
  }

  public static function getColumnInfo($column, $class)
  {
    foreach (self::getColumnsInfo($class) as $ColumnInfo) {
      if ($ColumnInfo->Field == $column) {
        return $ColumnInfo;
      }
    }
    trigger_error(__METHOD__.'(): Column "'.$class.'.'.$column.'" not found.', E_USER_ERROR);
  }

  /**
   * Verifica el tipo de una columna
   * 
   * @return bool Devuelte true si el tipo coincide, false de lo contrario.
   */
  public static function isType($type, $ColumnInfo)
  {
    $pattern = '';
    switch ($type) {
      case self::TYPE_NUMBER:
        $pattern = '/^(tinyint|smallint|mediumint|int|bigint|decimal|float|double)\b/';
        break;
      case self::TYPE_INTEGER:
        $pattern = '/^(tinyint|smallint|mediumint|int|bigint)\b/';
        break;
      case self::TYPE_TINY_INT:
        $pattern = '/^tinyint\b/';
        break;
      case self::TYPE_SMALL_INT:
        $pattern = '/^smallint\b/';
        break;
      case self::TYPE_MEDIUM_INT:
        $pattern = '/^mediumint\b/';
        break;
      case self::TYPE_INT:
        $pattern = '/^int\b/';
        break;
      case self::TYPE_BIG_INT:
        $pattern = '/^bigint\b/';
        break;
      case self::TYPE_DECIMAL:
        $pattern = '/^decimal\b/';
        break;
      case self::TYPE_FLOAT:
        $pattern = '/^float\b/';
        break;
      case self::TYPE_DOUBLE:
        $pattern = '/^double\b/';
        break;
      case self::TYPE_STRING:
        $pattern = '/^(char|varchar|tinytext|text|mediumtext|longtext)\b/';
        break;
      case self::TYPE_CHAR:
        $pattern = '/^char\b/';
        break;
      case self::TYPE_VARCHAR:
        $pattern = '/^varchar\b/';
        break;
      case self::TYPE_TINY_TEXT:
        $pattern = '/^tinytext\b/';
        break;
      case self::TYPE_TEXT:
        $pattern = '/^text\b/';
        break;
      case self::TYPE_MEDIUM_TEXT:
        $pattern = '/^mediumtext\b/';
        break;
      case self::TYPE_LONG_TEXT:
        $pattern = '/^longtext\b/';
        break;
      case self::TYPE_ENUM:
        $pattern = '/^enum\b/';
        break;
      case self::TYPE_SET:
        $pattern = '/^set\b/';
        break;
      case self::TYPE_DATE:
        $pattern = '/^date\b/';
        break;
      case self::TYPE_DATE_TIME:
        $pattern = '/^datetime\b/';
        break;
      case self::TYPE_TIME:
        $pattern = '/^time\b/';
        break;
      case self::TYPE_YEAR:
        $pattern = '/^year\b/';
        break;
      case self::TYPE_TIMESTAMP:
        $pattern = '/^timestamp\b/';
        break;
    }

    return (preg_match($pattern, $ColumnInfo->Type) == 1);
  }

  /**
   * Devuelve la información de las columnas en la tabla enlazada a $class
   * 
   * @param string $class
   * @return array(object)
   */
  public static function getColumnsInfo($class)
  {
    if (!isset(self::$columns_info[$class])) {
      $PDO = self::getPDO($class::CONNECTION);
      $PDOSt = $PDO->query('SHOW COLUMNS FROM `'.$class::TABLE.'`;');
      self::$columns_info[$class] = $PDOSt->fetchAll(PDO::FETCH_OBJ);
    }
    return self::$columns_info[$class];
  }

  /**
   * Devuelve un array con los nombres de las columnas en la tabla enlazada a $class
   * 
   * @param string $class
   * @return array(string)
   */
  public static function getColumns($class)
  {
    $columns = array();
    foreach (self::getColumnsInfo($class) as $ColumnInfo) {
      $columns[] = $ColumnInfo->Field;
    }
    return $columns;
  }

  /**
   * Devuelve un array con los nombres de columnas que forman el primary key
   * de la tabla enlazada a $class
   * 
   * @param string $class
   * @return array(string)
   */
  public static function getPrimaryKey($class)
  {
    $columns = array();
    foreach (self::getColumnsInfo($class) as $ColumnInfo) {
      if ($ColumnInfo->Key == 'PRI') {
        $columns[] = $ColumnInfo->Field;
      }
    }
    return $columns;
  }

  /**
   * Genera una cadena de condición y sus parametros, a partir de una lista de
   * columnas $columns, usando el operador logico $logic_op para unir las columnas.
   * Utiliza $class para el scope de los nombres de columnas.
   * La condición y parametros generados están listos para ser procesados con
   * el metodo buildCondition()
   * 
   * @param array $columns Array asociativo de columnas
   * @param string $logic_op Operador logico
   * @param string $class Nombre de clase para el scope
   * @param string &$condition_out La cadena de condición generada
   * @param array &$params_out El array de parametros generados
   */
  public static function prepareCondition(
    $columns,
    $logic_op,
    $class,
    &$condition_out,
    &$params_out
  ) {
    
    $assignments = array();
    $params_out  = array();

    foreach ($columns as $column => $value) {
      self::buildColumnScope($column, $class, $column_out, $class_out);
      $column_scoped = $class_out.'.'.$column_out;
      
      /* Agregar el scope de clase al placeholder, ya que pueden existir columnas
       * del mismo nombre pero con diferentes scopes, por ejemplo:
       * array('id' => 1, 'Product.id' => 2) */
      $placeholder   = ':'.$class_out.'_'.$column_out;

      /* Creamos la asignación, si $value es php null lo mapeamos a SQL NULL y lo
       * omitimos de la lista de parametros */
      if ($value !== null) {
        $assignments[]            = $column_scoped.'='.$placeholder;
        $params_out[$placeholder] = $value;
      } else {
        $assignments[] = $column_scoped.' IS NULL';
      }
    }

    $condition_out = implode(' '.$logic_op.' ', $assignments);
  }

  /**
   * Convierte una cadena de condición no SQL a SQL, además agrega los ID a los
   * placeholders.
   * 
   * @param string &$condition Condición
   * @param array &$params Parametros
   */
  public static function buildCondition(&$condition, &$params)
  {
    // Generar los nombres de columnas en formato SQL
    preg_match_all('/\w+\.\w+/', $condition, $matches);
    foreach ($matches[0] as $column) {
      self::buildColumnScope($column, null, $column_out, $class_out);
      $column_sql = self::buildColumnSQL($column_out, $class_out);
      $condition = str_replace($column, $column_sql, $condition);
    }

    // Agregar ID a los placeholders
    if (count($params) > 0) {
      $placeholder_id = self::getPlaceholderId();
      $params_out = array();
      foreach ($params as $placeholder => $value) {
        $new_placeholder = str_replace(':', ':'.$placeholder_id.'_', $placeholder);
        $condition = str_replace($placeholder, $new_placeholder, $condition);
        $params_out[$new_placeholder] = $value;
      }
      $params = $params_out;
    }
  }

  /**
   * Genera el scope para una columna, separando su nombre de columna y clase
   * 
   * @param string $column Nombre de columna de entrada
   * @param string $class Nombre de clase de entrada
   * @param string &$column_out Nombre de columna de salida
   * @param string &$class_out Nombre de clase de salida
   */
  public static function buildColumnScope($column, $class, &$column_out, &$class_out)
  {
    if (strpos($column, '.') === false) {
      $column_out = $column;
      $class_out  = $class;
    } else {
      $c          = explode('.', $column);
      $column     = array_map('trim', $c);
      $class_out  = $c[0];
      $column_out = $c[1];
    }
  }

  /**
   * Devuelve un string con todas las columnas a seleccionar concatenadas por comas,
   * con su respecitvo alias "AS", se le agrega el scope $class a las columnas sin
   * scope.
   * 
   * @param array $columnas Lista de nombres de columnas o instancias QuarkSQLExpression
   * @param string $class Class para el scope de tabla
   * @return string
   */
  public static function buildSelectColumns($columns, $class)
  {
    // Agregar lista de columnas que se van a seleccionar
    $select_columns = array();
    foreach ($columns as $column) {
      if ($column instanceof QuarkSQLExpression) {
        // La columna es una expresion SQL
        // Arreglar el scope del alias
        $alias     = $column->getAlias();
        $class_out = '';
        QuarkDBUtils::buildColumnScope($alias, $class, $alias, $class_out);
        $select_columns[] = $column->getExpression()
          .' AS `'.$class_out::TABLE.'_'.$alias.'`';
      } else {
        // La columna es un nombre de columna normal
        $select_columns[] = QuarkDBUtils::buildColumnSQL(
          $column['column'], $column['class']
        ).' AS `'.$column['class']::TABLE.'_'.$column['column'].'`';
      }
    }
    return implode(',', $select_columns);
  }

  /**
   * Devuelve un array de nombres de columnas especificados en un string de columnas
   * separadas por coma $columns
   * 
   * @param string $columns
   * @return array(string)
   */
  public static function splitColumns($columns)
  {
    $columns = explode(',', $columns);
    $columns = array_map('trim', $columns);
    return $columns;
  }

  /**
   * Devuelve un nombre de columna listo para usar en una sentencia SQL
   * 
   * @param string $column Nombre de columna
   * @param string $class Nombre de clase para el scope
   */
  public static function buildColumnSQL($column, $class)
  {
    return '`'.$class::TABLE.'`.`'.$column.'`';
  }

  /**
   * Devuelve un identificador nuevo para placeholders
   * 
   * @return string
   */
  public static function getPlaceholderId()
  {
    return self::$placeholder_id++;
  }
}
