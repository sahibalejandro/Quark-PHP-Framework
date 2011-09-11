<?php
/**
 * Quark 3 PHP Framework
 * Copyright (C) 2011 Sahib Alejandro Jaramillo Leo
 * 
 * @link http://quarkphp.com
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 */

/**
 * Capa de abstraccion para utilizar PDO
 * @author Sahib Alejandro Jaramillo Leo
 */
class QuarkModel
{
    /**
     * @var PDO
     */
    private static $_Pdo = NULL;
    
    /**
     * @var PDOStatement
     */
    private $_PdoStatment;
    
    /**
     * @throws PDOException
     * @return PDO
     */
    public static function Pdo()
    {
        if (self::$_Pdo == NULL) {
            $database = array();
            require 'config/database.php';
            
            self::$_Pdo = new PDO("mysql:host={$database['host']};dbname={$database['database']}", $database['user'], $database['password'], $database['options']);
            self::$_Pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $St = self::$_Pdo->prepare('SET NAMES ?;');
            $St->bindValue(1, $database['charset']);
            $St->execute();
        }
        
        return self::$_Pdo;
    }
    
    /**
     * Realiza una consulta directa y guarda el resultado para obtenerlo con $this->getResult()
     * Devuelve $this para hacer linking
     * 
     * @throws PDOException
     * @param string $sql
     * @return QuarkModel
     */
    protected function query($sql)
    {
        $this->_PdoStatment = self::Pdo()->query($sql);
        return $this;
    }
    
    /**
     * Prepara una consulta para agregarle parametros despues con $this->bindParam()
     * El resultado de la consulta preparada puede ser obtenido con $this->getResult()
     * 
     * @throws PDOException
     * @param string $sql
     * @return QuarkModel
     */
    protected function prepare($sql)
    {
        $this->_PdoStatment = self::Pdo()->prepare($sql);
        return $this;
    }
    
    /**
     * Ejecuta la ultima consulta preparada, el resultado de la ejecución se puede
     * obtener con $this->getResult(), $args es un array de argumentos para los
     * placeholders de una consulta preparada.
     * 
     * @throws PDOException
     * @param array $args
     * @return QuarkModel
     */
    protected function exec($args = NULL)
    {
        $this->_PdoStatment->execute($args);
        return $this;
    }
    
    /**
     * Bindea un argumento $value a la posicion $param_num de un placeholder de la
     * ultima consulta preparada, $type es el tipo de argumento, utilice las constantes PDO::PARAM_*
     * 
     * @throws PDOException
     * @param int $param_num
     * @param string $value
     * @param int $type
     * @return QuarkModel
     */
    protected function bindValue($param_num, $value, $type = PDO::PARAM_STR)
    {
        $this->_PdoStatment->bindValue($param_num, $value, $type);
        return $this;
    }
    
    /**
     * Alias de PDO::lastInsertedId()
     * 
     * @throws PDOException
     * @param string $name
     * @return int
     */
    protected function getLastID($name = NULL)
    {
        return self::Pdo()->lastInsertId($name);
    }
    
    /**
     * Devuelve el resultado (PDOStatment) de la ultima consulta preparada o ejecutada
     * 
     * @return PDOStatement
     */
    protected function getResult()
    {
        return $this->_PdoStatment;
    }
}