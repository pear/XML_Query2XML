#!/bin/bash

# diff the test directories DB, MDB2, ADOdbDefault, ADOdbException & ADOdbPEAR
# treating MDB2 as the master

cd `dirname $0` || exit 1
diff -ruN --exclude '*.php' --exclude Repository --exclude Entries MDB2/ DB/
diff -ruN --exclude '*.php' --exclude Repository --exclude Entries MDB2/ ADOdbDefault/
diff -ruN --exclude '*.php' --exclude Repository --exclude Entries MDB2/ ADOdbException/
diff -ruN --exclude '*.php' --exclude Repository --exclude Entries MDB2/ ADOdbPEAR/

