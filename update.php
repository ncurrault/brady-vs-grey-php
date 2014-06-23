<?php

//ini_set("display_errors", "On"); // For debugging errors
// header("Content-Type: text/plain"); // For debugging with print_r

// Some handy SQL functions
require_once "sql_functions.php";

// for the API key
require_once "secret_stuff.php";

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
	$vid = array();
	foreach ($unescapedVid as $property => $value)
	{
		$vid[$property] = sql_escape($value);
	}
	
	sqlWithoutRet("DELETE FROM Video WHERE YouTubeID='{$vid['YouTubeID']}'");
	
	sqlWithoutRet("INSERT INTO
		Video (Title, YouTubeID, UploadDate, Channel, Creator, ViewCount)
		VALUES ('{$vid['Title']}', '{$vid['YouTubeID']}', '{$vid['UploadDate']}', '{$vid['Channel']}', '{$vid['Creator']}', {$vid['ViewCount']} )"
	);
}
function deleteExtraneousVids()
{
	$latestGreyDate = sqlWithRet("SELECT * FROM Video WHERE Creator='C.G.P. Grey' ORDER BY UploadDate DESC LIMIT 1")[0]['UploadDate'];
	sqlWithoutRet("DELETE FROM Video WHERE UploadDate < '$latestGreyDate'"); 
}
function recordUpdate()
{
	// sqlWithoutRet("DELETE FROM UpdateLog"); // Taylor's idea
	$now = strftime("%F %T");
	
	sqlWithoutRet("INSERT INTO UpdateLog (UpdateDatetime) VALUES ('$now')");
}

// Functions for getting videos from the API and putting them in arrays
function vidEntryToArray($videoEntry, $channel) 
{
	global $greyChannels, $bradyChannels;
	return array(
	'Title' => $videoEntry->getVideoTitle(),
	'YouTubeID' => $videoEntry->getVideoId(),
	'UploadDate' =>
		str_replace('T',' ',
		str_replace('Z', '', $videoEntry->mediaGroup->uploaded->text
		)),
	'Channel' => $channel,
	'Creator' => (in_array($channel, $greyChannels) ? "C.G.P. Grey" : "Brady Haran"),
	'ViewCount' => $videoEntry->getVideoViewCount(),
	);
}
function getUploads($channel, $maxNum = 25)
{
	global $api_key;
	// Initialize the API with my key
	require_once 'Zend/Loader.php'; // the Zend dir must be in your include_path
	Zend_Loader::loadClass('Zend_Gdata_YouTube');
	$yt = new Zend_Gdata_YouTube(null, "Brady vs. Grey - PHP version", null, $api_key);
	$yt->setMajorProtocolVersion(2);
	
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
	
	global $greyChannels, $bradyChannels;
	$vids = array( );
	
	foreach ($greyChannels as $channel)
	{
		$vids = array_merge($vids, getUploads($channel, 1));
	}
	foreach ($bradyChannels as $channel)
	{
		$vids = array_merge($vids, getUploads($channel, 20));
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
	
	error_log("Updated!");
}
?>
