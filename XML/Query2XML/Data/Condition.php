<?php
/**
 * This file contains the interface XML_Query2XML_Callback.
 *
 * PHP version 5
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2009 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/XML_Query2XML 
 */

/**
 * XML_Query2XML_Data_Condition extends the class
 * XML_Query2XML_Data_Processor.
 */
require_once 'XML/Query2XML/Data/Processor.php';

/**
 * Abstract class extended by all Data Condition Classes.
 * Such classes allow the implementation of a condition as to
 * whether the return value of execute() is to be used.
 *
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2009 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/XML_Query2XML
 * @since     Release 1.8.0RC1
 */
abstract class XML_Query2XML_Data_Condition extends XML_Query2XML_Data_Processor
{
    /**
     * Returns a boolean value indicating whether the return value of execute()
     * is to be used.
     *
     * @param string $string The return value of execute()
     *
     * @return boolean
     */
    abstract public function evaluateCondition($string);
}
?>