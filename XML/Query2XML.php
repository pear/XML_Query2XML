<?php
/**This file contains the class XML_Query2XML and all exception classes.
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
*/

/**PEAR_Exception is used as the parent for XML_Query2XML_Exception.
*/
require_once 'PEAR/Exception.php';

/**PEAR.php will get included by DB or MDB2 anyway. As the method
* PEAR::isError() is used within XML_Query2XML we include it too.
*/
require_once 'PEAR.php';

/**Create XML data from SQL queries.
*
* XML_Query2XML heavily uses exceptions and therefore requires PHP5.
* PEAR DB or PEAR MDB2 is also required.
* The two most important public methods this class provides are:
*
* <b>{@link XML_Query2XML::getFlatXML()}</b>
* Transforms your SQL query into flat XML data.
*
* <b>{@link XML_Query2XML::getXML()}</b>
* Very powerful and flexible method that can produce whatever XML data you want. It
* was specifically written to also handle LEFT JOINS.
*
* They both return an instance of the class DomDocument provided by PHP5's
* DOM XML extension.
*
* A typical usage of XML_Query2XML looks like this:
* <code>
* require_once('XML/Query2XML.php');
* $query2xml = new XML_Query2XML(DB::connect($dsn));
* $dom = $query2xml->getXML($sql, $options);
* header('Content-Type: application/xml');
* print $dom->saveXML();
* </code>
*
* Please read the <b>{@tutorial XML_Query2XML.pkg tutorial}</b> for
* detailed usage examples and more documentation.
* 
* @author Lukas Feiler <lukas.feiler@lukasfeiler.com>
* @version Release: @package_version@
* @copyright Empowered Media 2006
* @package XML_Query2XML
* @todo $options['idColumn'] should also accept multiple columns in an array
*/
class XML_Query2XML
{
    /**An instance of PEAR DB or PEAR MDB2
	* @var mixed A subclass of DB_common or MDB2_Driver_Common.
	*/
    private $_db;
    
    /**An associative, multi-dimensional arrray used by {@see _getAllRecordsCached}
    * to cache retrieved records.
    * @var array An associative array; the query results are stored using the SQL
    *            query as the key.
    */
    private $_recordCache = array();
    
    /**An associative array used to store query handles returned by
    * DB_common::prepare() or MDB2_Driver_Common::prepare().
    * @var array An associative array; the query string is used as the array key.
    */
    private $_preparedQueries = array();
    
    /**An instance of PEAR Log
    * @var mixed An object that has a method with the signature log(String $msg);
    * preferably PEAR Log.
    * @see enableDebugLog
    * @see disableDebugLog
    */
    private $_debugLogger;
    
    /**Whether debug logging is to be performed
    * @var boolean
    * @see enableDebugLog
    * @see disableDebugLog
    */
    private $_debug = false;
    
    /**Whether profiling is to be performed
    * @var boolean
    * @see startProfiling()
    * @see stopProfiling()
    */
    private $_profiling = false;
    
    /**The profiling data.
    * @var array A multi dimensional associative array
    * @see startProfiling()
    * @see stopProfiling()
    * @see _debugStartQuery()
    * @see _debugStopQuery()
    * @see _debugCachedQuery()
    * @see _debugCachingQuery()
    * @see _stopDBProfiling()
    */
    private $_profile = array();
    
    /**Whether a MDB2 or DB abstraction layer is used.
    * @var boolean True if MDB2 is used.
    * @see _db
    */
    private $_isMDB2 = false;
    
    /**Constructor
    * @throws XML_Query2XML_DBException     If $db already is a PEAR error.
    * @throws XML_Query2XML_ConfigException If $db is not an instance of a child
    *                                       class of DB_common or MDB2_Driver_Common.
    * @param mixed $db                      An instance of PEAR DB or PEAR MDB2
    */
    private function __construct($db)
    {        
        if (
            class_exists('DB_common') &&
            $db instanceof DB_common
        ) {
            $this->_dbLayer = 'DB';
            $this->_db = $db;
        } elseif (
            class_exists('MDB2_Driver_Common') &&
            $db instanceof MDB2_Driver_Common
        ) {
            $this->_dbLayer = 'MDB2';
            $this->_db = $db;
        } elseif (
            class_exists('ADOConnection') &&
            $db instanceof ADOConnection
        ) {    
            $this->_dbLayer = 'ADOdb';
            if (!$db->IsConnected()) {
                //unit test: Query2XMLTestADOdb::testFactoryNotConnectedException()
                throw new XML_Query2XML_DBException(
                    'ADOConnection instance was not connected'
                );
            }
            $this->_db = $db;
        } elseif (PEAR::isError($db)) {
            //unit test: Query2XMLTest::testFactoryDBErrorException()
            throw new XML_Query2XML_DBException(
                'Could not connect to database: ' . $db->toString()
            );
        } else {
            //unit test: Query2XMLTest::testFactoryWrongArumentTypeException()
            throw new XML_Query2XML_ConfigException(
                'Argument passed to the XML_Query2XML constructor is not an '
                . 'instance of DB_common, MDB2_Driver_Common or ADOConnection.'
            );
        }
        
        if ($this->_dbLayer == 'MDB2') {
            $fetchModeError = $this->_db->setFetchMode(MDB2_FETCHMODE_ASSOC);
        } elseif ($this->_dbLayer == 'ADOdb') {
            //SetFetchMode only returns the previous fetch mode
            $this->_db->SetFetchMode(ADODB_FETCH_ASSOC);
            $fetchModeError = null;
        } else {
            $fetchModeError = $this->_db->setFetchMode(DB_FETCHMODE_ASSOC);
        }
        if (PEAR::isError($fetchModeError)) {
            throw new XML_Query2XML_DBException(
                'Could not set fetch mode to FETCHMODE_ASSOC: '
                . $fetchModeError->toString()
            );
        }
    }
    
    /**Factory method.
    * As first argument pass an instance of PEAR DB or PEAR MDB2:
    * <code>
    * require_once('XML/Query2XML.php');
    * require_once('DB.php');
    * $query2xml = XML_Query2XML::factory(
    *   DB::connect('mysql://root@localhost/Query2XML_Tests')
    * );
    * </code>
    * 
    * <code>
    * require_once('XML/Query2XML.php');
    * require_once('MDB2.php');
    * $query2xml = XML_Query2XML::factory(
    *   MDB2::factory('mysql://root@localhost/Query2XML_Tests')
    * );
    * </code>
    *
    * @throws XML_Query2XML_DBException     If $db already is a PEAR error.
    * @throws XML_Query2XML_ConfigException If $db is not an instance of a child
    *                                       class of DB_common or MDB2_Driver_Common.
    * @param mixed $db                      An instance of PEAR DB or PEAR MDB2
    * @return XML_Query2XML                 A new instance of XML_Query2XML
    */
    public static function factory($db)
    {
        return new XML_Query2XML($db);
    }
    
    /**Enable the logging of debug messages.
    * This will include all queries sent to the database.
    * Example:
    * <code>
    * require_once('Log.php');
    * require_once('XML/Query2XML.php');
    * $query2xml = new XML_Query2XML(DB::connect($dsn));
    * $debugLogger = Log::factory('file', 'out.log', 'XML_Query2XML');
    * $query2xml->enableDebugLog($debugLogger);
    * </code>
    * Please see {@link http://pear.php.net/package/Log} for details on PEAR Log.
    *
    * @param mixed $log  Most likely an instance of PEAR::Log but any object
    *                    that provides a method named 'log' is accepted.
    */
    public function enableDebugLog($log)
    {
        $this->_debugLogger = $log;
        $this->_debug = true;
    }
    
    /**Disable the logging of debug messages
    */
    public function disableDebugLog()
    {
        $this->_debug = false;
    }
    
    /**Start profiling.
    */
    public function startProfiling()
    {
        $this->_profile = array(
            'queries'    => array(),
            'start'      => microtime(1),
            'stop'       => 0,
            'duration'   => 0,
            'dbStop'     => 0,
            'dbDuration' => 0
        );
        $this->_profiling = true;
    }
    
    /**Stop profiling.
    */
    public function stopProfiling()
    {
        $this->_profiling = false;
        if (isset($this->_profile['start']) && $this->_profile['stop'] == 0) {
            $this->_profile['stop'] = microtime(1);
            $this->_profile['duration'] =
                $this->_profile['stop'] - $this->_profile['start'];
        }
    }
    
    /**Returns all raw profiling data.
    * In 99.9% of all cases you will want to use getProfile()
    * @see getProfile()
    * @return array
    */
    public function getRawProfile()
    {
        $this->stopProfiling();
        return $this->_profile;
    }
    
