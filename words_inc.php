<?php
include('inc_apikey'); //format - $api_key = "key";
$base = "http://www.flickr.com/services/rest/?api_key=$api_key&";

function get_group($letter) {
    switch ($letter) {
        case "a":
            $tag = "aa";
            $group_id='27034531@N00';           // Letter group
            break;
        case "i":
            $tag = "ii";
            $group_id='27034531@N00';           // Letter Group
            break;
        case (preg_match("/[b-z]/", $letter) ? $letter: !$letter):
            $tag = $letter;
            $group_id='27034531@N00';           // Letter Group
            break;
        case "?":
            $tag = "Question Mark";
            $tag = "questionmark";              // Not sure which to use
            $group_id='34231816@N00';           // Puncuation group
            break;
        case "+":
            $tag = "plus";
            $group_id='34231816@N00';           // Puncuation group
            break;
        case "&":
            $tag = "ampersand";
            $group_id='34231816@N00';           // Puncuation group
            break;
        case "!":
            $tag = "exclamation";
            $group_id='34231816@N00';           // Puncuation group
            break;
        case ":":
            $tag = "colon";
            $group_id='34231816@N00';           // Puncuation group
            break;
        case ".":
            $tag = "period";
            $group_id='34231816@N00';           // Puncuation group
            break;
        case "@":
            $tag = "atsign";
            $group_id='34231816@N00';           // Puncuation group
            break;
        case "0":
            $tag = "00";
            $group_id='54718308@N00';           // Number Group
            break;
        case (preg_match("/[1-9]/", $letter) ? $letter : !$letter):
            $tag = $letter;
            $group_id='54718308@N00';           // Number Group
            break;
        default:
            $group_id = "";
            break;
    }

    return array($group_id, $tag);
}

/* Functions to parse xml - not ideal */
/* Function to handle <(HERE)>.</tag> */
function startTag($parser, $name, $attrs) {
    global $tags, $doc;
    $name = strtolower($name);
    if ($name == "rsp") {
        $doc = "";
        $tags = Array();
    }
    array_push($tags, $name);
    $attrs = array_change_key_case($attrs);
    $tt = join("\"][\"", $tags);
    eval("\$doc[\"$tt\"][]=\$attrs;");
}
/* Function to handle <tag>.</(HERE)> */
function endTag($parser, $name) {
    global $tags;
    array_pop($tags);
}
/* Function to handle <tag>(HERE)</tag> */
function cdata($parser, $cdata) {
    global $tags, $doc;
    $tt = join("\"][\"", $tags);
    if (trim($cdata))
        eval("\$doc[\"$tt\"][count(\$doc[\"$tt\"])-1]['cdata']=\$cdata;");
}
/* / xml */

function parse_flickr_xml($xml_conts) {
    global $doc;

    /* for some reason, I must re-initialze each time :( */
    $xml_parser = xml_parser_create();
    xml_set_element_handler($xml_parser, "startTag", "endTag");
    xml_set_character_data_handler($xml_parser, "cdata");
    xml_parse($xml_parser, $xml_conts, true) or die(sprintf("XML error: %s at line %d\n\n%s", xml_error_string(xml_get_error_code($xml_parser)), xml_get_current_line_number($xml_parser), $xml_conts));
    xml_parser_free($xml_parser);
    /* done parsing xml */

    return($doc);
}

function fetch_xml($tag, $group_id) {
    global $base;
    $method = "flickr.groups.pools.getPhotos";
    $limit = "&per_page=100";
    $extra = "&group_id=$group_id";
    $extra .= "&tags=$tag";

    $method = "method=$method";

    $tags = Array();
    /* Check the filesystem first - rudimentary caching */
    $get_new = 1;
    $cache_name = "./cache/{$method}_" . str_replace("@", "-", $group_id) . "_{$tag}.xml";
    $cache_name = str_replace("..", "", $cache_name);
    $cache_name = preg_replace("/;\@\*/", "", $cache_name);
    if (file_exists($cache_name)) {
        #if (time() - (filemtime($cache_name)) <= (8 * (60 * 60))) {         //hours to cache for
        if (time() - (filemtime($cache_name)) <= (40 * (60 * 60))) {         //hours to cache for
            $xml_conts = file_get_contents($cache_name);
            $get_new = 0;
        }
    }
    /* request the xml */
    if ($get_new) {
        #echo "SKIPPED CACHE!!! - $letter<br />";    #hopefuly to figure out why I slammed the api :/
        $fi = fopen("cache-misses.txt", "a") or die("can't pen cache-misses.txt");
        fwrite($fi, "Cache miss at " . date("D M j G:i:s T Y") . " with letter $letter and string $string\n");
        fclose($fi);
        #echo "$base$method$limit$email$extra";
        $xml_conts = file_get_contents("$base$method$limit$email$extra");
        if (preg_match("/stat=\"fail/", $xml_conts) || strlen($xml_conts) < 200) {
            $fi = fopen("cache-misses.txt", "a") or die("can't pen cache-misses.txt");
            fwrite($fi, "    PROBLEM-+-+ Failure\n");
            fclose($fi);
            continue;
        }
        else {
            $hand = fopen($cache_name, 'w');
            fwrite($hand, $xml_conts);
            fclose($hand);
        }
    }
    #echo htmlentities($xml_conts);

    return($xml_conts);
}
