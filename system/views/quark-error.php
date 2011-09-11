<!--
Quark 3 PHP Framework
Copyright (C) 2011 Sahib Alejandro Jaramillo Leo

http://quarkphp.com
GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 -->
<!doctype html>
<html>
	<head>
		<meta charset="utf-8" />
		<title>Error</title>
		<link rel="stylesheet" type="text/css" href="<?php Quark::inst('QuarkUrl')->baseUrl() ?>system/public/css/quark.css" />
	</head>
	<body>
	<h1>Algo anda mal...</h1>
	Esta página presenta errores, <a href="javascript:window.location.reload(true);">intenta de nuevo</a>, si el error continúa intenta más tarde.
	<ul>
		<li><a href="<?php Quark::inst('QuarkUrl')->baseUrl()?>">Regresar al inicio</a></li>
	</ul>
	<?php if (QUARK_DEBUG): ?>
    	<h2>Debug mode:</h2>
    	<div id="errors"><?php echo QUARK_ERROR_MESSAGES ?></div>
    <?php endif ?>
	</body>
</html>