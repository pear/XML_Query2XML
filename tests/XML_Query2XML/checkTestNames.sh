#!/bin/bash

# Output all unit test files that have a name that does not match the
# directory they are stored in.
# Note: a unit test's name is stored in the second line of phpt file;
#       all names start with XML_Query2XML::<METHOD_NAME>; <METHOD_NAME>
#       should be equal to the directory name.

cd `dirname $0` || exit 1

grep -R '^XML_Query2XML::' DB/ | awk -F: '{print $1 " " $4}' | \
awk -F'(' '{print $1}' | while read FILE FUNCTION;
do
	DIR=$(basename `dirname $FILE`);
	if [ "$DIR" != "$FUNCTION" ] ; then
		echo $FILE: $FUNCTION
	fi
done

