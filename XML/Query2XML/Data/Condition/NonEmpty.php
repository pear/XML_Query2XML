<?php
/**
 * This file contains the class XML_Query2XML_Data_Condition_NonEmpty.
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
 * XML_Query2XML_Data_Condition_NonEmpty extends
 * XML_Query2XML_Data_Condition.
 */
require_once 'XML/Query2XML/Data/Condition.php';

/**
 * Data Condition Class implementing a condition based on whether the
 * value returned by a pre-processor is an object or a non-empty string.
 *
 * XML_Query2XML_Data_Condition_NonEmpty requires a pre-processor to be used.
 *
 * usage:
 * <code>
 * $commandObject = new XML_Query2XML_Data_Condition_NonEmpty(
 *   new XML_Query2XML_Data_Source_ColumnValue('name')  //pre-processor
 * );
 * </code>
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
class XML_Query2XML_Data_Condition_NonEmpty extends XML_Query2XML_Data_Condition
{
    /**
     * Create a new instance of this class.
     *
     * @param XML_Query2XML_Data $preProcessor The pre-processor to be used.
     *                                         This argument is optional.
     * @param string             $configPath   The configuration path within
     *                                         the $options array. This argument
     *                                         is optional.
     *
     * @return XML_Query2XML_Data_Condition_NonEmpty
     */
    public function create(XML_Query2XML_Data $preProcessor = null,
                           $configPath = '')
    {
        $commandObject = new XML_Query2XML_Data_Condition_NonEmpty();
        $commandObject->preProcessor = $preProcessor;
        $commandObject->configPath   = $configPath;
        if ($commandObject->configPath) {
            $commandObject->configPath .= ': ';
        }
        return $commandObject;
    }
    
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
     * As this class implements XML_Query2XML_Data_Condition, XML_Query2XML
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