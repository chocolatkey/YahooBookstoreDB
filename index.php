<?php
/**
 * YDB search page
 * Henry (chocolatkey) 2017-07-22
 */

date_default_timezone_set('UTC');
// For RSS (yeah sorry for not composing blabla)
require 'lib/FeedWriter/Item.php';
require 'lib/FeedWriter/Feed.php';
require 'lib/FeedWriter/RSS2.php';
require 'lib/FeedWriter/InvalidOperationException.php';
use \FeedWriter\RSS2;
// For Memecaching
require 'lib/Clickalicious/Memcached/Bootstrap.php';
use Clickalicious\Memcached\Client;
define('MCP', 'ydb_'); // memcache key prefix
$cache = new Client(
    '127.0.0.1'
);

function escapec($string) {
    $pattern = '/([\!\*\+\-\=\<\>\&\|\(\)\[\]\{\}\^\~\?\:\\/"])/g';
    $replacement = '\\$1';
    return preg_replace($pattern, $replacement, $string);
}

function getImageUrl($url) {
  return 'https://images'.rand(1,4).'-focus-opensocial.googleusercontent.com/gadgets/proxy?container=focus&refresh=604800&url='.$url;
  //'&resize_w=' + width
}

function sort_by_id($a, $b) {
    if((int)$a->id == (int)$b->id){ return 0 ; }
    return ($a->id > $b->id) ? -1 : 1;
}

function getdata($search) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8983/solr/ydb/'.$search);
    
    $result = curl_exec($ch);
    //echo curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    
    return $result;
}

function getresults($search, $unsafe = false) {
    global $cache;
    $shash = md5($search);
    $results = $cache->get(MCP.$shash);
    if(!$results) {
        $results = getdata('select?wt=json&fq=result:1'.($unsafe ? $search : '&q='.urlencode($search)).'&rows=50');
        $cache->set(MCP.$shash, $results, 60);
    }
    $results = json_decode($results);
    return $results->responseHeader->status === 0 ? $results->response->docs : [];
}

