<?php
/**
 * This file contains the class XML_Query2XML_Command_Base64.
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
 * XML_Query2XML_Command_Base64 extends the class XML_Query2XML_Command_Chain.
 */
require_once 'XML/Query2XML/Command/Chain.php';

/**
 * Command class that base64-encodes the string returned by a pre-processor.
 *
 * XML_Query2XML_Command_Base64 only works with a pre-processor
 * that returns a string.
 *
 * usage:
 * <code>
 * $commandObject = new XML_Query2XML_Command_Base64(
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
class XML_Query2XML_Command_Base64 extends XML_Query2XML_Command_Chain
{
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
                $this->configPath . ': XML_Query2XML_Command_Base64: string '
                . 'expected from pre-processor, but ' . gettype($data) . ' returned.'
            );
        }
        return base64_encode($data);
    }
}
?>