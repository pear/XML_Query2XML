<?php
/**This file contains the class XML_Query2XML_Command_NonEmpty.
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

/**XML_Query2XML_Command_NonEmpty extends the class XML_Query2XML_Command_Chain.
*/
require_once 'XML/Query2XML/Command/Chain.php';

/**Command class implementing a condition based on whether the value
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
* @access private
* @author Lukas Feiler <lukas.feiler@lukasfeiler.com>
* @version Release: @package_version@
* @copyright Empowered Media 2006
* @package XML_Query2XML
* @since Interface available since Release 1.5.0RC1
*/
class XML_Query2XML_Command_NonEmpty extends XML_Query2XML_Command_Chain implements XML_Query2XML_Command_Conditional
{
    /**Called by XML_Query2XML for every record in the result set.
    *
    * @throws XML_Query2XML_ConfigException If no pre-processor was set.
    * @param array $record An associative array.
    * @return mixed whatever is returned by the pre-processor
    */
    public function execute(array $record)
    {
        return $this->runPreProcessor($record);
    }
    
    /**As this class implements XML_Query2XML_Command_Conditional, XML_Query2XML
    * will call this method to determin whether the condition is fulfilled.
    * @param mixed $value The value previously returned by $this->execute().
    * @return boolean Whether the condition is fulfilled.
    */
    public function evaluateCondition($value)
    {
        return is_object($value) ||
            !(is_null($value) || (is_string($value) && strlen($value) == 0));
    }
}
?>