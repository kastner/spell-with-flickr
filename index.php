<?php
/* Code to take a string, and encode it as pictures from flickr.
Erik Kastner - feel free to modify code and redistribute (just mention me).
any fixes, or chagnes - kastner@gmail.com
--------BUGS-------
**FIXED** Spaces don't get perseved in output - made images inline and used padding-left (6/25/05)
validation fails when the same image is randomly assigned (prob. won't fix)
**FIXED** MASSIVE problem with caching -- when I was checking for the cache hit, I was still doing file_get_contents on teh url :(
**FIXED** IE only lets you click on the first letter - is this why everyone reloads? -- got rid of some old code, and it works now
IE doesn't always update the textarea!

--------TODO-------
I hate the rollover. I can't figure out a good way to attribute back to flickr while still enabling the letter to change

-------TODO - IDEAS ---
3006-02-01... 
    Ok, for version 2.0 I want to use ajax or at least dhtml to show the other available letters and 
    possibly double click to get to flickr...
    or do a popup LIKE flickr and under the image,a link back to them
    either way - object oriented.

------CHANGES------
2005-02-22: First code
2005-03-13: Added caching
2005-03-13: Added entry form
2005-03-15: Fixed caching after having my key suspended
2005-03-19: Added change size button after suggestion from viceroy321 (and made it save in a cookie) and cleaned up some code
2005-03-19: Cleaned up html, made "output" work with wordpress (and other wysiwyg editors that introduce <br />s
2005-03-19: Got it to validate in some cases (if two letters randomly get the same image, it won't validate, but that is rare)
2005-06-25: Switched to inline images
2005-06-25: Rewrote MAJOR portions of the site - added a flickrSource div to avoid the whole popup mess... started using the $() function
2006-01-27: Added adsense, am I a sellout?
3006-01-31: Got dugg today - 1800 diggs. Changed code for flickr's server switch
2006-02-24: Fixed googles server change a few more places
2006-02-25: Added an RSS feed - an idea from Rafael Sidi <http://rafaelsidi.blogspot.com/>
2006-05-12: Working on ads, and cleaning up code... moving javascript to a .js file to shorten up source. Made a library of the functions
2006-05-12: BIGGEST CHANGE - moved to JSON for the "other" letters... phew much better!
2007-06-21: Removing ads. (google TOS b/c of profanity)

2008-06-26: A YEAR??! changing this to work in a sub directory (like I should have from the start!) - spammers are destroying my site, so I'm fixing things
*/
include('words_inc.php');

/* Clean up the input string - this is because it doesn't like &'s */
/* new version - portable */
$string = $_SERVER["REQUEST_URI"];
$sn = "!" . str_replace(".php", "(.php)?", $_SERVER["SCRIPT_NAME"]) . "/?!";
$string = preg_replace("!js/!", "", $string);
$string = preg_replace($sn, "", $string);
$string = preg_replace("/%20/", " ", $string);
$string = preg_replace("/%21/", "!", $string);
$string = preg_replace("/\+/", " ", $string);
$string = preg_replace("/%2B/", "+", $string);
$string = str_replace("/words/", "", $string);

/* Send headers */
header("Pragma: no-cache");
header("Cache-Control: no-cache, must-revalidate");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 

srand(time());                          // To make the textarea not cache on refreshes
$ta_rnd = rand(1,2000);

$size = $_COOKIE["size"];
$size = ($size) ? $size : "t";
$sel_t = ($size == "t") ? "checked='checked'" : "";
$sel_s = ($size == "s") ? "CHECKED" : "";

if ($string) {
    $rss = "/myfeed/$string";
    $link = "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"Spell with Flickr - $string - RSS feed\" href=\"$rss\" />";
    $nowwith = "href=\"$rss\"";
}
/* Start of html */
$title = ($string) ? ": $string" : "";
$h1 = ($string) ? "<em>$string</em> by " : "";

#
# universal header
#
$body = <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd ">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html;charset=iso-8859-1" />  
        <title>Spell with flickr$title</title>
        $link
        <link href="http://metaatem.net/words/words.css" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="http://metaatem.net/words/words.js"></script>
    </head>
    <body>

        <h1>{$h1}<a href="http://metaatem.net/words/">Spell with <span class="flickr">flick<span class="r">r</span></a></h1>

        <script type="text/javascript"><!--
        google_ad_client = "pub-5369189596696267";
        google_ad_width = 728;
        google_ad_height = 15;
        google_ad_format = "728x15_0ads_al_s";
        google_ad_channel ="1599316718";
        google_color_border = "000000";
        google_color_bg = "000000";
        google_color_link = "0000FF";
        google_color_url = "CCCCCC";
        google_color_text = "CCCCCC";
        //--></script>
        <script type="text/javascript"
          src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
          </script>

        <h2>Created by <a href="http://flickr.com/photos/kastner/">kastne<span class="r">r</span></a> (Erik Kastner) with PHP and javascript</h2>
        
        <p class="contact">
            Please send me comments, suggestions or questions, <a href="mailto:kastner@gmail.com">kastner@gmail.com</a>. I love getting emails about Spell with Flickr - and all my programming projects.
        </p>

