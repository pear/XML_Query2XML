--TEST--
XML_Query2XML::unregisterPrefix()
--SKIPIF--
<?php require_once dirname(dirname(__FILE__)) . '/skipif.php'; ?>
--FILE--
<?php
require_once 'XML/Query2XML.php';
require_once dirname(dirname(__FILE__)) . '/db_init.php';
$query2xml = XML_Query2XML::factory($db);

$query2xml->unregisterPrefix('#');

try {
 $dom = $query2xml->getXML(
   "SELECT * FROM artist",
   array(
     'rootTag' => 'artists',
     'idColumn' => 'artistid',
     'rowTag' => 'artist',
     'elements' => array(
         'name',
         '#birth_year'
     )
   )
 );
} catch (XML_Query2XML_ConfigException $e) {
    echo get_class($e) . ': ' . $e->getMessage();
}
exit;
?>
--EXPECT--
XML_Query2XML_ConfigException: [elements][#birth_year]: The column "#birth_year" was not found in the result set.
