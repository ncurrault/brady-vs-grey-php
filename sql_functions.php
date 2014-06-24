<?php

// YAY INTERNET! (https://github.com/kch/heroku-php-pg/blob/master/index.php)
function pg_connection_string_from_database_url() {
  extract(parse_url($_ENV["DATABASE_URL"]));
  return "user=$user password=$pass host=$host dbname=" . substr($path, 1); # <- you may want to add sslmode=require there too
}

$sql_connection = pg_connect(pg_connection_string_from_database_url());
if (pg_connection_status($sql_connection) != PGSQL_CONNECTION_OK)
{
	error_log("Error connecting to database.");
}

function sqlQuery($q, $params=null)
{		
	global $sql_connection;
	
	if ($params)
	{
		$q_result = pg_query_params($sql_connection, $q, $params);
	}
	else
	{
		$q_result = pg_query($sql_connection, $q);
	}
	
	if (!$q_result)
	{
		return null;
	}
	
	$ret = pg_fetch_all($q_result);
	
	return $ret;
}

?>