    /**Returns the profile as a single multi line string.
    * @return string The profiling data.
    */
    public function getProfile()
    {
        $this->stopProfiling();
        if (count($this->_profile) === 0) {
            return '';
        }
        $ret = 'FROM_DB FROM_CACHE CACHED AVG_DURATION DURATION_SUM SQL' . "\n";
        foreach ($this->_profile['queries'] as $sql => $value) {
            $durationSum = 0.0;
            $durationCount = 0;
            $runTimes =& $this->_profile['queries'][$sql]['runTimes'];
            foreach ($runTimes as $key => $runTime) {
                $durationSum += ($runTime['stop'] - $runTime['start']);
                ++$durationCount;
            }
            if ($durationCount == 0) {
                //so that division does not fail
                $durationCount = 1;
            }
            $durationAverage = $durationSum / $durationCount;
            if ($this->_profile['queries'][$sql]['cached']) {
                $cached = 'true';
            } else {
                $cached = 'false';
            }
            
            if (
                $this->_profile['queries'][$sql]['cached']
                &&
                $this->_profile['queries'][$sql]['fromCache'] == 0
            ) {
                $cached .= '!';
            }
            $cached = str_pad($cached, 6);
            $ret .= str_pad($this->_profile['queries'][$sql]['fromDB'], 7)
                    . ' '
                    . str_pad($this->_profile['queries'][$sql]['fromCache'], 10)
                    . ' '
                    . $cached
                    . ' '
                    . substr($durationAverage, 0, 12). ' '
                    . substr($durationSum, 0, 12). ' '
                    . $sql . "\n";
        }
        $ret .= "\n";
        $ret .= 'TOTAL_DURATION: ' . $this->_profile['duration'] . "\n";
        $ret .= 'DB_DURATION:    ' . $this->_profile['dbDuration'] . "\n";
        return $ret;
    }
    
    /**Calls {@link XML_Query2XML::stopProfiling()} and then clears the profiling
    * data by resetting a private property.
    */
    public function clearProfile()
    {
        $this->stopProfiling();
        $this->_profile = array();
    }
    
    /**Transforms the data retrieved by a single SQL query into flat XML data.
    *
    * This method will return a new instance of DomDocument. The column names
    * will be used as element names.
    *
    * Example:
    * <code>
    * require_once('XML/Query2XML.php');
    * $query2xml = XML_Query2XML::factory(DB::connect($dsn));
    * $dom = $query2xml->getFlatXML(
    *   'SELECT * FROM artist',
    *   'music_library',
    *   'artist'
    * );
    * </code>
    *
    * @throws XML_Query2XML_Exception This is the base class for the exception
    *                            types XML_Query2XML_DBException and
    *                            XML_Query2XML_XMLException. By catching
    *                            XML_Query2XML_Exception you can catch all
    *                            exceptions this method will ever throw.
    * @throws XML_Query2XML_DBException If a database error occurrs.
    * @throws XML_Query2XML_XMLException If an XML error occurrs - most likely
    *                            $rootTagName or $rowTagName is not a valid
    *                            element name.
    * @param string $sql         The query string.
    * @param string $rootTagName The name of the root tag; this argument is optional
    *                            (default: 'root').
    * @param string $rowTagName  The name of the tag used for each row; this
    *                            argument is optional (default: 'row').
    * @return DomDocument        A new instance of DomDocument.
    */
    public function getFlatXML($sql, $rootTagName = 'root', $rowTagName = 'row')
    {
        $dom = self::_createDomDocument();
        $rootTag = self::_addNewDOMChild($dom, $rootTagName);
        $records = $this->_getAllRecords($sql);
        foreach ($records as $key => $record) {
            $rowTag = self::_addNewDOMChild($rootTag, $rowTagName);
            foreach ($record as $field => $value) {
                self::_addNewDOMChild($rowTag, $field, $value);
            }
        }
        return $dom;
    }
    
    /**Transforms your SQL data retrieved by one or more queries into complex and
    * highly configurable XML data.
    *
    * This method will return a new instance of DomDocument.
    * Please see the <b>{@tutorial XML_Query2XML.pkg tutorial}</b> for details.
    * 
    * @throws XML_Query2XML_Exception This is the base class for the exception types
    *                       XML_Query2XML_DBException, XML_Query2XML_XMLException
    *                       and XML_Query2XML_ConfigException. By catching
    *                       XML_Query2XML_Exception you can catch all exceptions
    *                       this method will ever throw.
    * @throws XML_Query2XML_DBException If a database error occurrs.
    * @throws XML_Query2XML_XMLException If an XML error occurrs - most likely
    *                       an invalid XML element name.
    * @throws XML_Query2XML_ConfigException If some configuration options passed
    *                       as second argument are invalid or missing.
    * @param array $options Options for the creation of the XML data stored in an
    *                       associative, potentially mutli-dimensional array
    *                       (please see the tutorial).
    * @return DomDocument   The XML data as a new instance of DomDocument.
    */
    public function getXML($sql, $options)
    {
        //the default root tag name is 'root'
        if (isset($options['rootTag'])) {
            $rootTagName = $options['rootTag'];
        } else {
            $rootTagName = 'root';
        }
        
        $dom = self::_createDomDocument();
        $rootTag = self::_addNewDOMChild($dom, $rootTagName);
        
        $options['sql'] = $sql;
        $options['sql_options'] = array('uncached' => true);
        
        /* Used to store the information which element has been created
        *  for which ID column value.
        */
        $tree = array();
        
        $records = $this->_applySqlOptionsToRecord($options, $emptyRecord = array());
        foreach ($records as $key => $record) {
            $tag = $this->_getNestedXMLRecord($records[$key], $options, $dom, $tree);
            
            /* _getNestedXMLRecord() returns false if an element already existed for
            *  the current ID column value.
            */
            if ($tag !== false) {
                $rootTag->appendChild($tag);
            }
        }
        $this->_clearRecordCache();
        $this->_stopDBProfiling();
        
        self::_removeContainers($dom);
        return $dom;
    }
    
