#!/bin/bash
date >> $0.log
pid=`ps -ef | egrep '^mysql' | awk '{print $2 }'`
if [ "$pid" > 0 ]
then
  echo OK >> $0.log

    exit
fi
echo Starting >> $0.log
service mysql start >> $0.log
