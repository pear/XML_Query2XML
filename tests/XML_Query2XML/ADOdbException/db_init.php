<?php
require_once dirname(dirname(__FILE__)) . '/settings.php';
require_once 'adodb/adodb.inc.php';
require_once 'adodb/adodb-exceptions.inc.php';
$db = NewADOConnection(DSN);
?>