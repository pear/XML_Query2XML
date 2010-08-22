--TEST--
XML_Query2XML_Driver_LDAP::getXML(): check for XML_Query2XML_LDAPException
--SKIPIF--
<?php require_once dirname(dirname(__FILE__)) . '/skipif.php'; ?>
--FILE--
<?php
    require_once 'XML/Query2XML.php';
    require_once dirname(dirname(__FILE__)) . '/ldap_init.php';

    try {
        $query2xml = XML_Query2XML::factory($ldap);
        $dom = $query2xml->getXML(
            array(
                'base' => 'ou=people,dc=example,dc=com',
                'filter' => '(object class=inetOrgPerson)'
            ),
            array(
                'rootTag' => 'persons',
                'rowTag' => 'person',
                'idColumn' => 'cn',
                'elements' => array(
                    'cn',
                    'sn',
                    'mail'
                )
            )
        );
        $dom->formatOutput = true;
        print $dom->saveXML();
    } catch (XML_Query2XML_LDAPException $e) {
        echo get_class($e);
    } catch (XML_Query2XML_LDAP2Exception $e) {
        echo str_replace('LDAP2', 'LDAP', get_class($e));
    }
?>
--EXPECT--
XML_Query2XML_LDAPException
