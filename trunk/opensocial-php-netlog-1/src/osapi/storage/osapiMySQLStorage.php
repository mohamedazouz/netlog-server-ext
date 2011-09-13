<?php

 /*
 *	assumes database that has table with text fields os_key and os_value - created by Wouter
 */

//die ("Got here");
class osapiMySQLStorage extends osapiStorage {


	public function __construct($host, $user, $pass, $db, $table)
	{
	  	$this->host = $host;
	  	$this->user = $user;
	  	$this->pass = $pass;
	  	$this->db = $db;
	  	$this->table = $table;
	  	//die (implode(" - " , Array($this->host, $this->user, $this->pass, $this->db, $this->table)));
	}

	 private function query($query, $isGet)
	{

		$conn = mysql_connect($this->host, $this->user, $this->pass);
		
		if(!$conn)
		{
			die("could not connect to db: ".mysql_error());
		}

		mysql_select_db($this->db);
		
		$queryresult = mysql_query($query) or die("query failed! Query: $query  | error: " . mysql_error());
		
		if ($isGet)
		{
			$row = mysql_fetch_assoc($queryresult);
			return $row;
		}

		return @mysql_affected_rows($queryresult);
	}
	
	private function close() {
		mysql_close($this->conn) or Die("Failed closing connection!");
	}
  
	public function get($key, $expiration = false)
	{
		$result = $this->query("SELECT os_value, dateadded FROM ".$this->table." WHERE `os_key`='$key'", true);
		
		if (!$expiration || ($expiration && (time() - strtotime($result['dateadded'])) < $expiration))
		{
			$result['os_value'] = unserialize($result['os_value']);

			return $result['os_value'];
		}
		else
		{
			return false;	
		}
	}
  
	public function set($key, $value)
	{
		 $value = serialize($value);
		
		 $result = $this->query("INSERT INTO ".$this->table." SET `os_key` = '$key', os_value = '$value', `dateadded` = NOW()", false);

		 return $result;
	}
	
	public function delete($key)
	{
		return $this->query("DELETE FROM ".$this->table." WHERE `os_key`='$key'", false);
	}

}
