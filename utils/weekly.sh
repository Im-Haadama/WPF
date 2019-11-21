#!/bin/bash
cd `dirname $0` || exit
unset LANG LANGUAGE LC_CTYPE
export LANG=he_IL.UTF-8 LANGUAGE=he LC_CTYPE=he_IL.UTF-8
/usr/bin/php ../fresh/auto/weekly-trigger.php
