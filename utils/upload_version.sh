#/bin/bash -f
if [ "$#" -ne 1 ]; then
    echo "Usage: $0 <target>"
    exit 1
fi
HOST="$1"

if [ -f $HOST.dep ]; then
  find . -name '*.mo' -newer $HOST.dep -exec utils/upload.sh "$HOST" {} \;
else
  find . -name '*.mo' -exec utils/upload.sh "$HOST" {} \;
fi