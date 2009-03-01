<?php
/**
 * This file contains the class XML_Query2XML_Data_Source.
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
 * @access    private
 */

/**
 * XML_Query2XML_Data_Source extends XML_Query2XML_Data.
 */
require_once 'XML/Query2XML/Data.php';

/**
 * Abstract class extended by all Data Source Classes.
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2009 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/XML_Query2XML
 * @access    private
 * @since     Release 1.7.1RC1
 */
abstract class XML_Query2XML_Data_Source extends XML_Query2XML_Data
{
    /**
     * This method will be called by XML_Query2XML to create a new instance
     * of a class extending this class.
     *
     * @param string $dataSourceString A string specifying the data source.
     * @param string $configPath       The configuration path used for exception
     *                                 messsages.
     *
     * @return XML_Query2XML_Data_Source
     */
    public abstract function create($dataSourceString, $configPath);
    
    /**
     * Returns the first pre-processor.
     *
     * As this is a Data Source Class, it has no pre-processors.
     * Therefore $this will be returned.
     *
     * @return XML_Query2XML_Data_Source $this
     */
    public final function getFirstPreProcessor()
    {
        return $this;
    }
    
    /**
     * Replaces every occurence of an asterisk ('*') in the data source
     * specification.
     *
     * @param string $columnName The name of the column that is to replace
     *                           all occurences of '*'.`
     *
     * @return void
     */
    public abstract function replaceAsterisks($columnName);
}
?>