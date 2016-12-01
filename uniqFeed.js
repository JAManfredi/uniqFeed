
// Update Each Time Since Tweet Text Every Second
$(document).ready(function() {
	setInterval(function() {
		var now = new Date().getTime() / 1000; //seconds
		$('.tweetTime').each(function() {
			var tweetTime = parseInt($(this).find("div").text()); //unixtime = seconds
			$(this).find("b").text(getTimeOfTweet(Math.floor(now - tweetTime)));
		});
	}, 1000);
});

function getTimeOfTweet(seconds) {
	if (seconds == 0) {
		return "now";
	} else if (seconds > 0 && seconds < 60) { // seconds
		return seconds + "s";
	} else if (seconds >= 60 && seconds < 3600) { // minutes
		return Math.floor(seconds / 60) + "m";
	} else if (seconds >= 3600 && seconds < 86400) { // hours
		return Math.floor(seconds / 3600) + "h";
	} else if (seconds >= 86400) { // days
		return Math.floor(seconds / 86400) + "d";
	}
}

function goto(url) {
   $("<a>").attr("href", url).attr("target", "_blank")[0].click();
}

function fetchNewTweets() {
	var params = { "request":"fetchNew" };
	var latestTweetId = null;
	var tweetTable = document.getElementById('mainTweetTable');
	if (tweetTable.rows.length > 0) {
		latestTweetId = tweetTable.rows[0].id;
		params["since"] = latestTweetId;
	}

	$.ajax({
		url: "fetchTweets.php", 
		type: "GET",
		data: params,
		dataType: "text",
		success: function(htmlString) {
			var htmlRows = htmlString.split("<!>");
        	// Loop Each JSON HTML Row And Add To Beginning Backwards
			for (var i = htmlRows.length - 1; i > -1; i--) {
			    var htmlTblRow = htmlRows[i];
			    if (htmlTblRow.length > 0) {
			    	var origDocHeight = $(document).height();
					var origScrollPosition = $(window).scrollTop();
					console.log("origdocheight: " + origDocHeight + " origscrollpos: " + origScrollPosition);

			    	$('#mainTweetTable').prepend(htmlTblRow);
			    	
			    	// Scroll To Original Position
			    	var newScroll = origScrollPosition + ($(document).height() - origDocHeight);
			    	console.log("newscrolltop: " + newScroll);
			    	$(document).scrollTop(newScroll);
			    }
			}
    	}
    });
}