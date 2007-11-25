<?php
/**
 * This is included from unit tests to initialize an LDAP connection.
 *
 * PHP version 5
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2007 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/XML_Query2XML
 * @access    private
 */

require_once dirname(dirname(__FILE__)) . '/settings.php';
require_once 'Net/LDAP.php';
$ldap = Net_LDAP::connect($ldapConfig);
?>