<?php include("../res/htmlhead.inc"); include("../res/banner.inc");?>
<?php

// -----------------------------[ Settings ]-------------------------------------
define("DISKPATH",	"/path/to/files/on/disk/");
define("WEBPATH",	"/path/to/files/in/web");
define("DATEFORMAT",    "d-m-y H:i:s");
define("GALLERYTITLE",  "File Viewer");
define("BINARYSIZE",	true);
define("DUMMY_IMAGE", "http://dummyimage.com/400x125");
$BLACKLIST		= array("..", "."); // silly PHP doesn't support vector constants.
date_default_timezone_set('UTC');




// -----------------------------[ Path Loading]-------------------------------------
// Load the path from the var.
if(array_key_exists('p', $_GET))
    $path = $_GET['p']; 
else
    $path = '';
//$path = "./";
//$path = realpath(blah);
if ("/" !== substr($path, -1)) {
    $path = $path . "/";
}
// Sanitise input to prevent ../../../ etc
# TODO: bake the regex for speedliness
$path = preg_replace('/\/+/', '/', preg_replace('/\.\.\.*\//', '/', $path));



// -----------------------------[ UI Functions ]-------------------------------------
// Format filesize as human.
function format_human_size($size, $bin) {
    $k = 1000;
    $units = array('&nbsp;B', 'KB', 'MB', 'GB', 'TB');
    if($bin){
	$units = array('&nbsp;&nbsp;B', 'KiB', 'MiB', 'GiB', 'TiB');
	$k = 1024;
    }
    for ($i = 0; $size >= $k && $i < 4; $i++) $size /= $k;
    return round($size, 2).'&nbsp;'.$units[$i];
}

// Escape HTML entities.
function h($str){
    return htmlentities($str);
}


// -----------------------------[ Path Functions ]-------------------------------------
// Used to blacklist things using array_uintersect.
function apply_blacklist($item){
    global $BLACKLIST;
    return !in_array($item, $BLACKLIST);
}

// Provide the real path to the file
function rp($path){
    return DISKPATH . $path;
}

// Provide the web path to the file.
function wp($path){
    return WEBPATH . $path;
}


// -----------------------------[ Listings ]-------------------------------------
// Load a dir listing
$listing	= array_filter(scandir(rp($path)), "apply_blacklist");
$dirlisting	= array();
$filelisting	= array();
foreach($listing as $item){
    if(is_dir(rp($path.$item)))
	array_push($dirlisting, $item);
    else
	array_push($filelisting, $item);
}

// Sort output alphabetically (asc)
sort($dirlisting);
sort($filelisting);

?>

<!-- Styling for misc auxiliary features -->
<style type="text/css">
    #gallerypic{
        max-width: 95%;
    }

    #gallerytxt{
    }

    .jsenable{
        display: none;
    }

    .showlink{
        text-decoration: none;
        font-size: smaller;
    }
</style>

<!-- Handle gallery, history management -->
<script type="text/javascript" language="javascript">
    /* Set the gallery title */
    function setGalleryTitle(title){
        ttl.textContent = "<?=GALLERYTITLE?> (" + title + ")"; 
    }

    function resetGallery(){
        ttl.textContent = "<?=GALLERYTITLE?>"; 
    }

    function setTitle(title){
        // FIXME
        //if(document.getElementsByTagName("h2").length>0)
        //document.title = document.title + ' - ' + document.getElementsByTagName("h2").item(0).textContent + " - previewing " + title;
    }

    /* Display an image in the gallery */
    function displayImage(title, src){
        iel.src = src;
        iel.alt = title;
        tel.style.display = "none";
        iel.style.display = "block";
    }



    /* Download and show text in the gallery */
    function displayText(title, src){
        var http = (navigator.appName == "Microsoft Internet Explorer") ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
        http.open("GET", src, true);
        http.onreadystatechange=function() {
            if(http.readyState == 4) {
                tel.textContent = http.responseText;
                iel.style.display = "none";
                tel.style.display = "block";
            }
        }
        http.send(null);
    }

    // load an image from a link
    // accomplishes all the ui "load" expectations.
    function goImage(title, src){
        displayImage(title, src);
        pushHistory(title, src);
        setTimeout("document.getElementById('galleryDiv').scrollIntoView(true);", 50); // FIXME: this delay is a hack to let images load.
    }

    // load a text file from a link
    // accomplishes all the ui "load" expectations.
    function goText(title, src){
        displayText(title, src);
        pushHistory(title, src);
        setTimeout("document.getElementById('galleryDiv').scrollIntoView(true);", 50);// FIXME: this delay is a hack to let AJAX load.
    }

    // Adds a history entry, with state information.
    function pushHistory(title, src){
        setGalleryTitle(title);
        setTitle(title);

        // Record things
        state = {	
             src    : src,
             title  : title,
             iel    : (iel.style.display == "block"),
             tel    : (tel.style.display == "block")
        }

        history.pushState(state, title, document.location);
    }

    window.onpopstate = function(ev){
        //alert("location: " + document.location + ", state: " + JSON.stringify(ev.state));
        resetGallery();
        iel.style.display = "none";
        tel.style.display = "none";

        if(ev.state){
            if(ev.state.iel)
                displayImage(ev.state.title, ev.state.src);
            else if(ev.state.tel)
                displayText(ev.state.title, ev.state.src);
        }
    };
