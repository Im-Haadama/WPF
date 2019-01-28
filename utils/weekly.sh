#!/bin/bash
DIR="/home/agla/store/utils"
cd $DIR
php $DIR/../tools/auto/weekly.php &> $DIR/weekly.log
