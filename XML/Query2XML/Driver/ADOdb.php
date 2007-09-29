<?php
/**This file contains the class XML_Query2XML_Driver_ADOdb.
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

/**XML_Query2XML_Driver_ADOdb extends XML_Query2XML_Driver.
*/
require_once 'XML/Query2XML.php';

/**Driver for the database abstraction layer PEAR MDB2.
*
* usage:
* <code>
* $driver = XML_Query2XML_Driver::factory(NewADOConnection(...));
* </code>
*
* @access private
* @author Lukas Feiler <lukas.feiler@lukasfeiler.com>
* @version Release: @package_version@
* @copyright Empowered Media 2006
* @package XML_Query2XML
* @since Release 1.5.0RC1
*/
class XML_Query2XML_Driver_ADOdb extends XML_Query2XML_Driver
{
    /**In instance of a class that extends ADOConnection.
    * @var ADOConnection
    */
    private $db = null;
    
    /**Constructor
    *
    * @throws XML_Query2XML_DBException If the ADOConnection instance passed as
    *                          argument was not connected to the database server.
    * @param ADOConnection $db An instance of ADOConnection.
    */
    public function __construct(ADOConnection $db)
    {
        if (!$db->IsConnected()) {
            throw new XML_Query2XML_DBException(
                'ADOConnection instance was not connected'
            );
        }
        $db->SetFetchMode(ADODB_FETCH_ASSOC);
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
            if (class_exists('PEAR_Error') && $record instanceof PEAR_Error) {
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
    
    /**Private method that will use ADOConnection::query() for simple and
    * ADOConnection::prepare() & ADOConnection::execute() for complex query
    * specifications.
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
            * SIMPLE QUERY
            */
            
            try {
                $result = $this->_db->query($sql);
            } catch (Exception $e) {
                /*
                * unit tests: ADOdbException/
                *  getXML/throwDBException.phpt
                *  getFlatXML/throwDBException.phpt
                *  _prepareAndExecute\throwDBException_simpleQuery.phpt
                */
                throw new XML_Query2XML_DBException(
                    $configPath . ': Could not run the following SQL query: '
                    . $sql .  '; ' . $e->getMessage()
                );
            }
            
            if (class_exists('PEAR_Error') && $result instanceof PEAR_Error) {
                /*
                * unit tests: ADOdbPEAR/
                *  getXML/throwDBException.phpt
                *  getFlatXML/throwDBException.diff
                *  _prepareAndExecute/throwDBException_simpleQuery.phpt
                */
                throw new XML_Query2XML_DBException(
                    $configPath . ': Could not run the following SQL query: '
                    . $sql .  '; ' . $result->toString()
                );
            } elseif ($result === false) {
                /*
                * unit tests: ADOdbDefault/
                *  getXML/throwDBException.phpt
                *  getFlatXML/throwDBException.phpt
                *  _prepareAndExecute/throwDBException_simpleQuery.phpt
                */
                throw new XML_Query2XML_DBException(
                    $configPath . ': Could not run the following SQL query: '
                    . $sql
                );
            }
        } else {
            /*
            * PREPARE & EXECUTE
            */
            
            $query =& $sql['query'];
            if (isset($this->_preparedQueries[$query])) {
                $queryHandle = $this->_preparedQueries[$query];
            } else {
                //ADOdb centralizes all error-handling in execute()
                $queryHandle = $this->_db->prepare($query);
                $this->_preparedQueries[$query] =& $queryHandle;
            }
            
            /*
            * EXECUTE
            */
            
            try {
                if (isset($sql['data'])) {
                    $result = $this->_db->execute($queryHandle, $sql['data']);
                } else {
                    $result = $this->_db->execute($queryHandle);
                }
            } catch (Exception $e) {
                /*
                * unit test: ADOdbException/
                *  _prepareAndExecute/throwDBException_complexQuery.phpt
                */
                throw new XML_Query2XML_DBException(
                    $configPath . ': Could not execute the following SQL '
                    . 'query: ' . $query .  '; ' . $e->getMessage()
                );
            }
                
            if ($result === false && function_exists('ADODB_Pear_Error')) {
                $result = ADODB_Pear_Error();
            }
            
            if (class_exists('PEAR_Error') && $result instanceof PEAR_Error) {
                /*
                * unit test: ADOdbPEAR/
                *  _prepareAndExecute/throwDBException_complexQuery.phpt
                */
                throw new XML_Query2XML_DBException(
                    $configPath . ': Could not execute the following SQL query: '
                    . $query . '; ' . $result->toString()
                );
            } elseif ($result === false) {
                /*
                * unit test: ADOdbDefault/
                *  _prepareAndExecute/throwDBException_complexQuery.phpt
                */
                throw new XML_Query2XML_DBException(
                    $configPath . ': Could not execute the following SQL query: '
                    . $query . ' (false was returned)'
                );
            }
        }
        return $result;
    }
}
?>