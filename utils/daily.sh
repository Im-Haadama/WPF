#!/bin/bash
cd `dirname $0` || exit
DIR='/var/www/html/fresh/auto'
echo $DIR
cd $DIR || exit
FILE="$DIR/logs/daily.log.`date +%d`.html"
echo $FILE
unset LANG LANGUAGE LC_CTYPE
export LANG=he_IL.UTF-8 LANGUAGE=he LC_CTYPE=he_IL.UTF-8
date > $FILE
/usr/bin/php $DIR/daily-trigger.php &>> $FILE
