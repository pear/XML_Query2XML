#!/bin/bash

diff -ruN --exclude '*.php' --exclude Repository --exclude Entries DB/ MDB2/
diff -ruN --exclude '*.php' --exclude Repository --exclude Entries DB/ ADOdbDefault/
diff -ruN --exclude '*.php' --exclude Repository --exclude Entries DB/ ADOdbException/
diff -ruN --exclude '*.php' --exclude Repository --exclude Entries DB/ ADOdbPEAR/
