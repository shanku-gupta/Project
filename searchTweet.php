<html>
	<head>
		<title>
			Tweet search page
		</title>
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.0/jquery.min.js">
		</script>
	</head>
	<body>
		<center>
			<h1>Search Tweet</h1>
			<form action="<?= $_SERVER['PHP_SELF'] ?>" method = "post">
				Search: <input type = "text" name = "searchTweet"><br><br>
				<input type = "submit" name = "search" value = "search">
			</form>
			<script type="text/javascript">
				var auto_refresh = setInterval(
				function () {
					$('#load_tweets').load('searchTweet.php').fadeIn("slow");
				}, 5000);
			</script>	
			<?php
				if ($_SERVER['REQUEST_METHOD'] == 'POST') {
					include("main.php");
					$hashTag = $_POST["searchTweet"];
			?>
			<div id = "loadTweets">
			<?php
					$obj = new TweetHandler();
					$response = $obj->curlExecute($hashTag,1);
					$jsonDecode = json_decode($response, TRUE);
					if($jsonDecode) {
						foreach ($jsonDecode AS $res) {
							echo "<br>";
							print_r($res);
						}
					} else {
						echo "<br>Something went wrong";
					}
				}
			?>
			</div>
		</center>
	</body>
</html>