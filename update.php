<?php

// Some handy SQL functions
function sql_ret($q)
{
	$sql_connection = mysqli_connect($host="localhost", "root", "root", "my_db_2", 8889);
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
function sql_no_ret($q)
{
	$sql_connection = mysqli_connect($host="localhost", "root", "root", "my_db_2", 8889);
	if (mysqli_connect_errno())
	{
		$n = mysqli_connect_errno();
		echo "Error with database (error number $n)";
	}
	else
	{
		$q = mysqli_query($sql_connection,$q);
		
		if (!$q)
		{
			echo "Error with query";
		}
	}
	
	/* I'll use these later
	 * "DELETE FROM $table WHERE PID=$pid"
	 * "INSERT INTO Video (Title, YouTubeID, UploadDate, Channel, Creator, ViewCount) VALUES ('Title', 'ABC123ID456', '2014-06-17 23:23:00', 'deastruction', 'Dylan Hong', 301)"
	 */
}

// Get the API key from my file
$key_file = fopen('api_key.txt', 'r');
$api_key = fread($key_file, 39);
fclose($key_file);

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

// Functions for getting videos from the API and putting them in arrays
function vidEntryToArray($videoEntry, $channel) 
{
	$ret = array(
	'Title' => $videoEntry->getVideoTitle(),
	'YouTubeID' => $videoEntry->getVideoId(),
	'ViewCount' => $videoEntry->getVideoViewCount(),
	'Channel' => $channel
	'UploadDate' => $videoEntry->mediaGroup->uploaded->text,
	);
	
	if (GREY_CHANNELS[$channel])
	{
		$ret["Creator"] = "C.G.P. Grey";
	}
	else
	{
		$ret["Creator"] = "Brady Haran";
	} 
	
	return $ret;
}
function getUploads(channel)
{
	$ret = array( );
	$videoFeed = $yt->getUserUploads($channel);
	
	foreach($videoFeed as $vid)
	{
		$ret.push(vidEntryToArray($vid));
	}
	
	return $ret;
}

echo "Done!";
?>
