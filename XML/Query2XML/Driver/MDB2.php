<?php
/**
 * This file contains the class XML_Query2XML_Driver_MDB2.
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
 * XML_Query2XML_Driver_MDB2 extends XML_Query2XML_Driver.
 */
require_once 'XML/Query2XML.php';

/**
 * As the method PEAR::isError() is used within XML_Query2XML_Driver_MDB2
 * we require PEAR.php.
 */
require_once 'PEAR.php';

/**
 * Driver for the database abstraction layer PEAR MDB2.
 *
 * usage:
 * <code>
 * $driver = XML_Query2XML_Driver::factory(MDB2::factory(...));
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
class XML_Query2XML_Driver_MDB2 extends XML_Query2XML_Driver
{
    /**
     * In instance of a class that extends MDB2_Driver_Common.
     * @var MDB2_Driver_Common
     */
    private $_db = null;
    
    /**
     * Constructor
     *
     * @param MDB2_Driver_Common $db An instance of MDB2_Driver_Common.
     *
     * @throws XML_Query2XML_DBException If the fetch mode cannot be set to
     *                               MDB2_FETCHMODE_ASSOC.
     */
    public function __construct(MDB2_Driver_Common $db)
    {
        $fetchModeError = $db->setFetchMode(MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($fetchModeError)) {
            throw new XML_Query2XML_DBException(
                'Could not set fetch mode to DB_FETCHMODE_ASSOC: '
                . $fetchModeError->toString()
            );
        }
        $this->_db = $db;
    }
    
    /**
     * Execute a SQL SELECT stement and fetch all records from the result set.
     *
     * @param mixed  $sql        The SQL query as a string or an array.
     * @param string $configPath The config path; used for exception messages.
     *
     * @return array An array of records.
     * @throws XML_Query2XML_DBException If a database related error occures.
     * @see XML_Query2XML_Driver::getAllRecords()
     */
    public function getAllRecords($sql, $configPath)
    {
        $result  =& $this->_prepareAndExecute($sql, $configPath);
        $records = array();
        while ($record = $result->fetchRow()) {
            if (PEAR::isError($record)) {
                // no unit test for this exception as it cannot be produced easily
                throw new XML_Query2XML_DBException(
                    $configPath . ': Could not fetch rows for the following '
                    . 'SQL query: ' . $sql['query'] . '; '
                    . $record->toString()
                );
            }
            $records[] = $record;
        }
        $result->free();
        return $records;
    }
    
    /**
     * Private method that will use MDB2_Driver_Common::query() for simple and
     * MDB2_Driver_Common::prepare() & MDB2_Statement_Common::execute() for complex
     * query specifications.
     *
     * @param mixed  $sql        A string or an array.
     * @param string $configPath The config path used for exception messages.
     *
     * @return MDB2_Result
     * @throws XML_Query2XML_DBException If a database related error occures.
     */
    private function _prepareAndExecute($sql, $configPath)
    {
        $query =& $sql['query'];
        if (isset($this->_preparedQueries[$query])) {
            $queryHandle = $this->_preparedQueries[$query];
        } else {
            // PREPARE
            $queryHandle = $this->_db->prepare($query);
            
            if (PEAR::isError($queryHandle)) {
                /*
                 * unit tests: (only if mysql or pgsql is used)
                 *  MDB2/_prepareAndExecute/throwDBException_complexQuery.phpt
                 */
                throw new XML_Query2XML_DBException(
                    $configPath . ': Could not prepare the following SQL query: '
                    . $query . '; ' . $queryHandle->toString()
                );
            }
            $this->_preparedQueries[$query] =& $queryHandle;
        }
        
        // EXECUTE
        if (isset($sql['data'])) {
            $result = $queryHandle->execute($sql['data']);
        } else {
            $result = $queryHandle->execute();
        }
        
        if (PEAR::isError($result)) {
            /*
             * unit tests:
             *  if sqlite is used: MDB2/_prepareAndExecute/
             *   throwDBException_complexQuery.phpt
             *  if sqlite or mysql is sued: MDB2/getXML/
             *   throwDBException_nullResultSet_complexQuery_multipleRecords.phpt
             *   throwDBException_nullResultSet_complexQuery_singleRecord.phpt
             */
            throw new XML_Query2XML_DBException(
                $configPath . ': Could not execute the following SQL query: '
                . $query . '; ' . $result->toString()
            );
        }
        return $result;
    }
}
?>