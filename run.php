<?php
	include("main.php");
	$param = $_POST["search"];
	$obj = new TweetHandler($param);
	$obj->mainFunction();
?>