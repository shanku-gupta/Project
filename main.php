<?php
	//include("connection.php");
	define('DO_LOG',TRUE);
	class TweetHandler {
		protected $_tweet, $_path, $_host, $_user, $_pass, $_database, $_currentDateTime, $_passPram;
		public function __construct($pram = NULL) {
			$this->_path = 'https://api.twitter.com/1.1/search/tweets.json';
			$this->_host = 'localhost';
			$this->_user = 'root';
			$this->_pass = '';
			$this->_database = 'mydb';
			$this->_currentDateTime = date('Y-m-d H:i:s',time()-1800);
			$this->_passPram = $pram;
		}
		public function setTweet($param) {
			$this->_tweet = $param;
		}
		public function getTweet() {
			return $this->_tweet;
		}
		protected function createLogEntry($msg) {
			$fileName = 'ErrorLog_'.date("d-m-Y").'.log';
			if (DO_LOG) {
				error_log($msg,3,$fileName);
			}
		}
		public function mainFunction() {
			$conn = new mysqli($this->_host, $this->_user, $this->_pass, $this->_database);
			if ($conn->connect_error) {
				die("Connection failed: ".$conn->connect_error);
				$this->createLogEntry("Connection failed: ".$conn->connect_error);
			} else {
				if ($this->_passPram) {
					if ($this->_passPram[0] == '#') {
						$this->setTweet($this->_passPram);
						$query = "SELECT COUNT(1) AS count FROM tweets";
						$result = $conn->query($query)->fetch_assoc();
						print_r($result);
						if ($result['count'] == 1) {
							$conn->query("UPDATE tweets SET hashtag = '".$this->getTweet()."'");
						} else if ($result['count'] > 1) {
							$conn->query("DELETE FROM tweets");
						} else {
							$conn->query("INSERT INTO tweets (hashtag) VALUES ('".$this->getTweet()."')");
						}
						$response = $this->curlExecute($this->getTweet());
					} else {
						$this->createLogEntry('\n'.$this->_passPram.' is not a valid tweet');
						echo "\nNot a valid tweet";
						return;
					}
				} else {
					$query = "SELECT hashtag FROM tweets LIMIT 1";
					$result = $conn->query($query)->fetch_assoc();
					$response = $this->curlExecute($result['hashtag']);
				}		
				if($response !== FALSE) {
					$response = trim($response);
					$jsonDecode = json_decode($response, TRUE);
					//print_r($jsonDecode);
					foreach($jsonDecode AS $res) {
						$createdAt = date('Y-m-d H:i:s',strtotime($res['created_at']));
						if($this->_currentDateTime <= $createdAt) {
							$query = "SELECT id FROM tweetdescription WHERE tweet = '".trim(res['tweet'])."' LIMIT 1";
							$result = $conn->query($query)->fetch_assoc();
							$result1 = $conn->query("SELECT hashtag FROM tweets LIMIT 1")->fetch_assoc();
							if($result == NULL) {
								$hash = $result1['hashtag'];
								$query = "INSERT INTO tweetdescription (hashtag, tweet, created_at) VALUES ('".hash."','".trim(res['tweet'])."','".$createdAt."')";
							} else {
								$query = "UPDATE tweetdescription SET created_at = '".$createdAt."' WHERE id = ".$result['id'];
							}
							$conn->query($query);
						}
					}
				}
			}
		}
		public function curlExecute($hashTag, $flag = 0) {
			$path = $this->_path;
			//echo "\n".$path."\t".$hashTag;
			$hashTag = trim($hashTag);
			if($hashTag == NULL) {
				echo "\nEmpty tweet";
				return FALSE;
			} else if ($hashTag[0] != '#') {
				echo "\nNot a valid tweet";
				$this->createLogEntry('\n'.$hashTag.' is not a valid tweet');
				return FALSE;
			} else {
				$query = '?q=%23'.substr($hashTag, 1);
				if($flag) {
					$query = $query.'&result_type=recent';
				}
				$url = $path.''.$query;
				echo "<br>".$url;
				$curl = curl_init();
				$consumerKey = "6r37Kq5U4MiW9EzwqY8l7yYqU";
				$consumerSecret = "DuXrvgUF4eu2vuyhK7g9ZHdz3AEaCjCc42bCbJRb4azM1Hufzy";
				$oauthAccessToken = "851856559923769344-AUgnbHhTc1uyqrXNlgirk2g7fwDDKaH";
				$oauthAccessTokenSecret = "OcQmbQbAPejt6BTJi8VQwI4vxl7BplnOzeeFWyWdLJE0U";
				$oauth = array( 'OAUTH_CONSUMER_KEY' => $consumerKey,
                    'OAUTH_SIGNATURE_METHOD' => 'HMAC-SHA1',
                    'OAUTH_TOKEN' => $oauthAccessToken,
                    'OAUTH_TIMESTAMP' => time(),
                    'OAUTH_VERSION' => '1.0');
				$baseInfo = $this->buildBaseString($url, 'GET', $oauth);
				$compositeKey = rawurlencode($consumerSecret) . '&' . rawurlencode($oauthAccessTokenSecret);
				$oauthSignature = base64_encode(hash_hmac('sha1', $baseInfo, $compositeKey, true));
				$oauth['OAUTH_SIGNATURE'] = $oauthSignature;
				$headers = array( 'Content-Type:application/json', $this->buildAuthorizationHeader($oauth));
				curl_setopt_array($curl, array(
					CURLOPT_HTTPHEADER => $headers,
					CURLOPT_HEADER => false,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_URL => $url,
					CURLOPT_SSL_VERIFYPEER => false,
					CURLOPT_USERAGENT => 'fetch tweets'
				));
				$resp = curl_exec($curl);
				echo "<br>";
				print_r($resp);
				if ($resp === FALSE) {
					$this->createLogEntry("<br>URL: $url Error Code: '" . curl_errno($curl) . "' - Error Message: " . curl_error($curl));
				}
				// Close request to clear up some resources
				curl_close($curl);
				return $resp;
			}
		}
		protected function buildBaseString($baseURI, $method, $params) {
			$r = array();
			ksort($params);
			foreach($params as $key=>$value){
				$r[] = "$key=" . rawurlencode($value);
			}
			return $method."&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $r));
		}
		protected function buildAuthorizationHeader($oauth) {
			$r = 'Authorization: OAuth ';
			$values = array();
			foreach($oauth as $key=>$value)
				$values[] = "$key=\"" . rawurlencode($value) . "\"";
			$r .= implode(', ', $values);
			return $r;
		}
	}
?>