#!/bin/bash
cd `dirname $0`
DIR=`pwd`
echo $DIR
cd $DIR
FILE="$DIR/daily.log.`date +%d`.html"
echo $FILE
unset LANG LANGUAGE LC_CTYPE
export LANG=he_IL.UTF-8 LANGUAGE=he LC_CTYPE=he_IL.UTF-8
date > $FILE
/usr/bin/php $DIR/../tools/auto/daily-trigger.php &>> $FILE
