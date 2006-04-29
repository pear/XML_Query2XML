<?php
/**This file contains the class ISO9075MapperTest.
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

// Call ISO9075MapperTest::main() if this source file is executed directly.
if (!defined("PHPUnit2_MAIN_METHOD")) {
    define("PHPUnit2_MAIN_METHOD", "ISO9075MapperTest::main");
}

require_once "PHPUnit2/Framework/TestCase.php";
require_once "PHPUnit2/Framework/TestSuite.php";

require_once "XML/Query2XML/ISO9075Mapper.php";


/**Test class for XML_Query2XML_ISO9075Mapper.
* 
* @author Lukas Feiler <lukas.feiler@lukasfeiler.com>
* @version Release: @package_version@
* @copyright Empowered Media 2006
*/
class ISO9075MapperTest extends PHPUnit2_Framework_TestCase
{
    /**Runs the test methods of this class.
    */
    public static function main()
    {
        require_once "PHPUnit2/TextUI/TestRunner.php";

        $suite  = new PHPUnit2_Framework_TestSuite("ISO9075MapperTest");
        $result = PHPUnit2_TextUI_TestRunner::run($suite);
    }

    public function testMapColon()
	{
        //see http://www.minet.uni-jena.de/dbis/lehre/ss2004/seminar/A_Sinko.pdf, 4.1
        //or http://publib.boulder.ibm.com/infocenter/dzichelp/v2r2/index.jsp?topic=/com.ibm.db2.doc.sqlref/bjnrmstr186.htm
        self::assertEquals(
            'dept_x003A_id',
            XML_Query2XML_ISO9075Mapper::map("dept:id")
        );
    }
    
    public function testMapStartWithxml()
	{
        //see http://www.oracle.com/technology/oramag/oracle/02-nov/o62industry.html
        self::assertEquals(
            '_x0078_ml_name',
            XML_Query2XML_ISO9075Mapper::map("xml_name")
        );
    }
    
    public function testMapStartWithXML_UC()
	{
        //see http://www.oracle.com/technology/oramag/oracle/02-nov/o62industry.html
        self::assertEquals(
            '_x0058_ML_name',
            XML_Query2XML_ISO9075Mapper::map("XML_name")
        );
    }
    
    public function testMapSpace()
	{
        self::assertEquals(
            'hire_x0020_date',
            XML_Query2XML_ISO9075Mapper::map("hire date")
        );
    }
    
    public function testMapAt()
	{
        //see http://www.oracle.com/technology/oramag/oracle/02-nov/o62industry.html
        self::assertEquals(
            'Works_x0040_home',
            XML_Query2XML_ISO9075Mapper::map("Works@home")
        );
    }
    
    public function testMapUnderscorexls()
	{
        self::assertEquals(
            'file_x005F_xls',
            XML_Query2XML_ISO9075Mapper::map("file_xls")
        );
    }
    
    public function testMapUnderscoreXLS_UC()
	{
        self::assertEquals(
            'file_XLS',
            XML_Query2XML_ISO9075Mapper::map("file_XLS")
        );
    }
    
    public function testMapRegular1()
	{
        self::assertEquals(
            'FIRST_NAME',
            XML_Query2XML_ISO9075Mapper::map("FIRST_NAME")
        );
    }
    
    public function testMapRegular2()
	{
        self::assertEquals(
            'test',
            XML_Query2XML_ISO9075Mapper::map("test")
        );
    }
    
    public function testMapPoundSign()
	{
        self::assertEquals(
            'n_x0023_p',
            XML_Query2XML_ISO9075Mapper::map("n#p")
        );
    }
    
    public function testMapCurlyBraceOpen()
	{
        self::assertEquals(
            'n_x007b_p',
            XML_Query2XML_ISO9075Mapper::map("n{p")
        );
    }
    
    public function testMapCurlyBraceClose()
	{
        self::assertEquals(
            'n_x007d_p',
            XML_Query2XML_ISO9075Mapper::map("n}p")
        );
    }
    
    public function testMapLessThan()
	{
        self::assertEquals(
            '_x003c_21years',
            XML_Query2XML_ISO9075Mapper::map("<21years")
        );
    }
    
    public function testMapGreaterThan()
	{
        self::assertEquals(
            '_x003e_21years',
            XML_Query2XML_ISO9075Mapper::map(">21years")
        );
    }
    
    public function testMapEqual()
	{
        self::assertEquals(
            '_x003d_21years',
            XML_Query2XML_ISO9075Mapper::map("=21years")
        );
    }
    
    public function testMapSemiColun()
	{
        self::assertEquals(
            'a_x003b_b',
            XML_Query2XML_ISO9075Mapper::map("a;b")
        );
    }
    
