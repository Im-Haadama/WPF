#!/bin/bash
DIR="/home/agla/store/utils"
cd $DIR
FILE="weekly.log.`date +%d`.html"
echo $FILE
date > $FILE
php ../tools/auto/weekly-trigger.php &> $FILE
