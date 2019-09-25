#/bin/bash -f
if [ "$#" -ne 1 ]; then
    echo "Usage: $0 <file>"
    exit 1
fi

for host in $(cat hosts.txt | cut -f 1 -d " ")
do
  ./utils/upload.sh $host $1
done
#  find . -name '*.php' -exec utils/upload.sh "$HOST" {} \;
#  find . -name '*.php' -newer "$HOST".dep -exec utils/upload.sh "$HOST" {} \;


# version file - fresh.<number>.tar
