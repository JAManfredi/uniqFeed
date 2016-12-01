<?php

require "tweetList.php";

$request = '';
switch(trim($_GET['request'])) {
  case 'fetchNew':
    $since = (isset($_GET['since']) ? trim($_GET['since']) : NULL);

    $now = new DateTime("now", new DateTimeZone('America/New_York'));
    $newTweets = fetchTweetsFromServer($since,NULL);
    addTweetsToCache($newTweets);

    $returnString = "";
	foreach ($newTweets as $tweet) {
		$rowHTML = createRowHTML($tweet,$now);
		$returnString .= ($rowHTML . "<!>");
	}
	echo $returnString;
    break;
  default:
    echo json_encode(array());
}

?>