    public function testMapSpecial1()
	{
        self::assertEquals(
            "a\xe0\xae\xb4",
            XML_Query2XML_ISO9075Mapper::map("a\xe0\xae\xb4")
        );
    }
    public function testMapSpecial2()
	{
        self::assertEquals(
            'a' . I18N_UnicodeString::unicodeCharToUtf8(hexdec(0x200C)),
            XML_Query2XML_ISO9075Mapper::map('a' . I18N_UnicodeString::unicodeCharToUtf8(hexdec(0x200C)))
        );
    }
    public function testMapValidNameStartChars()
	{
	    $validRanges[] = array(hexdec('C0'),    hexdec('D6'));
        $validRanges[] = array(hexdec('D8'),    hexdec('F6'));
        $validRanges[] = array(hexdec('F8'),    hexdec('2FF'));
        $validRanges[] = array(hexdec('370'),   hexdec('37D'));
        $validRanges[] = array(hexdec('37F'),   hexdec('1FFF'));
        $validRanges[] = array(hexdec('200C'),  hexdec('200D'));
        $validRanges[] = array(hexdec('2070'),  hexdec('218F'));
        $validRanges[] = array(hexdec('2C00'),  hexdec('2FEF'));
        $validRanges[] = array(hexdec('3001'),  hexdec('D7FF'));
        $validRanges[] = array(hexdec('F900'),  hexdec('FDCF'));
        $validRanges[] = array(hexdec('FDF0'),  hexdec('FFFD'));
        $validRanges[] = array(hexdec('10000'), hexdec('EFFFF'));
        
        for ($i = 0; $i < count($validRanges); $i++) {
            //we only test min, max and avg or this would take ages
            $min = $validRanges[$i][0];
            $max = $validRanges[$i][1];
            $avg = ($min + $max) / 2;
            
            self::assertEquals(
                I18N_UnicodeString::unicodeCharToUtf8($min),
                XML_Query2XML_ISO9075Mapper::map(I18N_UnicodeString::unicodeCharToUtf8($min))
            );
            
            self::assertEquals(
                I18N_UnicodeString::unicodeCharToUtf8($max),
                XML_Query2XML_ISO9075Mapper::map(I18N_UnicodeString::unicodeCharToUtf8($max))
            );
            
            self::assertEquals(
                I18N_UnicodeString::unicodeCharToUtf8($avg),
                XML_Query2XML_ISO9075Mapper::map(I18N_UnicodeString::unicodeCharToUtf8($avg))
            );
        }
    }
    
    public function testMapInvalidNameStartChars()
	{
	    $validRanges[] = array(hexdec('C0'),    hexdec('D6'));
        $validRanges[] = array(hexdec('D8'),    hexdec('F6'));
        $validRanges[] = array(hexdec('F8'),    hexdec('2FF'));
        $validRanges[] = array(hexdec('370'),   hexdec('37D'));
        $validRanges[] = array(hexdec('37F'),   hexdec('1FFF'));
        $validRanges[] = array(hexdec('200C'),  hexdec('200D'));
        $validRanges[] = array(hexdec('2070'),  hexdec('218F'));
        $validRanges[] = array(hexdec('2C00'),  hexdec('2FEF'));
        $validRanges[] = array(hexdec('3001'),  hexdec('D7FF'));
        $validRanges[] = array(hexdec('F900'),  hexdec('FDCF'));
        $validRanges[] = array(hexdec('FDF0'),  hexdec('FFFD'));
        $validRanges[] = array(hexdec('10000'), hexdec('EFFFF'));
        
        for ($i = 0; $i < count($validRanges); $i++) {
            $min = $validRanges[$i][1] + 1;
            if (!isset($validRanges[$i+1])) {
                $max = hexdec('FFFFF');
            } else {
                $max = $validRanges[$i+1][0];
            }
            
            for ($char = $min; $char < $max; $char++) {
                $expectedHex = dechex($char);
                if (strlen($expectedHex) < 4) {
                    $expectedHex = str_pad($expectedHex, 4, '0', STR_PAD_LEFT);
                } elseif (strlen($expectedHex) > 4 && strlen($expectedHex) < 8) {
                    $expectedHex = str_pad($expectedHex, 8, '0', STR_PAD_LEFT);
                }
                self::assertEquals(
                    '_x' . $expectedHex . '_',
                    XML_Query2XML_ISO9075Mapper::map(I18N_UnicodeString::unicodeCharToUtf8($char))
                );
            }
            
        }
    }
    
