<?php
require_once "sql_functions.php";
require_once "update.php";

// Specify these file names; they may change.
$cacheFile = "cache-simple.html";
$notesFile = "notes.html";

// Check if the cached file is still fresh. If it is, serve it up and exit.
if (file_exists($cacheFile))
{
	include($cacheFile);
	
	exit();
}
error_log("No cache file found; generating page...");


ob_start();
function fetchData()
{
	global $greyVid, $bradyVids, $lastUpdate;

	$greyVid = sqlQuery("SELECT * FROM Video WHERE creator='C.G.P. Grey' ORDER BY uploaddate DESC LIMIT 1")[0];
	$bradyVids = sqlQuery("SELECT * FROM Video WHERE creator='Brady Haran' AND uploaddate > $1 ORDER BY uploaddate DESC", array($greyVid["uploaddate"]));
	$lastUpdate = sqlQuery("SELECT * FROM UpdateLog ORDER BY updatedatetime DESC LIMIT 1")[0]['updatedatetime'];
}
fetchData();
if (!$greyVid || !$lastUpdate) // The database is urgently in need of an update...
{
	update_with_api();
	fetchData(); // Use the newly-found data
}	

function echoVidRow($vid)
{
	$creator = $vid['creator'];
	$channel = $vid['channel'];
	$uploaded = $vid['uploaddate'];
	$uploaded = strftime('%B %d, %Y @ %I:%M %p', strtotime($uploaded) );

	$views = $vid['viewcount'];
	$title = $vid['title'];
	$url = "http://youtube.com/watch?v=" . $vid['youtubeid'];

	if ($views == 301)
	{
		$views = '<a href="http://youtube.com/watch?v=oIkhgagvrjI">301</a>'; // EASTER EGG!
	}
	elseif ($views == -1)
	{
		$views = "&lt;live video&gt;"; // More helpful message for live video
	}
	elseif ($views == -2)
	{
		$views = "&lt;not yet calculated&gt;"; // This should never happen.
	}
	
	elseif ($views == -3)
	{
		$views = "&lt;error&gt;"; // This should REALLY never happen
	}
	
	echo "<tr>
	<td>$creator</td>
	<td>$channel</td>
	<td>$uploaded</td>
	<td>$views</td>
	<td><a href='$url'>$title</a></td>
	</tr>";
}

$greyViews = $greyVid['viewcount'];

$bradyTotal = 0;
foreach ($bradyVids as $vid)
{
	$bradyTotal = $bradyTotal + $vid['viewcount'];
}

if (count($bradyVids) == 0)
{
	$bradyAvg = "N/A";
}
else
{
	$bradyAvg = round( $bradyTotal / count($bradyVids) );
}
?>

<!DOCTYPE html>

<html>
<head>
	<title>Brady vs. Grey</title>
	<script>
		function revealThings ()
		{
			for (var i in document.getElementsByClassName('hidden-to-grey'))
			{
				document.getElementsByClassName('hidden-to-grey')[i].hidden = false;
			}
			var button = document.getElementById('hidden-stuff-toggle');
			button.parentNode.removeChild(button);	
		}
	</script>
	
	<script>
      (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
      (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
      m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
      })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
    
      ga('create', 'UA-50387911-1', 'brady-vs-grey.appspot.com');
      ga('send', 'pageview');
	</script>
</head>
<body>
<font size="5">Q: How many videos has Brady Haran released since C.G.P. Grey last released a video?<br />
A:
</font>
<span style="font-size: 100px;"><?php echo count($bradyVids); ?></span>
<br /><hr /><br />
<table cellpadding="5" cellspacing="5">
	<thead>
		<th>
			Creator
		</th>
		<th>
			Channel
		</th>
		<th>
			Uploaded
		</th>
		<th>
			View Count
		</th>
		<th>
			Title/Link
		</th>
	</thead>
	<?php
		echoVidRow($greyVid);
		foreach ($bradyVids as $vid)
		{
			echoVidRow($vid);
		}
	?>
</table>
<hr>
<font size="5">Q: How do their view counts compare?<br />A:</font>

<table cellpadding="3" cellspacing="3">
	<tr>
		<td>Grey</td>
		<td align="right"><?php echo $greyViews; ?></td>
	</tr>
	<tr>
		<td>Brady: Average</td>
		<td align="right"><?php echo $bradyAvg; ?></td>
	</tr>
	<tr id="hidden-stuff-toggle" <?php echo ($bradyTotal < $greyViews? "hidden": ""); ?> >
		<td colspan="2">
			<button onclick="revealThings();">Grey, don't click here!</button>
		</td>
	</tr>
	<tr class="hidden-to-grey" <?php echo ($bradyTotal >= $greyViews ? "hidden": ""); ?> >
		<td>Brady: Total</td>
		<td align="right"><?php echo $bradyTotal; ?></td>
	</tr>
</table>

<hr />
Last updated: <?php echo $lastUpdate; ?>.
Powered by YouTube Data API (v3).
<hr />
<h3>Notes:</h3>
<?php include($notesFile); ?>
</body>
</html>

<?php
// Send the output to the user
flush();

// Save the cache file
$cacheFile = fopen($cacheFile, 'w');
fwrite($cacheFile, ob_get_contents());
fclose($cacheFile);
error_log("New cache file created.");
?>