if(isset($_GET['ajax']) && isset($_GET['query'])) {
    header('Content-Type: application/json');
    echo json_encode(getresults($_GET['query']));
    die();
}
header('Content-Type: text/html; charset=utf-8');
if(!isset($_GET['rss'])) {
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="referrer" content="no-referrer" />
        <title>Yahoo Bookstore DB Browser</title>
        <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
        <link rel="stylesheet" href="static/bulma.min.css" type="text/css">
        <link rel="stylesheet" href="static/easy-autocomplete.min.css"> 
        <script src="static/jquery.min.js"></script> 
        <script src="static/jquery.easy-autocomplete.min.js"></script>
        <script src="static/lazysizes.min.js" async></script>
        <style>.select:after { z-index:1; }</style>
    </head>
    <body>
<?php
}
$results = [];
if(isset($_GET['search']) || isset($_GET['rss'])) {
    if(isset($_GET['id']) && $_GET['id'] != 0) {
        $ID = intval($_GET['id']);
        if($ID > 0 && isset($ID)) {
            $results = getresults('id:'.$ID.'');
        }
    } else {
        $params = [];
        
        if(isset($_GET['category']))
            if($_GET['category'] != "" && $_GET['category'] != "Category")
                array_push($params, "categories:\"".$_GET['category']."\"");
        
        if(isset($_GET['publisher']))
            if($_GET['publisher'] != "" && $_GET['publisher'] != "Publisher")
                array_push($params, "publisher:\"".$_GET['publisher']."\"");
        
        if(isset($_GET['title']))
            if($_GET['title'] != "")
                array_push($params, "(title:".$_GET['title']." OR titlekana:".$_GET['title'].")");
        
        if(isset($_GET['author']))
            if($_GET['author'] != "")
                array_push($params, "(authors.Name:".$_GET['author']." OR authors.Ruby:".$_GET['author'].")");

        if(isset($_GET['rss'])) {
            $results = getresults('&fl=id,title,description&q='.urlencode(implode(" AND ", $params)), true);
            usort($results, "sort_by_id"); // sort for largest id first
            
            $selfurl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $UFeed = new RSS2();
            $UFeed->setTitle('Yahoo Bookstore Database feed');
            $UFeed->setLink('https://github.com/chocolatkey/YahooBookstoreDB');
            $UFeed->setDescription('Updates for latest additions to the database.');
            $UFeed->setChannelElement('language', 'ja');
            $UFeed->setDate(time());
            // $UFeed->setChannelElement('pubDate', date($results[0]->_version_)); lol no epic fail
            $UFeed->setSelfLink($selfurl);
            $UFeed->addGenerator(); // Hell why not!
            foreach($results as $item) {
                $newItem = $UFeed->createNewItem();
                $newItem->setTitle($item->title.' (#'.$item->id.')');
                $newItem->setLink('https://viewer-bookstore.yahoo.co.jp/?cid='.$item->id.'&u0=1&u2=3&u1=3');
                $newItem->setDescription($item->description);
                //$newItem->setDate($item->_version_); lol epic fail
                $UFeed->addItem($newItem);
            }
            $UFeed->generateFeed();
            $UFeed->printFeed();
            die(); // Shineeee
        } else {
            $results = getresults(implode(" AND ", $params).'&rows=50');
        }
    }
?>
<section class="section">
            <div class="container">
                  <div class="content">
                  <a href="/manga">< Back to input form</a><br/>
                  <h1>Results</h1><hr/>
                  </div>
            </div>
<?php

            foreach($results as $item) {
                if(isset($item->title)) {
                    echo 
                    "<div class='columns'>
                      <div class='column is-one-quarter'>
                        <p class='image'>
                          <img class='lazyload' data-src='".getImageUrl($item->thumb)."/thumb_m.png'>
                        </p>
                      </div>
                      <div class='column is-three-quarters'>
                        <div class='content'>
                          <p>
                            <h1>".$item->title."</h1> <small>(".$item->titlekana.") ID: ".$item->id."</small>
                            <br/><br/>
                            <b>Author(s):</b>"; $authorskana = preg_split("/[、]+/u", $item->{'authors.Ruby'}[0]); foreach($item->{'authors.Name'} as $key => $author) { echo ' <!--<a href=\'?search=1&author='.$author.'\'>-->'.$author.'<!--</a>-->'; /* TODO: clean this disgustingness, fix japanese comma problem */ echo(isset($authorskana[$key]) && $authorskana[$key] != "") ? ' <!--<small>('.$authorskana[$key].')</small>-->' : ''; } echo "
                            <br/><b>Category(ies):</b>"; foreach($item->categories as $category) echo " <a href='?search=1&category=".$category."'>".$category.'</a>'; echo "
                            <br/><b>Publisher: </b><a href='?search=1&publisher=".$item->publisher."'>".$item->publisher."</a>
                            <br/><b>Description: </b>".$item->description."
                            <br/><br/><a class='button is-warning' href='https://bookstore.yahoo.co.jp/shoshi-".$item->id."'>Store</a> <a class='button is-info' href='https://viewer-bookstore.yahoo.co.jp/?cid=".$item->id."&u0=1&u2=3&u1=3'>Reader</a>
                          </p>
                        </div>
                      </div>
                    </div>";
                }
            }
?>          <br/>
            <div class="notification content">
              <h1>Remember!</h1>
              <ul>
                <li>Only the first 50 relevant items are displayed</li>
                <li>Database does not include Geo-restricted content (doesn't mean you can't get it)</li>
              </ul>
            </div>
        </section>
<?php } else {
    $results = $cache->get(MCP.'stats');
    if(!$results) {
        $results = json_decode(getdata('select?wt=json&fq=result:1&q=*&rows=0&facet=true&facet.field=categories&facet.field=publisher&stats=true&stats.field=id'));
        $cache->set(MCP.'stats', $results, 300);
    }
    $actual = [];
    if($results->responseHeader->status === 0) {
        $list = $results->facet_counts->facet_fields->categories;
        foreach($list as $item) {
            if(!is_numeric($item))
                $search_categories[] = $item;
        }
        
        $list = $results->facet_counts->facet_fields->publisher;
        foreach($list as $item) {
            if(!is_numeric($item))
                $search_publishers[] = $item;
        }
    }
?>
        <section class="section">
            <div class="container box" id="controlz">
                <form>
                    <div class="field">
                      <div class="content">
                      <h1>Yahoo Bookstore Search</h1>
                      <article class="message">
                      <div class="message-header">
                        <p>Current Stats</p>
                      </div>
                      <div class="message-body">
                        <ul>
                            <li>Total (not including geo-restricted and unreachable): <?php echo $results->stats->stats_fields->id->count; ?></li>
                            <li>Lowest: <?php echo $results->stats->stats_fields->id->min; ?>, Highest: <?php echo $results->stats->stats_fields->id->max; ?></li>
                        </ul>
                      </div>
                    </article>
                      </div>
                      <div class="field">
                        <p class="control">
                          <input class="input is-info" id="idsearch" type="text" name="id" placeholder="ID (overrides other fields)">
                        </p>
                      </div>
                      <p class="control">
                        <div class="ui-widget">
                          <input class="input" id="titlesearch" type="text" name="title" placeholder="Title (Japanese, katakana reading accepted)">
                        </div>
                      </p>
                    </div>
                    <div class="field">
                      <p class="control">
                        <div class="ui-widget">
                          <input class="input" id="authorsearch" type="text" name="author" placeholder="Author (Japanese, katakana reading accepted)">
                        </div>
                      </p>
                    </div>
                    <div class="field">
                      <p class="control">
                        <span class="select">
                          <select name="publisher">
                            <option>Publisher</option>
                            <?php
                            foreach($search_publishers as $item) {
                                echo "<option>$item</option>";
                            }
                            ?></select>
                        </span>
                      </p>
                    </div>
                    <div class="field">
                      <p class="control">
                        <span class="select">
                          <select name="category">
                            <option>Category</option>
                            <?php
                            foreach($search_categories as $item) {
                                echo "<option>$item</option>";
                            }
                            ?></select>
                        </span>
                      </p>
                    </div>
                    <!--
                    TODO!
                    <div class="field">
                        <fieldset style="padding: 5px;">
                            <legend>Type: </legend>
                            <label for="checkbox-1">Comic: </label>
                            <input type="checkbox" class="cbr" id="checkbox-1" name="cb_m" checked>
                            <label for="checkbox-2">Novel: </label>
                            <input type="checkbox" class="cbr" id="checkbox-2" name="cb_n" checked>
                            <label for="checkbox-3">Other: </label>
                            <input type="checkbox" class="cbr" id="checkbox-3" name="cb_o" checked>
                        </fieldset>
                    </div>
                    -->
                    <div class="field">
                      <p class="control">
                        <button class="button is-primary" name="search" value="1">Search</button>
                      </p>
                    </div>
                <form>
            </div>
        </section>
        <script>
        function getImageUrl(url) {
          return 'https://images' + (Math.floor(Math.random() * 4) + 1) + '-focus-opensocial.googleusercontent.com/gadgets/proxy?container=focus&refresh=604800&url=' + url;
        }
        var pattern = /([\!\*\+\-\=\<\>\&\|\(\)\[\]\{\}\^\~\?\:\\/"])/g;
        var options = {
            url: function(phrase) {
                return "?ajax&query=(title:" + phrase.replace(pattern, "\\$1") + " OR titlekana:" + phrase.replace(pattern, "\\$1") + ")";
            },
            list: {
                maxNumberOfElements: 25
            },
            requestDelay: 50,
            template: {
                type: "custom",
                method: function(value, item) {
                    return "<a href='?search=1&id=" + item["id"] + "'><img style='width: 50px; height: auto;' src='" + getImageUrl(item["thumb"]) + "/thumb_s.png' /> " + value + " (" + item["titlekana"] + ")</a>";
                }
            },
            getValue: "title"
        };
        $("#titlesearch").easyAutocomplete(options);
        
        var options2 = {
            url: function(phrase) {
                return "?ajax&query=authors.Name:" + phrase.replace(pattern, "\\$1") + " OR authors.Ruby:" + phrase.replace(pattern, "\\$1");
            },
            list: {
                maxNumberOfElements: 25
            },
            getValue: function(element) {
                return element["authors.Name"].join();
            }
        };
        $("#authorsearch").easyAutocomplete(options2);
        
        var options3 = {
            url: function(phrase) {
                return "?ajax&query=id:" + phrase.replace(pattern, "\\$1") + "*";
            },
            list: {
                maxNumberOfElements: 5
            },
            requestDelay: 500,
            template: {
                type: "custom",
                method: function(value, item) {
                    if(item["thumb"])
                        return "<a href='?search=1&id=" + item["id"] + "'><img style='width: 50px; height: auto;' src='" + getImageUrl(item["thumb"]) + "/thumb_s.png' /> " + value + " (" + item["title"] + ")</a>";
                    else
                        return value + " (Error: " + item["result"] + ")"
                }
            },
            getValue: "id"
        };
        $("#idsearch").easyAutocomplete(options3);
        </script>
<?php } ?>
    </body>
</html>