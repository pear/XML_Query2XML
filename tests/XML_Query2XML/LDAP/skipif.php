<?php
/**
 * This is included from unit tests to skip the test if Net/LDAP.php or the LDAP
 * directory itself is not available.
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

if (!@include_once 'Net/LDAP.php') {
    print 'skip could not find Net/LDAP.php';
    exit;
} else {
    include_once dirname(dirname(__FILE__)) . '/settings.php';
    $ldap = Net_LDAP::connect($ldapConfig);
    if (PEAR::isError($ldap)) {
        print 'skip could not connect to LDAP directory';
        exit;
    }
}
?>