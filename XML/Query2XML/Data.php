<?php
/**
 * This file contains the class XML_Query2XML_Data.
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
* XML_Query2XML_Data implements the interface XML_Query2XML_Callback.
*/
require_once 'XML/Query2XML/Callback.php';

/**
 * Abstract class extended by all Data classes.
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
abstract class XML_Query2XML_Data implements XML_Query2XML_Callback
{
    /**
     * Returns the first pre-processor in the chain.
     *
     * @return XML_Query2XML_Data
     */
    public abstract function getFirstPreProcessor();
    
    /**
     * Returns a textual representation of this instance.
     *
     * @return string
     */
    public abstract function toString();
}
?>