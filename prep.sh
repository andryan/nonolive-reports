#!/bin/bash
DATE=`date '+%Y%m%d'`
rm -f feeds/live-current.csv feeds/anchor-current.csv
scp -C -i ~ubuntu/private-key.pem nonolive_read@52.76.73.237:/home1/nonolive_read/live_logs/$DATE.log feeds/live-$DATE.csv
scp -C -i ~ubuntu/private-key.pem nonolive_read@52.76.73.237:/home1/nonolive_read/anchor_statis/$DATE.csv feeds/anchor-$DATE.csv
#scp -C -i ~ubuntu/private-key.pem nonolive_read@52.76.73.237:/home1/nonolive_read/live_logs/* feed/
#LASTFILE=`ls -1 feed/201* | tail -1`
#mv $LASTFILE feed/current.csv
head -n -6 feeds/anchor-$DATE.csv > feeds/anchor-$DATE-temp.csv
mv -f feeds/anchor-$DATE-temp.csv feeds/anchor-$DATE.csv
ln -r -s feeds/live-$DATE.csv feeds/live-current.csv
ln -r -s feeds/anchor-$DATE.csv feeds/anchor-current.csv
