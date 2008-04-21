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
                            ':2000'
                        ),
                        'query' => 'SELECT * FROM album WHERE artist_id = ? AND NOT albumid = ?',
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
CACHING: SELECT * FROM album WHERE artist_id = ? AND NOT albumid = ?; LIMIT:1; OFFSET:2; DATA:1,2000
QUERY: SELECT * FROM album WHERE artist_id = ? AND NOT albumid = ?; LIMIT:1; OFFSET:2; DATA:1,2000
DONE
CACHING: SELECT * FROM album WHERE artist_id = ? AND NOT albumid = ?; LIMIT:1; OFFSET:2; DATA:2,2000
QUERY: SELECT * FROM album WHERE artist_id = ? AND NOT albumid = ?; LIMIT:1; OFFSET:2; DATA:2,2000
DONE
CACHING: SELECT * FROM album WHERE artist_id = ? AND NOT albumid = ?; LIMIT:1; OFFSET:2; DATA:3,2000
QUERY: SELECT * FROM album WHERE artist_id = ? AND NOT albumid = ?; LIMIT:1; OFFSET:2; DATA:3,2000
DONE
CACHED: SELECT * FROM album WHERE artist_id = ? AND NOT albumid = ?; LIMIT:1; OFFSET:2; DATA:1,2000
CACHED: SELECT * FROM album WHERE artist_id = ? AND NOT albumid = ?; LIMIT:1; OFFSET:2; DATA:2,2000
CACHED: SELECT * FROM album WHERE artist_id = ? AND NOT albumid = ?; LIMIT:1; OFFSET:2; DATA:3,2000