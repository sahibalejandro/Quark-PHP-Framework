<?php
/**
 * Quark 3.5 PHP Framework
 * Copyright (C) 2012 Sahib Alejandro Jaramillo Leo
 * 
 * @link http://quarkphp.com
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 */

/**
 * Clase para manejar las tareas con upload
 * @author sahib
 */
class QuarkUpload
{
    /**
     * Permisos que se aplicarán a los archivos despues
     * de ser copiados al directorio destino.
     * 
     * @var octal
     */
    private $_chmod = 0666;
    
    /**
     * Flag especificar si se van a ignorar los input files vacios o no.
     * por default son ignorados (true)
     * 
     * @see QuarkUpload::setIgnoreEmpty()
     * @var bool
     */
    private $_ignore_empty = true;
    
    /**
     * Flag para especificar si se sobre escriben los archivos en el
     * directorio destnio, por default se sobre escriben (true)
     * 
     * @see QuarkUpload::setOverwrite()
     * @var bool
     */
    private $_overwrite = true;
    
    /**
     * Flag para especificar si se van a utilizar las funciones finfo_*
     * para obtener el mime type del archivo, solo valido para PHP 5.3 o PECL finfo.
     * El valor de este flag es establecido internamente.
     * 
     * @var bool
     */
    private $_use_finfo;
    
    /**
     * Lista de extensiones validas para los archivos enviados, solo serán
     * utilizadas si no se ha definido ningun mime type con QuarkUpload::setValidMimeTypes()
     * 
     * @see QuarkUpload::setValidExtensions()
     * @var array
     */
    private $_valid_extensions = array();
    
    /**
     * Lista de mime types validos para los archivos enviados,
     * si es definido algun mime type se ignoraran las listas
     * de extensiones definidas con QuarkUpload::setValidExtensions()
     * 
     * @see QuarkUpload::setValidMimeTypes()
     * @var array
     */
    private $_valid_mime_types = array();
    
    /**
     * Constructor
     * @return QuarkUpload
     */
    public function __construct()
    {
        $this->_use_finfo = function_exists('finfo_open');
    }
    
    /**
     * Define la lista de mime types validos para los archivos enviados
     * Si los mime types son definidos, se ignorarán las extensiones
     * defindas con QuarkUpload::setValidExtensions()
     * 
     * @see QuarkUpload::setValidExtensions()
     */
    public function setValidMimeTypes()
    {
        $args = func_get_args();
        $this->_valid_mime_types = $args;
    }
    
    /**
     * Define la lista de extensiones validas para los archivos enviados
     * Si los mime types son definidos con QuarkUpload::setValidMimeTypes()
     * se ignorarán las extensiones.
     * 
     * @see QuarkUpload::setValidMimeTypes()
     */
    public function setValidExtensions()
    {
        $args = func_get_args();
        $this->_valid_extensions = array_map('strtolower', $args);
    }
    
    /**
     * Establece si se va a ignorar los input files vacios o no
     * @param bool $ignore
     */
    public function setIgnoreEmpty($ignore)
    {
        $this->_ignore_empty = $ignore;
    }
    
    
    /**
     * Establece si se va a sobre escribir los archivos existentes en el directorio destino
     * @param bool $overwrite
     */
    public function setOverwrite($overwrite)
    {
        $this->_overwrite = $overwrite;
    }
    
    /**
     * Establece el valor para los permisos que se aplicaran a los archivos
     * movidos al directorio destino.
     * 
     * @param octal $chmod
     */
    public function setChmod($chmod)
    {
        $this->_chmod = $chmod;
    }
    
    /**
     * Mueve los archivos enviados por POST con el nombre $input_file_name
     * al directorio destino $upload_path.
     * Devuelve un resultado de copiado para single upload o un array de resultados un upload multiple.
     * 
     * @see QuarkUpload::setChmod()
     * @see QuarkUpload::setIgnoreEmpty()
     * @see QuarkUpload::setOverwrite()
     * @see QuarkUpload::setValidExtensions()
     * @see QuarkUpload::setValidMimeTypes()
     * 
     * @param string $input_file_name
     * @param string $upload_path
     * @return object|array(object)
     */
    public function moveUploads($input_file_name, $upload_path)
    {
        /* Acceso directo! */
        $F = &$_FILES[$input_file_name];
        
        if (!is_array($F['tmp_name'])) {
            return $this->_moveUploadedFile($F, $upload_path);
        } else {
            $results = array();
            
            foreach ( $F['tmp_name'] as $i => $tmp_name ) {
                $results[] = $this->_moveUploadedFile(array(
                    'name' => $F['name'][$i], 
                    'type' => $F['type'][$i], 
                    'size' => $F['size'][$i], 
                    'error' => $F['error'][$i], 
                    'tmp_name' => $F['tmp_name'][$i]
                ), $upload_path);
            }
            
            return $results;
        }
    }
    
