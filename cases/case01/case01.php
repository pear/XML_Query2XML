<?php
require_once('XML/Query2XML.php');
require_once('DB.php');
$query2xml = XML_Query2XML::factory(DB::connect('mysql://root@localhost/Query2XML_Tests'));
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