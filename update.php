<?php

// http_response_code(501); // NOT IMPLEMENTED

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
}

function sql_remove($table, $pid)
{
	sql_no_ret("DELETE FROM $table WHERE PID=$pid");
}
// sql_no_ret("INSERT INTO Video (Title, YouTubeID, UploadDate, Channel, Creator, ViewCount) VALUES ('Title', 'ABC123ID456', '2014-06-17 23:23:00', 'deastruction', 'Dylan Hong', 301)");

$key_file = fopen('api_key.txt', 'r');
$api_key = fread($file, 39);
fclose($key_file);

set_include_path(
	get_include_path()
	.
	PATH_SEPARATOR
	.
	$_SERVER['DOCUMENT_ROOT'] . "/ZendFramework-1.12.7/library"
);


require_once 'Zend/Loader.php'; // the Zend dir must be in your include_path
Zend_Loader::loadClass('Zend_Gdata_YouTube');
$yt = new Zend_Gdata_YouTube();



?>
