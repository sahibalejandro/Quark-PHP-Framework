<?php
/**
 * QuarkPHP Framework
 * Copyright 2012-2013 Sahib Alejandro Jaramillo Leo
 *
 * @author Sahib J. Leo <sahib.alejandro@gmail.com>
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 * @link    http://quarkphp.com
 */

/**
 * Clase estatica con utilidades para el motor QuarkDB
 */
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
  const TYPE_NUMBER      = 1; // Cualquier número
  const TYPE_INTEGER     = 2; // Solo números enteros
  const TYPE_TINY_INT    = 3;
  const TYPE_SMALL_INT   = 4;
  const TYPE_MEDIUM_INT  = 5;
  const TYPE_INT         = 6;
  const TYPE_BIG_INT     = 7;
  const TYPE_DECIMAL     = 8;
  const TYPE_FLOAT       = 9;
  const TYPE_DOUBLE      = 10;
  const TYPE_STRING      = 11; // Cualquier char, varchar o text
  const TYPE_CHAR        = 12;
  const TYPE_VARCHAR     = 13;
  const TYPE_TINY_TEXT   = 14;
  const TYPE_TEXT        = 15;
  const TYPE_MEDIUM_TEXT = 16;
  const TYPE_LONG_TEXT   = 17;
  const TYPE_TINY_BLOB   = 18;
  const TYPE_BLOB        = 19;
  const TYPE_MEDIUM_BLOB = 20;
  const TYPE_LONG_BLOB   = 21;
  const TYPE_ENUM        = 22;
  const TYPE_SET         = 23;
  const TYPE_DATE        = 24;
  const TYPE_DATE_TIME   = 25;
  const TYPE_TIME        = 26;
  const TYPE_YEAR        = 27;
  const TYPE_TIMESTAMP   = 28;

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
   * Devuelve el formato correcto para el valor $value.
   * Esto es util para convertir valores de tipo integer a valores string cuando
   * la columna $column es de tipo DATE*
   * 
   * @param mixed $value
   * @param string $column Nombre de la columna
   * @param string $class Nombre de la clase para el scope
   */
  public static function formatValue($value, $column, $class)
  {
    $ColumnInfo = self::getColumnInfo($column, $class);

    if ($value != null) {
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
    }

    return $value;
  }

  /**
   * Devuelve la información de una sola columna especificado por $column usando
   * el scope de tabla de $class
   * 
   * @param string $column Nombre de columna
   * @param string $class Clase para el scope de tabla
   * @return Object Información de la columan (SHOW COLUMNS FROM XYZ)
   * @throws QuarkDBException
   */
  public static function getColumnInfo($column, $class)
  {
    foreach (self::getColumnsInfo($class) as $ColumnInfo) {
      if ($ColumnInfo->Field == $column) {
        return $ColumnInfo;
      }
    }
    throw new QuarkDBException(
      __METHOD__.'(): Column "'.$class.'.'.$column.'" not found.',
      QuarkDBException::ERROR_COLUMN_NOT_FOUND
    );
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
        $pattern = '/^(tinyint|smallint|mediumint|int|bigint|decimal|float|double)\b/i';
        break;
      case self::TYPE_INTEGER:
        $pattern = '/^(tinyint|smallint|mediumint|int|bigint)\b/i';
        break;
      case self::TYPE_TINY_INT:
        $pattern = '/^tinyint\b/i';
        break;
      case self::TYPE_SMALL_INT:
        $pattern = '/^smallint\b/i';
        break;
      case self::TYPE_MEDIUM_INT:
        $pattern = '/^mediumint\b/i';
        break;
      case self::TYPE_INT:
        $pattern = '/^int\b/i';
        break;
      case self::TYPE_BIG_INT:
        $pattern = '/^bigint\b/i';
        break;
      case self::TYPE_DECIMAL:
        $pattern = '/^decimal\b/i';
        break;
      case self::TYPE_FLOAT:
        $pattern = '/^float\b/i';
        break;
      case self::TYPE_DOUBLE:
        $pattern = '/^double\b/i';
        break;
      case self::TYPE_STRING:
        $pattern = '/^(char|varchar|tinytext|text|mediumtext|longtext)\b/i';
        break;
      case self::TYPE_CHAR:
        $pattern = '/^char\b/i';
        break;
      case self::TYPE_VARCHAR:
        $pattern = '/^varchar\b/i';
        break;
      case self::TYPE_TINY_TEXT:
        $pattern = '/^tinytext\b/i';
        break;
      case self::TYPE_TEXT:
        $pattern = '/^text\b/i';
        break;
      case self::TYPE_MEDIUM_TEXT:
        $pattern = '/^mediumtext\b/i';
        break;
      case self::TYPE_LONG_TEXT:
        $pattern = '/^longtext\b/i';
        break;
      case self::TYPE_TINY_BLOB:
        $pattern = '/^tinyblob\b/i';
        break;
      case self::TYPE_BLOB:
        $pattern = '/^blob\b/i';
        break;
      case self::TYPE_MEDIUM_BLOB:
        $pattern = '/^mediumblob\b/i';
        break;
      case self::TYPE_LONG_BLOB:
        $pattern = '/^longblob\b/i';
        break;
      case self::TYPE_ENUM:
        $pattern = '/^enum\b/i';
        break;
      case self::TYPE_SET:
        $pattern = '/^set\b/i';
        break;
      case self::TYPE_DATE:
        $pattern = '/^date\b/i';
        break;
      case self::TYPE_DATE_TIME:
        $pattern = '/^datetime\b/i';
        break;
      case self::TYPE_TIME:
        $pattern = '/^time\b/i';
        break;
      case self::TYPE_YEAR:
        $pattern = '/^year\b/i';
        break;
      case self::TYPE_TIMESTAMP:
        $pattern = '/^timestamp\b/i';
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
   * Agrega las columnas que forman el primary key de la tabla $class::TABLE a la
   * lista de columnas $columns, solo si es necesario.
   */
  public static function addPkColumns($columns, $class)
  {
    if (is_string($columns)) {
      $columns = self::splitColumns($columns);
    }

    $primary_key = self::getPrimaryKey($class);
    foreach ($primary_key as $pk) {
      if (!in_array($pk, $columns)) {
        $columns[] = $pk;
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
        if ($value instanceof QuarkSQLExpression) {
          $assignments[] = $column_scoped.'='.$value->getExpression();
          $expression_params = $value->getParams();
          if (is_array($expression_params)) {
            $params_out = array_merge($params_out, $expression_params);
          }
        } else {
          $assignments[]            = $column_scoped.'='.$placeholder;
          $params_out[$placeholder] = $value;
        }
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
    self::assignPlaceholdersID($condition, $params);
  }

  /**
   * Asigna ID a los placeholders de $sql y $params, para evitar placeholders
   * ambiguos.
   * 
   * @param string $sql Código SQL con placeholders
   * @param array $params Lista de parametros (key/value) con los placeholders
   */
  public static function assignPlaceholdersID(&$sql, &$params)
  {
    if (count($params) > 0) {
      $placeholder_id = self::getPlaceholderId();
      $params_out = array();
      foreach ($params as $placeholder => $value) {
        $new_placeholder = str_replace(':', ':'.$placeholder_id.'_', $placeholder);
        $sql = str_replace($placeholder, $new_placeholder, $sql);
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
