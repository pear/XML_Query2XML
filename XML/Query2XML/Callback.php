<?php
/**This file contains the interface XML_Query2XML_Callback.
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
* @copyright Empowered Media 2007
* @license http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
* @author Lukas Feiler <lukas.feiler@lukasfeiler.com>
* @package XML_Query2XML
* @version $Id$
*/


/**Callback interface
*
* If you want to use a non-static method as a callback for XML_Query2XML
* you have to use an instance of a class that implements this interface.
* Your command class (read more about the command pattern 
* {@link http://en.wikipedia.org/wiki/Command_pattern here}) therefore
* has to implement a public method that is named "execute" and accepts an
* array as its first argument. Here goes an example:
* <code>
* require_once 'XML/Query2XML/Callback.php';
* class MyCallback implements XML_Query2XML_Callback
* {
*   public function execute(array $record)
*   {
*       $data = $record['some_column'];
*       //do some really complex things with $data
*
*       return $data;
*   }
* }
* $myCallback = new MyCallback();
* </code>
* XML_Query2XML will always invoke the execute() method and will pass
* the current record as an associative array as the first and only argument.
* A command object can be used for
* - Simple Element Specifications
* - Complex Element Specifications ($options['value')
* - Simple Attribute Specifications
* - Complex Attribute Specifications ($options['value')
* - $options['condition']
* - $options['sql']['data']
* - $options['idColumn']
*
* If you want to use the same command class for different columns, I suggest
* you pass the column name to the constructor:
* <code>
* require_once 'XML/Query2XML/Callback.php';
* class MyCallback implements XML_Query2XML_Callback
* {
*   private $_columnName = '';
*   public function __construct($columnName)
*   {
*       $this->_columnName = $columnName;
*   }
*   public function execute(array $record)
*   {
*       if (!isset($record[$this->_columnName])) {
*           //throw an exception here
*       }
*       $data = $record[$this->_columnName];
*       //do some really complex things with $data
*
*       return $data;
*   }
* }
* $myCallback = new MyCallback('some_column_name');
* </code>
*
* @author Lukas Feiler <lukas.feiler@lukasfeiler.com>
* @version Release: @package_version@
* @copyright Empowered Media 2007
* @package XML_Query2XML
* @since Interface available since Release 1.1.0
*/
interface XML_Query2XML_Callback
{
    /**This method will be called by XML_Query2XML.
    * This method has to return a value that can be cast to a string
    * or if used within a Complex Element Specification, an instance
    * of DOMNode.
    *
    * @param array $record A record as an associative array.
    * @return mixed A value that can be cast to a string or an instance of DOMNode.
    */
    public function execute(array $record);
}

/**This interface has to be implemented by all command classes that provide
* a condition as to whether the returne value of execute() is to be used.
*
* @access private
* @author Lukas Feiler <lukas.feiler@lukasfeiler.com>
* @version Release: @package_version@
* @copyright Empowered Media 2007
* @package XML_Query2XML
* @since Interface available since Release 1.5.0RC1
*/
interface XML_Query2XML_Command_Conditional extends XML_Query2XML_Callback
{
    /**Returns a boolean value indicating whether the return value of execute()
    * is to be used.
    * @param string $string The return value of execute()
    * @return boolean
    */
    public function evaluateCondition($string);
}

/**This interface has to be implemented by all command classes that function
* as a data source and wish to provide support for the asterisk shortcut.
*
* @access private
* @author Lukas Feiler <lukas.feiler@lukasfeiler.com>
* @version Release: @package_version@
* @copyright Empowered Media 2007
* @package XML_Query2XML
* @since Interface available since Release 1.5.0RC1
*/
interface XML_Query2XML_Command_DataSource extends XML_Query2XML_Callback
{
    /**Replaces every occurence of an asterisk ('*') in the data source
    * specification.
    * @param string $columnName The name of the column that is to replace
    *                           all occurences of '*'.
    */
    public function replaceAsterisks($columnName);
}
?>