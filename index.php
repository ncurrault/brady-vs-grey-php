<?php
require_once "sql_functions.php";
require_once "update.php";


function updateIfNecessary()
{
	$now = time();
	$lastUpdate = strtotime(sqlQuery("SELECT * FROM UpdateLog ORDER BY updatedatetime DESC LIMIT 1")[0]['updatedatetime']);

	if (!$lastUpdate || ($now - $lastUpdate) >= 21600) // Update when it's been >= 6 hours since the last update.
	{
		error_log("Update is necessary.  Updating...");
		update_with_api();
	}
}

// Check if the cached file is still fresh. If it is, serve it up and exit.
if (file_exists("cache_index.html"))
{
	error_log("Cache file found. Redirecting...");
	header("Location: /cache_index.html", true, 302);

	// Close the connection (http://www.php.net/manual/en/features.connection-handling.php#71172)
	error_log("Closing connection");
	ob_end_clean();
	header("Connection: close");
	ignore_user_abort(true); // just to be safe
	ob_start();
	$size = ob_get_length();
	header("Content-Length: $size");
	ob_end_flush(); // Strange behaviour, will not work
	flush(); // Unless both are called !
	
	updateIfNecessary();
	sleep(30);
	
	exit();
}

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
		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
			(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
			m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

		ga('create', 'UA-50387911-2', 'brady-vs-grey.herokuapp.com');
		ga('send', 'pageview');
		</script>
		
		<!-- Bootstrap -->
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
		<link href="css/bootstrap.min.css" rel="stylesheet">
		
		<!-- jQuery -->
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>

		<!-- Bootstrap -->
		<script src="js/bootstrap.min.js"></script>

		<!-- For my own CSS -->
		<link rel="stylesheet" href="css/main.css">

		<!-- Smooth scrolling -->
		<script src="js/jquery.smooth-scroll.min.js"></script>

		<!-- D3 -->
		<script src="js/d3.min.js"></script>

		<!-- For easing functions -->
		<script src="js/jquery-ui-1.10.4.custom.min.js"></script>

		<script>
			// Used in multiple functions
			function getCurrentTime()
			{
				var d = new Date();
				return d.getTime();
			}
			
			// The "ease-in/ease-out" main counter
			$(function ()
			{
				
				$("#theNumber").animate(
				{
					foo: parseInt( $("#theNumber").data("number") )
				},
				{
					"step": function(val)
					{
						$("#theNumber").html( Math.round(val) );
					},
					"duration": 3000,
					"easing": "easeInOutSine"
				}
				);
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
			
			// Hide/show back-to-top button appropriately
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
			
			// Draw graph
			$(function()
			{			
				var grey = parseInt($("#viewCountChart").data("grey"));
				var bradyAvg = parseInt($("#viewCountChart").data("brady-avg"));
				var bradyTotal = parseInt($("#viewCountChart").data("brady-total"));
				
				// Graph setup
				var data = [grey, bradyAvg, bradyTotal];
				
				var whereToWrite = {
					greyViews: grey,
					bradyAvg: bradyAvg,
					bradyTotal: bradyTotal
				};
				
				var x = d3.scale.linear()
				.domain([0, d3.max(data)])
				.range([0, $("#viewCountChart").width()]);
				
				d3.select("#viewCountChart").selectAll("div")
				.data(data)
				.enter().append("div")
				.attr({
					"data-width": function(d) { return x(d) + "px"; },
					"data-value": function(d) { return d; },
					"data-writespot": function(d)
					{
						for (e in whereToWrite)
						{
							if (d == whereToWrite[e]) return "#"+e;
						}
					}
				})
				.property("hidden", function (d) { return whereToWrite.bradyTotal == d })
				.classed("hidden-to-grey", function (d) { return whereToWrite.bradyTotal == d })
				.style({
					"height": (0.15 * $(window).height() ).toString() + "px",
				});
				
				function animateGraph()
				{
					$("#viewCountChart div").each(function ()
					{
						$(this).animate(
							{"width": $(this).data("width")},
							{
								"duration": 1000,
								"easing": "easeOutSine",
								"step":function ()
								{
									$($(this).data("writespot")).html(
										Math.round(
											( $(this).width()/parseFloat($(this).data("width")) ) * parseInt($(this).data("value"))
											)
										);
									if ($(this).hasClass("hidden-to-grey") && $("#bradyTotalReveal").css("display") == "none")
									{
										$(this).css("display","block");
									}
								},
								"complete": function ()
								{
									$($(this).data("writespot")).html($(this).data("value"));
								}
							}
							);
					});
				}
				
				var graphAnimated = false;
				
				$(document).scroll(function ()
				{
					var fullGraphVisible = (
						$(document).scrollTop()
						<=
						$("#viewCountChart").position().top
						&&
						$("#viewCountChart").position().top + $("#viewCountChart").innerHeight()
						<=
						1.03 * ($(document).scrollTop() + $(window).outerHeight())
						);
					
					if (fullGraphVisible && !graphAnimated)
					{
						setTimeout(animateGraph, 100);
						$("#greyViews, #bradyAvg, #bradyTotal").show();
						graphAnimated = true;
					}
					else if (!fullGraphVisible)
					{
						$("#viewCountChart div").css({"width":"3px"}).stop();
						$("#greyViews, #bradyAvg, #bradyTotal").hide();
						graphAnimated = false;
					}
				});
			});

			// For the "Grey, don't click here!" button
			$(function()
			{
				$("#bradyTotalReveal").click(function ()
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
			<h3 class="row">
				How many videos has Brady Haran released since C.G.P. Grey last released a video?
			</h3>
			<h1 class="row" id="theNumber" data-number="<?php echo count($bradyVids); ?>">
				<noscript><?php echo count($bradyVids); ?></noscript>
			</h1>
			<h2 class="row">
				<a class="btn btn-success btn-lg" href="#videoList">List the videos.</a>
				<a class="btn btn-warning btn-lg" href="#viewCountCompare">Compare the view counts.</a>
				<a class="btn btn-danger btn-lg" href="#appInfo">View the app's info.</a>
			</h2>
		</div>

		<div class="mainSection" id="videoList">
			<div class="row">
				<h2 class="row col-sm-10 col-xs-7"> 
					Grey's video
				</h2>
				<div class="col-sm-2 col-xs-5"></div>
			</div>
			
			<div class="row table-head">
				<div class="col-sm-2 col-sm-3">Channel</div>
				<div class="col-sm-3 col-sm-4 col-xs-8">Published</div>
				<div class="col-md-1 col-sm-2 col-xs-4 right-align">Views</div>
				<div class="col-md-6 col-sm-5">Title/Link</div>
			</div>	
			<?php echoVidRow($greyVid, true); ?>


			<h2 class="row">
				Brady's
				<?php
				if (count($bradyVids) == 0) echo "lack of videos";
				else if (count($bradyVids) == 1) echo "video";
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
			for ($i = 0; $i<count($bradyVids); $i++)
			{
				echoVidRow($bradyVids[$i], ($i % 2 == 0));
			}
			?>
		</div>

		<div class="mainSection" id="viewCountCompare">
			<div class="row">
				<h1 class="row col-sm-10 col-xs-7">
					How do their view counts compare?
				</h1>
				<div class="col-sm-2 col-xs-5"></div> <!-- To make space for the back-to-top button -->
			</div>

			<div class="row">
				<div class="col-xs-6">
					Grey
				</div>
				<div id="greyViews" class="col-xs-6 right-align">
					<noscript>
						<?php echo $greyViews; ?>
					</noscript>
				</div>
			</div>

			<div class="row">
				<div class="col-xs-6">
					Brady: Average
				</div>
				<div id="bradyAvg" class="col-xs-6 right-align">
					<noscript>
						<?php echo $bradyAvg; ?>
					</noscript>
				</div>
			</div>

			<div class="row hidden-to-grey" <?php echo ($bradyTotal >= $greyViews? "hidden": ""); ?> >
				<div class="col-xs-6">Brady: Total</div>
				<div id="bradyTotal" class="col-xs-6 right-align">
					<noscript>
						<?php echo $bradyTotal; ?>
					</noscript>
				</div>
			</div>

			<div class="row" id="hidden-stuff-toggle" <?php if ($bradyTotal < $greyViews) echo "hidden"; ?> >
				<div class="col-xs-12 center-align">
					<button id="bradyTotalReveal" class="btn btn-danger btn-lg">
						Grey, don't click here!
					</button>
				</div>
			</div>

			<div id="viewCountChart" data-grey="<?php echo $greyViews; ?>" data-brady-avg="<?php echo $bradyAvg; ?>" data-brady-total="<?php echo $bradyTotal; ?>">
			</div>
		</div>

		<div id="appInfo" class="mainSection">
			<div class="row">
				<h1 class="row col-sm-10 col-xs-7">
					App info
				</h1>
				<div class="col-sm-2 col-xs-5"></div><!-- To make space for the back-to-top button -->
			</div>

			<h3 class="row">Last Updated</h3>
			<h4 class="row">
				<?php echo $lastUpdate; ?> UTC
			</h4>

			<h3 class="row">Credits</h3>
			
			<h4 class="row">Graph generated with D3.</h4>
			<h4 class="row">Frontend created with Bootstrap.</h4>
			<h4 class="row">Powered by YouTube Data API (v2)</h4>
			<h4 class="row">Hosted by Heroku.</h4>
			
			
			<h3 class="row">
				<a href="http://github.com/nicktendo64/brady-vs-grey-php">View the source code on GitHub.</a>
			</h3>

			<!-- NOTES HERE -->
			<h3 class="row">Notes</h3>
			<h5 class="row">To conserve processer and network resources, this app should only update four times per day.</h5>
		</div>

		<!-- Floating back-to-top button (not part of any section) -->
		<a id="backButton" class="col-sm-2 col-xs-5 btn btn-primary btn-lg" href="#">Return to top.</a>
	</body>
</html>

<?php
$fp = fopen("cache_index.html", 'w');
fwrite($fp, ob_get_contents());
fclose($fp);
// finally send browser output
flush();
?>