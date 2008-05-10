<?php
/**
 * This file contains the class XML_Query2XML_Command_XPath.
 *
 * PHP version 5
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2008 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/XML_Query2XML
 */

/**
 * XML_Query2XML_Command_XPath extends the class XML_Query2XML_Command_Chain.
 */
require_once 'XML/Query2XML/Command/Chain.php';

/**
 * Command class that allows for easy integration of DOMXPath.
 *
 * usage:
 * <code>
 * $commandObject = new XML_Query2XML_Command_XPath(
 *   new DOMXPath(...),
 *   '/music_store/album[artist_id="?"]',
 *   array('artistid')
 * );
 * </code>
 * alternatively the first argument can also be a string that specifies
 * - the path to an XML file
 * - an XPath query
 * - a comma-separated list of column names (optional)
 * - the placeholder string to use (optional)
 * Each of the elements is separated by two colons ('::').
 * <code>
 * $commandObject = new XML_Query2XML_Command_XPath(
 *   './albums.xml::/music_store/album[artist_id="?"]::artistid'
 * );
 * </code>
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2008 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/XML_Query2XML
 * @since     Release 1.8.0RC1
 */
class XML_Query2XML_Command_XPath extends XML_Query2XML_Command_Chain implements XML_Query2XML_Command_DataSource
{
    /**
     * THe DOMXPath instance used.
     * @var DOMXPath An instance of DOMXPath
     */
    private $_xpath = null;
    
    /**
     * An array of column names.
     * @var array
     */
    private $_data = array();
    
    /**
     * The placeholder string to be used.
     * @var string
     */
    private $_placeholder;
    
    /**
     * Constructor function.
     *
     * @param mixed  $xpath       On instance of DOMXPath or a string with the
     *                            following format:
     *                            'FILE_NAME::XPATH_QUERY::COLUMN_NAMES::PLACEHOLDER'
     *                            Note: the last of the two doubl-column separated
     *                            fields are optional. If $xpath is specified using
     *                            a string, it overwrites the other arguments if
     *                            present.
     * @param string $query       The XPath query.
     * @param mixed  $data        A string or an array of strings. Each string will
     *                            replace an occurance of $placeholder within $query.
     *                            This argument is optional.
     * @param string $placeholder The string to use as a placeholder. The default
     *                            is '?'. This argument is optional.
     */
    public function __construct($xpath,
                                $query = '',
                                $data = null,
                                $placeholder = '?')
    {
        
        if (is_string($xpath)) {
            // e.g. 'albums.xml::/music_store/album[artist_id="?"]::artistid::?'
            $parts = split('::', $xpath);
            if (count($parts) < 2) {
                //throw exception
            }
            $fileName = $parts[0];
            $query    = $parts[1];
            if (count($parts) > 2) {
                $parts[2] = str_replace(' ', '', $parts[2]);
                $data     = split(',', $parts[2]);
            } else {
                $data = array();
            }
            if (count($parts) > 3) {
                $placeholder = $parts[3];
            }
            $domDocument = new DOMDocument();
            $domDocument->preserveWhiteSpace = false;
            $success = $domDocument->load($fileName);
            if (!$success) {
                throw new XML_Query2XML_Exception(
                    'XML_Query2XML_Command_XPath::__construct(): '
                    . 'Could not load XML file "' . $fileName . '".'
                );
            }
            $xpath = new DOMXPath($domDocument);
        } elseif (!($xpath instanceof DOMXPath)) {
            // unit test: MISSING
            throw new XML_Query2XML_Exception(
                'XML_Query2XML_Command_XPath::__construct(): '
                . 'DOMDocument, DOMXPath or string expected as first argument.'
            );
        }
        $this->_xpath = $xpath;
        $this->_query = $query;
        if (is_string($data)) {
            $this->_data = array($data);
        } elseif (is_array($data)) {
            $this->_data = $data;
        } else {
            // unit test: MISSING
            throw new XML_Query2XML_Exception(
                'XML_Query2XML_Command_XPath::__construct(): '
                . 'array or string expected as third argument.'
            );
        }
        $this->_placeholder = $placeholder;
    }
    
    /**
     * Called by XML_Query2XML for every record in the result set.
     *
     * @param array $record An associative array.
     *
     * @return array An array of DOMNode instances.
     * @throws XML_Query2XML_ConfigException If any of the columns specified
     *                                       using the constructor arguments
     *                                       does not exist.
     */
    public function execute(array $record)
    {
        $data = array();
        foreach ($this->_data as $columnName) {
            if (array_key_exists($columnName, $record)) {
                $data[] = $record[$columnName];
            } else {
                // UNIT TEST: MISSING
                throw new XML_Query2XML_ConfigException(
                    $this->configPath . 'The column "' . $columnName
                    . '" was not found in the result set.'
                );
            }
        }
        $query = self::_replacePlaceholders(
            $this->_query,
            $data,
            $this->_placeholder
        );
        
        $elements = array();
        $result   = $this->_xpath->query($query);
        foreach ($result as $element) {
            $elements[] = $element;
        }
        return $elements;
    }
    
    /**
     * Replaces all placeholder strings (e.g. '?') with replacement strings.
     *
     * @param string $string        The string in which to replace the placeholder
     *                              strings.
     * @param array  &$replacements An array of replacement strings.
     * @param string $placeholder   The placeholder string.
     *
     * @return string The modified version of $string.
     */
    private static function _replacePlaceholders($string,
                                                 &$replacements,
                                                 $placeholder)
    {
        while (($pos = strpos($string, $placeholder)) !== false) {
            if (count($replacements) > 0) {
                $string = substr($string, 0, $pos) .
                          array_shift($replacements) .
                          substr($string, $pos+strlen($placeholder));
            } else {
                break;
            }
        }
        return $string;
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
        $this->_query = str_replace('*', $columnName, $this->_query);
        foreach ($this->_data as $key => $value) {
            $this->_data[$key] = str_replace('*', $columnName, $value);
        }
    }
}
?>