<?php
/**This file contains the class Query2XMLTestMDB2.
*
* LICENSE:
* This source file is subject to version 2.1 of the LGPL
* that is bundled with this package in the file LICENSE.
*
* COPYRIGHT:
* Empowered Media
* http://www.empoweredmedia.com
* 481 Eighth Avenue Suite 1530
* New York, NY 10001
*
* @copyright Empowered Media 2006
* @license http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
* @author Lukas Feiler <lukas.feiler@lukasfeiler.com>
* @version $Id$
*/

// Call Query2XMLTestMDB2::main() if this source file is executed directly.
if (!defined("PHPUnit2_MAIN_METHOD")) {
    define("PHPUnit2_MAIN_METHOD", "Query2XMLTestMDB2::main");
}

require_once "PHPUnit2/Framework/TestCase.php";
require_once "PHPUnit2/Framework/TestSuite.php";

require_once "XML/Query2XML.php";
require_once "XML/Query2XML/ISO9075Mapper.php";

/**Test class for XML_Query2XML that requires PEAR MDB2.
* 
* @author Lukas Feiler <lukas.feiler@lukasfeiler.com>
* @version Release: @package_version@
* @copyright Empowered Media 2006
*/
class Query2XMLTestMDB2 extends PHPUnit2_Framework_TestCase
{
    /**Runs the test methods of this class.
    */
    public static function main() {
        require_once "PHPUnit2/TextUI/TestRunner.php";

        $suite  = new PHPUnit2_Framework_TestSuite("Query2XMLTestMDB2");
        $result = PHPUnit2_TextUI_TestRunner::run($suite);
    }
    
    private function _getXML($sql, $options)
    {
        $dom = $this->query2xml->getXML($sql, $options);
        return $dom->saveXML();
    }
    
    public function setUp()
    {
        require_once('MDB2.php');
        $mdb2 = MDB2::factory('mysql://root@localhost/Query2XML_Tests');
        $this->query2xml =& XML_Query2XML::factory($mdb2);
    }
    
    public function testFactoryMDBErrorException()
    {
        try {
            require_once('MDB2.php');
            $mdb2 = MDB2::factory('mysql://bogususer@256.256.256.256/bugusdb');
            $query2xml =& XML_Query2XML::factory($mdb2);
            //MDB2::factory does not return a PEAR error.
            self::assertTrue(true);
        } catch (XML_Query2XML_DBException $e) {
            self::assertTrue(false);
        }
    }
    
    public function testGetFlatXMLSpecialMDB2Exception()
    {
        try {
            require_once('MDB2.php');
            $mdb2 = MDB2::factory('mysql://bogususer@256.256.256.256/bugusdb');
            //MDB2::factory does not return a PEAR error.
            $query2xml =& XML_Query2XML::factory($mdb2);

            $dom =& $query2xml->getFlatXML("SELECT * FROM artist", 'music_library', 'artist');
            //exception should have been thrown
            self::assertFalse(true);
        } catch (XML_Query2XML_DBException $e) {
            self::assertEquals(
                'Could not run the following SQL query: SELECT * FROM artist; [mdb2_error: message="MDB2 Error: connect failed" code=-24 mode=return level=notice prefix="" info="[Error message: Unknown MySQL server host \'256.256.256.256\' (11001)]
"]',
                $e->getMessage()
            );
        }
    }
    
