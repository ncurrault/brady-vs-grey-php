<?php

require_once "secret_stuff.php";

// Some handy SQL functions
function sqlWithRet($q)
{
	global $sql_host, $sql_username, $sql_password, $sql_db_name, $sql_port;
	$sql_connection = mysqli_connect($sql_host, $sql_username, $sql_password, $sql_db_name, $sql_port);
	
	if (mysqli_connect_errno())
	{
		$n = mysqli_connect_errno();
		error_log("Error connecting to database (error number $n).");
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
		error_log("Error connecting to database (error number $n).");
	}
	else
	{
		$q = mysqli_query($sql_connection,$query);
		
		if (!$q)
		{
			error_log("Error with SQL query: `$query`");
		}
	}
}
function sql_escape($s)
{
	global $sql_host, $sql_username, $sql_password, $sql_db_name, $sql_port;
	$sql_connection = mysqli_connect($sql_host, $sql_username, $sql_password, $sql_db_name, $sql_port);
	
	return mysqli_real_escape_string($sql_connection, $s);
}
?>