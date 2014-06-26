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
		
	<!-- Bootstrap -->
	<meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<link href="css/bootstrap.min.css" rel="stylesheet">
	
	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="js/bootstrap.min.js"></script>
    
    <!-- For my own CSS -->
    <link rel="stylesheet" href="css/main.css">
    
    <!-- Smooth scrolling -->
    <script src="js/jquery.smooth-scroll.min.js"></script>
    
    <!-- D3 -->
    <script src="js/d3.min.js"></script>
    
    <script>
		// The "ease-in/ease-out" main counter
		$(function()
		{
			var element = $("#theNumber");
			var endNum = parseInt( element.data("number") );
			var timeToTake = 3;
			
			function getCurrentTime ()
			{
				var d = new Date();
				return d.getTime();
			}
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
				element.html( Math.round(numToSet) );
				
				currTime += 1/30;
				
				if (currTime >= timeToTake)
				{
					clearInterval(numAnimation);
				}
			}, 1000/30); // Screen refreshes only 30 times per second
			
			element.html( endNum );
			
			return;
		});
		
		// Calculate text sizes based on window size
		$(function()
		{			
			function fitText()
			{
				$("#theNumber").css({
					"font-size": (0.5 * $(window).height()) + "px"
				});
				
				$("#viewCountCompare div[class*='col-']").css({
					"font-size": (0.04 * $(window).height()) + "px"
				});
			}
			
			$(window).resize(fitText);
			fitText();
		});
		
		// Hide/show "back to top" button appropriately
		$(function()
		{
			$(document).scroll(function ()
			{
				var winHeight = $(window).height();
				var scrollPos = $(document).scrollTop();
				
				if (scrollPos < winHeight)
				{
					$("#backButton").css({"opacity": scrollPos/winHeight })
				}
				else
				{
					$("#backButton").css({"opacity": 1 })
				}
			});
			
		});
		
		// Set up smooth scrolling
		$(function()
		{
			$('a').smoothScroll();
			$("#backButton").click(function ()
			{
				$.smoothScroll({scrollTarget: '#'});
				return false;
			});
		});
		
		// For the "Grey, don't click here!" button
		$(function()
		{
			$("#bradyTotalReveal").click(function revealThings ()
			{
				$("#bradyTotalReveal").fadeOut();
				setTimeout(function ()
				{
					$(".hidden-to-grey").fadeIn();
				}, 400);
			});
		});
	</script>

</head>
<body class="container-fluid">
<div class="jumbotron mainSection" id="mainCounterJumbotron">
<h3 class="row">How many videos has Brady Haran released since C.G.P. Grey last released a video?</h3>
<h1 class="row" id="theNumber" data-number="<?php
	$grey_vid = sqlQuery("SELECT * FROM Video WHERE creator='C.G.P. Grey' ORDER BY uploaddate DESC LIMIT 1")[0];
	if (!$grey_vid) // The database is urgently in need of an update.
	{
		update_with_api();
		$grey_vid = sqlQuery("SELECT * FROM Video WHERE creator='C.G.P. Grey' ORDER BY uploaddate DESC LIMIT 1")[0];
	}	
	$brady_vids = sqlQuery("SELECT * FROM Video WHERE creator='Brady Haran' AND uploaddate > $1 ORDER BY uploaddate DESC", array($grey_vid["uploaddate"]));
	
	echo count($brady_vids);
?>"><noscript><?php echo count($brady_vids); ?></noscript></h1>
<h2 class="row">
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
	
			$classForShading = $isEven ? 'tableRowEven' : 'tableRowOdd';
			
			echo "<div class='row $classForShading'>
			<div class='col-sm-2 col-sm-3'>$channel</div>
			<div class='col-sm-3 col-sm-4 col-xs-8'>$uploaded</div>
			<div class='col-md-1 col-sm-2 col-xs-4 right-align'>$views</div>
			<div class='col-md-6 col-sm-5'><a href='$url'>$title</a></div>
			</div>";
		}
	?>

<div class="mainSection" id="videoList">
<div class="row">
	<h2 class="row col-sm-10 col-xs-6"> 
		Grey's video
	</h2>
	<div class="col-sm-2 col-xs-6"></div>
</div>
	<div class="row table-head">
		<div class="col-sm-2 col-sm-3">Channel</div>
		<div class="col-sm-3 col-sm-4 col-xs-8">Published</div>
		<div class="col-md-1 col-sm-2 col-xs-4 right-align">Views</div>
		<div class="col-md-6 col-sm-5">Title/Link</div>
	</div>	
	<?php echoVidRow($grey_vid, true); ?>

	<h2 class="row">
		Brady's <?php
		if (count($brady_vids) == 0) echo "lack of videos";
		else if (count($brady_vids) == 1) echo "video";
		else echo "videos";
		?>
	</h2>
	
	<div class="row table-head">
		<div class="col-sm-2 col-sm-3">Channel</div>
		<div class="col-sm-3 col-sm-4 col-xs-8">Published</div>
		<div class="col-md-1 col-sm-2 col-xs-4 right-align">Views</div>
		<div class="col-md-6 col-sm-5">Title/Link</div>
	</div>
	
	<?php
		for ($i = 0; $i<count($brady_vids); $i++)
		{
			echoVidRow($brady_vids[$i], ($i % 2 == 0));
		}
	?>
</div>

<div class="mainSection" id="viewCountCompare">
<div class="row">
	<h1 class="row col-sm-10 col-xs-6"> How do their view counts compare?</h1>
	<div class="col-sm-2 col-xs-6"></div>
</div>

<div class="row tableRowEven">
	<div class="col-xs-6">Grey</div>
	<div class="col-xs-6 right-align"><?php $grey_views = $grey_vid['viewcount']; echo $grey_views; ?></div>
</div>
<div class="row tableRowOdd">
	<div class="col-xs-6">Brady: Average</div>
	<div class="col-xs-6 right-align">
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
		?>
	</div>
</div>
<div class="row hidden-to-grey tableRowEven" <?php if ($brady_total >= $grey_views) echo "hidden"; ?> >
	<div class="col-xs-6">Brady: Total</div>
	<div class="col-xs-6 right-align"><?php echo $brady_total; ?></div>
</div>
<div class="row" id="hidden-stuff-toggle" <?php if ($brady_total < $grey_views) echo "hidden"; ?> >
	<div class="col-xs-12 center-align">
		<button id="bradyTotalReveal" class="btn btn-danger btn-lg">Grey, don't click here!</button>
	</div>
</div>

</div>


<div id="appInfo" class="mainSection">
<div class="row">
	<h1 class="row col-sm-10 col-xs-6">App info</h1>
	<div class="col-sm-2 col-xs-6"></div>
</div>

<h3 class="row">Last Updated</h3>
<h4 class="row">
	<?php
		$lastUpdate = sqlQuery("SELECT * FROM UpdateLog ORDER BY updatedatetime DESC LIMIT 1")[0]['updatedatetime']; echo $lastUpdate;
	?> UTC
</h4>

<h3 class="row">Powered by YouTube Data API (v2)</h3>
<h3 class="row">
<a href="http://github.com/nicktendo64/brady-vs-grey-php">View the source code on GitHub.</a>
</h3>

<!-- NOTES HERE -->
<h3 class="row">Notes</h3>
<h5 class="row">To conserve processer and network resources, this app should only update four times per day.</h5>

</div>

<a id="backButton" class="col-sm-2 col-xs-6 btn btn-primary btn-lg" href="#">Return to top.</a>
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