    public function testGetXMLSpecialMDB2Exception()
    {
        try {
            require_once('MDB2.php');
            $mdb2 = MDB2::factory('mysql://bogususer@256.256.256.256/bugusdb');
            //MDB2::factory does not return a PEAR error.
            $query2xml = XML_Query2XML::factory($mdb2);
            $xml = $query2xml->getXML(
                "SELECT
                    *
                 FROM
                    artist
                    LEFT JOIN album ON album.artist_id = artist.artistid",
                array(
                    'rootTag' => 'music_library',
                    'rowTag' => 'artist',
                    'idColumn' => 'artistid',
                    'elements' => array(
                        'artistid',
                        'name',
                        'birth_year',
                        'birth_place',
                        'genre',
                        'albums' => array(
                            'rootTag' => 'albums',
                            'rowTag' => 'album',
                            'idColumn' => 'albumid',
                            'elements' => array(
                                'albumid',
                                'title',
                                'published_year',
                                'comment'
                            )
                        )
                    )
                )
            );
            self::assertFalse(true);
        } catch (XML_Query2XML_DBException $e) {
            self::assertEquals(
                'Could not run the following SQL query: SELECT
                    *
                 FROM
                    artist
                    LEFT JOIN album ON album.artist_id = artist.artistid; [mdb2_error: message="MDB2 Error: connect failed" code=-24 mode=return level=notice prefix="" info="[Error message: Unknown MySQL server host \'256.256.256.256\' (11001)]
"]',
                $e->getMessage()
            );
        }
    }
    
    public function testGetFlatXML()
    {
        $dom =& $this->query2xml->getFlatXML('SELECT * FROM album');
        self::assertTrue(md5($dom->saveXML()) === '1235dd9cada59f43cee0f6d0b4829b27');
    }
    
    public function test_createDOMElementException()
    {
        try {
            $xml = $this->_getXML(
                "SELECT
                    *
                 FROM
                    artist
                    LEFT JOIN album ON album.artist_id = artist.artistid",
                array(
                    'rootTag' => 'music_library',
                    'rowTag' => 'artist',
                    'idColumn' => 'artistid',
                    'elements' => array(
                        'artistid',
                        'name',
                        'birth_year',
                        'birth_place',
                        'genre',
                        'albums' => array(
                            'rootTag' => 'albums',
                            'rowTag' => 'album',
                            'idColumn' => 'albumid',
                            'elements' => array(
                                'albumid ' => 'albumid',
                                'title',
                                'published_year',
                                'comment'
                            )
                        )
                    )
                )
            );
            self::assertFalse(true);
        } catch(XML_Query2XML_XMLException $e) {
            self::assertEquals('"albumid " is an invalid XML element name: Invalid Character Error', $e->getMessage());
        }
    }
    
    public function test_prepareAndExecuteExecuteQueryDBException()
    {
        try {
            $xml = $this->_getXML(
                "SELECT
                    *
                 FROM
                    artist
                    LEFT JOIN album ON album.artist_id = artist.artistid",
                array(
                    'rootTag' => 'music_library',
                    'rowTag' => 'artist',
                    'idColumn' => 'artistid',
                    'elements' => array(
                        'artistid',
                        'name',
                        'birth_year',
                        'birth_place',
                        'genre',
                        'albums' => array(
                            'sql' => array(
                                'query' => 'SELECT * FROM non_existing_table'
                            ),
                            'rootTag' => 'albums',
                            'rowTag' => 'album',
                            'idColumn' => 'albumid',
                            'elements' => array(
                                'albumid' => 'albumid',
                                'title',
                                'published_year',
                                'comment'
                            )
                        )
                    )
                )
            );
            self::assertFalse(true);
        } catch(XML_Query2XML_DBException $e) {
            self::assertEquals(
                'Could not execute the following SQL query: SELECT * FROM non_existing_table; [mdb2_error: message="MDB2 Error: no such table" code=-18 mode=return level=notice prefix="" info="[Last query: SELECT * FROM non_existing_table]
[Native code: 1146]
[Native message: Table \'query2xml_tests.non_existing_table\' doesn\'t exist]
"]',
                $e->getMessage()
            );
        }
    }
    
    public function test_prepareAndExecuteSimpleQueryDBException()
    {
        try {
            $xml = $this->_getXML(
                "SELECT
                    *
                 FROM
                    artist
                    LEFT JOIN album ON album.artist_id = artist.artistid",
                array(
                    'rootTag' => 'music_library',
                    'rowTag' => 'artist',
                    'idColumn' => 'artistid',
                    'elements' => array(
                        'artistid',
                        'name',
                        'birth_year',
                        'birth_place',
                        'genre',
                        'albums' => array(
                            'sql' => 'SELECT * FROM non_existing_table',
                            'rootTag' => 'albums',
                            'rowTag' => 'album',
                            'idColumn' => 'albumid',
                            'elements' => array(
                                'albumid ' => 'albumid',
                                'title',
                                'published_year',
                                'comment'
                            )
                        )
                    )
                )
            );
            self::assertFalse(true);
        } catch(XML_Query2XML_DBException $e) {
            self::assertEquals(
                'Could not run the following SQL query: SELECT * FROM non_existing_table; [mdb2_error: message="MDB2 Error: no such table" code=-18 mode=return level=notice prefix="" info="[Last query: SELECT * FROM non_existing_table]
[Native code: 1146]
[Native message: Table \'query2xml_tests.non_existing_table\' doesn\'t exist]
"]',
                $e->getMessage()
            );
        }
    }
    
    public function test_applyColumnStringToRecordException()
    {
        try {
            $xml = $this->_getXML(
                "SELECT
                    *
                 FROM
                    artist
                    LEFT JOIN album ON album.artist_id = artist.artistid",
                array(
                    'rootTag' => 'music_library',
                    'rowTag' => 'artist',
                    'idColumn' => 'artistid',
                    'elements' => array(
                        'artistid',
                        'name',
                        'birth_year',
                        'birth_place',
                        'genre',
                        'albums' => array(
                            'rootTag' => 'albums',
                            'rowTag' => 'album',
                            'idColumn' => 'albumid',
                            'elements' => array(
                                'albumid' => 'albumid ',
                                'title',
                                'published_year',
                                'comment'
                            )
                        )
                    )
                )
            );
            self::assertFalse(true);
        } catch(XML_Query2XML_ConfigException $e) {
            self::assertEquals(
                '[elements][albums]: The column "albumid " used in the option "elements" does not exist in the result set.',
                $e->getMessage()
            );
        }
    }
    
    public function test_applySqlOptionsToRecordMergeException2()
    {
        try {
            $xml = $this->_getXML(
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
                        'genre',
                        'albums' => array(
                            'sql' => array(
                                'data' => array(
                                    'artistid'
                                ),
                                'query' => 'SELECT * FROM album WHERE artist_id = ?'
                            ),
                            'sql_options' => array(
                                'merge_master' => false,
                                'merge_selective' => array('genre ')
                            ),
                            'rootTag' => 'albums',
                            'rowTag' => 'album',
                            'idColumn' => 'albumid',
                            'elements' => array(
                                'albumid',
                                'title',
                                'published_year',
                                'comment'
                            )
                        )
                    )
                )
            );
            self::assertTrue(false);
        } catch (Exception $e) {
            self::assertEquals(
                '[elements][albums][sql_options]: The column "genre " used in the option "merge_selective" does not exist in the result set.',
                $e->getMessage()
            );
        }
    }
    
    public function test_applySqlOptionsToRecordMergeException1()
    {
        try {
            $xml = $this->_getXML(
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
                        'genre',
                        'albums' => array(
                            'sql' => array(
                                'data' => array(
                                    'artistid'
                                ),
                                'query' => 'SELECT * FROM album WHERE artist_id = ?'
                            ),
                            'sql_options' => array(
                                'merge_master' => true,
                                'merge_selective' => array('genre ')
                            ),
                            'rootTag' => 'albums',
                            'rowTag' => 'album',
                            'idColumn' => 'albumid',
                            'elements' => array(
                                'albumid',
                                'title',
                                'published_year',
                                'comment'
                            )
                        )
                    )
                )
            );
            self::assertTrue(false);
        } catch (Exception $e) {
            self::assertEquals(
                '[elements][albums][sql_options]: The column "genre " used in the option "merge_selective" does not exist in the result set.',
                $e->getMessage()
            );
        }
    }
    
    public function test_applySqlOptionsToRecordWrongDataTypeException()
    {
        try {
            $xml = $this->_getXML(
                "SELECT
                    *
                 FROM
                    artist
                    LEFT JOIN album ON album.artist_id = artist.artistid",
                array(
                    'rootTag' => 'music_library',
                    'rowTag' => 'artist',
                    'idColumn' => 'artistid',
                    'elements' => array(
                        'artistid',
                        'name',
                        'birth_year',
                        'birth_place',
                        'genre',
                        'albums' => array(
                            'sql' => array(
                                'data' => 1,
                                'query' => 'SELECT * FROM some_table'
                            ),
                            'rootTag' => 'albums',
                            'rowTag' => 'album',
                            'idColumn' => 'albumid',
                            'elements' => array(
                                'albumid' => 'albumid',
                                'title',
                                'published_year',
                                'comment'
                            )
                        )
                    )
                )
            );
            self::assertFalse(true);
        } catch(XML_Query2XML_ConfigException $e) {
            self::assertEquals('[elements][albums][sql]: The configuration option "data" is not an array.', $e->getMessage());
        }
    }
    
    public function test_applySqlOptionsToRecordWrongQueryTypeException()
    {
        try {
            $xml = $this->_getXML(
                "SELECT
                    *
                 FROM
                    artist
                    LEFT JOIN album ON album.artist_id = artist.artistid",
                array(
                    'rootTag' => 'music_library',
                    'rowTag' => 'artist',
                    'idColumn' => 'artistid',
                    'elements' => array(
                        'artistid',
                        'name',
                        'birth_year',
                        'birth_place',
                        'genre',
                        'albums' => array(
                            'sql' => 1,
                            'rootTag' => 'albums',
                            'rowTag' => 'album',
                            'idColumn' => 'albumid',
                            'elements' => array(
                                'albumid' => 'albumid',
                                'title',
                                'published_year',
                                'comment'
                            )
                        )
                    )
                )
            );
            self::assertFalse(true);
        } catch(XML_Query2XML_ConfigException $e) {
            self::assertEquals('[elements][albums]: The configuration option "sql" is not an array or a string.', $e->getMessage());
        }
    }
    
    public function test_applySqlOptionsToRecordMissingQueryException()
    {
        try {
            $xml = $this->_getXML(
                "SELECT
                    *
                 FROM
                    artist
                    LEFT JOIN album ON album.artist_id = artist.artistid",
                array(
                    'rootTag' => 'music_library',
                    'rowTag' => 'artist',
                    'idColumn' => 'artistid',
                    'elements' => array(
                        'artistid',
                        'name',
                        'birth_year',
                        'birth_place',
                        'genre',
                        'albums' => array(
                            'sql' => array(),
                            'rootTag' => 'albums',
                            'rowTag' => 'album',
                            'idColumn' => 'albumid',
                            'elements' => array(
                                'albumid' => 'albumid',
                                'title',
                                'published_year',
                                'comment'
                            )
                        )
                    )
                )
            );
            self::assertFalse(true);
        } catch(XML_Query2XML_ConfigException $e) {
            self::assertEquals('[elements][albums][sql]: The configuration option "query" is missing.', $e->getMessage());
        }
    }
    
    public function test_applySqlOptionsToRecordMergeSelectiveTypeException()
    {
        try {
            $xml = $this->_getXML(
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
                        'genre',
                        'albums' => array(
                            'sql' => array(
                                'data' => array(
                                    'artistid'
                                ),
                                'query' => 'SELECT * FROM album WHERE artist_id = ?'
                            ),
                            'sql_options' => array(
                                'merge_master' => true,
                                'merge_selective' => 1
                            ),
                            'rootTag' => 'albums',
                            'rowTag' => 'album',
                            'idColumn' => 'albumid',
                            'elements' => array(
                                'albumid',
                                'title',
                                'published_year',
                                'comment'
                            )
                        )
                    )
                )
            );
            self::assertTrue(false);
        } catch (Exception $e) {
            self::assertEquals(
                '[elements][albums][sql_options]: The configuration option "merge_selective" is not an array.',
                $e->getMessage()
            );
        }
    }
    
    public function test_processComplexElementSpecificationRETHROW()
    {
        try {
            $xml = $this->_getXML(
                "SELECT
                    *
                 FROM
                    artist",
                array(
                    'rootTag' => 'music_library',
                    'rowTag' => 'artist',
                    'idColumn' => 'artistid',
                    'elements' => array(
                        'albums' => array(
                            'elements' => array(
                                'sub1' => array(
                                    'elements' => array(
                                        'sub2' => array(
                                            'elements' => array(
                                                'sub3' => array(
                                                    'sql' => array(
                                                        'data' => 1,
                                                        'query' => 'SELECT * FROM album WHERE artist_id = ?'
                                                    ),
                                                )
                                            )
                                        )
                                    )
                                )
                            )
                        )
                    )
                )
            );
            self::assertTrue(false);
        } catch (Exception $e) {
            self::assertEquals(
                '[elements][albums][elements][sub1][elements][sub2][elements][sub3][sql]: The configuration option "data" is not an array.',
                $e->getMessage()
            );
        }
    }
    
    public function test_processComplexAttributeSpecificationMissingValueException()
    {
        try {
            $xml = $this->_getXML(
                "SELECT
                    *
                 FROM
                    artist",
                array(
                    'rootTag' => 'music_library',
                    'rowTag' => 'artist',
                    'idColumn' => 'artistid',
                    'attributes' => array(
                        '*' => '*',
                        'genre' => array(
                            'condition' => '$record["genre"] == "Soul"',
                        )
                    )
                )
            );
            self::assertFalse(true);
        } catch(XML_Query2XML_ConfigException $e) {
            self::assertEquals(
                '[attributes][genre]: The option "value" is missing from the complex attribute specification',
                $e->getMessage()
            );
        }
    }
    
    public function test_getNestedXMLRecordAttributesTypeException()
    {
        try {
            $xml = $this->_getXML(
                "SELECT
                    *
                 FROM
                    artist
                    LEFT JOIN album ON album.artist_id = artist.artistid",
                array(
                    'rootTag' => 'music_library',
                    'rowTag' => 'artist',
                    'idColumn' => 'artistid',
                    'elements' => array(
                        'artistid',
                        'name',
                        'birth_year',
                        'birth_place',
                        'genre',
                        'albums' => array(
                            'rootTag' => 'albums',
                            'rowTag' => 'album',
                            'idColumn' => 'albumid',
                            'attributes' => 1
                        )
                    )
                )
            );
            self::assertFalse(true);
        } catch(XML_Query2XML_ConfigException $e) {
            self::assertEquals(
                '[elements][albums]: The configuration option "attributes" is not an array.',
                $e->getMessage()
            );
        }
    }
        
    public function test_getNestedXMLRecordElementsTypeException()
    {
        try {
            $xml = $this->_getXML(
                "SELECT
                    *
                 FROM
                    artist
                    LEFT JOIN album ON album.artist_id = artist.artistid",
                array(
                    'rootTag' => 'music_library',
                    'rowTag' => 'artist',
                    'idColumn' => 'artistid',
                    'elements' => array(
                        'artistid',
                        'name',
                        'birth_year',
                        'birth_place',
                        'genre',
                        'albums' => array(
                            'rootTag' => 'albums',
                            'rowTag' => 'album',
                            'idColumn' => 'albumid',
                            'elements' => 1
                        )
                    )
                )
            );
            self::assertFalse(true);
        } catch(XML_Query2XML_ConfigException $e) {
            self::assertEquals(
                '[elements][albums]: The configuration option "elements" is not an array.',
                $e->getMessage()
            );
        }
    }
    
    public function test_getNestedXMLRecordIdColumnMissingException()
    {
        try {
            $xml = $this->_getXML(
                "SELECT
                    *
                 FROM
                    artist
                    LEFT JOIN album ON album.artist_id = artist.artistid",
                array(
                    'rootTag' => 'music_library',
                    'rowTag' => 'artist',
                    'idColumn ' => 'artistid',
                    'elements' => array(
                        'artistid',
                        'name',
                        'birth_year',
                        'birth_place',
                        'genre',
                        'albums' => array(
                            'rootTag' => 'albums',
                            'rowTag' => 'album',
                            'idColumn' => 'albumid',
                            'elements' => array(
                                'albumid',
                                'title',
                                'published_year',
                                'comment'
                            )
                        )
                    )
                )
            );
            self::assertTrue(false);
        } catch (XML_Query2XML_ConfigException $e) {
            self::assertEquals('The configuration option "idColumn" is missing.', $e->getMessage());
        }
    }

    public function test_getNestedXMLRecordInvalidAttributeException()
    {
        try {
            $xml = $this->_getXML(
                "SELECT
                    *
                 FROM
                    artist",
                array(
                    'rootTag' => 'music_library',
                    'rowTag' => 'artist',
                    'idColumn' => 'artistid',
                    'attributes' => array(
                        '*' => '*',
                        'genre' => 3
                    )
                )
            );
            self::assertTrue(false);
        } catch (XML_Query2XML_ConfigException $e) {
            self::assertEquals(
                '[attributes]: The attribute "genre" was not specified using a string nor an array',
                $e->getMessage()
            );
        }
    }
    
    public function test_mapSQLIdentifierToXMLNameNotCallableException()
    {
        try {
            $dom =& $this->_getXML(
                "SELECT
                    *
                 FROM
                    artist
                    LEFT JOIN album ON album.artist_id = artist.artistid",
                array(
                    'rootTag' => 'music_library',
                    'rowTag' => 'artist',
                    'idColumn' => 'artistid',
                    'mapper' => 'No:suchMethod',
                    'elements' => array(
                        'artistid',
                        'name',
                        'birth_year',
                        'birth_place',
                        'genre',
                        'albums' => array(
                            'rootTag' => 'albums',
                            'rowTag' => 'album',
                            'idColumn' => 'albumid',
                            'elements' => array(
                                '*'
                            )
                        )
                    )
                )
            );
            self::assertTrue(false);
        } catch (XML_Query2XML_ConfigException $e) {
            self::assertEquals(
                'The method/function "No:suchMethod" specified in the configuration option "mapper" is not callable.',
                $e->getMessage()
            );
        }
    }
    
    public function test_mapSQLIdentifierToXMLNameNotCallableException2()
    {
        try {
            $dom =& $this->_getXML(
                "SELECT
                    *
                 FROM
                    artist
                    LEFT JOIN album ON album.artist_id = artist.artistid",
                array(
                    'rootTag' => 'music_library',
                    'rowTag' => 'artist',
                    'idColumn' => 'artistid',
                    'elements' => array(
                        'artistid',
                        'name',
                        'birth_year',
                        'birth_place',
                        'genre',
                        'albums' => array(
                            'rootTag' => 'albums',
                            'rowTag' => 'album',
                            'idColumn' => 'albumid',
                            'mapper' => 'No:suchMethod',
                            'elements' => array(
                                '*'
                            )
                        )
                    )
                )
            );
            self::assertTrue(false);
        } catch (XML_Query2XML_ConfigException $e) {
            self::assertEquals(
                '[elements][albums]: The method/function "No:suchMethod" specified in the configuration option "mapper" is not callable.',
                $e->getMessage()
            );
        }
    }
    
    public function test_mapSQLIdentifierToXMLNameNotCallableException3()
    {
        try {
            $dom =& $this->_getXML(
                "SELECT
                    *
                 FROM
                    artist
                    LEFT JOIN album ON album.artist_id = artist.artistid",
                array(
                    'rootTag' => 'music_library',
                    'rowTag' => 'artist',
                    'idColumn' => 'artistid',
                    'elements' => array(
                        'artistid',
                        'name',
                        'birth_year',
                        'birth_place',
                        'genre',
                        'albums' => array(
                            'rootTag' => 'albums',
                            'rowTag' => 'album',
                            'idColumn' => 'albumid',
                            'mapper' => array($this, 'noSuchMethod'),
                            'elements' => array(
                                '*'
                            )
                        )
                    )
                )
            );
            self::assertTrue(false);
        } catch (XML_Query2XML_ConfigException $e) {
            self::assertEquals(
                '[elements][albums]: The method/function "Query2XMLTestMDB2::noSuchMethod" specified in the configuration option "mapper" is not callable.',
                $e->getMessage()
            );
        }
    }
    
    public function test_mapSQLIdentifierToXMLNameNotMappable()
    {
        try {
            $dom =& $this->_getXML(
                "SELECT
                    *
                 FROM
                    artist
                    LEFT JOIN album ON album.artist_id = artist.artistid",
                array(
                    'rootTag' => 'music_library',
                    'rowTag' => 'artist',
                    'idColumn' => 'artistid',
                    'mapper' => array('Helper', 'throwException'),
                    'elements' => array(
                        'artistid',
                        'name',
                        'birth_year',
                        'birth_place',
                        'genre',
                        'albums' => array(
                            'rootTag' => 'albums',
                            'rowTag' => 'album',
                            'idColumn' => 'albumid',
                            'elements' => array(
                                '*'
                            )
                        )
                    )
                )
            );
            self::assertTrue(false);
        } catch (XML_Query2XML_XMLException $e) {
            self::assertEquals(
                'Could not map "artistid" to an XML name using the mapper Helper::throwException: Throwing exception for artistid',
                $e->getMessage()
            );
        }
    }
    
    public function testGetFlatXMLException()
    {
        try {
            $dom =& $this->query2xml->getFlatXML('SELECT * FROM non_existing_table');
            self::assertTrue(false);
        } catch (XML_Query2XML_DBException $e) {
            //did throw exception
            self::assertTrue(true);
        }
    }
    
    public function testGetXMLException()
    {
        try {
            $xml = $this->_getXML(
                "SELECT
                    *
                 FROM
                    artist
                    LEFT JOIN non_existing_table ON non_existing_table.artist_id = artist.artistid",
                array(
                    'rootTag' => 'music_library',
                    'rowTag' => 'artist',
                    'idColumn' => 'artistid',
                    'elements' => array(
                        'artistid',
                        'name',
                        'birth_year',
                        'birth_place',
                        'genre',
                        'albums' => array(
                            'rootTag' => 'albums',
                            'rowTag' => 'album',
                            'idColumn' => 'albumid',
                            'elements' => array(
                                'albumid',
                                'title',
                                'published_year',
                                'comment'
                            )
                        )
                    )
                )
            );
            self::assertTrue(false);
        } catch (XML_Query2XML_DBException $e) {
            //did throw exception
            self::assertTrue(true);
        }
    }

    
    
    public function testGetXMLExceptionWrongRootTag()
    {
        try {
            $xml = $this->_getXML(
                "SELECT
                    *
                 FROM
                    artist
                    LEFT JOIN album ON album.artist_id = artist.artistid",
                array(
                    'rootTag' => 'music_library ',
                    'rowTag' => 'artist',
                    'idColumn' => 'artistid',
                    'elements' => array(
                        'artistid',
                        'name',
                        'birth_year',
                        'birth_place',
                        'genre',
                        'albums' => array(
                            'rootTag' => 'albums',
                            'rowTag' => 'album',
                            'idColumn' => 'albumid',
                            'elements' => array(
                                'albumid',
                                'title',
                                'published_year',
                                'comment'
                            )
                        )
                    )
                )
            );
            self::assertTrue(false);
        } catch (XML_Query2XML_XMLException $e) {
            self::assertEquals('"music_library " is an invalid XML element name: Invalid Character Error', $e->getMessage());
        }
    }
    
    public function testGetXMLExceptionWrongRowTag()
    {
        try {
            $xml = $this->_getXML(
                "SELECT
                    *
                 FROM
                    artist
                    LEFT JOIN album ON album.artist_id = artist.artistid",
                array(
                    'rootTag' => 'music_library',
                    'rowTag' => 'artist ',
                    'idColumn' => 'artistid',
                    'elements' => array(
                        'artistid',
                        'name',
                        'birth_year',
                        'birth_place',
                        'genre',
                        'albums' => array(
                            'rootTag' => 'albums',
                            'rowTag' => 'album',
                            'idColumn' => 'albumid',
                            'elements' => array(
                                'albumid',
                                'title',
                                'published_year',
                                'comment'
                            )
                        )
                    )
                )
            );
            self::assertTrue(false);
        } catch (XML_Query2XML_XMLException $e) {
            self::assertEquals('"artist " is an invalid XML element name: Invalid Character Error', $e->getMessage());
        }
    }
    
    public function testGetXMLExceptionWrongIdColumn()
    {
        try {
            $xml = $this->_getXML(
                "SELECT
                    *
                 FROM
                    artist
                    LEFT JOIN album ON album.artist_id = artist.artistid",
                array(
                    'rootTag' => 'music_library',
                    'rowTag' => 'artist',
                    'idColumn' => 'artistid ',
                    'elements' => array(
                        'artistid',
                        'name',
                        'birth_year',
                        'birth_place',
                        'genre',
                        'albums' => array(
                            'rootTag' => 'albums',
                            'rowTag' => 'album',
                            'idColumn' => 'albumid',
                            'elements' => array(
                                'albumid',
                                'title',
                                'published_year',
                                'comment'
                            )
                        )
                    )
                )
            );
            self::assertTrue(false);
        } catch (XML_Query2XML_ConfigException $e) {
            self::assertEquals('The column "artistid " used in the option "idColumn" does not exist in the result set.', $e->getMessage());
        }
    }
    
    public function testGetXMLExceptionWrongElementColumnName()
    {
        try {
            $xml = $this->_getXML(
                "SELECT
                    *
                 FROM
                    artist
                    LEFT JOIN album ON album.artist_id = artist.artistid",
                array(
                    'rootTag' => 'music_library',
                    'rowTag' => 'artist',
                    'idColumn' => 'artistid',
                    'elements' => array(
                        'artistid ',
                        'name',
                        'birth_year',
                        'birth_place',
                        'genre',
                        'albums' => array(
                            'rootTag' => 'albums',
                            'rowTag' => 'album',
                            'idColumn' => 'albumid',
                            'elements' => array(
                                'albumid',
                                'title',
                                'published_year',
                                'comment'
                            )
                        )
                    )
                )
            );
            self::assertTrue(false);
        } catch (XML_Query2XML_ConfigException $e) {
            self::assertEquals('The column "artistid " used in the option "elements" does not exist in the result set.', $e->getMessage());
        }
    }
    
    public function testGetXMLExceptionWrongElementTagName()
    {
        try {
            $xml = $this->_getXML(
                "SELECT
                    *
                 FROM
                    artist
                    LEFT JOIN album ON album.artist_id = artist.artistid",
                array(
                    'rootTag' => 'music_library',
                    'rowTag' => 'artist',
                    'idColumn' => 'artistid',
                    'elements' => array(
                        'artistid ' => 'artistid',
                        'name',
                        'birth_year',
                        'birth_place',
                        'genre',
                        'albums' => array(
                            'rootTag' => 'albums',
                            'rowTag' => 'album',
                            'idColumn' => 'albumid',
                            'elements' => array(
                                'albumid',
                                'title',
                                'published_year',
                                'comment'
                            )
                        )
                    )
                )
            );
            self::assertTrue(false);
        } catch (XML_Query2XML_XMLException $e) {
            self::assertEquals('"artistid " is an invalid XML element name: Invalid Character Error', $e->getMessage());
        }
    }
    
    public function testGetXMLExceptionWrongSubRootName()
    {
        try {
            $xml = $this->_getXML(
                "SELECT
                    *
                 FROM
                    artist
                    LEFT JOIN album ON album.artist_id = artist.artistid",
                array(
                    'rootTag' => 'music_library',
                    'rowTag' => 'artist',
                    'idColumn' => 'artistid',
                    'elements' => array(
                        'artistid',
                        'name',
                        'birth_year',
                        'birth_place',
                        'genre',
                        'albums' => array(
                            'rootTag' => 'albums ',
                            'rowTag' => 'album',
                            'idColumn' => 'albumid',
                            'elements' => array(
                                'albumid',
                                'title',
                                'published_year',
                                'comment'
                            )
                        )
                    )
                )
            );
            self::assertTrue(false);
        } catch (XML_Query2XML_XMLException $e) {
            self::assertEquals('"albums " is an invalid XML element name: Invalid Character Error', $e->getMessage());
        }
    }
    
    public function testGetFlatXMLCase1()
    {
        $dom =& $this->query2xml->getFlatXML("SELECT * FROM artist", 'music_library', 'artist');
        self::assertTrue(md5($dom->saveXML()) === '0c33b7de1a587b4c2b436e7e495f1cf4');
    }
    
    public function testGetXMLCase2()
    {
        $xml = $this->_getXML(
            "SELECT
                *
             FROM
                artist
                LEFT JOIN album ON album.artist_id = artist.artistid",
            array(
                'rootTag' => 'music_library',
                'rowTag' => 'artist',
                'idColumn' => 'artistid',
                'elements' => array(
                    'artistid',
                    'name',
                    'birth_year',
                    'birth_place',
                    'genre',
                    'albums' => array(
                        'rootTag' => 'albums',
                        'rowTag' => 'album',
                        'idColumn' => 'albumid',
                        'elements' => array(
                            'albumid',
                            'title',
                            'published_year',
                            'comment'
                        )
                    )
                )
            )
        );
        self::assertTrue(md5($xml) === 'fb5ee5b2446fb9a4868a85b690fcb0b4');
    }
        
    public function testGetXMLCase3()
    {
        $xml = $this->_getXML(
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
                    'genre',
                    'albums' => array(
                        'sql' => array(
                            'data' => array(
                                'artistid'
                            ),
                            'query' => 'SELECT * FROM album WHERE artist_id = ?'
                        ),
                        'sql_options' => array(
                            'uncached'      => true,
                            'single_record' => false,
                            'merge'         => false,
                            'merge_master'  => false
                        ),
                        'rootTag' => 'albums',
                        'rowTag' => 'album',
                        'idColumn' => 'albumid',
                        'elements' => array(
                            'albumid',
                            'title',
                            'published_year',
                            'comment'
                        )
                    )
                )
            )
        );
        self::assertTrue(md5($xml) === 'fb5ee5b2446fb9a4868a85b690fcb0b4');
    }
    
    public function testGetXMLCase4()
    {
        $xml = $this->_getXML(
            "SELECT
                *
             FROM
                artist",
            array(
                'rootTag' => 'MUSIC_LIBRARY',
                'rowTag' => 'ARTIST',
                'idColumn' => 'artistid',
                'elements' => array(
                    'NAME' => 'name',
                    'BIRTH_YEAR' => 'birth_year',
                    'BIRTH_YEAR_TWO_DIGIT' => "!return substr(\"{\$record['birth_year']}\", 2);",
                    'BIRTH_PLACE' => 'birth_place',
                    'GENRE' => 'genre',
                    'albums' => array(
                        'sql' => array(
                            'data' => array(
                                'artistid'
                            ),
                            'query' => 'SELECT * FROM album WHERE artist_id = ?'
                        ),
                        'sql_options' => array(
                            'uncached'      => true,
                            'single_record' => false,
                            'merge'         => true,
                            'merge_master'  => false
                        ),
                        'rootTag' => '',
                        'rowTag' => 'ALBUM',
                        'idColumn' => 'albumid',
                        'elements' => array(
                            'TITLE' => 'title',
                            'PUBLISHED_YEAR' => 'published_year',
                            'COMMENT' => 'comment',
                            'GENRE' => 'genre'
                        ),
                        'attributes' => array(
                            'ALBUMID' => 'albumid'
                        )
                    )
                ),
                'attributes' => array(
                    'ARTISTID' => 'artistid',
                    'MAINTAINER' => ':Lukas Feiler'
                )
            )
        );
        self::assertTrue(md5($xml) === '0dc6a0524c80b9dd76bb039f4c41d943');
    }
        
    public function testGetXMLCase5()
    {
        $xml = $this->_getXML(
            "SELECT
                 *
             FROM
                 customer c
                 LEFT JOIN sale s ON c.customerid = s.customer_id
                 LEFT JOIN album al ON s.album_id = al.albumid
                 LEFT JOIN artist ar ON al.artist_id = ar.artistid",
            array(
                'rootTag' => 'music_store',
                'rowTag' => 'customer',
                'idColumn' => 'customerid',
                'elements' => array(
                    'customerid',
                    'first_name',
                    'last_name',
                    'email',
                    'sales' => array(
                        'rootTag' => 'sales',
                        'rowTag' => 'sale',
                        'idColumn' => 'saleid',
                        'elements' => array(
                            'saleid',
                            'timestamp',
                            'date' => "!return substr(\"{\$record['timestamp']}\", 0, strpos(\"{\$record['timestamp']}\", ' '));",
                            'time' => "!return substr(\"{\$record['timestamp']}\", strpos(\"{\$record['timestamp']}\", ' ') + 1);",
                            'album' => array(
                                'rootTag' => '',
                                'rowTag' => 'album',
                                'idColumn' => 'albumid',
                                'elements' => array(
                                    'albumid',
                                    'title',
                                    'published_year',
                                    'comment',
                                    'artist' => array(
                                        'rootTag' => '',
                                        'rowTag' => 'artist',
                                        'idColumn' => 'artistid',
                                        'elements' => array(
                                            'artistid',
                                            'name',
                                            'birth_year',
                                            'birth_place',
                                            'genre'
                                        ) //artist elements
                                    ) //artist array
                                ) //album elements
                            ) //album array
                        ) //sales elements
                    ) //sales array
                ) //root elements
            ) //root
        );
        self::assertTrue(md5($xml) === '24f140329f7137cf083f6ece279a0b25');
    }
        
    public function testGetXMLCase6()
    {
        $xml = $this->_getXML(
            "SELECT
                 s.*,
                 manager.employeeid AS manager_employeeid,
                 manager.employeename AS manager_employeename,
                 d.*,
                 department_head.employeeid AS department_head_employeeid,
                 department_head.employeename AS department_head_employeename,
                 e.*,
                 sa.*,
                 c.*,
                 al.*,
                 ar.*,
                 (SELECT COUNT(*) FROM sale WHERE sale.store_id = s.storeid) AS store_sales,
                 (SELECT
                    COUNT(*)
                  FROM
                    sale, employee, employee_department
                  WHERE
                    sale.employee_id = employee.employeeid
                    AND
                    employee_department.employee_id = employee.employeeid
                    AND
                    employee_department.department_id = d.departmentid
                 ) AS department_sales,
                 (SELECT
                    COUNT(*)
                  FROM
                    employee, employee_department, department
                  WHERE
                    employee_department.employee_id = employee.employeeid
                    AND
                    employee_department.department_id = department.departmentid
                    AND
                    department.store_id = s.storeid
                 ) AS store_employees,
                 (SELECT
                    COUNT(*)
                  FROM
                    employee, employee_department
                  WHERE
                    employee_department.employee_id = employee.employeeid
                    AND
                    employee_department.department_id = d.departmentid
                 ) AS department_employees
             FROM
                 store s
                  LEFT JOIN employee manager ON s.manager = manager.employeeid
                 LEFT JOIN department d ON d.store_id = s.storeid
                  LEFT JOIN employee department_head ON department_head.employeeid = d.department_head
                  LEFT JOIN employee_department ed ON ed.department_id = d.departmentid
                   LEFT JOIN employee e ON e.employeeid = ed.employee_id
                    LEFT JOIN sale sa ON sa.employee_id = e.employeeid
                     LEFT JOIN customer c ON c.customerid = sa.customer_id
                     LEFT JOIN album al ON al.albumid = sa.album_id
                      LEFT JOIN artist ar ON ar.artistid = al.artist_id",
            array(
                'rootTag' => 'music_company',
                'rowTag' => 'store',
                'idColumn' => 'storeid',
                'attributes' => array(
                    'storeid'
                ),
                'elements' => array(
                    'store_sales',
                    'store_employees',
                    'manager' => array(
                        'idColumn' => 'manager_employeeid',
                        'attributes' => array(
                            'manager_employeeid'
                        ),
                        'elements' => array(
                            'manager_employeename'
                        )
                    ),
                    'address' => array(
                        'elements' => array(
                            'country',
                            'state' => '!return Helper::getStatePostalCode($record["state"]);',
                            'city',
                            'street',
                            'phone'
                        )
                    ),
                    'department' => array(
                        'idColumn' => 'departmentid',
                        'attributes' => array(
                            'departmentid'
                        ),
                        'elements' => array(
                            'department_sales',
                            'department_employees',
                            'departmentname',
                            'department_head' => array(
                                'idColumn' => 'department_head_employeeid',
                                'attributes' => array(
                                    'department_head_employeeid'
                                ),
                                'elements' => array(
                                    'department_head_employeename'
                                )
                            ),
                            'employees' => array(
                                'rootTag' => 'employees',
                                'rowTag' => 'employee',
                                'idColumn' => 'employeeid',
                                'attributes' => array(
                                    'employeeid'
                                ),
                                'elements' => array(
                                    'employeename',
                                    'sales' => array(
                                        'rootTag' => 'sales',
                                        'rowTag' => 'sale',
                                        'idColumn' => 'saleid',
                                        'attributes' => array(
                                            'saleid'
                                        ),
                                        'elements' => array(
                                            'timestamp',
                                            'customer' => array(
                                                'idColumn' => 'customerid',
                                                'attributes' => array(
                                                    'customerid'
                                                ),
                                                'elements' => array(
                                                    'first_name',
                                                    'last_name',
                                                    'email'
                                                )
                                            ),
                                            'album' => array(
                                                'idColumn' => 'albumid',
                                                'attributes' => array(
                                                    'albumid'
                                                ),
                                                'elements' => array(
                                                    'title',
                                                    'published_year',
                                                    'comment' => '?!return Helper::summarize($record["comment"], 12);',
                                                    'artist' => array(
                                                        'idColumn' => 'artistid',
                                                        'attributes' => array(
                                                            'artistid'
                                                        ),
                                                        'elements' => array(
                                                            'name',
                                                            'birth_year',
                                                            'birth_place',
                                                            'genre'
                                                        )
                                                    )
                                                ) // album elements
                                            ) //album array
                                        ) //sales elements
                                    ) //sales array
                                ) //employees elements
                            ) //employees array
                        ) //department elements
                    ) // department array
                ) //root elements
            ) //root
        ); //getXML method call
        self::assertTrue(md5($xml) === '8f0ac92039bcacd8845819a7ac3935ac');
    }

    public function testGetXMLCase7()
    {
        $xml = $this->_getXML(
            "SELECT
                *
             FROM
                artist",
            array(
                'rootTag' => 'music_library',
                'rowTag' => 'artist',
                'idColumn' => 'artistid',
                'elements' => array(
                    '*',
                    'albums' => array(
                        'sql' => array(
                            'data' => array(
                                'artistid'
                            ),
                            'query' => 'SELECT * FROM album WHERE artist_id = ?'
                        ),
                        'sql_options' => array(
                            'uncached'      => true,
                            'single_record' => false,
                            'merge'         => false,
                            'merge_master'  => false
                        ),
                        'rootTag' => 'albums',
                        'rowTag' => 'album',
                        'idColumn' => 'albumid',
                        'elements' => array(
                            '*',
                            'artist_id' => '?:'
                        )
                    )
                )
            )
        );
        self::assertTrue(md5($xml) === 'fb5ee5b2446fb9a4868a85b690fcb0b4');
    }
    
    public function testGetXMLCase8()
    {
        $myMappers = new Mappers();
        
        $xml = $this->_getXML(
            "SELECT
                 s.*,
                 manager.employeeid AS manager_employeeid,
                 manager.employeename AS manager_employeename,
                 d.*,
                 department_head.employeeid AS department_head_employeeid,
                 department_head.employeename AS department_head_employeename,
                 e.*,
                 sa.*,
                 c.*,
                 al.*,
                 ar.*,
                 (SELECT COUNT(*) FROM sale WHERE sale.store_id = s.storeid) AS store_sales,
                 (SELECT
                    COUNT(*)
                  FROM
                    sale, employee, employee_department
                  WHERE
                    sale.employee_id = employee.employeeid
                    AND
                    employee_department.employee_id = employee.employeeid
                    AND
                    employee_department.department_id = d.departmentid
                 ) AS department_sales,
                 (SELECT
                    COUNT(*)
                  FROM
                    employee, employee_department, department
                  WHERE
                    employee_department.employee_id = employee.employeeid
                    AND
                    employee_department.department_id = department.departmentid
                    AND
                    department.store_id = s.storeid
                 ) AS store_employees,
                 (SELECT
                    COUNT(*)
                  FROM
                    employee, employee_department
                  WHERE
                    employee_department.employee_id = employee.employeeid
                    AND
                    employee_department.department_id = d.departmentid
                 ) AS department_employees
             FROM
                 store s
                  LEFT JOIN employee manager ON s.manager = manager.employeeid
                 LEFT JOIN department d ON d.store_id = s.storeid
                  LEFT JOIN employee department_head ON department_head.employeeid = d.department_head
                  LEFT JOIN employee_department ed ON ed.department_id = d.departmentid
                   LEFT JOIN employee e ON e.employeeid = ed.employee_id
                    LEFT JOIN sale sa ON sa.employee_id = e.employeeid
                     LEFT JOIN customer c ON c.customerid = sa.customer_id
                     LEFT JOIN album al ON al.albumid = sa.album_id
                      LEFT JOIN artist ar ON ar.artistid = al.artist_id",
            array(
                'rootTag' => 'music_company',
                'rowTag' => 'store',
                'idColumn' => 'storeid',
                'mapper' => 'strtoupper',
                'attributes' => array(
                    'storeid'
                ),
                'elements' => array(
                    'store_sales',
                    'store_employees',
                    'manager' => array(
                        'idColumn' => 'manager_employeeid',
                        'attributes' => array(
                            'manager_employeeid'
                        ),
                        'elements' => array(
                            'manager_employeename'
                        )
                    ),
                    'address' => array(
                        'elements' => array(
                            'country',
                            'state' => '!return Helper::getStatePostalCode($record["state"]);',
                            'city',
                            'street',
                            'phone'
                        )
                    ),
                    'department' => array(
                        'idColumn' => 'departmentid',
                        'mapper' => 'Mappers::departmentMapper',
                        'attributes' => array(
                            'departmentid'
                        ),
                        'elements' => array(
                            'department_sales',
                            'department_employees',
                            'departmentname',
                            'department_head' => array(
                                'idColumn' => 'department_head_employeeid',
                                'attributes' => array(
                                    'department_head_employeeid'
                                ),
                                'elements' => array(
                                    'department_head_employeename'
                                )
                            ),
                            'employees' => array(
                                'rootTag' => 'employees',
                                'rowTag' => 'employee',
                                'idColumn' => 'employeeid',
                                'mapper' => array('Mappers', 'employeeMapper'),
                                'attributes' => array(
                                    'employeeid'
                                ),
                                'elements' => array(
                                    'employeename',
                                    'sales' => array(
                                        'rootTag' => 'sales',
                                        'rowTag' => 'sale',
                                        'idColumn' => 'saleid',
                                        'mapper' => array($myMappers, 'saleMapper'),
                                        'attributes' => array(
                                            'saleid'
                                        ),
                                        'elements' => array(
                                            'timestamp',
                                            'customer' => array(
                                                'idColumn' => 'customerid',
                                                'mapper' => false,
                                                'attributes' => array(
                                                    'customerid'
                                                ),
                                                'elements' => array(
                                                    'first_name',
                                                    'last_name',
                                                    'email'
                                                )
                                            ),
                                            'album' => array(
                                                'idColumn' => 'albumid',
                                                'mapper' => 'XML_Query2XML_ISO9075Mapper::map',
                                                'attributes' => array(
                                                    'albumid'
                                                ),
                                                'elements' => array(
                                                    'title',
                                                    'published_year',
                                                    'comment' => '?!return Helper::summarize($record["comment"], 12);',
                                                    'artist' => array(
                                                        'idColumn' => 'artistid',
                                                        'mapper' => 'mapArtist',
                                                        'attributes' => array(
                                                            'artistid'
                                                        ),
                                                        'elements' => array(
                                                            'name',
                                                            'birth_year',
                                                            'birth_place',
                                                            'genre'
                                                        )
                                                    )
                                                ) // album elements
                                            ) //album array
                                        ) //sales elements
                                    ) //sales array
                                ) //employees elements
                            ) //employees array
                        ) //department elements
                    ) // department array
                ) //root elements
            ) //root
        );
        self::assertTrue(md5($xml) === 'd1e283ed8666a7d91e2965154eb13cc4');
    }
    
    public function testEnableDebugLog()
    {
        $debugLogger = new MyLogger();
        $this->query2xml->enableDebugLog($debugLogger);
        $xml = $this->_getXML(
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
                    'genre',
                    'albums' => array(
                        'sql' => array(
                            'data' => array(
                                'artistid'
                            ),
                            'query' => 'SELECT * FROM album WHERE artist_id = ?'
                        ),
                        'sql_options' => array(
                            'uncached'      => true,
                            'single_record' => false,
                            'merge'         => false,
                            'merge_master'  => false
                        ),
                        'rootTag' => 'albums',
                        'rowTag' => 'album',
                        'idColumn' => 'albumid',
                        'elements' => array(
                            'albumid',
                            'title',
                            'published_year',
                            'comment'
                        )
                    )
                )
            )
        );
        $this->query2xml->disableDebugLog();
        self::assertTrue(md5($debugLogger->data) === '712378b7719c26f8c751f8b7f131272f');
    }

    public function testDisableDebugLog()
    {
        $debugLogger = new MyLogger();
        $this->query2xml->enableDebugLog($debugLogger);
        $this->query2xml->disableDebugLog();
        $xml = $this->_getXML(
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
                    'genre',
                    'albums' => array(
                        'sql' => array(
                            'data' => array(
                                'artistid'
                            ),
                            'query' => 'SELECT * FROM album WHERE artist_id = ?'
                        ),
                        'sql_options' => array(
                            'uncached'      => true,
                            'single_record' => false,
                            'merge'         => false,
                            'merge_master'  => false
                        ),
                        'rootTag' => 'albums',
                        'rowTag' => 'album',
                        'idColumn' => 'albumid',
                        'elements' => array(
                            'albumid',
                            'title',
                            'published_year',
                            'comment'
                        )
                    )
                )
            )
        );
        self::assertTrue($debugLogger->data === '');
    }

    public function testStartProfiling()
    {
        $this->query2xml->startProfiling();
        $xml = $this->_getXML(
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
                    'genre',
                    'albums' => array(
                        'sql' => array(
                            'data' => array(
                                'artistid'
                            ),
                            'query' => 'SELECT * FROM album WHERE artist_id = ?'
                        ),
                        'sql_options' => array(
                            'uncached'      => true,
                            'single_record' => false,
                            'merge'         => false,
                            'merge_master'  => false
                        ),
                        'rootTag' => 'albums',
                        'rowTag' => 'album',
                        'idColumn' => 'albumid',
                        'elements' => array(
                            'albumid',
                            'title',
                            'published_year',
                            'comment'
                        )
                    )
                )
            )
        );
        self::assertTrue(count($this->query2xml->getRawProfile()) == 6);
        $this->query2xml->clearProfile();
    }

    public function testStopProfiling()
    {
        $this->query2xml->startProfiling();
        $this->query2xml->stopProfiling();
        $xml = $this->_getXML(
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
                    'genre',
                    'albums' => array(
                        'sql' => array(
                            'data' => array(
                                'artistid'
                            ),
                            'query' => 'SELECT * FROM album WHERE artist_id = ?'
                        ),
                        'sql_options' => array(
                            'uncached'      => true,
                            'single_record' => false,
                            'merge'         => false,
                            'merge_master'  => false
                        ),
                        'rootTag' => 'albums',
                        'rowTag' => 'album',
                        'idColumn' => 'albumid',
                        'elements' => array(
                            'albumid',
                            'title',
                            'published_year',
                            'comment'
                        )
                    )
                )
            )
        );
        $profile = $this->query2xml->getRawProfile();
        self::assertTrue(count($profile['queries']) === 0);
    }

    public function testClearProfile()
    {
        $this->query2xml->startProfiling();
        $xml = $this->_getXML(
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
                    'genre',
                    'albums' => array(
                        'sql' => array(
                            'data' => array(
                                'artistid'
                            ),
                            'query' => 'SELECT * FROM album WHERE artist_id = ?'
                        ),
                        'sql_options' => array(
                            'uncached'      => true,
                            'single_record' => false,
                            'merge'         => false,
                            'merge_master'  => false
                        ),
                        'rootTag' => 'albums',
                        'rowTag' => 'album',
                        'idColumn' => 'albumid',
                        'elements' => array(
                            'albumid',
                            'title',
                            'published_year',
                            'comment'
                        )
                    )
                )
            )
        );
        $this->query2xml->clearProfile();
        self::assertTrue(count($this->query2xml->getRawProfile()) === 0);
    }


    public function testGetRawProfile()
    {
        $this->query2xml->startProfiling();
        $xml = $this->_getXML(
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
                    'genre',
                    'albums' => array(
                        'sql' => array(
                            'data' => array(
                                'artistid'
                            ),
                            'query' => 'SELECT * FROM album WHERE artist_id = ?'
                        ),
                        'sql_options' => array(
                            'uncached'      => true,
                            'single_record' => false,
                            'merge'         => false,
                            'merge_master'  => false
                        ),
                        'rootTag' => 'albums',
                        'rowTag' => 'album',
                        'idColumn' => 'albumid',
                        'elements' => array(
                            'albumid',
                            'title',
                            'published_year',
                            'comment'
                        )
                    )
                )
            )
        );
        $rawProfile = $this->query2xml->getRawProfile();
        self::assertTrue(count($rawProfile['queries']) === 2);
    }
    
    public function testGetProfile()
    {
        $this->query2xml->startProfiling();
        $xml = $this->_getXML(
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
                    'genre',
                    'albums' => array(
                        'sql' => array(
                            'data' => array(
                                'artistid'
                            ),
                            'query' => 'SELECT * FROM album WHERE artist_id = ?'
                        ),
                        'sql_options' => array(
                            'uncached'      => true,
                            'single_record' => false,
                            'merge'         => false,
                            'merge_master'  => false
                        ),
                        'rootTag' => 'albums',
                        'rowTag' => 'album',
                        'idColumn' => 'albumid',
                        'elements' => array(
                            'albumid',
                            'title',
                            'published_year',
                            'comment'
                        )
                    )
                )
            )
        );
        $profile = $this->query2xml->getProfile();
        $selectCount = preg_match_all('/SELECT/', $profile, $matches);
        self::assertTrue($selectCount === 2);
    }
    
    public function testGetXMLCondition()
    {
        $xml = $this->_getXML(
            "SELECT
                *
             FROM
                artist
                LEFT JOIN album ON album.artist_id = artist.artistid",
            array(
                'rootTag' => 'music_library',
                'rowTag' => 'artist',
                'idColumn' => 'artistid',
                'condition' => '$record["artistid"] != 3',
                'elements' => array(
                    'artistid',
                    'name',
                    'birth_year',
                    'birth_place',
                    'genre',
                    'albums' => array(
                        'rootTag' => 'albums',
                        'rowTag' => 'album',
                        'idColumn' => 'albumid',
                        'elements' => array(
                            'albumid',
                            'title',
                            'published_year',
                            'comment'
                        )
                    )
                )
            )
        );
        self::assertTrue(md5($xml) === '288128a9bc952393f5329e519a819b0f');
    }
    
    public function testGetXMLCondition2()
    {
        $xml = $this->_getXML(
            "SELECT
                *
             FROM
                artist
                LEFT JOIN album ON album.artist_id = artist.artistid",
            array(
                'rootTag' => 'music_library',
                'rowTag' => 'artist',
                'idColumn' => 'artistid',
                'elements' => array(
                    'artistid',
                    'name',
                    'birth_year',
                    'birth_place',
                    'genre',
                    'albums' => array(
                        'condition' => '$record["albumid"] != 1',
                        'rootTag' => 'albums',
                        'rowTag' => 'album',
                        'idColumn' => 'albumid',
                        'elements' => array(
                            'albumid',
                            'title',
                            'published_year',
                            'comment'
                        )
                    )
                )
            )
        );
        self::assertTrue(md5($xml) === '44ec7d4abd392a07330233a68b8f6a1f');
    }
    
    public function testGetXMLConditionalOperatorInElement1()
    {
        $xml = $this->_getXML(
            "SELECT
                *
             FROM
                artist
                LEFT JOIN album ON album.artist_id = artist.artistid",
            array(
                'rootTag' => 'music_library',
                'rowTag' => 'artist',
                'idColumn' => 'artistid',
                'elements' => array(
                    'artistid',
                    'name',
                    'birth_year',
                    'birth_place',
                    'genre',
                    'albums' => array(
                        'rootTag' => 'albums',
                        'rowTag' => 'album',
                        'idColumn' => 'albumid',
                        'elements' => array(
                            'albumid',
                            'title',
                            'published_year',
                            'comment',
                            'test' => '?!return "";'
                        )
                    )
                )
            )
        );
        self::assertTrue(md5($xml) === 'fb5ee5b2446fb9a4868a85b690fcb0b4');
    }
    
    public function testGetXMLConditionalOperatorInElement2()
    {
        $xml = $this->_getXML(
            "SELECT
                *
             FROM
                artist
                LEFT JOIN album ON album.artist_id = artist.artistid",
            array(
                'rootTag' => 'music_library',
                'rowTag' => 'artist',
                'idColumn' => 'artistid',
                'elements' => array(
                    'artistid',
                    'name',
                    'birth_year',
                    'birth_place',
                    'genre',
                    'albums' => array(
                        'rootTag' => 'albums',
                        'rowTag' => 'album',
                        'idColumn' => 'albumid',
                        'elements' => array(
                            'albumid',
                            'title',
                            'published_year',
                            'comment',
                            'test' => '?:'
                        )
                    )
                )
            )
        );
        self::assertTrue(md5($xml) === 'fb5ee5b2446fb9a4868a85b690fcb0b4');
    }
    
    public function testGetXMLConditionalOperatorInElement3()
    {
        $xml = $this->_getXML(
            "SELECT
                artist.*,
                album.*,
                '' AS test
             FROM
                artist
                LEFT JOIN album ON album.artist_id = artist.artistid",
            array(
                'rootTag' => 'music_library',
                'rowTag' => 'artist',
                'idColumn' => 'artistid',
                'elements' => array(
                    'artistid',
                    'name',
                    'birth_year',
                    'birth_place',
                    'genre',
                    'albums' => array(
                        'rootTag' => 'albums',
                        'rowTag' => 'album',
                        'idColumn' => 'albumid',
                        'elements' => array(
                            'albumid',
                            'title',
                            'published_year',
                            'comment',
                            'test' => '?test'
                        )
                    )
                )
            )
        );
        self::assertTrue(md5($xml) === 'fb5ee5b2446fb9a4868a85b690fcb0b4');
    }
    
    public function testGetXMLConditionalOperatorInAttribute1()
    {
        $xml = $this->_getXML(
            "SELECT
                artist.*,
                album.*,
                '' AS test
             FROM
                artist
                LEFT JOIN album ON album.artist_id = artist.artistid",
            array(
                'rootTag' => 'music_library',
                'rowTag' => 'artist',
                'idColumn' => 'artistid',
                'elements' => array(
                    'artistid',
                    'name',
                    'birth_year',
                    'birth_place',
                    'genre',
                    'albums' => array(
                        'rootTag' => 'albums',
                        'rowTag' => 'album',
                        'idColumn' => 'albumid',
                        'elements' => array(
                            'albumid',
                            'title',
                            'published_year',
                            'comment',
                        )
                    )
                ),
                'attributes' => array(
                    'artistid',
                    'name',
                    'test' => '?!return "";'
                )
            )
        );
        self::assertTrue(md5($xml) === '7173cc3c836e17dc31229d05c6687371');
    }
    
    public function testGetXMLConditionalOperatorInAttribute2()
    {
        $xml = $this->_getXML(
            "SELECT
                artist.*,
                album.*,
                '' AS test
             FROM
                artist
                LEFT JOIN album ON album.artist_id = artist.artistid",
            array(
                'rootTag' => 'music_library',
                'rowTag' => 'artist',
                'idColumn' => 'artistid',
                'elements' => array(
                    'artistid',
                    'name',
                    'birth_year',
                    'birth_place',
                    'genre',
                    'albums' => array(
                        'rootTag' => 'albums',
                        'rowTag' => 'album',
                        'idColumn' => 'albumid',
                        'elements' => array(
                            'albumid',
                            'title',
                            'published_year',
                            'comment',
                        )
                    )
                ),
                'attributes' => array(
                    'artistid',
                    'name',
                    'test' => '?:'
                )
            )
        );
        self::assertTrue(md5($xml) === '7173cc3c836e17dc31229d05c6687371');
    }
    
    public function testGetXMLConditionalOperatorInAttribute3()
    {
        $xml = $this->_getXML(
            "SELECT
                artist.*,
                album.*,
                '' AS test
             FROM
                artist
                LEFT JOIN album ON album.artist_id = artist.artistid",
            array(
                'rootTag' => 'music_library',
                'rowTag' => 'artist',
                'idColumn' => 'artistid',
                'elements' => array(
                    'artistid',
                    'name',
                    'birth_year',
                    'birth_place',
                    'genre',
                    'albums' => array(
                        'rootTag' => 'albums',
                        'rowTag' => 'album',
                        'idColumn' => 'albumid',
                        'elements' => array(
                            'albumid',
                            'title',
                            'published_year',
                            'comment',
                        )
                    )
                ),
                'attributes' => array(
                    'artistid',
                    'name',
                    'test' => '?test'
                )
            )
        );
        self::assertTrue(md5($xml) === '7173cc3c836e17dc31229d05c6687371');
    }
    
    public function testGetXMLConditionalOperatorInValue1()
    {
        $xml = $this->_getXML(
            "SELECT
                *
             FROM
                artist
                LEFT JOIN album ON album.artist_id = artist.artistid",
            array(
                'rootTag' => 'music_library',
                'rowTag' => 'artist',
                'idColumn' => 'artistid',
                'elements' => array(
                    'artistid',
                    'name',
                    'birth_year',
                    'birth_place',
                    'genre',
                    'albums' => array(
                        'rootTag' => 'albums',
                        'rowTag' => 'album',
                        'idColumn' => 'albumid',
                        'value' => '?!return "";',
                        'elements' => array(
                            'albumid',
                            'title',
                            'published_year',
                            'comment',
                        )
                    )
                )
            )
        );
        self::assertTrue(md5($xml) === '709c61adbce3d4bde7ef2c59c23be370');
    }
    
    public function testGetXMLConditionalOperatorInValue2()
    {
        $xml = $this->_getXML(
            "SELECT
                *
             FROM
                artist
                LEFT JOIN album ON album.artist_id = artist.artistid",
            array(
                'rootTag' => 'music_library',
                'rowTag' => 'artist',
                'idColumn' => 'artistid',
                'elements' => array(
                    'artistid',
                    'name',
                    'birth_year',
                    'birth_place',
                    'genre',
                    'albums' => array(
                        'rootTag' => 'albums',
                        'rowTag' => 'album',
                        'idColumn' => 'albumid',
                        'value' => '?:',
                        'elements' => array(
                            'albumid',
                            'title',
                            'published_year',
                            'comment',
                        )
                    )
                )
            )
        );
        self::assertTrue(md5($xml) === '709c61adbce3d4bde7ef2c59c23be370');
    }
    
    public function testGetXMLConditionalOperatorInValue3()
    {
        $xml = $this->_getXML(
            "SELECT
                artist.*,
                album.*,
                '' AS test
             FROM
                artist
                LEFT JOIN album ON album.artist_id = artist.artistid",
            array(
                'rootTag' => 'music_library',
                'rowTag' => 'artist',
                'idColumn' => 'artistid',
                'elements' => array(
                    'artistid',
                    'name',
                    'birth_year',
                    'birth_place',
                    'genre',
                    'albums' => array(
                        'rootTag' => 'albums',
                        'rowTag' => 'album',
                        'idColumn' => 'albumid',
                        'value' => '?test',
                        'elements' => array(
                            'albumid',
                            'title',
                            'published_year',
                            'comment',
                        )
                    )
                )
            )
        );
        self::assertTrue(md5($xml) === '709c61adbce3d4bde7ef2c59c23be370');
    }
    
    public function testGetXMLElementAsteriskShortcut1()
    {
        $xml = $this->_getXML(
            "SELECT
                *
             FROM
                artist",
            array(
                'rootTag' => 'music_library',
                'rowTag' => 'artist',
                'idColumn' => 'artistid',
                'elements' => array(
                    '*'
                )
            )
        );
        self::assertTrue(md5($xml) === '0c33b7de1a587b4c2b436e7e495f1cf4');
    }
    
    public function testGetXMLElementAsteriskShortcut2()
    {
        $xml = $this->_getXML(
            "SELECT
                *
             FROM
                artist",
            array(
                'rootTag' => 'music_library',
                'rowTag' => 'artist',
                'idColumn' => 'artistid',
                'elements' => array(
                    '*' => '*',
                    'TAG1_*' => ':STATIC_VALUE',
                    'TAG2_*' => ':VALUE_*',
                    'TAG3_*' => '!return "--" . $record["*"] . "--";'
                )
            )
        );
        self::assertTrue(md5($xml) === '3abbbacb9dccf1c9de9aa4e062cef975');
    }
    
    public function testGetXMLElementAsteriskShortcut3()
    {
        $xml = $this->_getXML(
            "SELECT
                *
             FROM
                artist",
            array(
                'rootTag' => 'music_library',
                'rowTag' => 'artist',
                'idColumn' => 'artistid',
                'elements' => array(
                    '*' => '*',
                    'genre' => '!return strtoupper($record["genre"]);'
                )
            )
        );
        self::assertTrue(md5($xml) === '75a4d2b9d9ea86dff1e1240a4d09d30b');
    }
    
    public function testGetXMLElementAsteriskShortcut4()
    {
        $xml = $this->_getXML(
            "SELECT
                *
             FROM
                artist",
            array(
                'rootTag' => 'music_library',
                'rowTag' => 'artist',
                'idColumn' => 'artistid',
                'elements' => array(
                    '*' => '*',
                    'genre' => array(
                        'value' => '!return strtoupper($record["genre"]);'
                    )
                )
            )
        );
        self::assertTrue(md5($xml) === '75a4d2b9d9ea86dff1e1240a4d09d30b');
    }
    
    public function testGetXMLElementAsteriskShortcut5()
    {
        $xml = $this->_getXML(
            "SELECT
                *
             FROM
                artist",
            array(
                'rootTag' => 'music_library',
                'rowTag' => 'artist',
                'idColumn' => 'artistid',
                'elements' => array(
                    '*' => '*',
                    'genre' => '?:'
                )
            )
        );
        self::assertTrue(md5($xml) === 'd917081f2b96d050f2d75ebb2660a8e8');
    }
    
    public function testGetXMLAttributeAsteriskShortcut1()
    {
        $xml = $this->_getXML(
            "SELECT
                *
             FROM
                artist",
            array(
                'rootTag' => 'music_library',
                'rowTag' => 'artist',
                'idColumn' => 'artistid',
                'attributes' => array(
                    '*'
                )
            )
        );
        self::assertTrue(md5($xml) === '6507c0884a2bb2301c4bdf6e975f293e');
    }
    
    public function testGetXMLAttributeAsteriskShortcut2()
    {
        $xml = $this->_getXML(
            "SELECT
                *
             FROM
                artist",
            array(
                'rootTag' => 'music_library',
                'rowTag' => 'artist',
                'idColumn' => 'artistid',
                'attributes' => array(
                    '*' => '*',
                    'ATTR1_*' => ':STATIC_VALUE',
                    'ATTR2_*' => ':VALUE_*',
                    'ATTR3_*' => '!return "--" . $record["*"] . "--";'
                )
            )
        );
        self::assertTrue(md5($xml) === '0711a0ce7b6f206fbf53fd6766169d52');
    }
    
    public function testGetXMLAttributeAsteriskShortcut3()
    {
        $xml = $this->_getXML(
            "SELECT
                *
             FROM
                artist",
            array(
                'rootTag' => 'music_library',
                'rowTag' => 'artist',
                'idColumn' => 'artistid',
                'attributes' => array(
                    '*' => '*',
                    'genre' => '!return strtoupper($record["genre"]);'
                )
            )
        );
        self::assertTrue(md5($xml) === '939f1105362ad574fe47a0007f558049');
    }
    
    public function testGetXMLAttributeAsteriskShortcut4()
    {
        $xml = $this->_getXML(
            "SELECT
                *
             FROM
                artist",
            array(
                'rootTag' => 'music_library',
                'rowTag' => 'artist',
                'idColumn' => 'artistid',
                'attributes' => array(
                    '*' => '*',
                    'genre' => '?:'
                )
            )
        );
        self::assertTrue(md5($xml) === '7a4245b9688d6c70e18b1a8df397d674');
    }
    
    public function testGetXMLComplexAttributeSpecification1()
    {
        $xml = $this->_getXML(
            "SELECT
                *
             FROM
                artist",
            array(
                'rootTag' => 'music_library',
                'rowTag' => 'artist',
                'idColumn' => 'artistid',
                'attributes' => array(
                    'static' => array(
                        'value' => ':some static text',
                    ),
                    'hide' => array(
                        'value' => '?:'
                    ),
                    'six' => array(
                        'value' => '!return 2 * 3;'
                    ),
                    'hide2' => array(
                        'value' => '?!return "";'
                    ),
                    'artistid' => array(
                        'value' => 'artistid'
                    ),
                    'genre' => array(
                        'value' => '!return strtoupper($record["genre"]);'
                    ),
                    'birth_year' => array(
                        'value' => '?birth_year'
                    )
                )
            )
        );
        self::assertTrue(md5($xml) === '4b589562210a90b7d3a6bd9fa269e621');
    }
    
    public function testGetXMLComplexAttributeSpecification2()
    {
        $xml = $this->_getXML(
            "SELECT
                *
             FROM
                artist",
            array(
                'rootTag' => 'music_library',
                'rowTag' => 'artist',
                'idColumn' => 'artistid',
                'attributes' => array(
                    'attr1' => array(
                        'value' => ':only for atistid 1',
                        'condition' => '$record["artistid"] == 1'
                    ),
                    'attr2' => array(
                        'value' => ':only for atistid 2',
                        'condition' => '$record["artistid"] == 2'
                    ),
                    'attr3' => array(
                        'value' => ':only for atistid 3',
                        'condition' => '$record["artistid"] == 3'
                    ),
                    'name'
                )
            )
        );
        self::assertTrue(md5($xml) === 'a103ef84b6d2ea57314ba51b517f35dc');
    }
    
    public function testGetXMLComplexAttributeSpecification3()
    {
        $xml = $this->_getXML(
            "SELECT
                *
             FROM
                artist",
            array(
                'rootTag' => 'music_library',
                'rowTag' => 'artist',
                'idColumn' => 'artistid',
                'attributes' => array(
                    'name',
                    'firstAlbumTitle' => array(
                        'value' => 'title',
                        'sql' => array(
                            'data' => array(
                                'artistid'
                            ),
                            'query' => "SELECT * FROM album WHERE artist_id = ?"
                        )
                    ),
                    'firstAlbumYear' => array(
                        'value' => 'published_year',
                        'sql' => array(
                            'data' => array(
                                'artistid'
                            ),
                            'query' => "SELECT * FROM album WHERE artist_id = ?"
                        )
                    )
                )
            )
        );
        self::assertTrue(md5($xml) === 'bb675457741106b358a51eec2c6e4571');
    }
    
    public function testGetXMLComplexAttributeSpecification4()
    {
        $xml = $this->_getXML(
            "SELECT
                *
             FROM
                artist",
            array(
                'rootTag' => 'music_library',
                'rowTag' => 'artist',
                'idColumn' => 'artistid',
                'attributes' => array(
                    'name',
                    'firstAlbumTitle' => array(
                        'value' => 'title',
                        'sql' => array(
                            'data' => array(
                                'artistid'
                            ),
                            'query' => "SELECT * FROM album WHERE artist_id = ?"
                        )
                    ),
                    'firstAlbumYear' => array(
                        'value' => 'published_year',
                        'sql' => array(
                            'data' => array(
                                'artistid'
                            ),
                            'query' => "SELECT * FROM album WHERE artist_id = ?"
                        )
                    ),
                    'firstAlbumGenre' => array(
                        'value' => '!return $record["genre"] . " of " . $record["published_year"];',
                        'sql' => array(
                            'data' => array(
                                'artistid'
                            ),
                            'query' => "SELECT * FROM album WHERE artist_id = ?"
                        ),
                        'sql_options' => array(
                            'merge' => true
                        )
                    )
                )
            )
        );
        self::assertTrue(md5($xml) === 'cf7da8aa2ef9add47730688085c348a4');
    }
}