    /**Private recursive method that creates the nested XML elements from a record.
    *
    * getXML calls this method for every row in the initial result set.
    * The $tree argument deserves some more explanation. All DomNodes are stored
    * in $tree the way they appear in the XML document. The same hirachy needs to be
    * built so that we can know if a DomNode that corresponds to a column ID of 2 is
    * already a child node of a certain XML element. Let's have a look at an example
    * to clarify this:
    * <code>
    * <music_library>
    *   <artist>
    *     <artistid>1</artistid>
    *     <albums>
    *       <album>
    *         <albumid>1</albumid>
    *       </album>
    *       <album>
    *         <albumid>2</albumid>
    *       </album>
    *     </albums>
    *   </artist>
    *   <artist>
    *     <artistid>3</artistid>
    *     <albums />
    *   </artist>
    * </music_library>
    * </code>
    * would be represended in the $tree array as something like this:
    * <code>
    * array (
    *   [1] => array (
    *     [tag] => DOMElement Object
    *     [elements] => array (
    *       [albums] => array (
    *         [1] => array (
    *           [tag] => DOMElement Object
    *         )
    *         [2] => array (
    *           [tag] => DOMElement Object
    *         )
    *       )
    *     )
    *   )
    *   [2] => array (
    *     [tag] => DOMElement Object
    *     [elements] => array
    *     (
    *       [albums] => array ()
    *     )
    *   )
    * )
    * </code>
    * The numbers in the square brackets are column ID values.
    *
    * @see getXML()
    * @throws XML_Query2XML_DBException  Bubbles up through this method if thrown by
    *                         - _processComplexElementSpecification()
    * @throws XML_Query2XML_XMLException Bubbles up through this method if thrown by
    *                         - _createDOMElement()
    *                         - _setDOMAttribute
    *                         - _appendTextChildNode()
    *                         - _addNewDOMChild()
    *                         - _processComplexElementSpecification()
    *                         - _expandShortcuts()
    * @throws XML_Query2XML_ConfigException  Thrown if
    *                         - $options['idColumn'] is not set
    *                         - $options['elements'] is set but not an array
    *                         - $options['attributes'] is set but not an array
    *                         Bubbles up through this method if thrown by
    *                         - _applyColumnStringToRecord()
    *                         - _processComplexElementSpecification()
    *                         - _expandShortcuts()
    * @throws XML_Query2XML_Exception  Bubbles up through this method if thrown by
    *                         - _expandShortcuts()
    * @param array $record    An associative array representing a record; column
    *                         names must be used as keys.
    * @param array $options   An array containing the options for this nested 
    *                         element; this will be a subset of the array originally
    *                         passed to getXML().
    * @param DomDocument $dom An instance of DomDocument.
    * @param array $tree      An associative multi-dimensional array, that is
    *                         used to store the information which tag has already
    *                         been created for a certain ID column value.
    * @return mixed           The XML element's representation as a new instance of
    *                         DomNode or the boolean value false (meaning no
    *                         new tag was created).
    */
    private function _getNestedXMLRecord($record, &$options, $dom, &$tree)
    {
        //check $options for the essential settings first:
        if (!isset($options['idColumn'])) {
            //unit test: test_getNestedXMLRecordIdColumnMissingException()
            throw new XML_Query2XML_ConfigException(
                'The configuration option "idColumn" is missing.'
            );
        }
        if (!isset($options['elements'])) {
            $options['elements'] = array();
        } elseif (!is_array($options['elements'])) {
            //unit test: test_getNestedXMLRecordElementsTypeException()
            throw new XML_Query2XML_ConfigException(
                'The configuration option "elements" is not an array.'
            );
        }
        
        if (!isset($options['attributes'])) {
            $options['attributes'] = array();
        } elseif (!is_array($options['attributes'])) {
            //unit test: test_getNestedXMLRecordAttributesTypeException()
            throw new XML_Query2XML_ConfigException(
                'The configuration option "attributes" is not an array.'
            );
        }
        
        
        
        //the default row tag name is 'row'
        if (isset($options['rowTag'])) {
            $rowTagName = $options['rowTag'];
        } else {
            $rowTagName = 'row';
        }
        
        //the default mapper is ''
        if (isset($options['mapper'])) {
            $mapper = $options['mapper'];
            if (is_string($mapper) && strpos($mapper, '::') !== false) {
                $mapper = split('::', $mapper);
            }
            if ($mapper && !is_callable($mapper, false, $callableName)) {
                /*
                * only check if $mapper == true (that is not '', false, etc)
                *
                * unit tests:
                *  test_mapSQLIdentifierToXMLNameNotCallableException()
                *  test_mapSQLIdentifierToXMLNameNotCallableException2()
                *  test_mapSQLIdentifierToXMLNameNotCallableException3()
                */
                throw new XML_Query2XML_ConfigException(
                    'The method/function "' . $callableName . '" specified in the '
                    . 'configuration option "mapper" is not callable.'
                );
            }
        } else {
            $mapper = false;
        }
        
        $idColumn =& $options['idColumn'];
        $elements =& $options['elements'];
        $attributes =& $options['attributes'];
        if (!is_array($attributes)) {
            $attributes = array();
        }
        
        $id = $this->_applyColumnStringToRecord($idColumn, $record, 'idColumn');
        if ($id === null) {
            //the ID column is NULL
            return false;
        }
        
        //default return value
        $ret = false;

        /* Is there already an identical tag (identity being determined by the
        *  value of the ID-column)?
        */
        if (!isset($tree[$id])) {
            $tree[$id] = array();
            
            if (isset($options['value'])) {
                $parsedValue = $this->_applyColumnStringToRecord(
                    $options['value'],
                    $record,
                    'value'
                );
                if (!$this->_evaluateCondtion($parsedValue, $options['value'])) {
                    //this element is to be skipped
                    return false;
                }
            }
            if (isset($options['condition'])) {
                if (!eval('return ' . $options['condition'] . ';')) {
                    //this element is to be skipped
                    return false;
                }
            }
            $tree[$id]['tag'] = self::_createDOMElement($dom, $rowTagName);

            $tag = $tree[$id]['tag'];
            
            //add attributes
            try {
                $attributes = self::_expandShortcuts($attributes, $record, $mapper);
            } catch (XML_Query2XML_ConfigException $e) {
                //unit test: test_processComplexElementSpecificationRETHROW()
                $e->addConfigParents('attributes');
                throw $e;
            }
            foreach ($attributes as $attributeName => $column) {
                if (is_array($column)) {
                    //complex attribute specification
                    $this->_processComplexAttributeSpecification(
                        $attributeName,
                        $record,
                        $column,
                        $tag
                    );
                } elseif (is_string($column)) {
                    //simple attribute specifications
                    $attributeValue = $this->_applyColumnStringToRecord(
                        $column,
                        $record,
                        'attributes'
                    );
                    if ($this->_evaluateCondtion($attributeValue, $column)) {
                        self::_setDOMAttribute($tag, $attributeName, $attributeValue);
                    }
                } else {
                    //unit test: test_getNestedXMLRecordInvalidAttributeException()
                    throw new XML_Query2XML_ConfigException(
                        'The attribute "'
                        . $attributeName
                        . '" was not specified using a string nor an array',
                        'attributes'
                    );
                }
            }
            if (isset($options['value'])) {
                self::_appendTextChildNode($tag, $parsedValue);
            }
            
            //add child elements
            try {
                $elements = self::_expandShortcuts($elements, $record, $mapper);
            } catch (XML_Query2XML_ConfigException $e) {
                //unit test: test_processComplexElementSpecificationRETHROW()
                $e->addConfigParents('elements');
                throw $e;
            }
            foreach ($elements as $tagName => $column) {
                if (is_array($column)) {
                    //complex element specification
                    $this->_processComplexElementSpecification(
                        $record,
                        $elements[$tagName],
                        $dom,
                        $tree[$id],
                        $tagName,
                        $idColumn,
                        $mapper
                    );
                } else {
                    //simple element specification
                    $tagValue = $this->_applyColumnStringToRecord(
                        $column,
                        $record,
                        'elements'
                    );
                    if ($this->_evaluateCondtion($tagValue, $column)) {
                        self::_addNewDOMChild($tag, $tagName, $tagValue);
                    }
                }
            }
            /* We set $ret to $tag because $tag holds a newly created DomNode that
            *  needs to be added to it's parent; this is to be handled by the method
            *  that called _getNestedXMLRecord().
            */
            $ret = $tag;
        } else {
            foreach ($elements as $tagName => $column) {
                if (is_array($column)) {
                    $this->_processComplexElementSpecification(
                        $record,
                        $elements[$tagName],
                        $dom,
                        $tree[$id],
                        $tagName,
                        $idColumn,
                        $mapper
                    );
                }
            }
            //we leave $ret set to false because $tag already existed
        }
        
        //Return the whole tag (an instance of DomNode).
        return $ret;
    }
    
    /**Private method that will expand asterisk characters in an array
    * of simple element specifications.
    *
    * This method gets called to handle arrays specified using the 'elements'
    * or the 'attributes' option. An element specification that contains an
    * asterisk will be duplicated for each column present in $record.
    * Please see the {@tutorial XML_Query2XML.pkg tutorial} for details.
    *
    * @throws XML_Query2XML_ConfigException If only the column part but not the
    *                        explicitly defined tagName part contains an asterisk.
    * @throws XML_Query2XML_Exception Will bubble up if it is thrown by
    *                        _mapSQLIdentifierToXMLName(). This should never
    *                        happen as _getNestedXMLRecord() already checks if
    *                        $mapper is callable.
    * @throws XML_Query2XML_XMLException Will bubble up if it is thrown by
    *                        _mapSQLIdentifierToXMLName() which will happen if the
    *                        $mapper function called, throws any exception.
    * @param Array $elements An array of simple element specifications.
    * @param Array $record   An associative array that represents a single record.
    * @param mixed $mapper   A valid argument for call_user_func(), a full method
    *                        method name (e.g. "MyMapperClass::map") or a value
    *                        that == false for no special mapping at all.
    * @return Array The extended array.
    */
    private function _expandShortcuts(&$elements, &$record, $mapper)
    {
        $newElements = array();
        foreach ($elements as $tagName => $column) {
            if (is_numeric($tagName)) {
                $tagName = $column;
            }
            if (!is_array($column) && strpos($tagName, '*') !== false) {
                //expand all occurences of '*' to all column names
                foreach ($record as $columnName => $value) {
                    $newTagName = str_replace('*', $columnName, $tagName);
                    if (is_string($column)) {
                        $newColumn = str_replace('*', $columnName, $column);
                    } else {
                        $newColumn =& $column;
                    }
                    //do the mapping
                    $newTagName = self::_mapSQLIdentifierToXMLName($newTagName, $mapper);
                    if (!isset($newElements[$newTagName])) {
                        //only if the tagName hasn't already been used
                        $newElements[$newTagName] = $newColumn;
                    }
                }
            } else {
                /*
                * Complex element specifications will always be dealt with here.
                * We don't want any mapping or handling of the asterisk shortcut
                * to be done for complex element specifications.
                */
            
                if (!is_array($column)) {
                    //do the mapping but not for complex element specifications
                    $tagName = self::_mapSQLIdentifierToXMLName($tagName, $mapper);
                }
                    
                //explicit specification without an asterisk;
                //this always overrules an expanded asterisk
                unset($newElements[$tagName]);
                $newElements[$tagName] = $column;
            }
        }
        return $newElements;
    }
    
    /**Maps an SQL identifier to an XML name using the supplied $mapper.
    *
    * @throws XML_Query2XML_Exception If $mapper is not callable. This should never
    *                               happen as _getNestedXMLRecord() already checks
    *                               if $mapper is callable.
    * @throws XML_Query2XML_XMLException If the $mapper function called, throws any
    *                                    exception.
    * @param string $sqlIdentifier The SQL identifier as a string.
    * @param mixed $mapper   A valid argument for call_user_func(), a full method
    *                        method name (e.g. "MyMapperClass::map") or a value
    *                        that == false for no special mapping at all.
    * @return string The mapped XML name.
    */
    private function _mapSQLIdentifierToXMLName($sqlIdentifier, $mapper)
    {
        if (!$mapper) {
            //no mapper was defined
            $xmlName = $sqlIdentifier;
        } else {
            if (is_callable($mapper, false, $callableName)) {
                try {
                    $xmlName = call_user_func($mapper, $sqlIdentifier);
                } catch (Exception $e) {
                    /*
                    * This will also catch XML_Query2XML_ISO9075Mapper_Exception
                    * if $mapper was "XML_Query2XML_ISO9075Mapper::map".
                    * unit test: test_mapSQLIdentifierToXMLNameNotMappable()
                    */
                    throw new XML_Query2XML_XMLException(
                        'Could not map "' . $sqlIdentifier
                        . '" to an XML name using the mapper '
                        . $callableName . ': ' . $e->getMessage()
                    );
                }
            } else {
                /*
                * This should never happen as _getNestedXMLRecord() already
                * checks if $mapper is callable. Therefore no unit tests
                * can be provided for this exception.
                */
                throw new XML_Query2XML_Exception(
                    'The mapper "' . $callableName . '" is not callable.'
                );
            }
        }
        return $xmlName;
    }
    
