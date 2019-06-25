#!/bin/bash
DIR="/home/agla/store/utils"
cd $DIR
FILE="daily.log.`date +%d`.html"
echo $FILE
unset LANG LANGUAGE LC_CTYPE
export LANG=he_IL.UTF-8 LANGUAGE=he LC_CTYPE=he_IL.UTF-8
date > $FILE
php $DIR/../tools/auto/daily-trigger.php &>> $FILE
