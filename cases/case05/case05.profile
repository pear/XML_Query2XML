FROM_DB FROM_CACHE CACHED AVG_DURATION DURATION_SUM SQL
1       0          false  0.0141170024 0.0141170024 SELECT
         *
     FROM
         customer c
         LEFT JOIN sale s ON c.customerid = s.customer_id
         LEFT JOIN album al ON s.album_id = al.albumid
         LEFT JOIN artist ar ON al.artist_id = ar.artistid
     ORDER BY
         c.customerid,
         s.saleid,
         al.albumid,
         ar.artistid

TOTAL_DURATION: 0.11826181411743
DB_DURATION:    0.088551998138428
