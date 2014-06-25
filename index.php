<!DOCTYPE html>

<html>
<head>
	<title>Brady vs. Grey</title>
	<script>
		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','//www.google-analytics.com/analytics.js','ga');
		
		ga('create', 'UA-50387911-2', 'brady-vs-grey.herokuapp.com');
		ga('send', 'pageview');
	</script>
	<?php
		require_once "sql_functions.php";
		require_once "update.php";
	?>
	
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
		
	<!-- Bootstrap -->
	<meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="css/bootstrap.min.css" rel="stylesheet">
	
	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="js/bootstrap.min.js"></script>
    
    <!-- For what I don't like -->
    <link rel="stylesheet" href="main.css">
    
    <script>
		function getCurrentTime ()
		{
			var d = new Date();
			return d.getTime();
		}
		
		function countUpTo(elementID, timeToTake)
		{
			var element = document.getElementById(elementID);
			var endNum = parseInt( element.dataset.number );
			
			
			function getNumForTime(n)
			{
				if (n >= timeToTake)
				{
					return endNum;
				}
				else if (n <= 0)
				{
					return 0;
				}
				else
				{
					return endNum * (0.5 - 0.5 * Math.cos((Math.PI / timeToTake) * n)); // (n/timeToTake)*endNum;
				}
			}
			
			var currTime = 0;
			var numToSet;
			
			var numAnimation = setInterval(function ()
			{
				numToSet = getNumForTime(currTime);
				element.innerHTML = Math.round(numToSet);
				
				currTime += 1/30;
				
				if (currTime >= timeToTake)
				{
					clearInterval(numAnimation);
				}
			}, 1000/30)
			
			element.innerHTML = endNum;
			
			return;
		}
		$( document ).ready(function() { countUpTo("theNumber", 4);});
	</script>

</head>
<body>
<div class="jumbotron mainSection" id="mainCounterJumbotron">
<h3>How many videos has Brady Haran released since C.G.P. Grey last released a video?</h3>
<h1 id="theNumber" data-number="
<?php
	$grey_vid = sqlQuery("SELECT * FROM Video WHERE creator='C.G.P. Grey' ORDER BY uploaddate DESC LIMIT 1")[0];
	if (!$grey_vid) // The database is urgently in need of an update.
	{
		update_with_api();
		$grey_vid = sqlQuery("SELECT * FROM Video WHERE creator='C.G.P. Grey' ORDER BY uploaddate DESC LIMIT 1")[0];
	}	
	$brady_vids = sqlQuery("SELECT * FROM Video WHERE creator='Brady Haran' AND uploaddate > $1 ORDER BY uploaddate DESC", array($grey_vid["uploaddate"]));
	
	echo count($brady_vids);
?>"><noscript><?php echo count($brady_vids); ?></noscript></h1>
<h2>
	<a class="btn btn-success btn-lg" href="#videoList">List the videos.</a>
	<a class="btn btn-warning btn-lg" href="#viewCountCompare">Compare the view counts.</a>
	<a class="btn btn-danger btn-lg" href="#appInfo">View the app's info.</a>
</h2>
</div>

	<?php
		function echoVidRow($vid, $isEven)
		{
			$creator = $vid['creator'];
			$channel = $vid['channel'];
			$uploaded = $vid['uploaddate'];
			$uploaded = strftime('%B %d, %Y @ %I:%M %p', strtotime($uploaded) );
			
			$views = $vid['viewcount'];
			$title = $vid['title'];
			$url = "http://youtu.be/" . $vid['youtubeid'];
			
			if ($views == 301)
			{
				$views = '<a href="http://youtu.be/oIkhgagvrjI">301</a>'; // EASTER EGG!
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
	
			$rowClass = $isEven ? 'tableRowEven' : 'tableRowOdd';
			
			echo "<tr class=\"$rowClass\">
			<td>$channel</td>
			<td>$uploaded</td>
			<td style=\"text-align: right;\">$views</td>
			<td><a href=\"$url\">$title</a></td>
			</tr>";
		}
	?>

<div class="mainSection" id="videoList">
	<h2>
		Grey's video
	</h2>
<table>
	<thead>
		<th>Channel</th>
		<th>Published</th>
		<th>View Count</th>
		<th>Title/Link</th>
	</thead>
	
	<?php echoVidRow($grey_vid, true); ?>
</table>
<h2>
	Brady's <?php
	if (count($brady_vids) == 0) echo "lack of videos";
	else if (count($brady_vids) == 1) echo "video";
	else echo "videos";
	?>
	
</h2>
<table>
	<thead>
		<th>Channel</th>
		<th>Published</th>
		<th>View Count</th>
		<th>Title/Link</th>
	</thead>
	
	<?php
		for ($i = 0; $i<count($brady_vids); $i++)
		{
			echoVidRow($brady_vids[$i], ($i % 2 == 0));
		}
	?>
</table>
</div>

<div class="mainSection" id="viewCountCompare">
<h1>How do their view counts compare?</h1>

<table>
	<tr>
		<td>Grey</td>
		<td align="right"><?php $grey_views = $grey_vid['viewcount']; echo $grey_views; ?></td>
	</tr>
	<tr>
		<td>Brady: Average</td>
		<td align="right">
		<?php
			if (count($brady_vids) == 0)
			{
				echo "N/A";
			}
			else
			{
				$brady_total = 0;
				foreach ($brady_vids as $vid)
				{
					$brady_total = $brady_total + $vid['viewcount'];
				}
				
				echo round( $brady_total / count($brady_vids) );
			}
		?></td>
	</tr>
	<tr id="hidden-stuff-toggle" <?php if ($brady_total < $grey_views) echo "hidden"; ?> >
		<td colspan="2">
			<button class="btn btn-danger btn-lg" onclick="revealThings();">Grey, don't click here!</button>
		</td>
	</tr>
	<tr class="hidden-to-grey" <?php if ($brady_total >= $grey_views) echo "hidden"; ?> >
		<td>Brady: Total</td>
		<td align="right"><?php echo $brady_total; ?></td>
	</tr>
</table>

</div>


<div id="appInfo" class="mainSection">
<h1>App info</h1>
<h3>Last Updated</h3>
<h4>
	<?php
		$lastUpdate = sqlQuery("SELECT * FROM UpdateLog ORDER BY updatedatetime DESC LIMIT 1")[0]['updatedatetime']; echo $lastUpdate;
	?> UTC
</h4>

<h3>Powered by YouTube Data API (v2)</h3>
<h3>
<a href="http://github.com/nicktendo64/brady-vs-grey">View the source code on GitHub.</a>
</h3>

<iframe style="width: 100%; border: 0;" src="https://dl.dropboxusercontent.com/u/23230235/For%20Other%20Websites/brady_vs_grey_php_messages.html"></iframe>
</div>

<a id="backButton" class="btn btn-primary btn-lg" href="#">Return to top.</a>
</body>
</html>

<?php
flush(); // Send the output to the user (this will prevent the long load time.)

$now = time();
$lastUpdate = strtotime($lastUpdate);

if (($now - $lastUpdate) >= 21600 || ($now % 86400 == 0)) // Update when the time is midnight or it's been >= 6 hours since the last update.
{
	update_with_api();	
}
?>