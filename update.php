<?php

//ini_set("display_errors", "On"); // For debugging errors
// header("Content-Type: text/plain"); // For debugging with print_r

// Some handy SQL functions
require_once "sql_functions.php";

// for the API key
$api_key = $_ENV["YOUTUBE_API_KEY"];

$greyChannels = array(
'CGPGrey', 'CGPGrey2', 'greysfavs');
$bradyChannels = array(
'numberphile', 'Computerphile', 'sixtysymbols',
'periodicvideos', 'nottinghamscience', 'DeepSkyVideos',
'bibledex', 'wordsoftheworld', 'FavScientist',
'psyfile', 'BackstageScience', 'foodskey',
'BradyStuff');

function addVideoReplacing($unescapedVid)
{	
	$vid = array(
		$unescapedVid["title"],
		$unescapedVid["youtubeid"],
		$unescapedVid["uploaddate"],
		$unescapedVid["channel"],
		$unescapedVid["creator"],
		$unescapedVid["viewcount"]
	);
	
	sqlQuery("DELETE FROM Video WHERE youtubeid=$1", array($unescapedVid["youtubeid"]) );
	
	sqlQuery("INSERT INTO
		Video (title, youtubeid, uploaddate, channel, creator, viewcount)
		VALUES ($1, $2, $3, $4, $5, $6 )", 
		$vid
	);
}
function deleteExtraneousVids()
{
	$latestGreyDate = sqlQuery("SELECT * FROM Video WHERE creator='C.G.P. Grey' ORDER BY uploaddate DESC LIMIT 1")[0]['uploaddate'];
	sqlQuery("DELETE FROM Video WHERE UploadDate < $1", array($latestGreyDate)); 
}
function recordUpdate()
{
	// sqlQuery("DELETE FROM UpdateLog"); // Taylor's idea
	$now = strftime("%F %T");
	
	sqlQuery("INSERT INTO UpdateLog (updatedatetime) VALUES ($1)", array($now));
}

// Functions for getting videos from the API and putting them in arrays
function vidEntryToArray($videoEntry, $channel) 
{
	global $greyChannels, $bradyChannels;
	return array(
	'title' => $videoEntry->getVideoTitle(),
	'youtubeid' => $videoEntry->getVideoId(),
	'uploaddate' =>
		str_replace('T',' ',
		str_replace('Z', '', $videoEntry->mediaGroup->uploaded->text
		)),
	'channel' => $channel,
	'creator' => (in_array($channel, $greyChannels) ? "C.G.P. Grey" : "Brady Haran"),
	'viewcount' => $videoEntry->getVideoViewCount(),
	);
}
function getUploads($channel, $yt, $maxNum = 25)
{	
	$ret = array( );
	$videoFeed = $yt->getUserUploads($channel);
	
	foreach($videoFeed as $vid)
	{
		array_push($ret, vidEntryToArray($vid, $channel));
		if (count($ret) >= $maxNum)
		{
			break;
		}
	}
	
	return $ret;
}

function update_with_api()
{
	// The API needs its library in the include_path.
	set_include_path(
		get_include_path()
		.
		PATH_SEPARATOR
		.
		$_SERVER['DOCUMENT_ROOT'] . "/ZendFramework-1.12.7/library"
	);
	
	global $api_key;
	// Initialize the API with my key
	require_once 'Zend/Loader.php'; // the Zend dir must be in your include_path
	Zend_Loader::loadClass('Zend_Gdata_YouTube');
	$yt = new Zend_Gdata_YouTube(null, "Brady vs. Grey - PHP version", null, $api_key);
	$yt->setMajorProtocolVersion(2);

	global $greyChannels, $bradyChannels;
	$vids = array( );
	
	foreach ($greyChannels as $channel)
	{
		$vids = array_merge($vids, getUploads($channel, $yt, 1));
	}
	foreach ($bradyChannels as $channel)
	{
		$vids = array_merge($vids, getUploads($channel, $yt, 20));
	}
	
	foreach ($vids as $vid)
	{
		// If this video is already in the database, delete it (we need the updated view count)
		addVideoReplacing($vid);
	}
	
	// Delete unnecessary videos from both creators.
	deleteExtraneousVids();
	
	// Record the time
	recordUpdate();
	
	// Clear the cache (so this update will apply)
	unlink("cached.php");

	error_log("Updated!");
}

if (!count(debug_backtrace())) // Equivalent to Python's `if __name__ == '__main__'`
{
	$submittedSecret = $_GET["secret"];
	$realSecret = $_ENV["UPDATE_SECRET"];

	if ($submittedSecret == $realSecret)
	{
		update_with_api();
		echo "Updated successfully!  <a href=\"/\">View the homepage.</a>";
	}
	else
	{
		http_response_code(403); // Forbidden
		echo "You are not permitted to trigger an update.";
	}
}
?>
