#!/bin/bash

# diff the test directories DB, MDB2, ADOdbDefault, ADOdbException & ADOdbPEAR
# treating DB as the master

cd `dirname $0` || exit 1
diff -ruN --exclude '*.php' --exclude Repository --exclude Entries DB/ MDB2/
diff -ruN --exclude '*.php' --exclude Repository --exclude Entries DB/ ADOdbDefault/
diff -ruN --exclude '*.php' --exclude Repository --exclude Entries DB/ ADOdbException/
diff -ruN --exclude '*.php' --exclude Repository --exclude Entries DB/ ADOdbPEAR/
diff -ruN --exclude '*.php' --exclude Repository --exclude Entries DB/ PDO/

