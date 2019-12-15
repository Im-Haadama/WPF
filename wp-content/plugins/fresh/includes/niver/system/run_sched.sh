#!/bin/bash
pid=$(ps -ef |grep sched.php | grep -v grep | awk '{print $2}')
if [ -z ${pid} ]
then
  echo starting
  php sched.php &
  exit
fi
if [ $1 == '-f' ]
then
  echo killing $pid
  kill -9 $pid
  echo starting
  php sched.php &
fi

