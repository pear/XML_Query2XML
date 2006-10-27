--TEST--
XML_Query2XML::factory(): check for XML_Query2XML_DBException
--SKIPIF--
<?php chdir(dirname(dirname(__FILE__))); require_once './skipif.php'; ?>
--FILE--
<?php
    require_once('DB.php');
    require_once('XML/Query2XML.php');
    $db = DB::connect('mysql://bogususer@256.256.256.256/bugusdb');
    try {
        $query2xml =& XML_Query2XML::factory($db);
    } catch (XML_Query2XML_DBException $e) {
        echo get_class($e) . ': ' . substr($e->getMessage(), 0, 30);
    }
?>
--EXPECT--
XML_Query2XML_DBException: Could not connect to database:
