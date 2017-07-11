#!/bin/bash
# Henry (chocolatkey) 2017-07-10
if [ $# -eq 0 ]
  then
    echo "YDB updater"
    echo "Please provide ARGUMENT for additional DB file name"
else
    python3.6 /home/media/manga/yrip.py -c true -f true -a -d true -a "$@"
    sudo cp -R /home/media/manga/"$@" /var/solr
    sudo su - solr -c "/opt/solr/bin/post -c ydb $@"
    sleep 1
    curl http://localhost:8983/solr/ydb/update?optimize=true
    rm -f "$@"
fi
