<?php
require_once('XML/Query2XML.php');
require_once('DB.php');
$query2xml = XML_Query2XML::factory(DB::connect('mysql://root@localhost/Query2XML_Tests'));
$dom = $query2xml->getXML(
    "SELECT
        *
     FROM
        artist",
    array(
        'rootTag' => 'MUSIC_LIBRARY',
        'rowTag' => 'ARTIST',
        'idColumn' => 'artistid',
        'elements' => array(
            'NAME' => 'name',
            'BIRTH_YEAR' => 'birth_year',
            'BIRTH_YEAR_TWO_DIGIT' => "#firstTwoChars()",
            'BIRTH_PLACE' => 'birth_place',
            'GENRE' => 'genre',
            'albums' => array(
                'sql' => array(
                    'data' => array(
                        'artistid'
                    ),
                    'query' => 'SELECT * FROM album WHERE artist_id = ?'
                ),
                'sql_options' => array(
                    'uncached'        => true,
                    'merge_selective' => array('genre')
                ),
                'rootTag' => '',
                'rowTag' => 'ALBUM',
                'idColumn' => 'albumid',
                'elements' => array(
                    'TITLE' => 'title',
                    'PUBLISHED_YEAR' => 'published_year',
                    'COMMENT' => 'comment',
                    'GENRE' => 'genre'
                ),
                'attributes' => array(
                    'ALBUMID' => 'albumid'
                )
            )
        ),
        'attributes' => array(
            'ARTISTID' => 'artistid',
            'MAINTAINER' => ':Lukas Feiler'
        )
    )
);

header('Content-Type: application/xml');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

require_once('XML/Beautifier.php');
$beautifier = new XML_Beautifier();
print $beautifier->formatString($dom->saveXML());

function firstTwoChars($record)
{
    return substr($record['birth_year'], 2);
}
?>