    /**Private method that processes a complex element specification
    * for {@link XML_Query2XML::_getNestedXMLRecord()}.
    *
    * @throws XML_Query2XML_XMLException This exception will bubble up
    *                          if it is thrown by _addDOMGrandchildren() or
    *                          _getNestedXMLRecord().
    * @throws XML_Query2XML_DBException  This exception will bubble up
    *                          if it is thrown by _applySqlOptionsToRecord()
    *                          or _getNestedXMLRecord().
    * @throws XML_Query2XML_ConfigException This exception will bubble up
    *                          if it is thrown by _applySqlOptionsToRecord()
    *                          or _getNestedXMLRecord().
    * @throws XML_Query2XML_Exception  This exception will bubble up if it
    *                          is thrown by _getNestedXMLRecord().
    * @param array $record     The current record.
    * @param array $options    The current options.
    * @param array $tree       associative multi-dimensional array, that is used to
    *                          store which tags have already been created
    * @param string  $tagName  The element's name.
    * @param string  $parentIdColumn The parent ID column - it will be used if there
    *                          was none specified at this level.
    * @param mixed  $parentMapper A valid argument for call_user_func(), a full
    *                          method method name (e.g. "MyMapperClass::map") or a
    *                          value that == false for no special mapping at all.
    */
    private function _processComplexElementSpecification(&$record, &$options, $dom,
        &$tree, $tagName, $parentIdColumn, $parentMapper)
    {
        $tag = $tree['tag'];
        if (!isset($tree['elements'])) {
            $tree['elements'] = array();
        }
        if (!isset($tree['elements'][$tagName])) {
            $tree['elements'][$tagName] = array();
        }
        
        if (!isset($options['idColumn'])) {
            $options['idColumn'] = $parentIdColumn;
        }
        if (!isset($options['mapper'])) {
            $options['mapper'] = $parentMapper;
        }
        if (!isset($options['rootTag']) || $options['rootTag'] == '')  {
            /* If rootTag is not set or an empty string: create a
            *  hidden root tag
            */
            $options['rootTag'] = '__' . $tagName;
        }
        if (!isset($options['rowTag'])) {
            //the row tag defaults to $tagName
            $options['rowTag'] = $tagName;
        }
        
        try {
            $records =& $this->_applySqlOptionsToRecord($options, $record);
            if (!self::_hasDOMChild($tag, $options['rootTag'])) {
                //create the root tag if it does not yet exist
                self::_addNewDOMChild($tag, $options['rootTag']);
            }
            for ($i = 0; $i < count($records); $i++) {
                self::_addDOMGrandchildren(
                    $tag,
                    $this->_getNestedXMLRecord(
                        $records[$i],
                        $options,
                        $dom,
                        $tree['elements'][$tagName]
                    ),
                    $options['rootTag']
                );
            }
        } catch (XML_Query2XML_ConfigException $e) {
            //unit test: test_processComplexElementSpecificationRETHROW()
            $e->addConfigParents(
                array('elements', $tagName)
            );
            throw $e;
        }
    }
    
    /**Private method that processes a complex attribute specification
    * for {@link XML_Query2XML::_getNestedXMLRecord()}.
    *
    * A complex attribute specification consists of an associative array
    * with the keys 'value' (mandatory), 'condition', 'sql' and 'sql_options'.
    *
    * @throws XML_Query2XML_XMLException This exception will bubble up
    *                          if it is thrown by _setDOMAttribute().
    * @throws XML_Query2XML_DBException  This exception will bubble up
    *                          if it is thrown by _applySqlOptionsToRecord().
    * @throws XML_Query2XML_ConfigException This exception will bubble up
    *                          if it is thrown by _applySqlOptionsToRecord() or
    *                          _applyColumnStringToRecord(). It will also be thrown 
    *                          by this method if $options['value'] is not set.
    * @param string $attributeName The name of the attribute as it was specified
    *                          using the array key of the complex attribute
    *                          specification.
    * @param array $record     The current record.
    * @param array $options    The complex attribute specification itself.
    * @param DomNode $tag      The DomNode to which the attribute is to be added.
    */
    private function _processComplexAttributeSpecification($attributeName, &$record,
        &$options, $tag)
    {
        if (isset($options['condition'])) {
            if (!eval('return ' . $options['condition'] . ';')) {
                //this attribute is to be skipped
                return;
            }
        }
        if (!isset($options['value'])) {
            /*
            * the option "value" is mandatory
            * unit test:
            *  test_processComplexAttributeSpecificationMissingValueException()
            */
            throw new XML_Query2XML_ConfigException(
                'The option "value" is missing from the complex attribute '
                . 'specification',
                array('attributes', $attributeName)
            );
        }
        
        //only a fetching a single record makes sense for a single attribute
        $options['sql_options']['single_record'] = true;
        $records = $this->_applySqlOptionsToRecord($options, $record);
        if (count($records) == 0) {
            /*
            * $options['sql'] was set but the query did not return any records.
            * Therefore this attribute is to be skipped.
            */
            return;
        }
        $attributeRecord = $records[0];
        
        $attributeValue = $this->_applyColumnStringToRecord(
            $options['value'],
            $attributeRecord,
            'attributes'
        );
        if ($this->_evaluateCondtion($attributeValue, $options['value'])) {
            self::_setDOMAttribute($tag, $attributeName, $attributeValue);
        }
    }
                    
