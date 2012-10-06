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
var QUARK_DEBUG = <?php echo QUARK_DEBUG ? 'true' : 'false'; ?>;
var QUARK_FRIENDLY_URL = <?php echo QUARK_FRIENDLY_URL ? 'true' : 'false'; ?>;
var QUARK_MULTILANG = <?php echo QUARK_MULTILANG ? 'true' : 'false'; ?>;
var QUARK_LANG_ON_SUBDOMAIN = <?php
  echo QUARK_LANG_ON_SUBDOMAIN ? 'true' : 'false';
?>;

var Quark = new (function()
{
  var _base_url = <?php echo json_encode($this->QuarkURL->getBaseURL()) ?>;
  
  var _AJAXSettings = {
    type: 'post',
    dataType: 'json',
    data: {},
    lang: null,
    
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
      alert('An error has occurred while processing your request, please try again.'
        + (QUARK_DEBUG ? ("\nERROR:\n" + error_msg) : ''));
    },

    // Invocado cuando data.access_denied es true
    accessDenied: function(resource_url)
    {
      alert('Access denied to "' + resource_url + '"');
    },

    // Invocado cuando data.not_found es true
    notFound: function(resource_url)
    {
      alert('Resource "' + resource_url + '" not found.');
    },

    /**
     * jQuery's Ajax Event method, triggered when the request fails.
     */
    error: function(jqXHR, text_status, error_thrown)
    {
      alert("The request could not be completed.\nSTATUS: "
        + text_status + "\nERROR: " + error_thrown);
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
    
    // Send `quark_ajax` with Settings.data
    Settings.data.quark_ajax = Math.random();
    
    // Set the language to the request if needed.
    if (QUARK_MULTILANG && Settings.lang == null) {
      Settings.lang = Quark.Lang.getActualLang();
    }

    return $.ajax(Quark.URL.getURL(url, Settings.lang), Settings);
  };

  this.setAJAXSettings = function(Settings)
  {
    _AJAXSettings = $.extend({}, _AJAXSettings, Settings);
  };
  
  this.getAJAXSettings = function()
  {
    return _AJAXSettings;
  }

  /**
   * @deprecated This will be removed on version 3.6, use Quark.URL.getURL() instead.
   */
  this.getURL = function(url, lang)
  {
    return Quark.URL.getURL(url, lang);
  }
  
})();

/**
 * Object to work with URLs
 */
Quark.URL = new (function ()
{
  
  var urls_with_lang_on_subdomain = [];
  <?php foreach (Quark::getConfigVal('langs') as $lang_prefix): ?>
    urls_with_lang_on_subdomain['<?php echo $lang_prefix ?>'] = <?php echo json_encode($this->QuarkURL->getBaseURL($lang_prefix)); ?>;
  <?php endforeach; ?>
  
  /**
   * Get base url with an optional language prefix
   * Tail slash included!
   *
   * @param [String] lang
   * @return [String]
   */
  this.getBaseURL = function (lang)
  {
    if (!QUARK_LANG_ON_SUBDOMAIN) {
      // Return normal base URL
      return <?php echo json_encode($this->QuarkURL->getBaseURL()); ?>;
    } else {
      // Return pre-defined base URL with lang on subdomain
      if (!lang || urls_with_lang_on_subdomain[lang] == undefined
      ) {
        lang = Quark.Lang.getActualLang();
      }
      
      return urls_with_lang_on_subdomain[lang];
    }
  },
  
  /**
   * Get the full URL of url, with optional language prefix
   * Tail slash included!
   *
   * @param [String] url
   * @param [String] lang
   * @return [String]
   */
  this.getURL = function (url, lang)
  {
    var base_url = this.getBaseURL(lang);
    
    // Assign default value to `url`
    if (typeof url == 'undefined') {
      url = '';
    }
    
    // Prepend language prefix if needed.
    if (QUARK_MULTILANG && !QUARK_LANG_ON_SUBDOMAIN) {
      if (!lang || !Quark.Lang.isDefined(lang)) {
        lang = Quark.Lang.getActualLang();
      }
      url = lang + '/' + url;
    }
    
    // Replace sign '?' in url if friendly URLs are deactivated beacause when
    // friendly URLs are deactivated the sign '?' is prepened automatically.
    if (!QUARK_FRIENDLY_URL && url.indexOf('?') > -1) {
      url = url.replace('?', '&');
    }
    
    // Return well formed URL
    return base_url + ((!QUARK_FRIENDLY_URL && url != '') ? '?' : '') + url;
  }
})();

/**
 * Object to work with defined languages
 */
Quark.Lang = new (function ()
{
  /** Defined language prefixes */
  var langs = <?php echo json_encode(Quark::getConfigVal('langs')); ?>;
  
  /**
   * Get actual language prefix
   * @return [String]
   */
  this.getActualLang = function ()
  {
    return '<?php echo $this->QuarkURL->getPathInfo()->lang; ?>';
  };
  
  /**
   * Get defined language prefixes array
   * @return [Array]
   */
  this.getDefinedLangs = function ()
  {
    return langs;
  };
  
  /**
   * Get default language prefix
   * @return [String]
   */
  this.getDefaultLang = function ()
  {
    return langs[0];
  };
  
  /**
   * Check is `lang` exists on pre-defined language prefixes
   * @param [String]
   * @return [Boolean]
   */
  this.isDefined = function(lang)
  {
    for (i in langs) {
      if (lang == langs[i]) {
        return true;
      }
    }
    
    return false;
  }
  
})();
