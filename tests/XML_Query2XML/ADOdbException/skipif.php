<?php
if (!@include_once 'adodb/adodb.inc.php') {
    print 'skip could not find adodb/adodb.inc.php';
    exit;
} elseif (!@include_once 'adodb/adodb-exceptions.inc.php') {
    print 'skip could not find adodb/adodb-exceptions.inc.php';
    exit;
} else {
    require_once dirname(dirname(__FILE__)) . '/settings.php';
    $db = NewADOConnection(DSN);
    if (!$db) {
        print 'skip could not connect using DSN ' . DSN;
        exit;
    }
}
?>