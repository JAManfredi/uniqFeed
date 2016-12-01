 <!DOCTYPE html>
  <html>
   <head>
   <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
   <script type="text/javascript" src="uniqFeed.js"></script>
   <link rel="stylesheet" type="text/css" href="uniqFeed.css">
   </head>
   <body>
   	<div id="headerDiv">
   		<span id="titleText">Politics</span>
   		<button id="refreshBtn" onclick="fetchNewTweets();"><img id="refreshImg" src="images/refresh.png" /></button>
   	</div>
   <div id="contentDiv">
   	<?php 
   		include('tweetList.php');
   		loadNewTweetsTable();
   	?>
   </div>
  </body>
</html>