#!/bin/bash
date >> $0.log
pid=`ps -ef | egrep '^mysql' | awk '{print $2 }'`
if [ "$pid" > 0 ]
then
  echo OK >> $0.log

    exit
fi
echo Starting
/usr/sbin/service mysql start
