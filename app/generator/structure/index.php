<?php
################################
# Hooks
################################
define('SYS_USE_FIREPHP',true,true);

################################
# Including required files
################################
require_once('app/phpBurn.php');
require_once('config.php');

################################
# Starting application
################################
PhpBURN::startApplication();

################################
# Sending a End of File
################################
PhpBURN_Message::output('[!EOF!]');
?>
