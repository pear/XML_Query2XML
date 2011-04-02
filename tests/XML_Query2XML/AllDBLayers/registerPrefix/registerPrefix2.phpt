--TEST--
XML_Query2XML::registerPrefix(): prefix class in external file
--SKIPIF--
<?php require_once dirname(dirname(__FILE__)) . '/skipif.php'; ?>
--FILE--
<?php
require_once 'XML/Query2XML.php';
require_once dirname(dirname(__FILE__)) . '/db_init.php';
$query2xml = XML_Query2XML::factory($db);
$query2xml->registerPrefix('-', 'Year2UnixTime', dirname($argv[0]) . './Year2UnixTime.php');

$dom = $query2xml->getXML(
  "SELECT * FROM artist",
  array(
    'rootTag' => 'artists',
    'idColumn' => 'artistid',
    'rowTag' => 'artist',
    'elements' => array(
        'name',
        'birth_year',
        'birth_year_in_unix_time' => '-birth_year'
    )
  )
);

header('Content-Type: application/xml');
$dom->formatOutput = true;
print $dom->saveXML();
?>
--EXPECT--
<?xml version="1.0" encoding="UTF-8"?>
<artists>
  <artist>
    <name>Curtis Mayfield</name>
    <birth_year>1920</birth_year>
    <birth_year_in_unix_time>-1577923200</birth_year_in_unix_time>
  </artist>
  <artist>
    <name>Isaac Hayes</name>
    <birth_year>1942</birth_year>
    <birth_year_in_unix_time>-883612800</birth_year_in_unix_time>
  </artist>
  <artist>
    <name>Ray Charles</name>
    <birth_year>1930</birth_year>
    <birth_year_in_unix_time>-1262304000</birth_year_in_unix_time>
  </artist>
</artists>
