<?php

//ini_set("display_errors", "On"); // For debugging errors
// header("Content-Type: text/plain"); // For debugging with print_r

// YouTube Data API v3
require_once 'vendor/autoload.php';

// for the API key
$api_key = $_ENV["YOUTUBE_API_KEY"];

$greyChannelName = 'CGPGrey';
$greyChannelID = 'UC2C_jShtL725hvbm1arSV9w';

$bradyChannelNames = array(
'numberphile', 'sixtysymbols',
'periodicvideos', 'nottinghamscience', 'DeepSkyVideos',
'bibledex', 'wordsoftheworld', 'FavScientist',
'psyfile', 'BackstageScience', 'foodskey',
'BradyStuff', 'Numberphile2', 'Objectivity',
'PhilosophyFile');

$bradyChannelIDs = array(
'UCoxcjq-8xIDTYp3uz647V5A', 'UCvBqzzvUBLCs8Y7Axb-jZew',
'UCtESv1e7ntJaLJYKIO1FoYw', 'UCCAgrIbwcJ67zIow1pNF30A', 'UCo-3ThNQmPmQSQL_L6Lx1_w',
'UCnQtJro3IurKlxp7XSPqpaA', 'UC-YO7JkqlrBsgMGiAlqQ7Tg', 'UC4_HPD_v8Id82lnBrX7w7lg',
'UCNGSLqZab4TkgY8cnJQxgtA', 'UCP16wb-IThCVvM8D-Xx8HXA', 'UCWUq6teKH18Iwuh41D75sQg',
'UCRwr1kxjL-8m1L6mQXB2zSQ', 'UCyp1gCHZJU_fGWFf2rtMkCg', 'UCtwKon9qMt5YLVgQt1tvJKg',
'UCIThl1QA8ICaoYT630pn4IA');

$allNames = array_merge(array($greyChannelName), $bradyChannelNames);
$allIDs = array_merge(array($greyChannelID), $bradyChannelIDs);
$idToName = array_combine($allIDs, $allNames);

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
	global $greyChannelID, $idToName;
	return array(
		'title' => $playlistItem['snippet']['title'],
		'youtubeid' => $playlistItem['snippet']['resourceId']['videoId'],
		'uploaddate' =>
			str_replace('T',' ',
			str_replace('Z', '', $playlistItem['snippet']['publishedAt']
			)),
		'channel' => $idToName[$channelID],
		'creator' => (($channelID == $greyChannelID) ? "C.G.P. Grey" : "Brady Haran"),
		'viewcount' => vidoGetViews($playlistItem, $youtube),
	);
}


function getRecentUpload($channelID, $youtube) {
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
		'maxResults' => 1
	))['items'];

    return playlistItemToArray($playlistItems[0], $channelID, $youtube);
}

function getUploadsSince($channelID, $youtube, $date)
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
		'maxResults' => 25 // TODO assumes this covers everything (doesn't deal with pages of results)
	))['items'];

	foreach($playlistItems as $vidItem)
	{
        $vidArray = playlistItemToArray($vidItem, $channelID, $youtube);

		if ($vidArray['uploaddate'] < $date) {
			break;
		} else {
            array_push($ret, $vidArray);
        }
	}

	return $ret;
}

function getVidData() {
    global $api_key, $greyChannelID, $bradyChannelIDs;

    $client = new Google_Client();
    $client->setDeveloperKey($api_key);
    $youtube = new Google_Service_YouTube($client);

    $greyVid = getRecentUpload($greyChannelID, $youtube);

    $bradyVids = array( );
    foreach ($bradyChannelIDs as $channelID)
    {
    	$bradyVids = array_merge($bradyVids,
            getUploadsSince($channelID, $youtube, $greyVid['uploaddate']));
    }

    return array('greyVid' => $greyVid, 'bradyVids' => $bradyVids);
}
?>