HTML;

#
# no string - show the default page
#
if (!$string) { 
    $body .= <<<HTML

    <form action="nothing" onsubmit="subform(); return false;">
        <label for="words">Spell:</label> <input type="text" size="40" name="words" id="words" /><input type="submit" value="spell" />
    </form>
    <script type="text/javascript">
        document.getElementById("words").focus();
    </script>

HTML;
}
#
# there is a string
#
else {
    $body .= <<<HTML

        <h2>here is &ldquo;$string&rdquo; in images</h2>
        <div class="explain">Click on each letter to get a new one!</div>

        <div id="flickrImages">

HTML;

    $js_letters_array = array();

    /* Loop over each letter/number/punch in input */
    $clean_string = strtolower(preg_replace("/\/words\//", "", $string));
    foreach(explode("\r\n", chunk_split($clean_string, 1))  as $letter) {
        $img = "";
        if (strlen($letter) == 1) {     // if ($letter) fails on "0"
            $id_spot++;
            list($group_id, $tag) = get_group($letter);
            if ($group_id) {
                if (!$letters[$tag]) {
                    #
                    # fetch and parse the xml
                    #
                    $letters[$tag] = parse_flickr_xml(fetch_xml($tag, $group_id));

                    #
                    # loop through the xml and put it in an array (for later use in json)
                    #
                    $loop = $letters[$tag]["rsp"]["photos"]["photo"];
                    $js = array();
                    foreach($loop as $photo) {
                        $title = addslashes($photo["title"]);

                        # json string - SHORT
                        $js[] = "{\"id\":$photo[id],\"o\":\"$photo[owner]\",\"s\":$photo[server],\"st\":\"$photo[secret]\",\"t\":\"$title\"}";
                    }
                    $js_letters_array[] = "\"$tag\":[" . join(",", $js) . "]";
                }

                if ($letters[$tag]["rsp"][0]["stat"] == "ok") {
                    $photo = $letters[$tag]["rsp"]["photos"]["photo"][rand(1,count($letters[$tag]["rsp"]["photos"]["photo"]))];
                    $title = addslashes($photo["title"]);
                    $img = <<<HTML
    <a href="#" id="f_{$id_spot}" onClick="swapImg('$tag', this, $ta_rnd); return false;"><img border='0' alt='{$title}' title='{$title}' src='http://static.flickr.com/{$photo[server]}/{$photo[id]}_{$photo[secret]}_$size.jpg'/></a>
HTML;
                    $flickrSource .= <<<HTML
    <a href='http://www.flickr.com/photos/{$photo[owner]}/{$photo[id]}' id='fs_{$id_spot}' title='{$title}'><img alt='{$title}' border='0' src='http://static.flickr.com/{$photo[server]}/{$photo[id]}_{$photo[secret]}_{$size}.jpg' /></a>
HTML;

                }
                else {
                    $body .= "Error - {$letters[$tag][rsp][err][0][code]}: {$letters[$tag][rsp][err][0][msg]}<br />";
                }
            }
        }
        $class = ($img) ? "photo" : "spacer";
        $img = ($img) ? $img : "&nbsp;";
        $body .= <<<HTML
                <div class="$class">
                    $img
                </div>

HTML;

    }
    $body .= <<<HTML
        </div>
        <script type="text/javascript"><!--
        google_ad_client = "pub-5369189596696267";
        /* 728x90, created 9/20/08 spell_between */
        google_ad_slot = "8716881773";
        google_ad_width = 728;
        google_ad_height = 90;
        //-->
        </script>
        <script type="text/javascript"
        src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
        </script>
        <br/>
        <fieldset>
            <legend>On Flickr</legend>
            <div id="flickrSource">
            $flickrSource
            </div>
        </fieldset>

        <p>
        </p>

HTML;
}

