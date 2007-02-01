--TEST--
XML_Query2XML::_applyColumnStringToRecord(): check for XML_Query2XML_ConfigException - using the callback interface for data specification
--SKIPIF--
<?php require_once dirname(dirname(__FILE__)) . '/skipif.php'; ?>
--FILE--
<?php
    require_once 'XML/Query2XML.php';
    require_once 'XML/Query2XML/Callback.php';
    require_once 'MDB2.php';
    class Test {}
    
    $query2xml = XML_Query2XML::factory(MDB2::factory('mysql://root@localhost/Query2XML_Tests'));
    try {
        $dom =& $query2xml->getXML(
            "SELECT
                *
             FROM
                artist",
            array(
                'rootTag' => 'music_store',
                'rowTag' => 'artist',
                'idColumn' => 'artistid',
                'elements' => array(
                    'artistid',
                    'name',
                    'albums' => array(
                        'idColumn' => 'albumid',
                        'sql' => array(
                            'data' => array(
                                new Test()
                            ),
                            'query' => 'SELECT * FROM album WHERE artist_id = ?'
                        ),
                        'elements' => array(
                            'title'
                        )
                    )
                )
            )
        );
    } catch (XML_Query2XML_ConfigException $e) {
        print $e->getMessage();
    }
?>
--EXPECT--
[elements][albums][sql]: "data" was not specified using a string, an array or an instance of XML_Query2XML_Callback