class MyLogger
{
    public $data = '';
    public function log($str)
    {
        $this->data .= $str . "\n";
    }
}


/**Static class that provides validation and parsing methods for
* generating XML.
*
* It is static so that we can easyly call its methods from inside
* Query2XML using eval'd code.
*/
class Helper
{
    /**Associative array of US postal state codes*/
    public static $statePostalCodes = array(
        'ALABAMA' => 'AL', 'ALASKA' => 'AK', 'AMERICAN SAMOA' => 'AS', 'ARIZONA' => 'AZ', 'ARKANSAS' => 'AR', 'CALIFORNIA' => 'CA',
        'COLORADO' => 'CO', 'CONNECTICUT' => 'CT', 'DELAWARE' => 'DE', 'DISTRICT OF COLUMBIA' => 'DC', 'FEDERATED STATES OF MICRONESIA' => 'FM',
        'FLORIDA' => 'FL', 'GEORGIA' => 'GA', 'GUAM' => 'GU', 'HAWAII' => 'HI', 'IDAHO' => 'ID', 'ILLINOIS' => 'IL', 'INDIANA' => 'IN',
        'IOWA' => 'IA', 'KANSAS' => 'KS', 'KENTUCKY' => 'KY', 'LOUISIANA' => 'LA', 'MAINE' => 'ME', 'MARSHALL ISLANDS' => 'MH', 'MARYLAND' => 'MD',
        'MASSACHUSETTS' => 'MA', 'MICHIGAN' => 'MI', 'MINNESOTA' => 'MN', 'MISSISSIPPI' => 'MS', 'MISSOURI' => 'MO', 'MONTANA' => 'MT',
        'NEBRASKA' => 'NE', 'NEVADA' => 'NV', 'NEW HAMPSHIRE' => 'NH', 'NEW JERSEY' => 'NJ', 'NEW JESEY' => 'NJ', 'NEW MEXICO' => 'NM', 'NEW YORK' => 'NY',
        'NORTH CAROLINA' => 'NC', 'NORTH DAKOTA' => 'ND', 'NORTHERN MARIANA ISLANDS' => 'MP', 'OHIO' => 'OH', 'OKLAHOMA' => 'OK', 'OREGON' => 'OR',
        'PALAU' => 'PW', 'PENNSYLVANIA' => 'PA', 'PUERTO RICO' => 'PR', 'RHODE ISLAND' => 'RI', 'SOUTH CAROLINA' => 'SC', 'SOUTH DAKOTA' => 'SD',
        'TENNESSEE' => 'TN', 'TEXAS' => 'TX', 'UTAH' => 'UT', 'VERMONT' => 'VT', 'VIRGIN ISLANDS' => 'VI', 'VIRGINIA' => 'VA', 'WASHINGTON' => 'WA',
        'WEST VIRGINIA' => 'WV', 'WISCONSIN' => 'WI', 'WYOMING' => 'WY'
    );
            
