<?php
/**This file contains the class XML_Query2XML_Command_CDATA.
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

/**XML_Query2XML_Command_CDATA extends the class XML_Query2XML_Command_Chain.
*/
require_once 'XML/Query2XML/Command/Chain.php';

/**Command class that creates a CDATA section around the string returned by
* a pre-processor.
*
* XML_Query2XML_Command_CDATA only works with a pre-processor
* that returns a string.
*
* usage:
* <code>
* $commandObject = new XML_Query2XML_Command_CDATA(
*   new XML_Query2XML_Command_ColumnValue('name')  //pre-processor
* );
*
* @access private
* @author Lukas Feiler <lukas.feiler@lukasfeiler.com>
* @version Release: @package_version@
* @copyright Empowered Media 2006
* @package XML_Query2XML
* @since Interface available since Release 1.5.0RC1
* </code>
*/
class XML_Query2XML_Command_CDATA extends XML_Query2XML_Command_Chain
{
    /**Called by XML_Query2XML for every record in the result set.
    * This method will return an instance of DOMCDATASection or null
    * if an empty string was returned by the pre-processor.
    *
    * @throws XML_Query2XML_ConfigException If the pre-processor returns
    *                      something that cannot be converted to a string (i.e. an
    *                      array or an object) or if no pre-processor was set.
    * @param array $record An associative array.
    * @return DOMCDATASection
    */
    public function execute(array $record)
    {
        $doc = new DOMDocument();
        $data = $this->runPreProcessor($record);
        if (is_array($data) || is_object($data)) {
            throw new XML_Query2XML_ConfigException(
                $this->configPath . 'XML_Query2XML_Command_CDATA: string expected '
                . 'from pre-processor, but ' . gettype($data) . ' returned.'
            );
        }
        if (strlen($data) > 0) {
            return $doc->createCDATASection($data);
        } else {
            return null;
        }
    }
}
?>