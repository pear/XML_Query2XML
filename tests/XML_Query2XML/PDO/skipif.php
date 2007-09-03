<?php
/**This is included from unit tests to skip the test if MDB2 is not available.
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

if (!class_exists('PDO')) {
    print 'skip could not find PDO';
    exit;
} else {
    require_once dirname(dirname(__FILE__)) . '/settings.php';
    
    list($protocol, $address) = split('://', DSN);
    if (strpos($address, '@') === false) {
        if ($protocol == 'sqlite') {
            $protocol .= '2';
        }
        $address = ltrim($address, '/');
        $db = new PDO($protocol . ':' . $address);
    } else {
        list($credentials, $address) = split('@', $address);
            if (strpos($credentials, ':') === false) {
            $username = $credentials;
            $password = '';
        } else {
            list($username, $password) = split(':', $credentials);
        }
        list($host,$database) = split('/', $address);
        try {
            $db = new PDO($protocol . ':host=' . $host . ';dbname=' . $database, $username, $password);
        } catch (PDOException $e) {
            print 'skip could not connect using DSN ' . DSN . ': ' . $e->getMessage();
            exit;
        }
    }
}
?>