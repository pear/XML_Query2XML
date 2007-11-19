<?php
/**
 * This file contains the class XML_Query2XML_Command_NonEmpty.
 *
 * PHP version 5
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2006 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/XML_Query2XML
 * @access    private
 */

/**
 * XML_Query2XML_Command_NonEmpty extends the class XML_Query2XML_Command_Chain.
 */
require_once 'XML/Query2XML/Command/Chain.php';

/**
 * Command class implementing a condition based on whether the value
 * returned by a pre-processor is an object or a non-empty string.
 *
 * XML_Query2XML_Command_NonEmpty requires a pre-processor to be used.
 *
 * usage:
 * <code>
 * $commandObject = new XML_Query2XML_Command_NonEmpty(
 *   new XML_Query2XML_Command_ColumnValue('name')  //pre-processor
 * );
 * </code>
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2006 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/XML_Query2XML
 * @access    private
 * @since     Release 1.5.0RC1
 */
class XML_Query2XML_Command_NonEmpty extends XML_Query2XML_Command_Chain implements XML_Query2XML_Command_Conditional
{
    /**
     * Called by XML_Query2XML for every record in the result set.
     *
     * @param array $record An associative array.
     *
     * @return mixed whatever is returned by the pre-processor
     * @throws XML_Query2XML_ConfigException If no pre-processor was set.
     */
    public function execute(array $record)
    {
        return $this->runPreProcessor($record);
    }
    
    /**
     * As this class implements XML_Query2XML_Command_Conditional, XML_Query2XML
     * will call this method to determin whether the condition is fulfilled.
     *
     * @param mixed $value The value previously returned by $this->execute().
     *
     * @return boolean Whether the condition is fulfilled.
     */
    public function evaluateCondition($value)
    {
        return is_object($value) ||
            !(is_null($value) || (is_string($value) && strlen($value) == 0));
    }
}
?>