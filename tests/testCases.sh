#!/bin/bash

#
# Name: testCases.sh
# Version: $Id: testCases.sh,v 1.1 2006-04-29 07:03:55 lukasfeiler Exp $
#

printUsage()
{
    echo "testCases.sh [-C] [-f FIRST_CASE] [-c CASES] [-u BASE_URL] [-L CASES_LOCATION]" >&2
    echo " -C                   Clean up old files first" >&2
    echo " -f FIRST_CASE        Case number to start with; default is 1" >&2
    echo " -c CASES             Number of cases; default is 5" >&2
    echo " -u BASE_URL          The URL of the cases directory; default" >&2
    echo "                      is http://localhost/Query2XML/cases" >&2
    echo " -L CASES_LOCATION    The path to all case directories; default" >&2
    echo "                      is ../../../docs/XML_Query2XML/cases." >&2
    echo "" >&2
    echo "This script tests all six cases by running all of them and" >&2
    echo "comparing their output to the XML files in the specified" >&2
    echo 'case directory. $BASE_URL/case$i/case$i.php is requested for' >&2
    echo "this purpose." >&2
    echo "The advantage of this script over the unit tests is that the" >&2
    echo "expected and the actual result are diffed and you can inspect" >&2
    echo "the files yourself." >&2
}

CASE_COUNT=8
FIRST_CASE=1
BASE_URL="http://localhost/Query2XML/cases"
CASES_LOCATION="../../../docs/XML_Query2XML/cases"
while getopts ":Cf:c:u:L:" opt; do
    case $opt in
        C ) CLEANUP=true ;;
        f ) FIRST_CASE=$OPTARG
            CASE_COUNT=1;;
        c ) CASE_COUNT=$OPTARG ;;
        u ) BASE_URL=$OPTARG ;;
        L ) CASES_LOCATION=$OPTARG ;;
        ? ) printUsage
            exit 1 ;;
    esac
done

CASES_DIR=`(cd $CASES_LOCATION; pwd)`
TMP_FILE=$CASES_DIR/test.tmp

if [ "$CLEANUP" = "true" ] ; then
    echo -n "Cleanung up ... "
    i=1
    while [ $i -lt $CASE_COUNT ] ; do
        [ $i -lt 10 ] && i="0$i"
        rm -f case$i.xml
        rm -f case$i.xml.diff
        i=$((i + 1))
    done
    echo OK
fi

if [ "`find . -name 'case[0-9]*.*'`" != "" ] ; then
  echo "The following old case[0-9]*.* files need to be removed first:"
  find . -name 'case[0-9]*.*'
  exit 1
fi


i=$FIRST_CASE
MAX=$(($FIRST_CASE + $CASE_COUNT))
ERRORS=0
while [ $i -lt $MAX ] ; do
    [ $i -lt 10 ] && i="0$i"
    echo -n "Testing Case $i...   "
    wget -O case$i.xml $BASE_URL/case$i/case$i.php >$TMP_FILE 2>&1;ret=$?
    if [ $ret -ne 0 ] ; then
        echo "Could not download $BASE_URL/case$i/case$i.php:"
        cat $TMP_FILE
        rm -f $TMP_FILE
        exit 1
    fi
    rm -f $TMP_FILE
    
    echo "download OK"
    echo -n " diffing case$i.xml...         "
    diff -u case$i.xml $CASES_DIR/case$i/case$i.xml > case$i.xml.diff 2>&1;ret=$?
    if [ $ret -eq 0 ] ; then
        echo "OK"
        rm -f case$i.xml case$i.xml.diff
    else
        echo "ERROR: diff returned $ret: see case$i.xml.diff"
        ERRORS=$((ERRORS + 1))
    fi
    
    i=${i##0}
    i=$((i + 1))
done

rm -f $TMP_FILE


echo -n "Summary: "
if [ $ERRORS -eq 0 ] ; then
    echo "all OK"
else
    echo "$ERRORS errors"
fi
