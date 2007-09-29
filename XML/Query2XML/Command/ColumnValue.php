<?php
/**This file contains the class XML_Query2XML_Command_ColumnValue.
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

/**XML_Query2XML_Command_ColumnValue extends the class XML_Query2XML_Command_Chain.
*/
require_once 'XML/Query2XML/Command/Chain.php';

/**Command class that allows the column value to be used as the data source.
*
* This command class does not accept a pre-processor.
*
* usage:
* <code>
* $commandObject = new XML_Query2XML_Command_ColumnValue('name');
* </code>
*
* @access private
* @author Lukas Feiler <lukas.feiler@lukasfeiler.com>
* @version Release: @package_version@
* @copyright Empowered Media 2006
* @package XML_Query2XML
* @since Interface available since Release 1.5.0RC1
*/
class XML_Query2XML_Command_ColumnValue extends XML_Query2XML_Command_Chain implements XML_Query2XML_Command_DataSource
{
    /**The column name.
    * @var string
    */
    private $_column = '';
    
    /**Constructor
    * @param string $column The name of the column.
    * @param string $configPath The configuration path within the $options array.
    */
    public function __construct($column, $configPath)
    {
        $this->_column = $column;
        $this->configPath = $configPath;
        if ($this->configPath) {
            $this->configPath .= ': ';
        }
    }
    
    /**Called by XML_Query2XML for every record in the result set.
    *
    * @throws XML_Query2XML_ConfigException If $column does not exist in the
    *                      result set, i.e. $record[$column] does not exist.
    * @param array $record An associative array.
    * @return mixed The contents of $record[$column] where $column is the first
    *               argument passed to the constructor.
    */
    public function execute(array $record)
    {
        if (array_key_exists($this->_column, $record)) {
            return $record[$this->_column];
        }
        throw new XML_Query2XML_ConfigException(
            $this->configPath . 'The column "' . $this->_column
            . '" was not found in the result set'
        );
    }
    
    /**This method is called by XML_Query2XML in case the asterisk shortcut was used.
    *
    * The interface XML_Query2XML_Command_DataSource requires an implementation of
    * this method.
    *
    * @param string $columnName The column name that is to replace every occurance
    *                           of the asterisk character '*' in $column (the first
    *                           argument passed to the constructor).
    */
    public function replaceAsterisks($columnName)
    {
        $this->_column = str_replace('*', $columnName, $this->_column);
    }
}
?>