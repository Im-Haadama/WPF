#/bin/bash -f
if [ "$#" -ne 2 ]; then
    echo "Usage: $0 <host> <file>"
    exit 1
fi
HOST=$1
FILE=$2
if [ ! -f "$FILE" ]; then
  echo "file $FILE not found"
  exit 2
fi
USER=$(grep $HOST hosts.txt | awk '{ print $2}')
PASSWORD=$(grep $HOST hosts.txt | awk '{ print $3}')
REMOTE_DIR=$(grep $HOST hosts.txt | awk '{ print $4}')

ftp -inv $HOST << EOF
user $USER $PASSWORD
bin
cd $REMOTE_DIR
put $FILE
bye
EOF