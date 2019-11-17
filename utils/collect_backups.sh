#!/usr/bin/env bash
for host in $(cat hosts.txt | cut -f 1 -d " ")
do
  ./utils/upload.sh $host $1
done
