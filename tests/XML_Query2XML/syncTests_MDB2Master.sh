#!/bin/bash

# sync the test directories DB, MDB2, ADOdbDefault, ADOdbException & ADOdbPEAR
# treating DB as the master

cd `dirname $0` || exit 1

./diffTests_MDB2Master.sh | grep 'diff -ruN' | awk '{print $9 " " $10}' | \
while read SRC DST;
do
	echo syncing $DST
	cat $SRC > $DST;
done
