--TEST--
XML_Query2XML::enableDebugLog() with $options['sql_options']['cached'] = true
--SKIPIF--
<?php $db_layers = array('MDB2', 'DB'); require_once dirname(dirname(__FILE__)) . '/skipif.php'; ?>
--FILE--
<?php
    class MyLogger
    {
        public $data = '';
        public function log($str)
        {
            $this->data .= $str . "\n";
        }
    }

    require_once 'XML/Query2XML.php';
    require_once dirname(dirname(__FILE__)) . '/db_init.php';
    $query2xml =& XML_Query2XML::factory($db);
    $debugLogger = new MyLogger();
    $query2xml->enableDebugLog($debugLogger);
    $dom =& $query2xml->getXML(
        'SELECT * FROM artist UNION ALL SELECT * FROM artist',
        array(
            'rootTag' => 'music_library',
            'rowTag' => 'artist',
            'idColumn' => 'artistid',
            'elements' => array(
                'artistid',
                'name',
                'birth_year',
                'birth_place',
                'genre',
                'albums' => array(
                    'sql' => array(
                        'data' => array(
                            'artistid',
                            ':test'
                        ),
                        'query' => 'SELECT * FROM album WHERE artist_id = ? AND "test" = ?',
                        'limit' => 1,
                        'offset' => 2
                    ),
                    'sql_options' => array(
                        'cached' => true
                    ),
                    'rootTag' => 'albums',
                    'rowTag' => 'album',
                    'idColumn' => 'albumid',
                    'elements' => array(
                        'albumid',
                        'title',
                        'published_year',
                        'comment'
                    )
                )
            )
        )
    );
    $query2xml->disableDebugLog();
    echo $debugLogger->data;
?>
--EXPECT--
QUERY: SELECT * FROM artist UNION ALL SELECT * FROM artist
DONE
CACHING: SELECT * FROM album WHERE artist_id = ? AND "test" = ?; LIMIT:1; OFFSET:2; DATA:1,test
QUERY: SELECT * FROM album WHERE artist_id = ? AND "test" = ?; LIMIT:1; OFFSET:2; DATA:1,test
DONE
CACHING: SELECT * FROM album WHERE artist_id = ? AND "test" = ?; LIMIT:1; OFFSET:2; DATA:2,test
QUERY: SELECT * FROM album WHERE artist_id = ? AND "test" = ?; LIMIT:1; OFFSET:2; DATA:2,test
DONE
CACHING: SELECT * FROM album WHERE artist_id = ? AND "test" = ?; LIMIT:1; OFFSET:2; DATA:3,test
QUERY: SELECT * FROM album WHERE artist_id = ? AND "test" = ?; LIMIT:1; OFFSET:2; DATA:3,test
DONE
CACHED: SELECT * FROM album WHERE artist_id = ? AND "test" = ?; LIMIT:1; OFFSET:2; DATA:1,test
CACHED: SELECT * FROM album WHERE artist_id = ? AND "test" = ?; LIMIT:1; OFFSET:2; DATA:2,test
CACHED: SELECT * FROM album WHERE artist_id = ? AND "test" = ?; LIMIT:1; OFFSET:2; DATA:3,test