    /**Private method to apply the givenen sql option to a record.
    *
    * This method handles the sql options 'uncached', 'single_record',
    * 'merge', 'merge_master' and 'merge_selective'. Please see the
    * {@tutorial XML_Query2XML.pkg tutorial} for details.
    * 
    * @throws XML_Query2XML_ConfigException This exception is thrown if
    *                         - merge_selective is set but not an array
    *                         - sql is set but not an array or a string
    *                         - sql is an array but $sql['query'] is missing
    *                         - $sql['data'] is set but not an array
    *                         - a column specified in merge_selective does not exist
    *                           in the result set
    *                         - it bubbles up from _applyColumnStringToRecord()
    * @throws XML_Query2XML_DBException This exception will bubble up
    *                                   if it is thrown by _getRecord(),
    *                                   _getAllRecords(), _getRecordCached()
    *                                   or _getAllRecordsCached()
    * @param array $options   An associative multidimensional array of options.
    * @param array $record    The current record as an associative array.
    * @return array           An indexed array of records that are themselves
    *                         represented as associative arrays.
    */
    private function _applySqlOptionsToRecord(&$options, &$record)
    {
        if (!isset($options['sql'])) {
            return array($record);
        }
        
        $uncached        = false;
        $single_record   = false;
        $merge           = false;
        $merge_master    = false;
        $merge_selective = false;
        if (isset($options['sql_options'])) {
            if (isset($options['sql_options']['uncached'])) {
                $uncached = $options['sql_options']['uncached'];
            }
            if (isset($options['sql_options']['single_record'])) {
                $single_record = $options['sql_options']['single_record'];
            }
            if (isset($options['sql_options']['merge'])) {
                $merge = $options['sql_options']['merge'];
            }
            if (isset($options['sql_options']['merge_master'])) {
                $merge_master = $options['sql_options']['merge_master'];
            }
            if (isset($options['sql_options']['merge_selective'])) {
                $merge_selective = $options['sql_options']['merge_selective'];
                if (!is_array($merge_selective)) {
                    /* unit test:
                    *  test_applySqlOptionsToRecordMergeSelectiveTypeException
                    */
                    throw new XML_Query2XML_ConfigException(
                        'The configuration option "merge_selective" is '
                        . 'not an array.',
                        'sql_options'
                    );
                }
            }
        }

        if (is_string($options['sql'])) {
            eval('$sql = "' . $options['sql'] . '";');
        } else {
            $sql = $options['sql'];
            if (isset($sql['query'])) {
                eval('$sql[\'query\'] = "' . $sql['query'] . '";');
            } elseif (!is_array($sql)) {
                //unit test: test_applySqlOptionsToRecordWrongQueryTypeException()
                throw new XML_Query2XML_ConfigException(
                    'The configuration option "sql" is not an array or a string.'
                );
            } else {
                //unit test: test_applySqlOptionsToRecordMissingQueryException()
                throw new XML_Query2XML_ConfigException(
                    'The configuration option "query" is missing.',
                    'sql'
                );
            }
        
            if (isset($sql['data'])) {
                if (is_array($sql['data'])) {
                    for ($i = 0; $i < count($sql['data']); $i++) {
                        $sql['data'][$i] = $this->_applyColumnStringToRecord(
                            $sql['data'][$i],
                            $record,
                            'data',
                            'sql'
                        );
                    }
                } else {
                    //unit test: test_applySqlOptionsToRecordWrongDataTypeException()
                    throw new XML_Query2XML_ConfigException(
                        'The configuration option "data" is not an array.',
                        'sql'
                    );
                }
            }
        }
        
        if ($uncached) {
            if ($single_record) {
                $records = array();
                $newRecord =& $this->_getRecord($sql);
                if (is_array($newRecord)) {
                    $records[] =& $newRecord;
                }
            } else {
                $records =& $this->_getAllRecords($sql);
            }
        } else {
            if ($single_record) {
                $records = array();
                $newRecord =& $this->_getRecordCached($sql);
                if (is_array($newRecord)) {
                    $records[] =& $newRecord;
                }
            } else {
                $records =& $this->_getAllRecordsCached($sql);
            }
        }
        
        if (is_array($merge_selective)) {
            //selective merge
            if ($merge_master) {
                //current records are master
                for ($ii = 0; $ii < count($merge_selective); $ii++) {
                    for ($i = 0; $i < count($records); $i++) {
                        if (!array_key_exists($merge_selective[$ii], $record)) {
                            /* Selected field does not exist in the parent record
                            * (passed as argumnet $record)
                            * unit test:
                            *  test_applySqlOptionsToRecordMergeException1()
                            */
                            throw new XML_Query2XML_ConfigException(
                                'The column "' . $merge_selective[$ii] . '" '
                                . 'used in the option "merge_selective" does '
                                . 'not exist in the result set.',
                                'sql_options'
                            );
                        }
                        if (!array_key_exists($merge_selective[$ii], $records[$i])) {
                            //we are the master, so only if it does not yet exist
                            $records[$i][$merge_selective[$ii]] =
                                $record[$merge_selective[$ii]];
                        }
                    }
                }
            } else {
                //parent record is master
                for ($ii = 0; $ii < count($merge_selective); $ii++) {
                    for ($i = 0; $i < count($records); $i++) {
                        if (!array_key_exists($merge_selective[$ii], $record)) {
                            /* Selected field does not exist in the parent record
                            *  (passed as argumnet $record)
                            *  unit test:
                            *   test_applySqlOptionsToRecordMergeException2()
                            */
                            throw new XML_Query2XML_ConfigException(
                                'The column "' . $merge_selective[$ii] . '" '
                                . 'used in the option "merge_selective" does not '
                                . 'exist in the result set.',
                                'sql_options'
                            );
                        }
                        //parent is master!
                        $records[$i][$merge_selective[$ii]] =
                            $record[$merge_selective[$ii]];
                    }
                }
            }
        } elseif ($merge) {
            //regular merge
            if ($merge_master) {
                for ($i = 0; $i < count($records); $i++) {
                    $records[$i] = array_merge($record, $records[$i]);
                } 
            } else {
                for ($i = 0; $i < count($records); $i++) {
                    $records[$i] = array_merge($records[$i], $record);
                }
            }
        }
        return $records;
    }
    
    /**Private method to apply a column string to a record.
    * Please see the tutorial for details on the different column strings.
    *
    * @throws XML_Query2XML_ConfigException  Thrown if $record[$columnStr]
    *               does not exist (and $columnStr has no special prefix).
    * @param string $columnStr  One of the following: if prefixed by ':' it means
    *               that $columnStr will be returned as is (the prefix removed of
    *               course); if prefixed by '!' $columnStr is passed to the
    *               native method eval(); in any other case, $columnStr must
    *               be a valid column name or XML_Query2XML_ConfigException
    *               will be thrown.
    * @return mixed The resulting value.
    */
    private function _applyColumnStringToRecord($columnStr, &$record, $optionName,
        $parentOptionName = '')
    {
        if (strpos($columnStr, '?') === 0) {
            $columnStr = substr($columnStr, 1);
        }
        
        if (strpos($columnStr, ':') === 0) {
            $ret = substr($columnStr, 1);
            if ($ret === false) {
                $ret = '';
            }
        } elseif (strpos($columnStr, '!') === 0) {
            $ret = eval(substr($columnStr, 1));
        } else {
            if (array_key_exists($columnStr, $record)) {
                $ret = $record[$columnStr];
            } else {
                //unit test: test_applyColumnStringToRecordException()
                throw new XML_Query2XML_ConfigException(
                    'The column "' . $columnStr . '" used in the option '
                    . '"' . $optionName . '" does not exist in the result set.',
                    $parentOptionName
                );
                
            }
        }
        return $ret;
    }
    
    /**Returns whether $value is to be included in the output.
    * If $spec is prefixed by a question mark this method will return false if
    * $value is null or is a string with a length of zero. In any other case,
    * this method will return the true.
    *
    * @param string $value The value.
    * @param string $spec The value specification with an optional question
    *                     mark prefix.
    * @return boolean Whether $value is to be included in the output.
    */
    private function _evaluateCondtion($value, $spec)
    {
        if (strpos($spec, '?') === 0) {
            /*
            * $spec defines a non-empty condition; return false if
            * $value is null or is a string with a length of zero.
            */
            return !(is_null($value) || (is_string($value) && strlen($value) == 0));
        }
        return true;
    }
            
    /**Private method to fetch a single record.
    * @throws XML_Query2XML_DBException This exception will bubble up
    *                   if it is thrown by _prepareAndExecute().
    * @throws XML_Query2XML_ConfigException  This exception will bubble up
    *                   if it is thrown by _prepareAndExecute(). It will also be
    *                   thrown if DB_result::fetchRow() or MDB2_Result::fetchRow()
    *                   return an error.
    * @param mixed $sql The SQL query as a string or an array.
    * @return mixed A single record as an associative array or null if the
    *               query did not return any records.
    */
    private function &_getRecord($sql)
    {
        $this->_debugStartQuery($sql);
        $result =& $this->_prepareAndExecute($sql);
        $record =& $result->fetchRow();
        $this->_debugStopQuery($sql);
        if (PEAR::isError($record)) {
            //no unit test for this exception as it cannot be produced easily
            throw new XML_Query2XML_DBException(
                'Could not fetch a single row for the following SQL query: '
                . $this->_extractQueryString($sql) . '; '
                . $record->toString()
            );
        }
        return $record;
    }
    
    /**Private method to fetch all records from a result set.
    * @throws XML_Query2XML_DBException  This exception will bubble up
    *                   if it is thrown by _prepareAndExecute().
    * @throws XML_Query2XML_ConfigException  This exception will bubble up
    *                   if it is thrown by _prepareAndExecute(). It will also be
    *                   thrown if DB_result::fetchRow() or MDB2_Result::fetchRow()
    *                   return an error.
    * @param mixed $sql The SQL query as a string or an array.
    * @return array An array of records. Each record itself will be an
    *                   associative array.
    */
    private function &_getAllRecords($sql)
    {
        $this->_debugStartQuery($sql);
        $result =& $this->_prepareAndExecute($sql);
        $records = array();
        while ($record =& $result->fetchRow()) {
            if (PEAR::isError($record)) {
                //no unit test for this exception as it cannot be produced easily
                throw new XML_Query2XML_DBException(
                    'Could not fetch rows for the following SQL query: '
                    . $this->_extractQueryString($sql) . '; '
                    . $record->toString()
                );
            }
            $records[] =& $record;
        }
        $this->_debugStopQuery($sql);
        return $records;
    }
    
    /**Private method to fetch a single record and cache the result.
    * @see _getAllRecordsCached()
    * @throws XML_Query2XML_DBException  This exception will bubble up
    *                   if it is thrown by _getAllRecordsCached().
    * @throws XML_Query2XML_ConfigException  This exception will bubble up
    *                   if it is thrown by _getAllRecordsCached().
    * @param mixed $sql The SQL query as a string or an array.
    * @return mixed A single record as an associative array or null if the
    *               query did not return any records.
    */
    private function &_getRecordCached($sql)
    {
        $records =& $this->_getAllRecordsCached($sql);
        if (isset($records[0])) {
            return $records[0];
        } else {
            $record = null;
            return $record;
        }
    }
    
    /**Private method to fetch all records and cache the results.
    * @throws XML_Query2XML_DBException  This exception will bubble up
    *                   if it is thrown by _getAllRecords().
    * @throws XML_Query2XML_ConfigException  This exception will bubble up
    *                   if it is thrown by _extractQueryString().
    * @param mixed $sql The SQL query as a string or an array.
    * @return array An array of records. Each record itself will be an
    *                   associative array.
    */
    private function &_getAllRecordsCached($sql)
    {
        $query = $this->_extractQueryString($sql);
        $queryUnchanged = $query;
        if (is_array($sql) && isset($sql['data']) && is_array($sql['data'])) {
            $query .= '; DATA:' . implode(', ', $sql['data']);
        }
        if (isset($this->_recordCache[$query])) {
            $this->_debugCachedQuery($queryUnchanged);
            return $this->_recordCache[$query];
        }
        $this->_debugCachingQuery($queryUnchanged);
        $this->_recordCache[$query] =& $this->_getAllRecords($sql);
        return $this->_recordCache[$query];
    }
    
    /**Private method to execute an SQL query and return the DB_result
    * or MDB2_Result.
    *
    * For the different ways to specify $sql please see
    * {@link XML_Query2XML::_prepareAndExecute()} which is directly
    * called by _runSQLQuery().
    *
    * @throws XML_Query2XML_DBException  This exception will bubble up
    *                   if it is thrown by _prepareAndExecute().
    * @throws XML_Query2XML_ConfigException  This exception will bubble up
    *                   if it is thrown by _prepareAndExecute().
    * @param mixed $sql The SQL query as a string or an array.
    * @return mixed An instance of DB_result or MDB2_Result as it is
    *                   returned by _prepareAndExecute().
    */
    private function _runSQLQuery($sql)
    {
        $this->_debugStartQuery($sql);
        $result = $this->_prepareAndExecute($sql);
        $this->_debugStopQuery($sql);
        return $result;
    }
    
    /**Private method to prepare and execute an SQL query.
    * $sql can be defined as one of the following:
    * <code>
    * $sql = 'SELECT * FROM sometable';
    * </code>
    * or
    * <code>
    * $sql = array(
    *   'query' => 'SELECT * FROM sometable'
    * );
    * </code>
    * or
    * <code>
    * $sql = array(
    *   'data' => array(
    *       '2'
    *   ),
    *   'query' => 'SELECT * FROM sometable WHERE id = ?'
    * );
    * </code>
    * Note: when passed to this method, the values in the data array must already be
    * interpreted in terms of the '!' and ':' prefix. This is done by
    * _applySqlOptionsToRecord() which calls _applyColumnStringToRecord() for
    * every element in the data array.
    *
    * @throws XML_Query2XML_DBException  Thrown if $this->_db->query(),
    *                   $this->_db->prepare() or $this->_db->execute() return
    *                   an error.
    * @throws XML_Query2XML_ConfigException  Thrown if $sql is neither a string nor
    *                   an array with at least the element 'query'.
    * @param mixed $sql The SQL query to prepare (and the data to execute it with).
    * @return mixed An instance of DB_result or MDB2_Result or ADORecordSet as it
    *                   is returned by $this->_db->query() or $this->_db->execute().
    *                   Note that DB_result, MDB2_Result and ADORecordSet all
    *                   support the fetchRow() method.
    */
    private function _prepareAndExecute($sql)
    {
        if (is_string($sql)) {
            /*
            * SIMPLE QUERY
            */
            
            $query =& $sql;
            if ($this->_dbLayer == 'ADOdb' && class_exists('ADODB_Exception')) {
                try {
                    $result = $this->_db->query($query);
                } catch (ADODB_Exception $e) {
                    //unit test: Query2XMLTestADOdbException::
                    // test_prepareAndExecuteSimpleQueryDBException()
                    throw new XML_Query2XML_DBException(
                        'Could not run the following SQL query: '
                        . $query .  '; '
                        . $e->getMessage()
                    );
                }
            } else {
                $result = $this->_db->query($query);
            }
                
            if (PEAR::isError($result)) {
                //DB & MDB2 return PEAR_Error on failure
                
                //unit test: Query2XMLTestDB/Query2XMLTestMDB2::
                // test_prepareAndExecuteSimpleQueryDBException()
                throw new XML_Query2XML_DBException(
                    'Could not run the following SQL query: '
                    . $query .  '; '
                    . $result->toString()
                );
            } elseif ($result === false) {
                //ADOdb returns false on failure
                
                //unit test: Query2XMLTestADOdb::
                // test_prepareAndExecuteSimpleQueryDBException()
                throw new XML_Query2XML_DBException(
                    'Could not run the following SQL query: '
                    . $query
                );
            }
        } elseif (is_array($sql) && isset($sql['query'])) {
            /*
            * PREPARE & EXECUTE
            */
            
            $query =& $sql['query'];
            if (isset($this->_preparedQueries[$query])) {
                $queryHandle = $this->_preparedQueries[$query];
            } else {
                /*
                * PREPARE
                */
                if ($this->_dbLayer == 'ADOdb' && class_exists('ADODB_Exception')) {
                    try {
                        $queryHandle = $this->_db->prepare($query);
                    } catch (ADODB_Exception $e) {
                        /* No unit test for this exception as the mysql driver of ADOdb
                        *  never throws an exception.
                        */
                        throw new XML_Query2XML_DBException(
                            'Could not prepare the following SQL query: '
                            . $query .  '; '
                            . $e->getMessage()
                        );
                    }
                } else {
                    $queryHandle = $this->_db->prepare($query);
                }
                
                if (PEAR::isError($queryHandle)) {
                    /* No unit test for this exception as the mysql driver of DB
                    *  never returns a PEAR error from prepare().
                    */
                    throw new XML_Query2XML_DBException(
                        'Could not prepare the following SQL query: '
                        . $query . '; '
                        . $queryHandle->toString()
                    );
                } elseif ($queryHandle === false) {
                    /* No unit test for this exception as the mysql driver of DB
                    *  never returns false from prepare().
                    */
                    throw new XML_Query2XML_DBException(
                        'Could not prepare the following SQL query - DB::prepare() '
                        . 'returned false: '
                        . $query
                    );
                }
                $this->_preparedQueries[$query] =& $queryHandle;
            }
            
            /*
            * EXECUTE
            */
            if ($this->_dbLayer == 'MDB2') {
                if (isset($sql['data'])) {
                    $result = $queryHandle->execute($sql['data']);
                } else {
                    $result = $queryHandle->execute();
                }
            } elseif ($this->_dbLayer == 'ADOdb') {
                if (class_exists('ADODB_Exception')) {
                    try {
                        if (isset($sql['data'])) {
                            $result = $this->_db->execute($queryHandle, $sql['data']);
                        } else {
                            $result = $this->_db->execute($queryHandle);
                        }
                    } catch (ADODB_Exception $e) {
                        //unit test: Query2XMLTestADOdbException::
                        // test_prepareAndExecuteExecuteQueryDBException()
                        throw new XML_Query2XML_DBException(
                            'Could not execute the following SQL query: '
                            . $query .  '; '
                            . $e->getMessage()
                        );
                    }
                } else {
                    if (isset($sql['data'])) {
                        $result = $this->_db->execute($queryHandle, $sql['data']);
                    } else {
                        $result = $this->_db->execute($queryHandle);
                    }
                    if ($result === false && function_exists('ADODB_Pear_Error')) {
                        $result = ADODB_Pear_Error();
                    }
                }
            } else {
                //$this->_db is DB
                if (isset($sql['data'])) {
                    $result = $this->_db->execute($queryHandle, $sql['data']);
                } else {
                    $result = $this->_db->execute($queryHandle);
                }
            }
            
            if (PEAR::isError($result)) {
                /* unit test:
                *   test_prepareAndExecuteExecuteQueryDBException()
                */
                throw new XML_Query2XML_DBException(
                    'Could not execute the following SQL query: '
                    . $query . '; '
                    . $result->toString()
                );
            } elseif ($result === false) {
                /* unit test:
                *   test_prepareAndExecuteExecuteQueryDBException()
                */
                throw new XML_Query2XML_DBException(
                    'Could not execute the following SQL query; '
                    . 'false was returned: '
                    . $query
                );
            }
        } elseif (!is_array($sql)) {
            /* This should never happen as _applySqlOptionsToRecord should already
            *  throws an exception.
            */
            throw new XML_Query2XML_ConfigException(
                'The configuration option "sql" is not an array or a string.'
            );
        } else {
            /* This should never happen as _applySqlOptionsToRecord should already
            *  throws an exception.
            */
            throw new XML_Query2XML_ConfigException(
                'The configuration option "query" is missing.',
                'sql'
            );
        }
        
        return $result;
    }

    /**Extract the query string - no matter whether a simple or complex definition
    * was used.
    *
    * If $sql is defined as array('query' => ... , 'data' => ...) this method will
    * return $sql['query']. If $sql is a string it will return $sql.
    *
    * @throws XML_Query2XML_ConfigException If $sql is not a string or an array
    *                   containing the array key 'query'.
    * @param mixed $sql A string or an associative array.
    * @return String The SQL query.
    */
    private function &_extractQueryString($sql)
    {
        if (is_string($sql)) {
            return $sql;
        } elseif (is_array($sql) && isset($sql['query'])) {
            return $sql['query'];
        } elseif (!is_array($sql)) {
            /* This should never happen as _applySqlOptionsToRecord should already
            *  throws an exception
            */
            throw new XML_Query2XML_ConfigException(
                'The configuration option "sql" is not an array or a string.'
            );
        } else {
            /* This should never happen as _applySqlOptionsToRecord should already
            *  throws an exception
            */
            throw new XML_Query2XML_ConfigException(
                'The configuration option "query" is missing.',
                'sql'
            );
        }
    }
    
    /**Initializes a query's profile (only used if profiling is turned on).
    * @see startProfiling()
    * @param mixed $sql The SQL query as a string or an array.
    */
    private function _initQueryProfile(&$sql)
    {
        if (!isset($this->_profile['queries'][$sql])) {
            $this->_profile['queries'][$sql] = array(
                'fromDB' => 0,
                'fromCache' => 0,
                'cached' => false,
                'runTimes' => array()
            );
        }
    }
    
    /**Starts the debugging and profiling of the query passed as argument
    * @see _extractQueryString()
    * @throws XML_Query2XML_ConfigException This exception will bubble up
    *                       if it is thrown by _extractQueryString().
    * @param mixed $sql     The SQL query as a string or an array.
    */
    private function _debugStartQuery($sql)
    {
        $query = $this->_extractQueryString($sql);
        $this->_debug('QUERY: ' . $query);
        if ($this->_profiling) {
            $this->_initQueryProfile($query);
            ++$this->_profile['queries'][$query]['fromDB'];
            $this->_profile['queries'][$query]['runTimes'][] = array(
                'start' => microtime(true),
                'stop' => 0
            );
        }
    }
    
    /**Ends the debugging and profiling of the query passed as argument
    * @see _extractQueryString()
    * @throws XML_Query2XML_ConfigException This exception will bubble up
    *                       if it is thrown by _extractQueryString().
    * @param mixed $sql The SQL query as a string or an array.
    */
    private function _debugStopQuery($sql)
    {
        $this->_debug('DONE');
        if ($this->_profiling) {
            $query = $this->_extractQueryString($sql);
            $this->_initQueryProfile($query);
            $lastIndex = count($this->_profile['queries'][$query]['runTimes']) - 1;
            $this->_profile['queries'][$query]['runTimes'][$lastIndex]['stop'] =
                microtime(true);
        }
    }
    
    /**Does the debugging and profiling of the query passed as argument which
    * was cached before
    *
    * @see _extractQueryString()
    * @throws XML_Query2XML_ConfigException This exception will bubble up
    *                       if it is thrown by _extractQueryString().
    * @param mixed $sql The SQL query as a string or an array.
    */
    private function _debugCachedQuery($sql)
    {
        $query = $this->_extractQueryString($sql);
        $this->_debug('CACHED: ' . $query);
        if ($this->_profiling) {
            $this->_initQueryProfile($query);
            ++$this->_profile['queries'][$query]['fromCache'];
        }
    }
    
    /**Does the debugging and profiling of the query passed as argument
    * which will be cached for the first time
    * @see _extractQueryString()
    * 
    * @throws XML_Query2XML_ConfigException This exception will bubble up
    *                       if it is thrown by _extractQueryString().
    * @param mixed $sql The SQL query as a string or an array.
    */
    private function _debugCachingQuery($sql)
    {
        $query = $this->_extractQueryString($sql);
        $this->_debug('CACHING: ' . $query);
        if ($this->_profiling) {
            $this->_initQueryProfile($query);
            $this->_profile['queries'][$query]['cached'] = true;
        }
    }
    
    /**Stops the DB profiling.
    * This will set $this->_profile['dbDuration'].
    */
    private function _stopDBProfiling()
    {
        if ($this->_profiling && isset($this->_profile['start'])) {
            $this->_profile['dbStop'] = microtime(1);
            $this->_profile['dbDuration'] =
                $this->_profile['dbStop'] - $this->_profile['start'];
        }
    }
    
    /**Clears the record cache.
    * @see _recordCache
    */
    private function _clearRecordCache()
    {
        $this->_recordCache = array();
    }
    
    /**Private method used to log debug messages.
    * This method will do no logging if $this->_debug is set to false.
    *
    * @param string $msg The message to log.
    * @see _debugLogger
    * @see _debug
    */
    private function _debug($msg)
    {
        if ($this->_debug) {
            $this->_debugLogger->log($msg);
        }
    }
    
    /**Creates a new instance of DomDocument.
    * '1.0' is passed as first argument and 'UTF-8' as second to the
    * DomDocument constructor.
    * @return DomDocument The new instance.
    */
    private static function _createDomDocument()
    {
        return new DomDocument('1.0', 'UTF-8');
    }
    
    /**Create and then add a new child element.
    *
    * @throws XML_Query2XML_XMLException This exception will bubble up if it is
    *                          thrown by _createDOMElement().
    * @param DomNode $element  The parent DomNode the new DOM element should be
    *                          appended to.
    * @param string $name      The tag name of the new element.
    * @param string $value     The value of a child text node. This argument is
    *                          optional. The default is the boolean value false,
    *                          which means that no child text node will be appended.
    * @param array $attributes An associative array used to define the element's
    *                          attributes. The array keys are used as the attribute
    *                          names and the array values are used as the attribute
    *                          values. This argument is optional. The default is an
    *                          empty array (e.g. no attributes).
    * @return DomNode          The newly created DomNode instance that was appended
    *                          to $element.
    */
    private static function _addNewDOMChild(DomNode $element, $name, $value = false)
    {
        if ($element instanceof DomDocument) {
            $dom = $element;
        } else {
            $dom = $element->ownerDocument;
        }
        $child = self::_createDOMElement($dom, $name, $value);
        $element->appendChild($child);
        return $child;
    }
    
    /**Helper method to create a new instance of DomNode
    *
    * @throws XML_Query2XML_XMLException If $name is an invalid XML identifier.
    *                          Also it will bubble up if it is thrown by
    *                          _setDOMAttribute() or _appendTextChildNode().
    * @param DomDocument $dom  An instance of DomDocument. It's createElement()
    *                          method is used to create the new DomNode instance.
    * @param string name       The tag name of the new element.
    * @param string $value     The value of a child text node. This argument is
    *                          optional. The default is the boolean value false,
    *                          which means that no child text node will be appended.
    * @param array $attributes An associative array used to define the element's
    *                          attributes. The array keys are used as the attribute
    *                          names and the array values are used as the attribute
    *                          values. This argument is optional. The default is an
    *                          empty array (e.g. no attributes).
    * @return DomNode An instance of DomNode.
    */
    private static function _createDOMElement(DomDocument $dom, $name,
        $value = false)
    {
        try {
            $element = $dom->createElement($name);
        } catch(DOMException $e) {
            //unit test: test_createDOMElementException()
            throw new XML_Query2XML_XMLException(
                '"' . $name . '" is an invalid XML element name: '
                . $e->getMessage(),
                $e
            );
        }
        if ($value !== false) {
            self::_appendTextChildNode($element, $value);
        }
        return $element;
    }
    
    /**Append a new child text node to $element.
    * $value must not be UTF8-encoded as this method will call
    * self::_utf8encode() itself.
    *
    * @throws XML_Query2XML_XMLException Any lower-level DOMException will be wrapped
    *                 and re-thrown as a XML_Query2XML_XMLException. This will happen
    *                 if $value cannot be UTF8-encoded for some reason.
    * @param DomDocument $dom An instance of DomDocument.
    * @param DomNode $element An instance of DomNode
    * @param string $value The value of the text node.
    */
    private static function _appendTextChildNode(DomNode $element, $value)
    {
        $dom = $element->ownerDocument;
        try {
            $element->appendChild($dom->createTextNode(self::_utf8encode($value)));
        } catch(DOMException $e) {
            //this should never happen as $value is UTF-8 encoded
            throw new XML_Query2XML_XMLException(
                '"' . $value . '" is not a vaild text node: '
                . $e->getMessage(),
                $e
            );
        }
    }
    
    /**Set the attribute $name with a value of $value for $element.
    * $value must not be UTF8-encoded as this method will call
    * self::_utf8encode() itself.
    *
    * @throws XML_Query2XML_XMLException Any lower-level DOMException will be wrapped
    *                 and re-thrown as a XML_Query2XML_XMLException. This will happen
    *                 if $name is not a valid attribute name.
    * @param DomNode $element An instance of DomNode
    * @param string $name The name of the attribute to set.
    * @param string $value The value of the attribute to set.
    */
    private static function _setDOMAttribute(DomNode $element, $name, $value)
    {
        try {
            $element->setAttribute($name, self::_utf8encode($value));
        } catch(DOMException $e) {
            //no unit test available for this one
            throw new XML_Query2XML_XMLException(
                '"' . $name . '" is an invalid XML attribute name: '
                . $e->getMessage(),
                $e
            );
        }
    }
    
    /**Returns whether $element has a child node named $childTagName.
    * It will also return true if $element has multiple child nodes
    * named $childTagName.
    *
    * @param DomNode $element An instance of DomNode
    * @param string $childTagName The name of the node to look for
    * @return boolean
    */
    private static function _hasDOMChild(DomNode $element, $childTagName)
    {
        return (bool)$element->getElementsByTagName($childTagName)->length;
    }
    
    /**Returns the first child node of $element that is named $childTagName.
    *
    * @throws XML_Query2XML_XMLException If $element has no child
    *                                    named $childTagName.
    * @param DomNode $element An instance of DomNode
    * @param string $childTagName The name of the node to look for
    * @return DomNode The first child node named $childTagName
    */
    private static function _getDOMChild(DomNode $element, $childTagName)
    {
        $domNodeList = $element->getElementsByTagName($childTagName);
        if($domNodeList->length) {
            return $domNodeList->item(0);
        }
        
        /* This should never happen as _hasDOMChild() is always asked first whether
        *  the child node exists at all.
        */
        throw new XML_Query2XML_XMLException(
            $element->tagName
            . ' has no child named '
            . $childTagName
        );
    }
    
    /**Add a child element and one or more grandchildren in a single step.
    * An already existing child element with the given name will be used
    * in favour of creating a new one. _addDOMChildren() is called to add the
    * new elements.
    * @see _addDOMChildren()
    *
    * @throws XML_Query2XML_XMLException It will bubble up if it is thrown
    *                              by _addDOMChildren().
    * @param DomNode $base         An instance of DomNode.
    * @param mixed $granchildren   An array of DomNode instances or
    *                              just a single DomNode instance.
    *                              Boolean values of false are always ignored.
    * @param string $childTagName  The name of the child element the grandchildren
    *                              should be added to; if this is an empty string,
    *                              $grandchildren will be directly added to $base.
    */
    private static function _addDOMGrandchildren(DomNode $base, $grandchildren,
        $childTagName)
    {
        $dom = $base->ownerDocument;
        if ($childTagName == '') {
            $child = $base;
        } else {
            if (self::_hasDomChild($base, $childTagName)) {
                $child = self::_getDomChild($base, $childTagName);
            } else {
                $child = $dom->createElement($childTagName);
                $base->appendChild($child);
            }
        }
        self::_addDOMChildren($child, $grandchildren);
    }
    
    /*Adds one or more child nodes to an existing DomNode instance.
    *
    * @throws XML_Query2XML_XMLException If one of the specified children
    *                         is not one of the following: an instance of DomNode,
    *                         the boolean value false, or an array containing
    *                         these two.
    * @param DomNode $base    An instance of DomNode.
    * @param mixed $children  An array of DomNode instances or
    *                         just a single DomNode instance.
    *                         Boolean values of false are always ignored.
    */
    private static function _addDOMChildren(DomNode $base, $children)
    {
        if ($children === false) {
            //don't do anything
            return;
        } elseif ($children instanceof DomNode) {
            //$children is a single complex child
            $base->appendChild($children);
        } elseif (is_array($children) && count($children) > 0) {
            for ($i = 0; $i < count($children); $i++) {
                if ($children[$i] === false) {
                    //don't do anything
                } elseif ($children[$i] instanceof DomNode) {
                    $base->appendChild($children[$i]);
                } else {
                    //this should never happen
                    throw new XML_Query2XML_XMLException(
                        'array argument passed to XML_Query2XML::_addDOMChildren() '
                        . 'has an element of a wrong type ('
                        . gettype($children[$i])
                        . ')'
                    );
                }
            }
        } else {
            //this should never happen
            throw new XML_Query2XML_XMLException(
                'argument passed to XML_Query2XML::_addDOMChildren() '
                . 'is of the wrong type ('
                . gettype($children[$i])
                . ')'
            );
        }
    }
    
    /**Remove all container elements created by XML_Query2XML to ensure that all
    * elements are correctly ordered.
    *
    * This is a recursive method. This method calls
    * {@link XML_Query2XML::_replaceParentWithChildren()}. For the concept of
    * container elements please see the {@tutorial XML_Query2XML.pkg tutorial}.
    *
    * @param DomNode $element An instance of DomNode.
    */
    private static function _removeContainers($element)
    {
        $child = $element->firstChild;
        $children = array();
        while ($child) {
            $children[] = $child;
            $child = $child->nextSibling;
        }
        for ($i = 0; $i < count($children); $i++) {
            if (
                isset($children[$i]->tagName)
                &&
                strpos($children[$i]->tagName, '__') === 0
            ) {
                self::_removeContainers($children[$i]);
                self::_replaceParentWithChildren($children[$i]);
            }
        }
        
        $child = $element->firstChild;
        $children = array();
        while ($child) {
            $children[] = $child;
            $child = $child->nextSibling;
        }
        for ($i = 0; $i < count($children); $i++) {
            self::_removeContainers($children[$i]);
        }
        
    }
    
    /**Replace a certain node with its child nodes.
    * @param DomNode $parent An instance of DomNode.
    */
    private static function _replaceParentWithChildren(DomNode $parent)
    {
        $child = $parent->firstChild;
        $children = array();
        while ($child) {
            $children[] = $child;
            $child = $child->nextSibling;
        }
        for ($i = 0; $i < count($children); $i++) {
            $parent->removeChild($children[$i]);
            $parent->parentNode->insertBefore($children[$i], $parent);
        }
        $parent->parentNode->removeChild($parent);
    }
    
    /**UTF-8 encode $str using mb_conver_encoding or if that is not
    * present, utf8_encode.
    *
    * @param string $str The string to encode
    * @return String The UTF-8 encoded version of $str
    */
    private static function _utf8encode($str)
    {
        if (function_exists('mb_convert_encoding')) {
            $str = mb_convert_encoding($str, 'UTF-8');
        } else {
            $str = utf8_encode($str);
        }
        return $str;
    }
    
    /**Default mapper that just returns unchanged argument.
    * @param string $str
    * @return string $str without any modifications
    */
    private static function _mapUnchanged($str)
    {
        return $str;
    }
}

/**Parent class for ALL exceptions thrown by this package.
* By catching XML_Query2XML_Exception you will catch all exceptions
* thrown by XML_Query2XML.
*
* @package XML_Query2XML
*/
class XML_Query2XML_Exception extends PEAR_Exception
{
    
    /**Constructor method
    * @param string $message       The error message.
    * @param Exception $exception  The Exception that caused this exception 
    *                              to be thrown. This argument is optional.
    */
    public function __construct($message, $exception = null)
    {
        parent::__construct($message, $exception);
    }
}

/**Exception for database errors
* @package XML_Query2XML
*/
class XML_Query2XML_DBException extends XML_Query2XML_Exception
{
    /**Constructor
    * @param string $message The error message.
    */
    public function __construct($message)
    {
        parent::__construct($message);
    }
}

/**Exception for XML errors
* In most cases this exception will be thrown if a DOMException occurs.
* @package XML_Query2XML
*/
class XML_Query2XML_XMLException extends XML_Query2XML_Exception
{
    /**Constructor
    * @param string $message         The error message.
    * @param DOMException $exception The DOMException that caused this exception 
    *                                to be thrown. This argument is optional.
    */
    public function __construct($message, DOMException $exception = null)
    {
        parent::__construct($message, $exception);
    }
}

/**Exception that handles configuration errors.
*
* This exception handels errors in the $options array passed to
* XML_Query2XML::getXML() and wrong arguments passed to the constructor via
* XML_Query2XML::factory(). It provides the special method addConfigParents()
* that allows for exact specification where the error is located in the
* configuration array.
*
* @see XML_Query2XML::getXML()
* @package XML_Query2XML
*/
class XML_Query2XML_ConfigException extends XML_Query2XML_Exception
{
    /**The parent configuration options of the option that caused the error
    * @var string  e.g. [level1][level2]
    */
    protected $_configParents = '';
    
    /**The error details.
    * @var string
    */
    protected $_details = '';
    
    /**Add a single parent configuration option.
    * @param string $configParent The parent configuraton option's name.
    */
    private function _addConfigParent($configParent)
    {
        if ($configParent != '') {
            $this->_configParents = '[' . $configParent . ']'
                                    . $this->_configParents;
            $this->message = $this->_configParents
                             . ': ' . $this->_details;
        }
    }
    
    /**Add multiple parent configuration options.
    * @param mixed $configParents A single parent configuration option as a string or
    *               one or more parents as an array of strings. If the option is in
    *               [elements][albums] pass array('elements', 'albums') as argument.
    */
    public function addConfigParents($configParents)
    {
        if (is_string($configParents)) {
            $this->_addConfigParent($configParents);
        } else {
            $configParentsReversed = array_reverse($configParents);
            foreach ($configParentsReversed as $key => $parent) {
                $this->_addConfigParent($parent);
            }
        }
        
    }
    
    /**Constructor method
    * @see addConfigParents()
    * @param string $details A detailed error message.
    * @param mixed $configParents A single parent configuration option as a string or
    *                             one or more parents as an array of strings. This
    *                             argument is optional.
    */
    public function __construct($details, $configParents = '')
    {
        parent::__construct($details);
        $this->_details = $details;
        $this->addConfigParents($configParents);
    }
}
?>