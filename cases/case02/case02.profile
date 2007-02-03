FROM_DB FROM_CACHE CACHED AVG_DURATION DURATION_SUM SQL
1       0          false  0.0122361183 0.0122361183 SELECT
        *
     FROM
        artist
        LEFT JOIN album ON album.artist_id = artist.artistid
     ORDER BY
        artist.artistid,
        album.albumid

TOTAL_DURATION: 0.028741836547852
DB_DURATION:    0.021204948425293