    /**Translates a US state name into its two-letter postal code.
    * If the translation fails, $state is returned unchanged
    * @param $state The state's name
    */
    public static function getStatePostalCode($state)
    {
        $s = str_replace("  ", " ", trim(strtoupper($state)));
        if (isset(self::$statePostalCodes[$s])) {
            return self::$statePostalCodes[$s];
        } else {
            return $state;
        }
    }
      
    function summarize($str, $limit=50, $appendString=' ...')
    {
        if (strlen($str) > $limit) {
            $str = substr($str, 0, $limit - strlen($appendString)) . $appendString;
        }
        return $str;
    }
    
    public function throwException($str)
    {
        throw new Exception('Throwing exception for ' . $str);
    }
}

class Mappers
{
    public static function departmentMapper($str)
    {
        //maps 'one_two_three' to 'oneTwoThree'
        return preg_replace("/(_)([a-z])/e", "strtoupper('\\2')", $str);
    }
    
    public static function employeeMapper($str)
    {
        //maps 'one_two_three' to 'OneTwoThree'
        return ucfirst(preg_replace("/(_)([a-z])/e", "strtoupper('\\2')", $str));
    }
    
    public function saleMapper($str)
    {
        //maps 'one_two_three' to 'ONETWOTHREE'
        return strtoupper(str_replace('_', '', $str));
    }
}

function mapArtist($str)
{
    //maps 'one_two_three' to 'onetwothree'
    return strtolower(str_replace('_', '', $str));
}

// Call Query2XMLTestMDB2::main() if this source file is executed directly.
if (PHPUnit2_MAIN_METHOD == "Query2XMLTestMDB2::main") {
    Query2XMLTestMDB2::main();
}
?>