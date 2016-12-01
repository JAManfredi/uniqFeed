<?php

require "vendor/autoload.php";
include "debugDump.php";

use Abraham\TwitterOAuth\TwitterOAuth;

function fetchTweetsFromServer($since, $max) {
	$slug = "sports";
	$listId = "__REPLACE WITH LIST ID__";
	$fetchCnt = "200";

	$connection = getConnectionWithAccessToken("REPLACE WITH AUTH TOKEN", "REPLACE WITH SECRET");
	//$lists = $connection->get("lists/list");

	// Fetch Tweets From Server 
	$requestParams = ["slug" => $slug, "list_id" => $listId, "include_rts" => true, "include_entities" => true, "tweet_mode" => "extended", "count" => $fetchCnt];
	if ($since != NULL) {	
		$requestParams["since_id"] = $since;
	}

	$tweets = $connection->get("lists/statuses", $requestParams);
	if ($connection->getLastHttpCode() == 200) {
		return $tweets;
	} else {
		$lastBody = $connection->getLastBody();
		$errorArray = $lastBody->errors;
		if ($errorArray != null && count($errorArray) > 0) {
			$error = $errorArray[0];
			//debug_to_console($error->message);
			return array();
		}
	}
}

function loadNewTweetsTable() {
	$hashLookup = array();
	$tempTweetArray = array();
	$cacheddata = loadCachedTweets();

	if (count($cacheddata) > 0) {
		$tweetArray = $cacheddata;
		$tweet = $cacheddata[0];
		if ($tweet != NULL) {
			$tempTweetArray = fetchTweetsFromServer($tweet->id_str,NULL);
		} else {
			$tempTweetArray = fetchTweetsFromServer(NULL,NULL);
		}
	} else {
		$tempTweetArray = fetchTweetsFromServer(NULL,NULL);
	}
 
	$outputHTML = "<table id='mainTweetTable'>";
	date_default_timezone_set('UTC');
	
	$now = new DateTime("now", new DateTimeZone('UTC'));

	if (count($tempTweetArray) > 0 && count($cacheddata) > 0) {
		// Merge Data
		$tweetArray = array_merge($tempTweetArray, $cacheddata);
		cacheTweets($tweetArray);
	} else if (count($tempTweetArray) > 0 && count($cacheddata) == 0) {
		// Use & Save New Tweets
		$tweetArray = $tempTweetArray;
		cacheTweets($tweetArray);
	} else if (count($tempTweetArray) == 0 && count($cacheddata) > 0) {
		// Use Cached Data
		$tweetArray = $cacheddata;
	}

	foreach ($tweetArray as $tweet) {
		if (array_key_exists($tweet->full_text, $hashLookup)) {
			debug_to_console("Skipped Duplicate Tweet");
		} else {
			//$tweet = $tweetArray[0];
			//print_r(var_dump_pre($tweet));
			$hashLookup[$tweet->full_text] = NULL;
			$outputHTML .= createRowHTML($tweet,$now);
		}	
	}
	$outputHTML .= "</table>";
	echo $outputHTML;
}

function getConnectionWithAccessToken($oauth_token, $oauth_token_secret) {
  $connection = new TwitterOAuth("__REPLACE WITH CONSUMER KEY__", "__REPLACE WITH CONSUMER SECRET__", $oauth_token, $oauth_token_secret);
  return $connection;
}

function getTimeOfTweet($seconds) {
	if ($seconds == 0) {
		return "now";
	} else if ($seconds > 0 && $seconds < 60) { // seconds
		return $seconds . "s";
	} else if ($seconds >= 60 && $seconds < 3600) { // minutes
		return floor($seconds / 60) . "m";
	} else if ($seconds >= 3600 && $seconds < 86400) { // hours
		return floor($seconds / 3600) . "h";
	} else if ($seconds >= 86400) { // days
		return floor($seconds / 86400) . "d";
	}
}

function convertTextToInteractive($text) {
	return convertMentionsHref(convertHashtagsHref(convertLinksHref($text)));
}

function convertLinksHref($text){
    return preg_replace('!(((f|ht)tp(s)?://)[-a-zA-Zа-яА-Я()0-9@:%_+.~#?&;//=]+)!i', '<a href="$1">$1</a>', $text);
}

function convertMentionsHref($text) {
    return preg_replace('/\@([a-zA-Z1-9]+)/', '<a title="@$1" href="https://twitter.com/$1">@$1</a>', $text);
}

function convertHashtagsHref($text) {
	return preg_replace('/#([a-zA-Z1-9]+)/', '<a title="#$1" href="https://twitter.com/search?q=%23$1&src=hash">#$1</a>', $text);
}

function debug_to_console($data) {
    if (is_array($data)) {
        $output = "<script>console.log('Debug Objects:" . implode(',', $data) . "');</script>";
    } else {
        $output = "<script>console.log('Debug Objects: " . $data . "');</script>";
    }
    echo $output;
}

