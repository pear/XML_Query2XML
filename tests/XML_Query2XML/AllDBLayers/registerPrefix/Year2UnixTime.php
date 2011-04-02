<?php
require_once 'XML/Query2XML/Data/Processor.php';

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
?>
