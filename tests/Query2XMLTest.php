<?php
/**This file contains the class Query2XMLTest.
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

// Call Query2XMLTest::main() if this source file is executed directly.
if (!defined("PHPUnit2_MAIN_METHOD")) {
    define("PHPUnit2_MAIN_METHOD", "Query2XMLTest::main");
}

require_once "PHPUnit2/Framework/TestCase.php";
require_once "PHPUnit2/Framework/TestSuite.php";

require_once "XML/Query2XML.php";
require_once "XML/Query2XML/ISO9075Mapper.php";

/**Test class for XML_Query2XML that does not require any data abastraction layer.
* 
* @author Lukas Feiler <lukas.feiler@lukasfeiler.com>
* @version Release: @package_version@
* @copyright Empowered Media 2006
*/
class Query2XMLTest extends PHPUnit2_Framework_TestCase
{
    /**Runs the test methods of this class.
    */
    public static function main() {
        require_once "PHPUnit2/TextUI/TestRunner.php";

        $suite  = new PHPUnit2_Framework_TestSuite("Query2XMLTest");
        $result = PHPUnit2_TextUI_TestRunner::run($suite);
    }
    
    private function _getXML($sql, $options)
    {
        $dom = $this->query2xml->getXML($sql, $options);
        return $dom->saveXML();
    }
    
    public function setUp()
    {
        require_once('DB.php');
        $db = DB::connect('mysql://root@localhost/Query2XML_Tests');
        $this->query2xml =& XML_Query2XML::factory($db);
    }
    
    public function testFactoryDBErrorException()
    {
        try {
            require_once('DB.php');
            $db = DB::connect('mysql://bogususer@256.256.256.256/bugusdb');
            $query2xml =& XML_Query2XML::factory($db);
            //did not throw exception
            self::assertTrue(false);
        } catch (XML_Query2XML_DBException $e) {
            self::assertTrue(true);
        }
    }
    
    public function testFactoryWrongArumentTypeException()
    {
        try {
            $query2xml =& XML_Query2XML::factory("some string");
            self::assertTrue(false);
        } catch (XML_Query2XML_ConfigException $e) {
            self::assertEquals(
                'Argument passed to the XML_Query2XML constructor is not an '
                . 'instance of DB_common, MDB2_Driver_Common or ADOConnection.',
                $e->getMessage()
            );
        }
    }
    
    public function testFactoryWrongArgumentException()
    {
        try {
            $query2xml =& XML_Query2XML::factory("some other argument type");
            //did not throw exception
            self::assertTrue(false);
        } catch (XML_Query2XML_ConfigException $e) {
            self::assertTrue(true);
        }
    }
}

// Call Query2XMLTest::main() if this source file is executed directly.
if (PHPUnit2_MAIN_METHOD == "Query2XMLTest::main") {
    Query2XMLTest::main();
}
?>
