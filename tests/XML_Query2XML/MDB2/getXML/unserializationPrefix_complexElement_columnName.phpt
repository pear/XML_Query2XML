--TEST--
XML_Query2XML::getXML(): unserialization prefix with column name within complex element specification
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
                'builing_xmldata' => array(
                    'value' => '&builing_xmldata'
                ),
                'builing_xmldata2' => array(
                    'rowTag' => '__container',
                    'value' => '&builing_xmldata'
                ),
                'builing_xmldata3' => array(
                    'value' => '?&builing_xmldata'
                ),
                '__builing_xmldata4' => array(
                    'value' => '&builing_xmldata'
                ),
                'builing_xmldata5' => array(
                    'rowTag' => '__builing_xmldata5',
                    'value' => '&builing_xmldata'
                )
            )
        )
    );
    $dom->formatOutput = true;
    print $dom->saveXML();
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
    <builing_xmldata>
      <building>
        <floors>4</floors>
        <elevators>2</elevators>
        <square_meters>3200</square_meters>
      </building>
    </builing_xmldata>
    <building>
      <floors>4</floors>
      <elevators>2</elevators>
      <square_meters>3200</square_meters>
    </building>
    <builing_xmldata3>
      <building>
        <floors>4</floors>
        <elevators>2</elevators>
        <square_meters>3200</square_meters>
      </building>
    </builing_xmldata3>
    <building>
      <floors>4</floors>
      <elevators>2</elevators>
      <square_meters>3200</square_meters>
    </building>
    <building>
      <floors>4</floors>
      <elevators>2</elevators>
      <square_meters>3200</square_meters>
    </building>
  </store>
  <store>
    <storeid>2</storeid>
    <country>US</country>
    <state>New York</state>
    <city>Larchmont</city>
    <street>Palmer Ave 71</street>
    <phone>456 7890</phone>
    <builing_xmldata>
      <building>
        <floors>2</floors>
        <elevators>1</elevators>
        <square_meters>400</square_meters>
      </building>
    </builing_xmldata>
    <building>
      <floors>2</floors>
      <elevators>1</elevators>
      <square_meters>400</square_meters>
    </building>
    <builing_xmldata3>
      <building>
        <floors>2</floors>
        <elevators>1</elevators>
        <square_meters>400</square_meters>
      </building>
    </builing_xmldata3>
    <building>
      <floors>2</floors>
      <elevators>1</elevators>
      <square_meters>400</square_meters>
    </building>
    <building>
      <floors>2</floors>
      <elevators>1</elevators>
      <square_meters>400</square_meters>
    </building>
  </store>
</music_stores>