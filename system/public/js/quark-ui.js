/**
 * Quark 3 PHP Framework
 * Copyright (C) 2011 Sahib Alejandro Jaramillo Leo
 * 
 * @link http://quarkphp.com
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 */

/**
 * Definición de clase Quark.UI 
 */
Quark.UI = {
		
	OK_BUTTON: 'quark-ui-button-ok',
	CANCEL_BUTTON: 'quark-ui-button-cancel',
	modal_bg_on: false,
	on_ie6: (navigator.appVersion.indexOf('MSIE 6') > -1),
		
	/**
	 * Muestra un mensaje de alerta con un boton para aceptar
	 */
	alert:  function( message, Options )
	{
		Options = $.extend({
			title: 'Alerta',
			ok_button_text: 'Aceptar',
			callback: function(){}
		}, Options);
		
		/*
		 * Botones
		 */
		var $OkButton = Quark.UI.createButton({
			text: Options.ok_button_text,
			css_class: 'quark-ui-button-ok',
			callback: Options.callback,
			close_dialog: true
		});
		
		/*
		 * Crear alert box
		 */
		var $AlertBox = Quark.UI.createDialog({
			title: Options.title,
			html: message,
			buttons: [ $OkButton ]
		});
	},
	
	/**
	 * Muestra un dialog de confirmación, el callback recibe como argumento
	 * true o false, dependiendo de la acción del usuario.
	 */
	confirm: function(message, Options)
	{
		Options = $.extend({
			title: 'Dialogo de confirmación',
			ok_button_text: 'Aceptar',
			cancel_button_text: 'Cancelar',
			callback: function(){}
		}, Options);
		
		/*
		 * Botones
		 */
		var $OkButton = Quark.UI.createButton({
			text: Options.ok_button_text,
			css_class: 'quark-ui-button-ok',
			callback: function()
			{
				Options.callback(false);
			},
			close_dialog: true
		});
		
		var $CancelButton = Quark.UI.createButton({
			text: Options.cancel_button_text,
			callback: function()
			{
				Options.callback(false);
			},
			close_dialog: true
		});
		
		/*
		 * Confirm box
		 */
		var $ConfirmBox = Quark.UI.createDialog({
			html: message,
			title: Options.title,
			buttons: [$OkButton, $CancelButton]
		});
	},
	
	/**
	 * Crea un dialogo a partir de un contenido HTML cargado con AJAX
	 * @param Options
	 */
	loadDialog: function(url, Options)
	{
		Options = $.extend({
			modal: true,
			title: '',
			buttons: [],
			onLoad: function(){}
		}, Options);
		
		/*
		 * Crear el dialogo donde se insertará el contenido cargado.
		 */
		$Dialog = this.createDialog({
			title: Options.title,
			modal: Options.modal,
			html: 'Cargando...'
		});
		
		Quark.ajax(url,{
			error: function(error)
			{
				Quark.UI.destroy($Dialog);
				Quark.UI.alert(error);
			},
			success: function(html)
			{
				$('.quark-ui-dialog-content', $Dialog).html( html );
				Quark.UI.appendButtons(Options.buttons, $Dialog);
				Quark.UI.centerDialog($Dialog);
				Options.onLoad();
			}
		});
		
		return $Dialog;
	},
	
	/**
	 * Crea un dialogo generico y devuelve su referencia
	 */
	createDialog: function( Options )
	{
		Options = $.extend({
			title: '',
			html: '',
			modal: true,
			buttons: []
		}, Options);
		
		var $DialogBox = $('<div>').addClass('quark-ui-dialog');
		$DialogBox.css({
			'min-width': '400px',
			'max-width': ($(window).width() - 100) + 'px'
		});
		
		/*
		 * FIX de WIDTH para IE
		 */
		if( $.support.changeBubbles == false )
			$DialogBox.css('width', '400px');
		
		
		if( Options.title != '' )
		{
			$DialogBox.append( $('<div>').text(Options.title).addClass('quark-ui-dialog-title') );
		}
		var $Content = $('<div>').addClass('quark-ui-dialog-content');
		
		$Content.html(Options.html);
		
		$DialogBox.append( $Content ).data('quark-ui-modal', Options.modal);

		/*
		 * Agregar botones
		 */
		this.appendButtons(Options.buttons, $DialogBox);
		
		/*
		 * Configurar fondo modal, no para ie6
		 */
		if( !Quark.UI.on_ie6 && Options.modal )
		{
			/*
			 * En caso de ser dialogo modal creamos un background
			 * del tamaño del documento
			 */
			$ModalBackground = $('<div>').css({
				position: 'absolute',
				top: 0,
				left: 0,
				width: $(document).width() + 'px',
				height: $(document).height() + 'px'
			});
			
			/*
			 * Enlazamos el background con su dialogo para que Quark.UI.destroy()
			 * pueda remover el background de este dialogo.
			 */
			$DialogBox.data('quark-ui-dialog-modal-background', $ModalBackground);
			
			/*
			 * si es el primer dialogo modal mostramos un fondo
			 * transparente y encendemos el flag Quark.UI.modal_bg_on
			 * y asignamos un flag al dialogo para que Quark.UI.destroy()
			 * puede apagar el flag Quark.UI.modal_bg_on
			 */
			if( Quark.UI.modal_bg_on == false )
			{
				$ModalBackground.css({
					'background-color': '#FFF',
					'opacity': 0.8
				});
				
				$DialogBox.data('quark-ui-moda-bg-main', true);
				Quark.UI.modal_bg_on = true;
			}
			
			$('body').append($ModalBackground);
		}
		
		/*
		 * Primer posicionar absolutamente el dialogo en 0,0 para evitar
		 * que la pagina haga un scrolldown al insertar el elemento en body.
		 */
		$DialogBox.css({position:'absolute',top:'0px',left:'0px'});
		$('body').append($DialogBox);
		
		/*
		 * Centrar dialogo
		 */
		this.centerDialog($DialogBox);
		
		/*
		 * Autofocus al primer boton.
		 */
		if( Options.buttons.length > 0 )
		{
			Options.buttons[0].focus();
		}
		
		return $DialogBox;
	},
	
	/**
	 * Agrega botones a un dialogo ya creado, y asigna el valor 'quark-ui-prarent-dialog' a cada boton
	 * @param buttons
	 * @param $Dialog
	 */
	appendButtons: function( buttons, $Dialog )
	{
		if( buttons.length > 0 )
		{
			var $ButtonsWrapper = $('<div>').addClass('quark-ui-buttons-wrapper');
			
			$.each(buttons, function(i, $Button)
			{
				$ButtonsWrapper.append( $Button );
				$Button.data('quark-ui-parent-dialog', $Dialog);
			});
			
			$Dialog.append($ButtonsWrapper);
		}
	},
	
	/**
	 * Centra un dialogo en la ventana
	 */
	centerDialog: function($Dialog)
	{
		$Dialog.css({
			top: Math.round( ($(window).height() - $Dialog.outerHeight(true)) / 2 ) + 'px', 
			left: Math.round( ($(window).width() - $Dialog.outerWidth(true)) / 2 ) + 'px'
		});
	},
	
	/**
	 * Crea un boton y le asigna su evento click
	 */
	createButton: function( Options )
	{
		Options = $.extend({
			text: 'Aceptar',
			css_class: '',
			callback: function(){},
			close_dialog: false
		}, Options);
		
		return $('<button>').addClass('quark-ui-button ' + Options.css_class).text(Options.text).click(function(){
			
			Options.callback.call( $(this) );
			
			if(Options.close_dialog)
			{
				Quark.UI.destroy( $(this).data('quark-ui-parent-dialog') );
			}
			
		}).data('quark-ui-button-options', Options);
	},
	
	/**
	 * Destruye un dialogo
	 */
	destroy: function($Dialog)
	{
		if ( !Quark.UI.on_ie6 && $Dialog.data('quark-ui-modal') == true ) {
			if ( $Dialog.data('quark-ui-moda-bg-main') ) {
				Quark.UI.modal_bg_on = false;
			}
			$Dialog.data('quark-ui-dialog-modal-background').remove();
		}
		$Dialog.remove();
	}
};