--TEST--
XML_Query2XML::getFlatXML(): Case01
--SKIPIF--
<?php require_once dirname(dirname(__FILE__)) . '/skipif.php'; ?>
--FILE--
<?php
require_once 'XML/Query2XML.php';
require_once 'MDB2.php';
$query2xml = XML_Query2XML::factory(MDB2::factory('mysql://root@localhost/Query2XML_Tests'));
$dom = $query2xml->getFlatXML(
    "SELECT
        *
     FROM
        artist",
    'music_library',
    'artist');

header('Content-Type: application/xml');

$dom->formatOutput = true;
print $dom->saveXML();
?>
--EXPECT--
<?xml version="1.0" encoding="UTF-8"?>
<music_library>
  <artist>
    <artistid>1</artistid>
    <name>Curtis Mayfield</name>
    <birth_year>1920</birth_year>
    <birth_place>Chicago</birth_place>
    <genre>Soul</genre>
  </artist>
  <artist>
    <artistid>2</artistid>
    <name>Isaac Hayes</name>
    <birth_year>1942</birth_year>
    <birth_place>Tennessee</birth_place>
    <genre>Soul</genre>
  </artist>
  <artist>
    <artistid>3</artistid>
    <name>Ray Charles</name>
    <birth_year>1930</birth_year>
    <birth_place>Mississippi</birth_place>
    <genre>Country and Soul</genre>
  </artist>
</music_library>
