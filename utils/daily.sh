#!/bin/bash
DIR="/home/agla/store/utils"
cd $DIR
FILE="daily.log.`date +%d`.html"
echo $FILE
date > $FILE
php ../tools/auto/daily.php &> $FILE