    public function testMapValidNameChars()
	{
	    $validRanges[] = array(hexdec('C0'),    hexdec('D6'));
        $validRanges[] = array(hexdec('D8'),    hexdec('F6'));
        $validRanges[] = array(hexdec('F8'),    hexdec('2FF'));
        $validRanges[] = array(hexdec('300'),   hexdec('36F'));     //this is only for nameChar
        $validRanges[] = array(hexdec('370'),   hexdec('37D'));
        $validRanges[] = array(hexdec('37F'),   hexdec('1FFF'));
        $validRanges[] = array(hexdec('200C'),  hexdec('200D'));
        $validRanges[] = array(hexdec('203F'),  hexdec('2040'));    //this is only for nameChar
        $validRanges[] = array(hexdec('2070'),  hexdec('218F'));
        $validRanges[] = array(hexdec('2C00'),  hexdec('2FEF'));
        $validRanges[] = array(hexdec('3001'),  hexdec('D7FF'));
        $validRanges[] = array(hexdec('F900'),  hexdec('FDCF'));
        $validRanges[] = array(hexdec('FDF0'),  hexdec('FFFD'));
        $validRanges[] = array(hexdec('10000'), hexdec('EFFFF'));
        
        for ($i = 0; $i < count($validRanges); $i++) {
            //we only test min, max and avg or this would take ages
            $min = $validRanges[$i][0];
            $max = $validRanges[$i][1];
            $avg = ($min + $max) / 2;
            
            self::assertEquals(
                'a' . I18N_UnicodeString::unicodeCharToUtf8($min),
                XML_Query2XML_ISO9075Mapper::map('a' . I18N_UnicodeString::unicodeCharToUtf8($min))
            );
            
            self::assertEquals(
                'a' . I18N_UnicodeString::unicodeCharToUtf8($max),
                XML_Query2XML_ISO9075Mapper::map('a' . I18N_UnicodeString::unicodeCharToUtf8($max))
            );
            
            self::assertEquals(
                'a' . I18N_UnicodeString::unicodeCharToUtf8($avg),
                XML_Query2XML_ISO9075Mapper::map('a' . I18N_UnicodeString::unicodeCharToUtf8($avg))
            );
        }
    }
    
    public function testMapInvalidNameChars()
	{
	    $validRanges[] = array(hexdec('C0'),    hexdec('D6'));
        $validRanges[] = array(hexdec('D8'),    hexdec('F6'));
        $validRanges[] = array(hexdec('F8'),    hexdec('2FF'));
        $validRanges[] = array(hexdec('300'),   hexdec('36F'));     //this is only for nameChar
        $validRanges[] = array(hexdec('370'),   hexdec('37D'));
        $validRanges[] = array(hexdec('37F'),   hexdec('1FFF'));
        $validRanges[] = array(hexdec('200C'),  hexdec('200D'));
        $validRanges[] = array(hexdec('203F'),  hexdec('2040'));    //this is only for nameChar
        $validRanges[] = array(hexdec('2070'),  hexdec('218F'));
        $validRanges[] = array(hexdec('2C00'),  hexdec('2FEF'));
        $validRanges[] = array(hexdec('3001'),  hexdec('D7FF'));
        $validRanges[] = array(hexdec('F900'),  hexdec('FDCF'));
        $validRanges[] = array(hexdec('FDF0'),  hexdec('FFFD'));
        $validRanges[] = array(hexdec('10000'), hexdec('EFFFF'));
        
        for ($i = 0; $i < count($validRanges); $i++) {
            $min = $validRanges[$i][1] + 1;
            if (!isset($validRanges[$i+1])) {
                $max = hexdec('FFFFF');
            } else {
                $max = $validRanges[$i+1][0];
            }
            
            for ($char = $min; $char < $max; $char++) {
                $expectedHex = dechex($char);
                if (strlen($expectedHex) < 4) {
                    $expectedHex = str_pad($expectedHex, 4, '0', STR_PAD_LEFT);
                } elseif (strlen($expectedHex) > 4 && strlen($expectedHex) < 8) {
                    $expectedHex = str_pad($expectedHex, 8, '0', STR_PAD_LEFT);
                }
                self::assertEquals(
                    '_x' . $expectedHex . '_',
                    XML_Query2XML_ISO9075Mapper::map(I18N_UnicodeString::unicodeCharToUtf8($char))
                );
            }
        }
    }
    public function testMapException1()
	{
        try {
            XML_Query2XML_ISO9075Mapper::map("a\xff");
            self::assertTrue(false);
        } catch (XML_Query2XML_ISO9075Mapper_Exception $e) {
            self::assertEquals('Malformed UTF-8 string', $e->getMessage());
        }
    }
    
    public function testMapException2()
	{
        try {
            XML_Query2XML_ISO9075Mapper::map("a\xff\xff");
            self::assertTrue(false);
        } catch (XML_Query2XML_ISO9075Mapper_Exception $e) {
            self::assertEquals('Malformed UTF-8 string', $e->getMessage());
        }
    }
    
    public function testMapException3()
	{
        try {
            XML_Query2XML_ISO9075Mapper::map("a\xff\xff\xff");
            self::assertTrue(false);
        } catch (XML_Query2XML_ISO9075Mapper_Exception $e) {
            self::assertEquals('Malformed UTF-8 string', $e->getMessage());
        }
    }
    
    public function testMapAll()
	{
        self::assertEquals(
            "_x0078_ml_x003A__x0020__x0040__x005F_x_Xtest_x0023__x007b__x007d__x003c__x003e__x003d__x003b_ThiS_iS_JuSt_sOMe_ReGUlAR_tExT\xe0\xae\xb4",
            XML_Query2XML_ISO9075Mapper::map("xml: @_x_Xtest#{}<>=;ThiS_iS_JuSt_sOMe_ReGUlAR_tExT\xe0\xae\xb4")
        );
    }
}

// Call ISO9075MapperTest::main() if this source file is executed directly.
if (PHPUnit2_MAIN_METHOD == "ISO9075MapperTest::main") {
    ISO9075MapperTest::main();
}
?>