--TEST--
XML_Query2XML::factory(): check for XML_Query2XML_DBException
--FILE--
<?php
    require_once 'XML/Query2XML.php';
    $db = new PEAR_Error('error message');
    try {
        $query2xml =& XML_Query2XML::factory($db);
    } catch (XML_Query2XML_DBException $e) {
        echo get_class($e) . ': ' . substr($e->getMessage(), 0, 30);
    }
?>
--EXPECT--
XML_Query2XML_DBException: Could not connect to database:
