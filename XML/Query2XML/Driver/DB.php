<?php
/**This file contains the class XML_Query2XML_Driver_DB.
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

/**XML_Query2XML_Driver_DB extends XML_Query2XML_Driver.
*/
require_once 'XML/Query2XML.php';

/**As the method PEAR::isError() is used within XML_Query2XML_Driver_DB we require PEAR.php.
*/
require_once 'PEAR.php';

/**Driver for the database abstraction layer PEAR DB.
*
* usage:
* <code>
* $driver = XML_Query2XML_Driver::factory(DB::connect(...));
* </code>
*
* @access private
* @author Lukas Feiler <lukas.feiler@lukasfeiler.com>
* @version Release: @package_version@
* @copyright Empowered Media 2006
* @package XML_Query2XML
* @since Release 1.5.0RC1
*/
class XML_Query2XML_Driver_DB extends XML_Query2XML_Driver
{
    /**In instance of a class that extends DB_common.
    * @var DB_common
    */
    private $db = null;
    
    /**Constructor
    *
    * @throws XML_Query2XML_DBException If the fetch mode cannot be set to
    *                               DB_FETCHMODE_ASSOC.
    * @param DB_Common $db An instance of a class that extends DB_Common.
    */
    public function __construct(DB_common $db)
    {
        $fetchModeError = $db->setFetchMode(DB_FETCHMODE_ASSOC);
        if (PEAR::isError($fetchModeError)) {
            //no unit tests for this one
            throw new XML_Query2XML_DBException(
                'Could not set fetch mode to DB_FETCHMODE_ASSOC: '
                . $fetchModeError->toString()
            );
        }
        $this->_db = $db;
    }
    
    /**Execute a SQL SELECT stement and fetch all records from the result set.
    *
    * @see XML_Query2XML_Driver::getAllRecords()
    * @throws XML_Query2XML_DBException If a database related error occures.
    * @param mixed  $sql The SQL query as a string or an array.
    * @param string $configPath The config path; used for exception messages.
    * @return array An array of records.
    */
    public function &getAllRecords($sql, $configPath)
    {
        $result =& $this->_prepareAndExecute($sql, $configPath);
        $records = array();
        while ($record = $result->fetchRow()) {
            if (PEAR::isError($record)) {
                //no unit test for this exception as it cannot be produced easily
                throw new XML_Query2XML_DBException(
                    $configPath . ': Could not fetch rows for the following '
                    . 'SQL query: ' . $this->extractQueryString($sql) . '; '
                    . $record->toString()
                );
            }
            $records[] = $record;
        }
        return $records;
    }
    
    /**Private method that will use DB_Common::query() for simple and
    * DB_Common::prepare() & DB_Common::execute() for complex query specifications.
    *
    * @throws XML_Query2XML_DBException If a database related error occures.
    * @param mixed $sql A string or an array.
    * @param string $configPath The config path used for exception messages.
    * @return DB_result
    */
    private function _prepareAndExecute($sql, $configPath)
    {
        if (is_string($sql)) {
            /*
            * simple query specification
            */
            
            $result = $this->_db->query($sql);
            if (PEAR::isError($result)) {
                /*
                * unit tests: DB/
                *  getXML/throwDBException.phpt
                *  getFlatXML\throwDBException.phpt
                *  _prepareAndExecute\throwDBException_simpleQuery.phpt
                */
                throw new XML_Query2XML_DBException(
                    $configPath . ': Could not run the following SQL query: '
                    . $sql .  '; ' . $result->toString()
                );
            }
            if (!($result instanceof DB_result)) {
                /*
                * unit tests: DB/getXML/
                *  throwDBException_nullResultSet_simpleQuery_multipleRecords.phpt
                *  throwDBException_nullResultSet_simpleQuery_singleRecord.phpt
                */
                throw new XML_Query2XML_DBException(
                    $configPath . ': the following SQL query returned no '
                    . 'result set: ' . $sql
                );
            }
        } else {
            /*
            * complex query specification
            */
            
            $query =& $sql['query'];
            if (isset($this->_preparedQueries[$query])) {
                $queryHandle = $this->_preparedQueries[$query];
            } else {
                //PREPARE
                $queryHandle = $this->_db->prepare($query);
                
                if (PEAR::isError($queryHandle)) {
                    /*
                    * No unit test for this exception as DB's mysql and pgsql
                    * drivers never return a PEAR error from prepare().
                    */
                    throw new XML_Query2XML_DBException(
                        $configPath . ': Could not prepare the following SQL query: '
                        . $query . '; ' . $queryHandle->toString()
                    );
                }
                
                $this->_preparedQueries[$query] =& $queryHandle;
            }
            
            //EXECUTE
            if (isset($sql['data'])) {
                $result = $this->_db->execute($queryHandle, $sql['data']);
            } else {
                $result = $this->_db->execute($queryHandle);
            }
            
            if (PEAR::isError($result)) {
                /*
                * unit test: DB/_prepareAndExecute/
                *  throwDBException_complexQuery.phpt
                */
                throw new XML_Query2XML_DBException(
                    $configPath . ': Could not execute the following SQL query: '
                    . $query . '; ' . $result->toString()
                );
            }
            if (!($result instanceof DB_result)) {
                /*
                * unit tests: DB/getXML/
                *  throwDBException_nullResultSet_complexQuery_multipleRecords.phpt
                *  throwDBException_nullResultSet_complexQuery_singleRecord.phpt
                */
                throw new XML_Query2XML_DBException(
                    $configPath . ': the following SQL query returned no '
                    . 'result set: ' . $query
                );
            }
        }
        
        return $result;
    }
}
?>