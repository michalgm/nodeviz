<?php 
/************************************************************
Name:	dbaccess.php
Version:
Date:
Common database-related functions
************************************************************/

//dbconnect: open db connection, return connection pointer
function dbconnect() {
	global $db, $dblogin, $dbpass, $dbname, $dbport, $dbsocket, $dbhost;
	if (! $db) {
		$db = new mysqli($dbhost,$dblogin,$dbpass, $dbname, $dbport, $dbsocket);
		if ($db->connect_error) {
			die('Connect Error (' . $db->connect_errno . ') ' . $db->connect_error);
		}
		$db->query("SET NAMES 'utf8'");
	}
	return $db;
}

//dbwrite: perform db query, return query result
//FIXME: rename this to dbquery
function dbwrite($query) {
	$res = query($query);
	return $res;	
}

//fetchRow: performs query and returns single row result
function fetchRow($query) {
	$res = query($query);
	if ($res) { return $res->fetch_row(); }
}

function fetchValue($query) {
	$res = fetchRow($query);
	if ($res && $res[0]) {
		return $res[0];
	}
}

function query($query) {
	global $db;
	if (! $db) { $db = dbconnect(); }
	$res = $db->query($query) or print ('Query failed: '.$db->error."\n\t<br/>$query\n");
	return $res;
}


function fetchCol($query) {
	$res = query($query);
	$array = Array();
	if ($res) { 
		while ($row = $res->fetch_row()) {
			$array[] = $row[0];
		}
	}
	return $array;
}


//dbLookupArray: performs query and returns associative array
function dbLookupArray($query) {
	$res = dbwrite($query);
	$array = Array();
	while($row = $res->fetch_assoc()) {
		$key = current($row);
		$array[$key] = $row;
	}
	return $array;
}

function dbEscape($string) {
	global $db;
	if (! $db) { $db = dbconnect(); }
	return $db->real_escape_string($string);	
}

//tableize: accepts assoc. array, returns html table string
function tableize($data) {
	global $partycolor;
	$table = "<table border=1>";
	$first = 0;
	foreach ($data as $row) {
		$color = "#EEEEEE";
		if (! $first) {
			$table .="<tr>";
			foreach (array_keys($row) as $name) { $table.="<th>$name</th>\n"; }
			$first = 1;
			$table .="</tr>";
		}
		if ( 0 && isset($row['PartyDesignation1'])) {
		 	if (isset($partycolor[$row['PartyDesignation1']])) {
				$color = $partycolor[$row['PartyDesignation1']];
			}
		}
		$table .="<tr>";
		foreach($row as $col) { 
			$table .="<td bgcolor='$color'>".$col."</td>\n";
		}
		$table .="</tr>";
	}
	$table .="</table>";
	return $table;
}

function arrayValuesToInString($array) {
	return "'".join("','", array_values($array))."'";
}

function arrayToInString($array, $assoc=0) {
	$array2 = Array();
	if($assoc) { 
		$array = array_keys($array);
	}
	foreach ($array as $key) {
		$key = dbEscape($key);
		$array2[] = $key;
	}
	return "'".join("','", $array2)."'";
}