if ($letters) {

    $js_letters = join(",\n\t\t", $js_letters_array);

    #
    # change tile code, links to sources
    #
    $body .= <<<HTML
        <h3>Change tile size</h3>
        <form action="nothing">
            <input type="radio" onclick="changesize('t', $ta_rnd);" id="size_t_$ta_rnd" name="size" value="t" $sel_t /><label for="size_t_$ta_rnd">Regular</label> 
            <br />
            <input type="radio" onclick="changesize('s', $ta_rnd);" id="size_s_$ta_rnd" name="size" value="s" $sel_s /><label for="size_s_$ta_rnd">Square</label> 
        </form>

        <a href="/words/">Entry form</a><br />
        <a href="/words/index.phps">main source</a><br />
        <a href="/words/myfeed.phps">RSS 2.0 feed source</a><br />
        <a href="/spell.phps">js (spell) source</a><br />
        <div id="badge">
            Javascript badge:<br />
            &lt;script type="text/javascript" src="http://metaatem.net/spell.php?picsize=$size&string=$string">&lt;/script>
        </div>
        <a href="/myfeed/$string">RSS Feed for these letters</a>
        <br />
        Here is the html of these images for use on other sites (you may need to &lt;img&gt; to [img]):
        <form action="nothing">
            <textarea onfocus="this.select();" name="flickrOut_$ta_rnd" class="flickrOut" id="flickrOut_{$ta_rnd}" cols="100" rows="8">$flickrSource</textarea>
        </form>
        <script type="text/javascript"> 
            <!--
            /* firefox's source view can't see all the letters, but they're there */
            var letters = {
                $js_letters
            };
            -->
        </script>

        <h3>Words from <a href="http://flickr.com/">flick<span class="r">r</span></a></h3>
        <h4>Created by <a href="http://flickr.com/photos/kastner/">kastne<span class="r">r</span></a></h4>
    
HTML;
}
$blog_contents = file_get_contents("latest_post.html");
$body .= <<<HTML

        Any comments or suggestions, please send them to kastner-at-gmail.com. Thanks<br />
        <hr />
        <h3>My latest <a href="http://metaatem.net/">blog</a> entry</h3>
        $blog_contents
        <hr />
        <h3>Some of my other projects</h3>
        <ul>
            <li><a href="http://metaatem.net/">Meta | ateM - my blog</a></li>
            <li><a href="http://www.sixpackbysummer.com/">Six pack by summer</a> - I'm trying to get a six pack by the end of summer or lose $1,500+</li>
            <li><a href="http://metaatem.net/projects/">Other projects</a></li>
        </ul>

        <hr/>

        <p>Spell with flick grabs images from <a href="http://flickr.com/">flickr</a> (the <a href="http://www.flickr.com/groups/oneletter/">One Letter</a> and <a href="http://www.flickr.com/groups/onedigit/">One Digit</a> groups) and uses them to spell what you've typed in.</p>

        <hr/>

        <h3>The story of <a href="http://metaatem.net/words/">Spell with flickr</a><h3>
        <p>
            <a>Krazy Dad (jbum)</a> made a note once using the letters from the <a href="http://www.flickr.com/groups/oneletter/">One Letter group</a> with the <a>Flickr API</a>.
            I decided to try making something similar as a way to learn Flickr's wonderful developer tools. Along the way,
            it got <a>VERY popular</a>. I still feel like it could be better, but it gets the job done. I have made the 
            <a href="/words.phps">source code</a> available for free. It's not coded very well, my first time parsing XML(ish) 
            with PHP(4), and also my first time doing caching of remote resources (funny story, I had a bug in this script 
            that bypassed the cache each time - when I got really popular, flickr felt the strain, and temporarly disabled my API Key). 
            I'm still thrilled with the response it has gotten, and welcome any suggestions or comments. 
            You can reach me on my blog at <a href="http://metaatem.net/">metaatem.net</a>, or email me at
            <a href="mailto:kastner@gmail.com">kastner@gmail.com</a>.
        </p>
        <div>
            <a href="/words.phps">main source code</a><br />
            <a href="/spell.phps">js wrapper (spell) source code</a>
        </div>
        <script type="text/javascript"><!--
        google_ad_client = "pub-5369189596696267";
        google_ad_width = 728;
        google_ad_height = 90;
        google_ad_format = "728x90_as";
        google_ad_type = "text_image";
        //2007-08-09: spell_between
        google_ad_channel = "1412156223";
        google_color_border = "336699";
        google_color_bg = "FFFFFF";
        google_color_link = "0000FF";
        google_color_text = "000000";
        google_color_url = "008000";
        google_ui_features = "rc:6";
        //-->
        </script>
        <script type="text/javascript">
        var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
        document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
        </script>
        <script type="text/javascript">
        var pageTracker = _gat._getTracker("UA-70669-14");
        pageTracker._initData();
        pageTracker._trackPageview();
        </script>
    </body>
</html>

HTML;


header("Content-Type: text/html;\n\tcharset=utf-8");
echo $body;
exit();

?>
