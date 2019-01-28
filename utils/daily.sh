#!/bin/bash
DIR="/home/agla/store/utils"
cd $DIR
php ../tools/auto/daily.php &> $DIR/daily.log