function createRowHTML($tweet, $fetchTime) {
	// Set Tweet Meta Variables
	$name = $tweet->user->name;
	$screen_name = $tweet->user->screen_name;
	$verified = $tweet->user->verified;
	$createdAt = $tweet->created_at;
	$retweeted_status = $tweet->retweeted_status;
	$text = $tweet->full_text;
	$profileImgUrl = $tweet->user->profile_image_url;
	$retweetedBy = NULL;

	// Set Variable From Retweeted Status If Retweet
	if (isset($retweeted_status)) {
		$retweetedBy = $tweet->user;
		$name = $retweeted_status->user->name;
		$screen_name = $retweeted_status->user->screen_name;
		$verified = $retweeted_status->user->verified;
		$createdAt = $retweeted_status->created_at;
		$text = $retweeted_status->full_text;
		$profileImgUrl = $retweeted_status->user->profile_image_url;
	}

	// Get How Long Ago Tweet Was Created
	$date = new DateTime($createdAt);
	$date->setTimeZone(new DateTimeZone('UTC'));
	$difference_in_seconds = $fetchTime->getTimestamp() - $date->getTimestamp();

	// Adjust User Image URL To Get Bigger Profile Image
	$verifiedImg = " <img class='verifiedLogo' src='images/verified.png' alt='Verified' />";
	$userImageURL = str_replace("_normal", "_bigger", $profileImgUrl); 
	$outputHTML = "<tr id='" . $tweet->id_str . "'>
						<td class='profileImgColumn' onclick=\"goto('https://twitter.com/" . $screen_name . "')\">
							<img class='profileImg' src='" . $userImageURL . "' />
							" . (($verified) ? $verifiedImg : "") . "
						</td>
				  		<td>
							<table class='innerTweetTbl'>";

	// Create Table Row With Tweet Meta
	$outputHTML .= "<tr>
						<td>
							<b>" . $name . "</b> @" . $screen_name . "
						</td>
					 	<td class='tweetTime'>
					 		<b>" . getTimeOfTweet($difference_in_seconds) . "</b>
					 		<div style='display:none'>" . $date->getTimestamp() . "</div>
					 	</td>
					 </tr>
					 <tr>
					 	<td colspan='2'>" . convertTextToInteractive($text) . "</td>
					 </tr>";

	// Show Quoted Status If Relevant
	if (isset($tweet->quoted_status)) {
		$outputHTML .=  "<tr class='quotedStatusRow' onclick=\"goto('" . $tweet->entities->urls[0]->expanded_url . "')\">
							<td colspan='2'>
								<table class='quotedStatusTable'> 
									<tr>
										<td><b>" . $tweet->quoted_status->user->name . "</b> @" . $tweet->quoted_status->user->screen_name . "</td>
						 			</tr>
									<tr>
						 				<td colspan='2'>" . $tweet->quoted_status->full_text . "</td>
						 			</tr>
				  				</table>
			 				</td>
			 			</tr>";
	}

	// Add Image To Tweet
	if (isset($tweet->entities->media)) {
		$mediaArray = $tweet->entities->media;
		foreach ($mediaArray as $media) {
			if ($media->type == "photo") {
				$imageURL = $media->media_url;

				// Sizing The Container Div Means We Can Calculate Appropriate Offset When Refreshing List So 
				// It No Longer Scrolls To A Random (To The User) Location
				$size = $media->sizes->medium;
				$widthRatio = 320 / $size->w;
				$newHeight = $widthRatio * $size->h;

				$outputHTML .= "<tr>
									<td colspan='2'>
										<div class='imageHolderDiv' style='width:320px; height:" . floor($newHeight) . "px;' 
										onclick=\"goto('" . $media->media_url . "')\">
											<img class='entityImage' src=\"" . $imageURL . "\" \>
										<div>
									</td>
								</tr>";	
				break; // Just add 1 photo
			}
		}
	}

	// Add Retweet Info If Needed
	if (isset($retweeted_status)) {
		$outputHTML .= "<tr>
							<td class='retweetInfo' colspan='2' onclick=\"goto('https://twitter.com/" . $tweet->user->screen_name . "')\">
								<img class='retweetIcon' src=\"" . "images/retweet.png" . "\" alt='Retweeted' />" . $tweet->user->name . "
							</td>
						</tr>";
	}

	$outputHTML .= 			"</table>
						</td>
					</tr>";
	return $outputHTML;
}

function cacheTweets($array) {
	$cachedata = serialize($array);
	file_put_contents('cachedTweets.txt', $cachedata);
}

function addTweetsToCache($array) {
	if ($array != NULL && count($array) > 0) {
		$tempTweetArray = array();
		$cacheddata = loadCachedTweets();
		if (count($cacheddata) > 0) {
			$tempTweetArray = array_merge($array, $cacheddata);
		} else {
			$tempTweetArray = $array;
		}
		cacheTweets($tempTweetArray);
	}
}

function loadCachedTweets() {
	$cacheddata = unserialize(file_get_contents('cachedTweets.txt'));
	return $cacheddata;
}

function var_dump_pre($mixed = null) {
  echo '<pre>';
  var_dump($mixed);
  echo '</pre>';
  return null;
}

?>