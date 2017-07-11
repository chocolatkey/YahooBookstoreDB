#!/bin/bash

sudo su - solr -c "/opt/solr/bin/solr delete -c ydb"
sleep 3
sudo su - solr -c "/opt/solr/bin/solr create -c ydb -n data_driven_schema_configs"
sleep 5
curl http://localhost:8983/solr/ydb/schema -X POST -H 'Content-type:application/json' --data-binary '{
    "add-field" : {
        "name":"title",
        "type":"text_ja",
        "multiValued":false,
        "stored":true
    },
    "add-field" : {
        "name":"titlekana",
        "type":"text_ja",
        "multiValued":false,
        "stored":true
    },
    "add-field" : {
        "name":"authors.Name",
        "type":"text_ja",
        "multiValued":true,
        "stored":true
    },
    "add-field" : {
        "name":"authors.Ruby",
        "type":"text_ja",
        "multiValued":true,
        "stored":true
    },
    "add-field" : {
        "name":"categories",
        "type":"string",
        "multiValued":true,
        "stored":true
    },
    "add-field" : {
        "name":"description",
        "type":"text_ja",
        "multiValued":false,
        "stored":true
    },
    "add-field" : {
        "name":"publisher",
        "type":"string",
        "multiValued":false,
        "stored":true
    },
    "add-field" : {
        "name":"type",
        "type":"tint",
        "multiValued":false,
        "stored":true
    },
    "add-field" : {
        "name":"thumb",
        "type":"string",
        "multiValued":false,
        "stored":true
    }
}'
sudo cp -R db.json /var/solr
sudo su - solr -c "/opt/solr/bin/post -c ydb db.json"
sleep 1
curl http://localhost:8983/solr/ydb/update?optimize=true
