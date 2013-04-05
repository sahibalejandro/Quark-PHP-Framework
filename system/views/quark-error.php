<!--
QuarkPHP Framework
Copyright (C) 2012 Sahib Alejandro Jaramillo Leo

http://quarkphp.com
GNU General Public License (http://www.gnu.org/licenses/gpl.html)
 -->
<!DOCTYPE html>
<html lang="en-US">
<head>
  <meta charset="UTF-8">
  <title>Something is wrong</title>
  <link rel="stylesheet" type="text/css" href="<?php echo Quark::inst('QuarkURL')->getBaseURL(); ?>system/public/css/quark.css">
</head>
<body>
  <h2>This page is not working properly.</h2>
  <p>
    The support team will repair this problem as soon as possible, please back later.
  </p>
  <?php
  if (QUARK_DEBUG):
  ?>
  <div id="debug">
    <strong>Debug mode enabled</strong>
    <div id="debug_tip">
      Debug mode should be disabled in production environments, to disable debug mode set <em>$config['debug']=false</em> in your config file.
    </div>
    <strong>Error messages:</strong>
    <div id="error_messages">
      <ol>
        <?php
        foreach ($error_messages as $error_msg):
        ?>
        <li class="error_message"><?php echo nl2br($error_msg); ?></li>
        <?php
        endforeach;
        ?>
      </ol>
    </div>
  </div>
  <?php
  endif;
  ?>
  <div id="footer">
    This web site is builded with QuarkPHP v<?php echo Quark::VERSION; ?>
    - <a href="http://quarkphp.com">http://quarkphp.com</a>
  </div>
</body>
</html>
