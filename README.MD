# Yahoo Bookstore Database
[Available live here](https://ivy.chocolatkey.com/manga/)

No, crawler will not be released. Example cron:

```
*/20 * * * * /home/media/manga/cron.sh crondb.json -r 790000-795000
```

## Needs

- Apache Solr
- Linux w/ webserver & PHP
- Python