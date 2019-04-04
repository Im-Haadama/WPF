#!/bin/bash
LANG=he_IL.UTF-8
export LANG
file=$1
result="${file%.*}.csv"
if [ -f $result ]; then
	rm -f $result
fi
extension="${file##*.}"
case "$extension" in
"xlsx")
	ssconvert $file "${file%.*}.csv"
	;;
"xls")
	ssconvert $file "${file%.*}.csv"
	;;

"html")
	ssconvert $file "${file%.*}.csv"
	;;

"pdf")
	/home/agla/store/utils/run_sadot.sh  $file > "${file%.*}.csv"
	;;

	*)
	echo Extention $extension not supported.
	exit 1
	;;
esac
if [ -f $result ]; then
	echo $(basename $result)
fi
