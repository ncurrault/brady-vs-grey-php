<?php

require_once "secret_stuff.php";

// YAY INTERNET! (https://github.com/kch/heroku-php-pg/blob/master/index.php)
function pg_connection_string_from_database_url() {
  extract(parse_url($_ENV["DATABASE_URL"]));
  return "user=$user password=$pass host=$host dbname=" . substr($path, 1); # <- you may want to add sslmode=require there too
}

// Some handy SQL functions
function sqlQuery($q, $params=array())
{
	$sql_connection = pg_connect(pg_connection_string_from_database_url());
		
	if (pg_connection_status($sql_connection) != PGSQL_CONNECTION_OK)
	{
		error_log("Error connecting to database.");
		return null;
	}
	else
	{
		$q_result = pg_query_params($sql_connection, $q, $params);
		
		if (!$q_result)
		{
			error_log("Error with query `$q`: ". pg_last_error($sql_connection));
		}
		
		$ret = pg_fetch_all($q_result);
		
		pg_close($sql_connection);
	
		return $ret;
	}
}


?>