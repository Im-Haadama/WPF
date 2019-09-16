#/bin/bash -f
echo old
exit
if [ "$#" -ne 1 ]; then
    echo "Usage: $0 <target>"
    exit 1
fi
HOST="$1"

# upload langunage
if [ -f $HOST.dep ]; then
  find . -name '*.mo' -newer "$HOST".dep -exec utils/upload.sh "$HOST" {} \;
else
  find . -name '*.mo' -exec utils/upload.sh "$HOST" {} \;
fi

# upload php
if [ -f $HOST.dep ]; then
  $version=
  tar cvf version/
else
fi

#  find . -name '*.php' -exec utils/upload.sh "$HOST" {} \;
#  find . -name '*.php' -newer "$HOST".dep -exec utils/upload.sh "$HOST" {} \;


# version file - fresh.<number>.tar
