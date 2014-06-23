<?php

//ini_set("display_errors", "On"); // For debugging errors
// header("Content-Type: text/plain"); // For debugging with print_r

// Get the API key from my file
$key_file = fopen('api_key.txt', 'r');
$api_key = fread($key_file, 39);
fclose($key_file);

require_once "secret_stuff.php";

// Some handy SQL functions
function sqlWithRet($q)
{
	global $sql_host, $sql_username, $sql_password, $sql_db_name, $sql_port;
	$sql_connection = mysqli_connect($sql_host, $sql_username, $sql_password, $sql_db_name, $sql_port);
	
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
function sqlWithoutRet($query)
{
	global $sql_host, $sql_username, $sql_password, $sql_db_name, $sql_port;
	$sql_connection = mysqli_connect($sql_host, $sql_username, $sql_password, $sql_db_name, $sql_port);
	
	if (mysqli_connect_errno())
	{
		$n = mysqli_connect_errno();
		echo "Error with database (error number $n)";
	}
	else
	{
		$q = mysqli_query($sql_connection,$query);
		
		if (!$q)
		{
			echo "Error with query: <pre><code>$query</code></pre><br />";
		}
	}
}

function addVideoReplacing($unescapedVid)
{
	global $sql_host, $sql_username, $sql_password, $sql_db_name, $sql_port;
	$sql_connection = mysqli_connect($sql_host, $sql_username, $sql_password, $sql_db_name, $sql_port);
	
	$vid = array();
	foreach ($unescapedVid as $property => $value)
	{
		$vid[$property] = mysqli_real_escape_string($sql_connection, $value);
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
	sqlWithoutRet("INSERT INTO UpdateLog () VALUES ()");
}

// The API needs its library in the include_path.
set_include_path(
	get_include_path()
	.
	PATH_SEPARATOR
	.
	$_SERVER['DOCUMENT_ROOT'] . "/ZendFramework-1.12.7/library"
);

// Initialize the API with my key
require_once 'Zend/Loader.php'; // the Zend dir must be in your include_path
Zend_Loader::loadClass('Zend_Gdata_YouTube');
$yt = new Zend_Gdata_YouTube(null, "Brady vs. Grey - PHP version", null, $api_key);
$yt->setMajorProtocolVersion(2);

$greyChannels = array(
'CGPGrey', 'CGPGrey2', 'greysfavs');
$bradyChannels = array(
'numberphile', 'Computerphile', 'sixtysymbols',
'periodicvideos', 'nottinghamscience', 'DeepSkyVideos',
'bibledex', 'wordsoftheworld', 'FavScientist',
'psyfile', 'BackstageScience', 'foodskey',
'BradyStuff');

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
	global $yt;
	
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

echo "Done!";
?>
