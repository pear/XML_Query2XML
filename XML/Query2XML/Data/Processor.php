<?php
/**
 * This file contains the class XML_Query2XML_Data_Processor.
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
 * XML_Query2XML_Data_Processor extends XML_Query2XML_Data.
 */
require_once 'XML/Query2XML/Data.php';

/**
 * Abstract class extended by all Data Processor Classes.
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
abstract class XML_Query2XML_Data_Processor extends XML_Query2XML_Data
{
    /**
     * Another instance of XML_Query2XML_Data to process before this one.
     * @var XML_Query2XML_Data
     */
    protected $preProcessor = null;
    
    /**
     * The configuration path; it is used for exception messages.
     * @var string
     */
    protected $configPath = '';
    
    /**
     * Create a new instance of this class.
     *
     * @param XML_Query2XML_Data $preProcessor The pre-processor to be used.
     *                                         This argument is optional.
     * @param string             $configPath   The configuration path within
     *                                         the $options array. This argument
     *                                         is optional.
     *
     * @return XML_Query2XML_Data_Processor
     */
    public abstract function create(XML_Query2XML_Data $preProcessor = null,
                                    $configPath = '');
    
    /**
     * Allows the pre-processor to be set (or changed) after an instance was created.
     *
     * @param XML_Query2XML_Data $preProcessor The pre-processor to be used.
     *
     * @return void
     */
    public function setPreProcessor(XML_Query2XML_Data $preProcessor)
    {
        $this->preProcessor = $preProcessor;
    }
    
    /**
     * Returns the first pre-processor in the chain.
     *
     * @return XML_Query2XML_Data
     */
    public function getFirstPreProcessor()
    {
        if (!is_null($this->preProcessor)) {
            return $this->preProcessor->getFirstPreProcessor();
        }
        return $this;
    }
    
    /**
     * Runs the pre-processor if one was defined and returns it's return value.
     *
     * @param array $record The record to process - this is an associative array.
     *
     * @return mixed Whatever was returned by the pre-processor
     * @throws XML_Query2XML_ConfigException If no pre-processor was defined.
     */
    protected function runPreProcessor(array $record)
    {
        if (!is_null($this->preProcessor)) {
            return $this->preProcessor->execute($record);
        } else {
            include_once 'XML/Query2XML.php';
            // UNIT TEST: MISSING
            throw new XML_Query2XML_ConfigException(
                $this->configPath . get_class($this) . ' requires a pre-processor.'
            );
        }
    }
    
    /**
     * Returns a textual representation of this instance.
     * This might be useful for debugging.
     *
     * @return string
     */
    public function toString()
    {
        $str = get_class($this) . '(';
        if (!is_null($this->preProcessor)) {
            $str .= $this->preProcessor->toString();
        }
        return $str . ')';
    }
}
?>