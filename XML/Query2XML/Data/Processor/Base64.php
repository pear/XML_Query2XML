<?php
/**
 * This file contains the class XML_Query2XML_Data_Processor_Base64.
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
 * XML_Query2XML_Data_Processor_Base64 extends the class XML_Query2XML_Data_Processor.
 */
require_once 'XML/Query2XML/Data/Processor.php';

/**
 * Data Processor Class that base64-encodes the string returned by a
 * pre-processor.
 *
 * XML_Query2XML_Data_Processor_Base64 only works with a pre-processor
 * that returns a string.
 *
 * usage:
 * <code>
 * $commandObject = new XML_Query2XML_Data_Processor_Base64(
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
class XML_Query2XML_Data_Processor_Base64 extends XML_Query2XML_Data_Processor
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
     * @return XML_Query2XML_Data_Processor_Base64
     */
    public function create(XML_Query2XML_Data $preProcessor = null,
                           $configPath = '')
    {
        $commandObject = new XML_Query2XML_Data_Processor_Base64();
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
     * @return string The base64-encoded version the string returned
     *                by the pre-processor.
     * @throws XML_Query2XML_ConfigException If the pre-processor returns
     *                something that cannot be converted to a string
     *                (i.e. an object or an array).
     */
    public function execute(array $record)
    {
        $data = $this->runPreProcessor($record);
        if (is_array($data) || is_object($data)) {
            throw new XML_Query2XML_ConfigException(
                $this->configPath . ': XML_Query2XML_Data_Processor_Base64: string '
                . 'expected from pre-processor, but ' . gettype($data) . ' returned.'
            );
        }
        return base64_encode($data);
    }
}
?>