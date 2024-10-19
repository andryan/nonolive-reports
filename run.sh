#!/bin/bash
cd /usr/share/nginx/html/nonolive/automated
./prep.sh
php step1.php > processed.csv
php step2.php > rep.txt
exit 0
