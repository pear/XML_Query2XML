<?php
/**
 * This file contains the class XML_Query2XML_Command_ColumnValue.
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
 * XML_Query2XML_Command_ColumnValue extends the class XML_Query2XML_Command_Chain.
 */
require_once 'XML/Query2XML/Command/Chain.php';

/**
 * Command class that allows the column value to be used as the data source.
 *
 * This command class does not accept a pre-processor.
 *
 * usage:
 * <code>
 * $commandObject = new XML_Query2XML_Command_ColumnValue('name');
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
class XML_Query2XML_Command_ColumnValue extends XML_Query2XML_Command_Chain implements XML_Query2XML_Command_DataSource
{
    /**
     * The column name.
     * @var string
     */
    private $_column = '';
    
    /**
     * Constructor
     *
     * @param string $column     The name of the column.
     * @param string $configPath The configuration path within the $options array.
     */
    public function __construct($column, $configPath)
    {
        $this->_column    = $column;
        $this->configPath = $configPath;
        if ($this->configPath) {
            $this->configPath .= ': ';
        }
    }
    
    /**
     * Called by XML_Query2XML for every record in the result set.
     *
     * @param array $record An associative array.
     *
     * @return mixed The contents of $record[$column] where $column is the first
     *               argument passed to the constructor.
     * @throws XML_Query2XML_ConfigException If $column does not exist in the
     *               result set, i.e. $record[$column] does not exist.
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
    
    /**
     * This method is called by XML_Query2XML in case the asterisk shortcut was used.
     *
     * The interface XML_Query2XML_Command_DataSource requires an implementation of
     * this method.
     *
     * @param string $columnName The column name that is to replace every occurance
     *                           of the asterisk character '*' in $column (the first
     *                           argument passed to the constructor).
     *
     * @return void
     */
    public function replaceAsterisks($columnName)
    {
        $this->_column = str_replace('*', $columnName, $this->_column);
    }
}
?>