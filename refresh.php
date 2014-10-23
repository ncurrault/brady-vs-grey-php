<?php

$cachefiles = array( "cache-standard.html", "cache-classic.html" );

function refresh()
{
	global $cachefiles;
	
	foreach ($cachefiles as $file)
	{
		unlink($file);
	}
	error_log("Refreshed successfully!");
}

if (!count(debug_backtrace())) // Equivalent to Python's `if __name__ == '__main__'`
{
	$submittedSecret = $_GET["secret"];
	$realSecret = $_ENV["UPDATE_SECRET"];

	if ($submittedSecret == $realSecret)
	{
		refresh();
		echo "Refreshed successfully!  <a href=\"/\">View the homepage.</a>";
	}
	else
	{
		http_response_code(403); // Forbidden
		echo "You are not permitted to trigger a refresh.";
	}
}
?>
