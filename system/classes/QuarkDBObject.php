<?php
abstract class QuarkDBObject
{
  /**
   * Mensaje de error.
   * @see setErrorMsg()
   * @var string
   */
  private $qdbo_error_msg;

  public function __construct()
  {
    $class = get_class($this);
    foreach (QuarkDBUtils::getColumns($class) as $column) {
      if (!isset($this->$column)) {
        $this->$column = null;
        /**
         * TODO:
         * Verificar que tan eficiente es utilizar la siguiente linea.
         * $this->$column = QuarkDBUtils::getPossibleValue($column, $class, null);
         */
      }
    }
  }

  /**
   * Este metodo se invoca dentro de save(), si el resultado es true se guardan
   * los datos, de lo contrario no se guardan.
   * El programador debe hacer override de este metodo.
   * 
   * @return bool
   */
  protected function validate()
  {
    return true;
  }

  /**
   * Define el mensaje de error que se puede recuperar con getErrorMsg()
   * 
   * @param string $error_msg Mensaje
   */
  protected function setErrorMsg($error_msg)
  {
    $this->qdbo_error_msg = $error_msg;
  }

  /**
   * Devuelve el mensaje de error
   * 
   * @return string
   */
  public function getErrorMsg()
  {
    return $this->qdbo_error_msg;
  }

  /**
   * Guarda los cambios en la tabla
   * @return bool true si guarda, false si no.
   */
  public function save()
  {
    // Guardar cambios solo cuando la validación de datos es correcta
    if (!$this->validate()) {
      return false;
    } else {

      // Por default el valor de retorno es true
      $return = true;

      // Para no invocar a get_class() varias veces.
      $class = get_class($this);

      // Objeto para realizar la consulta INSERT o UPDATE.
      $Query = new QuarkDbQuery($class);

      // Extraer los valores de las columnas que van a ser insertados/actualizados
      $columns = array();
      foreach (QuarkDBUtils::getColumns($class) as $column) {
        $columns[$column] = $this->$column;
      }

      // ¿Crear nuevo o actualizar?

      if ($this->isNew()) {
        // Insertar datos
        if ($Query->insert($columns)->exec() == 0) {
          $this->setErrorMsg('The new record was not inserted.');
          // Cambiar el valor de retorno a false
          $return = false;
        }
      } else {
        // Actualizar datos, necesitamos el primary key para el WHERE
        $primary_key = array();
        foreach (QuarkDBUtils::getPrimaryKey($class) as $pk) {
          $primary_key[$pk] = $this->$pk;
        }
        $Query->update($columns)->where($pk)->exec();
      }

      /* Los valores almacenados en la tabla pueden ser diferentes de los valores en
       * las propiedades de este objeto, es necesario recargar desde la DB */
      $row = $Query->getLastRow();
      if ($row != null) {
        $this->fillFromArray($row);
      }

      return $return;
    }

  }

  /**
   * Devuelve true si el objeto no esta relacionado con ninguna fila en la tabla
   * de lo contrario devuelve false.
   * 
   * @return bool
   */
  public function isNew()
  {
    /* Si el primary key tiene valores nulos, se asume que el objeto actual no
     * esta relacionado con ninguna fila en la tabla */
    foreach (QuarkDBUtils::getPrimaryKey(get_class($this)) as $pk) {
      if ($this->$pk === null) {
        return true;
      }
    }

    return false;
  }

  /**
   * Llena las propiedades del objeto a partir de un array
   * 
   * @param array $columns Array asociativo con los nuevos valores
   */
  public function fillFromArray($columns)
  {
    foreach ($columns as $column => $value) {
      if (is_array($value)) {
        $QuarkDBObject = new $column();
        $QuarkDBObject->fillFromArray($value);
        $value = $QuarkDBObject;
        /**
         * TODO:
         * Establecer de alguna manera que la instancia actual tiene propiedades
         * que son instancias de otros QuarkDBObject, para cuando se invoque el
         * metodo save() de la instancia padre tambien se invoque save() en las
         * instancias hijas.
         */
      }
      $this->$column = $value;
    }
  }
}
