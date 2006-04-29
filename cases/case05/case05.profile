FROM_DB FROM_CACHE CACHED AVG_DURATION DURATION_SUM SQL
1       0          false  0.0074028968 0.0074028968 SELECT
         *
     FROM
         customer c
         LEFT JOIN sale s ON c.customerid = s.customer_id
         LEFT JOIN album al ON s.album_id = al.albumid
         LEFT JOIN artist ar ON al.artist_id = ar.artistid

TOTAL_DURATION: 0.22688508033752
DB_DURATION:    0.050441980361938
