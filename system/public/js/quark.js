/**
 * Quark 3 PHP Framework
 * Copyright (C) 2011 Sahib Alejandro Jaramillo Leo
 *
 * Este script es procesado como si fuera una vista
 * en QuarkController::quarkIncludeJs();
 * 
 * @link http://quarkphp.com
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 */
var QUARK_DEBUG = <?php echo QUARK_DEBUG ? 'true' : 'false' ?>;
var QUARK_FRIENDLY_URL = <?php echo QUARK_FRIENDLY_URL ? 'true' : 'false' ?>;

var Quark = new (function()
{
  var _base_url = <?php echo json_encode($this->QuarkURL->getBaseURL()) ?>;
  var _AJAXSettings = {
    type: 'post',
    dataType: 'json',
    
    // NOTA: Esta function es movida a userComplete()
    // Funcion invocada cuando data.data.error es false
    complete: function(jqXHR, status_text)
    {
    },

    // NOTA: Esta function es movida a userSuccess()
    // Funcion invocada cuando data.data.error es false
    success: function(data, status_text, jqXHR)
    {
    },
    
    // Funcion invocada cuando data.data.error es true
    fail: function(data, status_text, jqXHR)
    {
    },

    // Invocado cuando data.error es true
    scriptError: function(error_msg)
    {
      alert('Ocurrió un error al procesar la solicitud, intenta de nuevo.'
        + (QUARK_DEBUG ? ("\nERROR:\n" + error_msg) : ''));
    },

    // Invocado cuando data.access_denied es true
    accessDenied: function(resource_url)
    {
      alert('Acceso denegado a "' + resource_url + '"');
    },

    // Invocado cuando data.not_found es true
    notFound: function(resource_url)
    {
      alert('Recurso "' + resource_url + '" no encontrado.');
    },

    error: function(jqXHR, text_status, error_thrown)
    {
      alert("La solicitud no se pudo completar.\nSTATUS: " + text_status + "\nERROR: " + error_thrown);
    }
  };

  /**
   * Hace una solicitud AJAX al recurso url
   * @param  string url
   * @param  object Settings
   */
  this.ajax = function(url, Settings)
  {
    Settings = $.extend({}, _AJAXSettings, Settings);

    // Mover metodo success() a userSuccess()
    Settings.userSuccess = Settings.success;

    // Mover metodo complete() a userComplete()
    Settings.userComplete = Settings.complete;

    Settings.complete = function(jqXHR, text_status)
    {
      var QuarkJSONResponse = null;
      
      try{
        QuarkJSONResponse = jQuery.parseJSON(jqXHR.responseText);
      }catch(err){}

      Settings.userComplete(jqXHR, text_status, QuarkJSONResponse);
    };

    // Sobre escribir el metodo success()
    Settings.success = function(data, text_status, jqXHR)
    {
      if(data.error){
        // Error en php script
        Settings.scriptError(data.error);
      } else if(data.access_denied) {
        // Acceso denegado
        Settings.accessDenied(url);
      } else if(data.not_found) {
        // No encontrado
        Settings.notFound(url);
      } else if(data.data.error) {
        // Error generado por el usuario
        Settings.fail(data.data, text_status, jqXHR);
      } else {
        // Todo bien
        Settings.userSuccess(data.data, text_status, jqXHR);
      }
    };

    return $.ajax(_base_url
      + (!QUARK_FRIENDLY_URL ? '?' : '')
      + url
      + (!QUARK_FRIENDLY_URL ? '&' : '?')
      + 'quark_ajax=1', Settings);
  };

  this.setAJAXSettings = function(Settings)
  {
    _AJAXSettings = $.extend({}, _AJAXSettings, Settings);
  };

  /**
   * Devuelve una URL valida, igual que QuarkURL::getURL();
   * @param  string url
   * @return string
   */
  this.getURL = function(url)
  {
    return _base_url + (!QUARK_FRIENDLY_URL ? '?' : '') + url;
  }
})();
