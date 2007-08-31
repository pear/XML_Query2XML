--TEST--
XML_Query2XML::_addDOMChildren(): check for XML_Query2XML_XMLException when returning array with integeter from callback
--SKIPIF--
<?php require_once dirname(dirname(__FILE__)) . '/skipif.php'; ?>
--FILE--
<?php
    require_once 'XML/Query2XML.php';
    require_once dirname(dirname(__FILE__)) . '/db_init.php';
    try {
        $query2xml =& XML_Query2XML::factory($db);
        $query2xml->getXML(
            "SELECT
                *
             FROM
                artist",
            array(
                'rootTag' => 'music_library',
                'rowTag' => 'artist',
                'idColumn' => 'artistid',
                'elements' => array(
                    'artistid',
                    'name',
                    'birth_year',
                    'birth_place',
                    'genre' => '#getNewArray()'
                )
            )
        );
    } catch (XML_Query2XML_XMLException $e) {
        echo get_class($e) . ': ' . substr($e->getMessage(), 0, 101);
    }
    
function getNewArray()
{
    return array(2);
}
class Test {}
?>
--EXPECT--
XML_Query2XML_XMLException: The array argument passed to XML_Query2XML::_addDOMChildren() has an element of a wrong type: integer