</script>

<h2>Misc Files - <?=h($path)?></h2>
<h3>File Listing for <?=h($path)?></h3>
<table class="monospace">
    <thead>
        <tr>
            <th>Filename</th>
            <th>Mtime</th>
            <th>Size</th>
            <th>Type</th>
        </tr>
    </thead>
    <tbody>
<?php if(preg_match('/^.?\/[^.^\/]+\//', $path)){ 
    $dirs = preg_split('/\/+/', $path);
    if(count($dirs) > 0){
?>
    <tr>
	<td><a href="?p=<?=implode('/', array_slice($dirs, 0, -2))?>/">Up (../)</a></td>
	<td></td>
	<td></td>
	<td></td>
    </tr>
<?php } } ?>
<?php foreach($dirlisting as $item){ ?>
    <tr>
	<td><a href="?p=<?=$path.$item?>"><?=h($item)?></a></td>
	<td></td>
	<td></td>
	<td>Directory</td>
    </tr>
<?php } ?>

<?php foreach( $filelisting as $item){ 
    $mime = mime_content_type(rp($path.$item))
?>
    <tr>
	<td><a href="<?=wp($path.$item)?>"><?=h($item)?></a><?php
        if(preg_match('/image\/.+/', $mime)) { ?><a class="showlink jsenable" href="javascript:goImage('<?=h($item)?>', '<?=wp($path.$item)?>');">(preview)</a><?php } 
        if(preg_match('/text\/.+/', $mime)) { ?><a class="showlink jsenable" href="javascript:goText('<?=h($item)?>', '<?=wp($path.$item)?>');">(preview)</a><?php } 
    ?></td>
	<td><?=date(DATEFORMAT, filemtime(rp($path.$item)))?></td>
	<td class="alright"><?=format_human_size(filesize(rp($path.$item)), BINARYSIZE)?></td>
	<td><?=$mime?></td>
    </tr>
<?php } ?>
    </tbody>
</table>

<!-- JE-enabled Image Gallery -->
<div id="galleryDiv" class="jsenable"><h3 id="galleryTitle"></h3>
    <img src="<?=DUMMY_IMAGE?>" alt="Sample pic." class="jsenable" id="gallerypic" onload="this.scrollIntoView();"/>
    <pre id="gallerytxt" class="jsenable">Sample text.</pre>
</div>

<!-- Script to enable gallery if JS is available -->
<script type="text/javascript" language="javascript">
    // Find some handy elements for later.
    var iel = document.getElementById('gallerypic');
    var tel = document.getElementById('gallerytxt');
    var ttl = document.getElementById('galleryTitle');

    // Enable the JS DOM items.
    window.onload=function(){
	if(init){init();} // Run the global init function we displaced
	
	// Load links and check for the jsenable class
	var links = document.getElementsByTagName('a');
	var jslinkcount = 0;
	for(var i=0; i<links.length; i++){
	    if(links[i].className.split(" ").indexOf("jsenable") != -1){
		jslinkcount ++ ;
		links[i].style.display = "inline";
	    }
	}


	// If there is anything to display, tantalise the user...
	if(jslinkcount > 0)
	    document.getElementById('galleryDiv').style.display = "block";

	resetGallery();
    }
</script>

</body></html>