    /**
     * Mueve un archivo definido por $file_info (al estilo $_FILES) al directorio $upload_path
     * Devuelve un objeto resultado que tiene las propiedades:
     * 		error: Si ocurrio algun error al copiar el archivo, de lo contrario false.
     * 		empty: Si el input file esta vacio y el flag QuarkUpload::_ignore_empty es true.
     * 		file_name: Nombre del archivo enviado.
     * 		final_file_name: Nombre del archivo copiado.
     * 
     * @param array $file_info
     * @param string $upload_path
     * @return object
     */
    private function _moveUploadedFile($file_info, $upload_path)
    {
        $Result = (object)array(
            'error' => false, 
            'empty' => false, 
            'file_name' => $file_info['name'], 
            'final_file_name' => $file_info['name']
        );
        
        /*
         * Verificar errores de upload
         */
        if ($file_info['error'] != UPLOAD_ERR_OK) {
            switch ($file_info['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $Result->error = 'El archivo excede el tamaño máximo.';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    if ($this->_ignore_empty) {
                        $Result->empty = true;
                    } else {
                        $Result->error = 'No se envió ningún archivo.';
                    }
                    break;
                default :
                    $Result->error = 'Error al enviar el archivo.';
                    break;
            }
        }
        
        /*
         * Si ocurrio algun error o esta vacio, terminamos.
         */
        if ($Result->error || $Result->empty) {
            return $Result;
        }
        
        if (!empty($this->_valid_mime_types)) {
            /* 
             * Validar el mime type del archivo contra los definidos
             * por el usuario utilizando QuarkUpload::setMimeTypes()
             */
            if (!$this->_use_finfo) {
                $mime_type = mime_content_type($file_info['tmp_name']);
            } else {
                $finfo_handler = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo_handler, $file_info['tmp_name']);
                finfo_close($finfo_handler);
            }
            
            if (false === array_search($mime_type, $this->_valid_mime_types)) {
                $Result->error = 'Tipo de archivo inválido.';
            }
        
        } elseif (!empty($this->_valid_extensions)) {
            /*
             * Validar extension del archivo contra las definidas por
             * el usuario con QuarkUpload::setValidExtensions()
             */
            $file_ext = strtolower(pathinfo($Result->file_name, PATHINFO_EXTENSION));
            
            if (false === array_search($file_ext, $this->_valid_extensions)) {
                $Result->error = 'Extension de archivo inválida.';
            }
        }
        
        /*
         * Si ocurrio algun error terminamos el metodo.
         */
        if ($Result->error != false) {
            return $Result;
        }
        
        /*
         * Continua si no hay errores...
         */
        if (!$this->_overwrite) {
            /* Buscar un nombre de archivo disponible */
            $file_name_parts = explode('.', $Result->file_name);
            $file_ext = array_pop($file_name_parts);
            $safe_file_name_tmp = $safe_file_name = implode('.', $file_name_parts);
            unset($file_name_parts);
            
            $file_counter = 0;
            while ( is_file($upload_path . '/' . $safe_file_name_tmp . '.' . $file_ext) ) {
                $safe_file_name_tmp = $safe_file_name . '[' . (++$file_counter) . ']';
            }
            
            /* Este es el nombre de archivo disponible */
            $Result->final_file_name = $safe_file_name_tmp . '.' . $file_ext;
        } /* if (!$this->_overwrite) */
        
        /*
         * Mover archivo al directorio final
         */
        if (@move_uploaded_file($file_info['tmp_name'], $upload_path . '/' . $Result->final_file_name)) {
            @chmod($upload_path . '/' . $Result->final_file_name, $this->_chmod);
        } else {
            $Result->error = 'No se pudo copiar el archivo al directorio destino, verifique los permisos.';
        }
        
        return $Result;
    }
}
