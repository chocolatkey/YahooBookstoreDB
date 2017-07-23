# Yahoo Bookstore Database
[Available live here](https://ivy.chocolatkey.com/manga/)

No, crawler will not be released. Example cron:

```
*/20 * * * * cd /home/media/manga && /home/media/manga/cron.sh $(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
```

## Needs

- Apache Solr
- Linux w/ webserver, PHP and memcached
- Python 3.x

## TODO

- Caching w/ Memcached
- Pagination
