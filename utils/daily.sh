#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
php $DIR/../tools/auto/daily.php &> $DIR/daily.log