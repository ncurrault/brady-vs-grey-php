<?php

//ini_set("display_errors", "On"); // For debugging errors
// header("Content-Type: text/plain"); // For debugging with print_r

// Some handy SQL functions
require_once "sql_functions.php";
require_once "refresh.php";

// YouTube Data API v3
require_once 'Google/autoload.php';

// for the API key
$api_key = $_ENV["YOUTUBE_API_KEY"];

$greyChannelNames = array('CGPGrey');
$bradyChannelNames = array(
'numberphile', 'Computerphile', 'sixtysymbols',
'periodicvideos', 'nottinghamscience', 'DeepSkyVideos',
'bibledex', 'wordsoftheworld', 'FavScientist',
'psyfile', 'BackstageScience', 'foodskey',
'BradyStuff', 'Numberhpile2', 'Objectivity');

$greyChannelIDs = array('UC2C_jShtL725hvbm1arSV9w');
$bradyChannelIDs = array(
'UCoxcjq-8xIDTYp3uz647V5A', 'UC9-y-6csu5WGm29I7JiwpnA', 'UC9-y-6csu5WGm29I7JiwpnA',
'UCtESv1e7ntJaLJYKIO1FoYw', 'UCCAgrIbwcJ67zIow1pNF30A', 'UCo-3ThNQmPmQSQL_L6Lx1_w',
'UCnQtJro3IurKlxp7XSPqpaA', 'UC-YO7JkqlrBsgMGiAlqQ7Tg', 'UC4_HPD_v8Id82lnBrX7w7lg',
'UCNGSLqZab4TkgY8cnJQxgtA', 'UCP16wb-IThCVvM8D-Xx8HXA', 'UCWUq6teKH18Iwuh41D75sQg',
'UCRwr1kxjL-8m1L6mQXB2zSQ', 'UCyp1gCHZJU_fGWFf2rtMkCg', 'UCtwKon9qMt5YLVgQt1tvJKg');

$allNames = array_merge($greyChannelNames, $bradyChannelNames);
$allIDs = array_merge($greyChannelIDs, $bradyChannelIDs);
$idToName = array_combine($allIDs, $allNames);


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


function vidoGetViews($playlistObj, $youtube)
{
	$vidItems = $youtube->videos->listVideos('statistics', array(
		'id' => $playlistObj['snippet']['resourceId']['videoId'],
		'maxResults' => 1
	))['items'];
	return $vidItems[0]['statistics']['viewCount'];
}
// Functions for getting videos from the API and putting them in arrays
function playlistItemToArray($playlistItem, $channelID, $youtube) 
{
	global $greyChannelIDs, $idToName;
	return array(
		'title' => $playlistItem['snippet']['title'],
		'youtubeid' => $playlistItem['snippet']['resourceId']['videoId'],
		'uploaddate' =>
			str_replace('T',' ',
			str_replace('Z', '', $playlistItem['snippet']['publishedAt']
			)),
		'channel' => $idToName[$channelID],
		'creator' => (in_array($channelID, $greyChannelIDs) ? "C.G.P. Grey" : "Brady Haran"),
		'viewcount' => vidoGetViews($playlistItem, $youtube),
	);
}
function getUploads($channelID, $youtube, $maxNum = 25)
{	
	$ret = array( );
	
	$channelsResponse = $youtube->channels->listChannels('contentDetails', array(
	  'id' => $channelID,
	));

	if (empty($channelsResponse)) {
		error_log("No channel with ID '" . $channelID . "' found.");
		return array();
	}
	
	$uploadsPlaylist = $channelsResponse[0]['contentDetails']['relatedPlaylists']['uploads'];

	$playlistItems = $youtube->playlistItems->listPlaylistItems('snippet', array(
		'playlistId' => $uploadsPlaylist,
		'maxResults' => $maxNum
	))['items'];

	foreach($playlistItems as $vid)
	{
		array_push($ret, playlistItemToArray($vid, $channelID, $youtube));
		if (count($ret) >= $maxNum)
		{
			break;
		}
	}
	
	return $ret;
}

function update_with_api()
{
	error_log("Update triggered...");

	$client = new Google_Client();
	global $api_key;
	$client->setDeveloperKey($api_key);
	$youtube = new Google_Service_YouTube($client);
	
	global $greyChannelIDs, $bradyChannelIDs;
	$vids = array( );
	
	foreach ($greyChannelIDs as $channelID)
	{
		$vids = array_merge($vids, getUploads($channelID, $youtube, 1));
	}
	foreach ($bradyChannelIDs as $channelID)
	{
		$vids = array_merge($vids, getUploads($channelID, $youtube, 20));
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
	refresh();

	error_log("Updated successfully!");
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
