<?php
/**This file allows you to change settings for all unit tests.
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
* @package XML_Query2XML
* @version $Id$
*/

if (getenv('PHP_PEAR_XML_QUERY2XML_TEST_DSN') != '') {
    define('DSN', getenv('PHP_PEAR_XML_QUERY2XML_TEST_DSN'));
} else {
    //define('DSN', 'mysql://root@localhost/Query2XML_Tests');
    //define('DSN', 'pgsql://postgres:test@localhost/query2xml_tests');
    define('DSN', 'sqlite:///' . dirname(dirname(__FILE__)) . '/Query2XML_Tests.sq2');
}
?>