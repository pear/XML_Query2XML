--TEST--
XML_Query2XML::getXML(): unserialization prefix with empty callback within simple element specification
--SKIPIF--
<?php require_once dirname(dirname(__FILE__)) . '/skipif.php'; ?>
--FILE--
<?php
    require_once 'XML/Query2XML.php';
    require_once dirname(dirname(__FILE__)) . '/db_init.php';
    $query2xml =& XML_Query2XML::factory($db);
    $dom =& $query2xml->getXML(
        "SELECT
            *
         FROM
            store",
        array(
            'rootTag' => 'music_stores',
            'rowTag' => 'store',
            'idColumn' => 'storeid',
            'elements' => array(
                'storeid',
                'country',
                'state',
                'city',
                'street',
                'phone',
                'builing_xmldata' => '&#getEmptyString()',
                'builing_xmldata2' => '&#getNull()',
                'builing_xmldata3' => '?&#getEmptyString()',
                'builing_xmldata4' => '?&#getNull()',
                '__builing_xmldata5' => '&#getEmptyString()',
                '__builing_xmldata6' => '&#getNull()',
                '__builing_xmldata7' => '?&#getEmptyString()',
                '__builing_xmldata8' => '?&#getNull()'
            )
        )
    );
    $dom->formatOutput = true;
    print $dom->saveXML();
    
    function getEmptyString()
    {
        return '';
    }
    
    function getNull()
    {
        return null;
    }
?>
--EXPECT--
<?xml version="1.0" encoding="UTF-8"?>
<music_stores>
  <store>
    <storeid>1</storeid>
    <country>US</country>
    <state>New York</state>
    <city>New York</city>
    <street>Broadway &amp; 72nd Str</street>
    <phone>123 456 7890</phone>
    <builing_xmldata/>
    <builing_xmldata2/>
  </store>
  <store>
    <storeid>2</storeid>
    <country>US</country>
    <state>New York</state>
    <city>Larchmont</city>
    <street>Palmer Ave 71</street>
    <phone>456 7890</phone>
    <builing_xmldata/>
    <builing_xmldata2/>
  </store>
</music_stores>