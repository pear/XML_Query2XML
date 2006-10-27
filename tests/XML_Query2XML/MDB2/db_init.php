<?php
require_once dirname(dirname(__FILE__)) . '/settings.php';
require_once 'MDB2.php';
$db = MDB2::factory(DSN);
?>