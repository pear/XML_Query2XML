--TEST--
XML_Query2XML::registerPrefix()
--SKIPIF--
<?php require_once dirname(dirname(__FILE__)) . '/skipif.php'; ?>
--FILE--
<?php
require_once 'XML/Query2XML.php';
require_once 'XML/Query2XML/Data/Processor.php';
require_once dirname(dirname(__FILE__)) . '/db_init.php';

class Year2UnixTime extends XML_Query2XML_Data_Processor
{
    /**
     * Create a new instance of this class.
     *
     * @param mixed  $preProcessor The pre-processor to be used. An instance of
     *                             XML_Query2XML_Data or null.
     * @param string $configPath   The configuration path within the $options
     *                             array.
     *
     * @return XML_Query2XML_Data_Processor_Base64
     */
    public function create($preProcessor, $configPath)
    {
        $processor = new Year2UnixTime($preProcessor);
        $processor->setConfigPath($configPath);
        return $processor;
    }
    
    /**
     * Called by XML_Query2XML for every record in the result set.
     *
     * @param array $record An associative array.
     *
     * @return string The base64-encoded version the string returned
     *                by the pre-processor.
     * @throws XML_Query2XML_ConfigException If the pre-processor returns
     *                something that cannot be converted to a string
     *                (i.e. an object or an array).
     */
    public function execute(array $record)
    {
        $data = $this->runPreProcessor($record);
        if (is_array($data) || is_object($data)) {
            throw new XML_Query2XML_ConfigException(
                $this->getConfigPath()
                . ': XML_Query2XML_Data_Processor_Base64: string '
                . 'expected from pre-processor, but ' . gettype($data) . ' returned.'
            );
        }
        return DateTime::createFromFormat('Y-m-d H:i:s', $data . '-01-01 00:00:00', new DateTimeZone('Etc/GMT+0'))->format('U');
    }
}

$query2xml = XML_Query2XML::factory($db);

$query2xml->registerPrefix('§', 'Year2UnixTime');

$dom = $query2xml->getXML(
  "SELECT * FROM artist",
  array(
    'rootTag' => 'artists',
    'idColumn' => 'artistid',
    'rowTag' => 'artist',
    'elements' => array(
        'name',
        'birth_year',
        'birth_year_in_unix_time' => '§birth_year'
    )
  )
);

header('Content-Type: application/xml');
$dom->formatOutput = true;
print $dom->saveXML();
?>
--EXPECT--
<?xml version="1.0" encoding="UTF-8"?>
<artists>
  <artist>
    <name>Curtis Mayfield</name>
    <birth_year>1920</birth_year>
    <birth_year_in_unix_time>-1577923200</birth_year_in_unix_time>
  </artist>
  <artist>
    <name>Isaac Hayes</name>
    <birth_year>1942</birth_year>
    <birth_year_in_unix_time>-883612800</birth_year_in_unix_time>
  </artist>
  <artist>
    <name>Ray Charles</name>
    <birth_year>1930</birth_year>
    <birth_year_in_unix_time>-1262304000</birth_year_in_unix_time>
  </artist>
</artists>
