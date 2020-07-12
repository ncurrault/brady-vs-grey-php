<?php
require_once "load_vids.php";

// Specify these file names; they may change.
$cacheFile = "cache-classic.html";
$notesFile = "notes.html";

$cachetime = 21600; // 6 hours
if (file_exists($cacheFile) && time() - $cachetime < filemtime($cacheFile))
{
	include($cacheFile);

	exit();
}
touch($cacheFile); // prevent almost-simultaneous requests from triggering 2 updates
error_log("No cache file found; generating page...");

ob_start();
$vidData = getVidData();
$greyVid = $vidData['greyVid'];
$bradyVids = $vidData['bradyVids'];
$lastUpdate = strftime("%F %T");

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
	<title>Brady vs. Grey | Classic</title>
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
</head>
<body>
<?php include_once("analyticstracking.php") ?>
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
