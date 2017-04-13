<?php
	$database = 'mydb';
	$host = 'localhost';
	$user = 'root';
	$pass = '';
	$conn = new mysqli($host, $user, $pass, $database);
	if ($conn->connect_error) {
		die("Connection failed: ".$conn->connect_error);
	}
	//echo "Connected successfully";
?>