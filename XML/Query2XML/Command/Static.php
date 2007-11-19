<?php
/**
 * This file contains the class XML_Query2XML_Command_Static.
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
 * XML_Query2XML_Command_Static extends the class XML_Query2XML_Command_Chain.
 */
require_once 'XML/Query2XML/Command/Chain.php';

/**
 * Command class that allows a static value to be used as the data source.
 *
 * This command class does not accept a pre-processor.
 *
 * usage:
 * <code>
 * $commandObject = new XML_Query2XML_Command_Static('my static value');
 * </code>
 *
 * The static value can also be an instance of DOMNode or an array of DOMNode
 * instances:
 * <code>
 * $commandObject = new XML_Query2XML_Command_Static(new DOMElement('test'));
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
class XML_Query2XML_Command_Static extends XML_Query2XML_Command_Chain implements XML_Query2XML_Command_DataSource
{
    /**
     * The static data.
     * @var mixed
     */
    private $_data = null;
    
    /**
     * Constructor
     *
     * @param mixed $data The static data.
     */
    public function __construct($data)
    {
        if ($data === false) {
            $data = '';
        }
        $this->_data = $data;
    }
    
    /**
     * Called by XML_Query2XML for every record in the result set.
     *
     * @param array $record An associative array.
     *
     * @return mixed Whatever was passed as $data to the constructor.
     */
    public function execute(array $record)
    {
        return $this->_data;
    }
    
    /**
     * This method is called by XML_Query2XML in case the asterisk shortcut was used.
     *
     * The interface XML_Query2XML_Command_DataSource requires an implementation of
     * this method.
     *
     * @param string $columnName The column name that is to replace every occurance
     *                           of the asterisk character '*' in the static value,
     *                           in case it is a string.
     *
     * @return void
     */
    public function replaceAsterisks($columnName)
    {
        if (is_string($this->_data)) {
            $this->_data = str_replace('*', $columnName, $this->_data);
        }
    }
}
?>