#!/bin/bash
# Henry (chocolatkey) 2017-08-06
if [ $# -eq 0 ]
  then
    echo "YDB updater"
    echo "Please provide ARGUMENT for additional DB file name"
else
    python3.6 /home/media/manga/yrip.py -c true -f true -d true -a "$@"-cron.json -r 801000-820000
    sudo chmod 0777 /home/media/manga/db.json
    sudo cp -R /home/media/manga/"$@"-cron.json /var/solr
    sudo su - solr -c "/opt/solr/bin/post -c ydb *-cron.json"
    sleep 1
    curl http://localhost:8983/solr/ydb/update?optimize=true
    sudo rm -f "$@"-cron.json && sudo rm -f /var/solr/"$@"-cron.json
fi