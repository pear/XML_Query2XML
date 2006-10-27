<?php
if (!@include_once 'MDB2.php') {
    print 'skip could not find MDB2.php';
    exit;
} else {
    require_once dirname(dirname(__FILE__)) . '/settings.php';
    $db = @MDB2::factory(DSN);
    if (PEAR::isError($db)) {
        print 'skip could not connect using DSN ' . DSN;
        exit;
    }
}
?>