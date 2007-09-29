<?php
/**This file contains the class XML_Query2XML_Command_Unserialize.
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
* @access private
*/

/**XML_Query2XML_Command_Unserialize extends the class XML_Query2XML_Command_Chain.
*/
require_once 'XML/Query2XML/Command/Chain.php';

/**Command object that allows unserialization of XML data returned by a pre-processor
* as a string.
*
* XML_Query2XML_Command_Unserialize only works with a pre-processor
* that returns a string.
*
* usage:
* <code>
* $commandObject = new XML_Query2XML_Command_Unserialize(
*   new XML_Query2XML_Command_ColumnValue('xml_data')  //pre-processor
* );
* </code>
*
* @access private
* @author Lukas Feiler <lukas.feiler@lukasfeiler.com>
* @version Release: @package_version@
* @copyright Empowered Media 2006
* @package XML_Query2XML
* @since Interface available since Release 1.5.0RC1
*/
class XML_Query2XML_Command_Unserialize extends XML_Query2XML_Command_Chain
{
    /**Called by XML_Query2XML for every record in the result set.
    * This method will return an instance of DOMElement or null
    * if an empty string was returned by the pre-processor.
    *
    * @throws XML_Query2XML_ConfigException If the pre-processor returned
    *                      something that cannot be converted to a string (i.e. an
    *                      array or an object) or if that string could not be
    *                      unserialized, i.e. was not corretly formatted XML.
    * @param array $record An associative array.
    * @return DOMElement
    */
    public function execute(array $record)
    {
        $doc = new DOMDocument();
        $xml = $this->runPreProcessor($record);
        if (is_array($xml) || is_object($xml)) {
            throw new XML_Query2XML_XMLException(
                $this->configPath . 'XML_Query2XML_Command_Unserialize: string '
                . 'expected from pre-processor, but ' . gettype($xml) . ' returned.'
            );
        } else {
            if (strlen($xml)) {
                if (!@$doc->loadXML($xml)) {
                    throw new XML_Query2XML_XMLException(
                        $this->configPath . 'XML_Query2XML_Command_Unserialize: '
                        . 'Could not unserialize the following XML data: "'
                        . $xml . '"'
                    );
                }
                return $doc->documentElement;
            } else {
                return null;
            }    
        }
    }
}
?>