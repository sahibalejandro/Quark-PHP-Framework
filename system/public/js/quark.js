/**
 * Quark 3 PHP Framework
 * Copyright (C) 2011 Sahib Alejandro Jaramillo Leo
 * 
 * @link http://quarkphp.com
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 */

/**
 * Definición de metodos del objeto Quark
 * Definición de Plugin quark para jQuery (más abajo)
 */
Quark.ajax = function(url, Options) {

	var success_callback = function() {};
	var error_callback;
	var access_denied_callback;
	var not_found_callback;

	if (typeof Options == 'undefined') {
		Options = {};
	}

	/* Mapear callbacks */
	if (typeof Options.success == 'function') {
		success_callback = Options.success;
	}

	if (typeof Options.error == 'function') {
		error_callback = Options.error;
	}

	if (typeof Options.accessDenied == 'function') {
		access_denied_callback = Options.accessDenied;
	}

	if (typeof Options.notFound == 'function') {
		not_found_callback = Options.notFound;
	}

	/* Remover los callbacks de las opciones de entrada, ya que fueron mapeados */
	delete Options.success, Options.error, Options.accessDenied,
			Options.notFound;

	Options = jQuery.extend({
		type : 'post',
		dataType : 'json',
		url : Quark.getUrl(url) + (this.FRIENDLY_URL ? '?' : '&')
				+ 'quark_ajax=1&__=' + Math.random(),
		error : function(Xhr, st_text, error) {
			throw "Quark AJAX Request error:\nStatus text: " + st_text
					+ "\nThrown error: " + error;
		},
		success : function(Response) {

			if (Response.access_denied) {

				if (typeof access_denied_callback == 'function') {
					access_denied_callback();
				} else {
					Quark.ajaxAccessDenied();
				}

			} else if (Response.not_found) {

				if (typeof not_found_callback == 'function') {
					not_found_callback();
				} else {
					Quark.ajaxNotFound();
				}

			} else if (Response.error == false) {
				success_callback(Response.data);

			} else {
				if (typeof error_callback == 'function') {
					error_callback(Response.error);
				} else {
					Quark.ajaxError(Response.error);
				}
			}
		}
	}, Options);

	return jQuery.ajax(Options);
};
/* END: Quark.ajax() */

Quark.ajaxError = function(error_message) {
	var message = 'Ocurrió un error en la solicitud, intenta más tarde.'
			+ (Quark.DEBUG ? "\n\nError:\n" + error_message : '');
	if (typeof Quark.UI != 'undefined') {
		Quark.UI.alert(message.replace(/\n/g, '<br />'), {
			title : 'Ups!'
		});
	} else
		alert(message);
};
/* END: Quark.ajaxError() */

/**
 * Muestra un mensaje de Acceso Denegado
 */
Quark.ajaxAccessDenied = function() {
	var msg = 'Usted no tiene permiso para realizar esta acción.';
	if (typeof Quark.UI != 'undefined')
		Quark.UI.alert(msg, {
			title : 'Acceso Denegado'
		});
	else
		alert("Acceso Denegado\n" + msg);
};
/* END: Quark.ajaxAccessDenied() */

Quark.ajaxNotFound = function() {
	var msg = 'No se puede realizar la solicitud por que el recurso no existe.';
	if (typeof Quark.UI != 'undefined')
		Quark.UI.alert(msg, {
			title: 'Error 404'
		});
	else
		alert("Error 404\n" + msg);
};
/* END: Quark.ajaxNotFound() */

Quark.getUrl = function(url) {
	var base_url = this.BASE_URL;
	base_url += (!this.FRIENDLY_URL ? '?' : '');
	base_url += (this.MULTILANG && !this.LANG_ON_SUBDOMAIN ? (this.LANG + '/')
			: '');
	return base_url + url;
};
/* END: Quark.getUrl() */

Quark.changeLocation = function(url) {
	window.location = this.getUrl(url);
};
/* END: Quark.changeLocation() */

/* ----------------------------------------------------------------------------------------------------
 * Quark jQuery Plugin
 */
(function($) {

	$.fn.quark = function(method) {

		var methods = {
			disable : function() {
				return this.attr('disabled', 'disabled');
			},
			enable : function() {
				return this.removeAttr('disabled');
			}
		};

		/*
		 * Logica de llamada a los metodos
		 */
		if (methods[method])
			return methods[method].apply(this, Array.prototype.slice.call(
					arguments, 1));
		else
			throw 'Metodo "' + method + '" no definido en jQuery.quark';

	};

})(jQuery);
