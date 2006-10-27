<?php
if (!@include_once 'DB.php') {
    print 'skip could not find DB.php';
    exit;
} else {
    require_once dirname(dirname(__FILE__)) . '/settings.php';
    $db = @DB::connect(DSN);
    if (PEAR::isError($db)) {
        print 'skip could not connect using DSN ' . DSN;
        exit;
    }
}
?>