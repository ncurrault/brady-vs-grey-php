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
	<?php
		function sql_query($q)
		{
			$sql_connection = mysqli_connect($host="localhost", "root", "root", "my_db", 8889);
			if (mysqli_connect_errno())
			{
				$n = mysqli_connect_errno();
				echo "Error with database (error number $n)";
				return null;
			}
			else
			{
				$q_result = mysqli_query($sql_connection,$q);
				$q_len = $q_result->num_rows;
				
				$ret = array();
				
				for ($i=0; $i<$q_len; $i++)
				{
					$row = mysqli_fetch_array($q_result);
					array_push($ret, $row);
				}
				
				mysqli_close($sql_connection);
				
				return $ret;
			}
		}
	?>
</head>
<body>
<font size="5">Q: How many videos has Brady Haran released since C.G.P. Grey last released a video?<br />
A:
</font>
<span style="font-size: 100px;">
<?php
	$grey_vid = sql_query("SELECT * FROM Video WHERE Creator='C.G.P. Grey' ORDER BY UploadDate DESC LIMIT 1")[0];
	$brady_vids = sql_query("SELECT * FROM Video WHERE Creator='Brady Haran' AND UploadDate > '" . $grey_vid["UploadDate"] . "' ORDER BY UploadDate DESC");
	
	echo count($brady_vids);
?></span>
<br /><hr /><br />
<table cellpadding="5" cellspacing="5">
	<tr>
		<td><strong>
			Creator
		</strong></td>
		<td><strong>
			Channel
		</strong></td>
		<td><strong>
			Uploaded
		</strong></td>
		<td><strong>
			View Count
		</strong></td>
		<td><strong>
			Title/Link
		</strong></td>
	</tr>
	<?php 
	
		foreach (array_merge(array($grey_vid), $brady_vids) as $vid)
		{
			$creator = $vid['Creator'];
			$channel = $vid['Channel'];
			$uploaded = $vid['UploadDate'];
			$uploaded = strftime('%B %d, %Y @ %I:%M %p', strtotime($uploaded) );
			
			$views = $vid['ViewCount'];
			$title = $vid['Title'];
			$url = "http://youtu.be/" . $vid['YouTubeID'];
			
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
	
			
			echo "<tr>
			<td>$creator</td>
			<td>$channel</td>
			<td>$uploaded</td>
			<td>$views</td>
			<td><a href=\"$url\">$title</a></td>
			</tr>";
		}
	?>
</table>
<hr>
<font size="5">Q: How do their view counts compare?<br />A:</font>

<table cellpadding="3" cellspacing="3">
	<tr>
		<td>Grey</td>
		<td align="right"><?php $grey_views = $grey_vid['ViewCount']; echo $grey_views; ?></td>
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
					$brady_total = $brady_total + $vid['ViewCount'];
				}
				
				echo round( $brady_total / count($brady_vids) );
			}
		?></td>
	</tr>
	<tr id="hidden-stuff-toggle" <?php if ($brady_total < $grey_views) echo "hidden"; ?> >
		<td colspan="2">
			<button onclick="revealThings();">Grey, don't click here!</button>
		</td>
	</tr>
	<tr class="hidden-to-grey" <?php if ($brady_total >= $grey_views) echo "hidden"; ?> >
		<td>Brady: Total</td>
		<td align="right"><?php echo $brady_total; ?></td>
	</tr>
</table>

<hr />
Last updated: <?php echo sql_query("SELECT * FROM UpdateLog ORDER BY UpdateDatetime DESC LIMIT 1")[0]['UpdateDatetime']; ?>.
Powered by YouTube Data API (v3).
<hr />
<a href="http://github.com/nicktendo64/brady-vs-grey">View the source code on GitHub.</a>
<br />
<iframe style="width: 100%; border: 0;" src="https://dl.dropboxusercontent.com/u/23230235/For%20Other%20Websites/brady_vs_grey_messages.html"></iframe>
</body>
</html>