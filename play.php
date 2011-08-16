<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Make an array of non-empty player names
$players = array();
foreach($_REQUEST['players'] as $player)
	if(!empty($player)) 
		$players[] = array('name'=>$player, 'score'=>0, 'has_guessed'=>false);
$num_players = count($players);

$rounds = isset($_REQUEST['rounds']) ? intval($_REQUEST['rounds']) : 10;
if($rounds < 0 || $rounds > 20) $rounds = 10;

// Check for problems
if(count($_REQUEST['categories'])==0)		header("Location: index.php?error=nocats");
if($num_players!=2 && $num_players!=3) 		header("Location: index.php?error=playercount");

$keys_note = strpos($_SERVER['HTTP_USER_AGENT'],'iPad')
	? "using the blue, green, and red buttons"
	: "using the keys in the diagram below";
?>
<!doctype html>
<!--[if lt IE 7 ]> <html lang="en" class="no-js ie6"> <![endif]-->
<!--[if IE 7 ]>    <html lang="en" class="no-js ie7"> <![endif]-->
<!--[if IE 8 ]>    <html lang="en" class="no-js ie8"> <![endif]-->
<!--[if IE 9 ]>    <html lang="en" class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="en" class="no-js"> <!--<![endif]-->
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	
	<title>Casual Encounters: The Game</title>
	<meta name="description" content="">
	<meta name="author" content="Team Casual">
	
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	
	<link rel="shortcut icon" href="favicon.ico">
	<link rel="apple-touch-icon" href="apple-touch-icon.png">
	<link rel="stylesheet" href="css/style.css?v=2">
	<link rel="stylesheet" media="handheld" href="css/handheld.css?v=2">
	<link rel="stylesheet" type="text/css" href="css/custom-theme/jquery-ui-1.8.15.custom.css"  />	
	<link rel="stylesheet" type="text/css" href="css/game.css" />
	
	<script src="js/libs/modernizr-1.7.min.js"></script>
	<style type="text/css">	
		
	</style>
</head>
<body>
	<div id="corner"><a href="index.php"><img src="gs/corner.png" /></a></div>
	<div id="image_holder"><img id="the_image" /></div>
	<div id="container">
		<header>
			<table id="top_bar">
				<tr>
					<?php for($i=0; $i<$num_players; $i++): ?>
					<td id="player-0">
						<span id="player-<?php echo $i; ?>-name"><?php echo $players[$i]['name']; ?></span>  <span id="player-<?php echo $i; ?>-score">0</span>
						<?php if(strpos($_SERVER['HTTP_USER_AGENT'],'iPad')): ?>
						<div id="player-<?php echo $i; ?>-buttons">
							<img src="gs/a.png" onclick="guess(<?php echo $i; ?>, 0);" />
							<img src="gs/b.png" onclick="guess(<?php echo $i; ?>, 1);" />
							<img src="gs/c.png" onclick="guess(<?php echo $i; ?>, 2);" />
						</div>
						<?php endif; ?>
					</td>
					<?php endfor; ?>
				</tr>
				<tr>
					<td id="info_box" colspan="<?php echo $num_players; ?>">
						<div id="round_info"></div>
						<div id="time_display"></div>
					</td>
				</tr>
			</table>
		</header>

		<div id="main" role="main">
			<div id="dialog_begin" class="dialog_boxes">
				<ul>
					<li>When you press "I'm Ready" below, three titles will appear on the bottom of the screen. 
						Each is a title from a Casual Encounters posting. The city and category is shown on the top of the screen.
					</li>
					<li>A timer will begin and an image will begin to appear from the bottom of the screen.  The faster
						you guess the correct headline (<?php echo $keys_note; ?>), the more points you receive.  HOWEVER,
						if you guess incorrectly, you will also lose more points!
						
						<?php if(!strpos($_SERVER['HTTP_USER_AGENT'],'iPad')): ?>
							<div style="text-align: center;">
							<?php if($num_players==2): ?>
							<img src="gs/2-players.png" />
							<?php else: ?>
							<img src="gs/3-players.png" />
							<?php endif; ?>
							</div>
						<?php endif; ?>
						
						</li>
					<li>The player who has the highest score after the specified number of rounds wins!</li>
					<li class="warning">PLEASE NOTE:  This is a very explicit game and is definitely NSFW!</li>
				</ul>
				<!--
				<h2>Player 1:<h2>
				<p>use the 'q' key for the left option, the 'w' key for the middle option, and the 'e' key for the right option.</p>
				<h2>Player 2: </h2>
				<?php if($num_players==3): ?>
				<p>Use the 'c' key for the left option, the 'v' key for the middle option, and the 'b' key for the right option.</p>
				<h2>Player 3: </h2>
				<?php endif;?>
				<p>Use the 'i' key for the left option, the 'o' key for the middle option, and the 'p' key for the right option.</p>
				-->
				<p>Good luck!</p>
			</div>
			<div id="dialog_end" class="dialog_boxes">Congratulations!  Wanna play again?</div>
		</div>

		<footer>
			<table id="answers">
				<tr>
					<td id="answer-0"></td>
					<td id="answer-1"></td>
					<td id="answer-2"></td>
				</tr>
			</table>
		</footer>
	</div>

	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
	<script>!window.jQuery && document.write(unescape('%3Cscript src="js/libs/jquery-1.6.2.min.js"%3E%3C/script%3E'))</script>

	<script type="text/javascript" src="js/libs/jquery-ui-1.8.15.custom.min.js"></script>

	<script>
		
	//
	//	INITIALIZE SOME GLOBAL VARS
	//
	var players = <?php print json_encode($players); ?>
	
	// get the categories from the URL
	var categories = ["<?php echo implode('","', $_REQUEST['categories']); ?>"];
	var category;	// The randomly chosen category
	
	var items = new Array();	// Craigslist pages loaded from the API
	var item_i;					// The randomly chosen index (0-2)
	var image = new Image();	// An image loaded from the random item (items[item_i].image)
	
	// sounds
	var applause, trombone;
	
	var city;					// The city that the current round is from
	var time_left;				// The time remaining in the current round
	var round_length = 10000;	// The duration of a single round in ms
	var tick_interval = 20;		//
	var xhr_ptr;				// ajax pointer
	var interval_ptr;			// tick interval pointer
	var guesses;				// The number of guesses that have been made in the current round
	var num_rounds=<?php print $rounds; ?>;
	var round=0;
	var paused=false;
	//
	//	BODY READY FUNCTION
	//
	$(function() {
		applause = document.createElement('audio');
		applause.setAttribute('src', 'sounds/applause.mp3');
		applause.load()
		
		trombone = document.createElement('audio');
		trombone.setAttribute('src', 'sounds/sad_trombone.mp3' );
		trombone.load();
		
		// Set up the key listener.  
		$(window).keypress( function(e) {
			var character = String.fromCharCode(e.keyCode ? e.keyCode : e.which); 
			console.log("keyPress " + character);
			
			switch(character)
			{
				case ' ':	toggle_paused();break;
				case 'q':	guess(0, 0);	break;
				case 'w': 	guess(0, 1);	break;
				case 'e':	guess(0, 2);	break;
				<?php if($num_players==2): ?>
				case 'i': 	guess(1, 0);	break;
				case 'o': 	guess(1, 1);	break;
				case 'p': 	guess(1, 2);	break;
				<?php else: ?>
				case 'c':	guess(1, 0);	break;
				case 'v': 	guess(1, 1);	break;
				case 'b':	guess(1, 2);	break;
				case 'i': 	guess(2, 0);	break;
				case 'o': 	guess(2, 1);	break;
				case 'p': 	guess(2, 2);	break;
				<?php endif; ?>
			}
		});
	
		// Show the initial dialog box.  Pressing 'I'm Ready!' starts the game.
		$("#dialog_begin").dialog({width: '700px', title: 'How to play', closeOnEscape: false, buttons: [
			{	text: "I'm Ready!",
				click: function() {
					$(this).dialog('close');
					start_round();
				}
			},
			{	text: "No, take me back",
				click: function() { window.location.href = "index.php"; }
			}
		] });
	});
	
	// -----------------------------
	function toggle_paused()
	{
		if(paused)
		{
			interval_ptr = setInterval("tick()", tick_interval);
			paused=false;
		}
		else
		{
			$("#time_display").html( "paused" );
			if(interval_ptr==null) return;
			clearInterval(interval_ptr);
			interval_ptr=null;
			paused=true;
		}
	}
	
	// -----------------------------
	// Loads 3 'items' from api.php into the global 'items' var
	// and then start the tick() interval
	function start_round()
	{
		console.log("start_round()");
		
		// Reset the items array, the guess count, and the css colors
		reset_round();
	
		// Pick a new category and put it into the info div
		category = categories[Math.floor(Math.random()*categories.length)];
		$("#round_info").html("Loading "+category+' <img src="gs/ajax-loader.gif" />');
		
		// Make the call to the API
		xhr_ptr = $.ajax({
			url: "api.php",
			dataType: 'json',
			data: {'query': category},
			success: function(response){
			
				// If we get a bad response, wait a second and try to load again
				if(response.error!=undefined||response.length!=3)
				{
					console.log("error from api. '"+response.error+"' trying again");
					setTimeout("start_round()", 1000);
					return;
				}
				else 
				{	
					// Save the response array to the global 'items'
					items = response;
					city = items[0].city;	// they all come from the same city, so just take the first
					console.log("api success: "+items.length+" items");


					// Choose a random item from the array
					// keep the index of the chosen item so that we can 
					// tell if a player chose the correct answer later
					item_i = Math.floor(Math.random()*3);
					console.log("correct answer is: "+item_i);
					var src = items[item_i].image;
	
					// Load the random image into the JS object 'image'
					console.log("Loading image "+src);
					$(image).load(function() {	// Start the round if it loads successfully
						console.log("success loading "+this.src);
						
						// get rid of any 'load' function that has been bound
						// to the $(image) in previous rounds
						$(this).unbind('load');
						
						// The main thing that happens to kick off the round is to start the tick()
						// function, so only start it if there is currently no tick interval
						if(interval_ptr==null)
						{
							// Loop through the items array and put the titles into the correct div
							for(i=0; i<items.length; i++) 
								$("#answer-"+i).html(items[i].title);
				
							// Cook up some CSS for the image
							var height = $(window).height();
							var width = ($(window).height()/this.height) * this.width;
							var margin_left = Math.ceil( ($(window).width()/2) - (width/2) );	// center the image
							var css = {'height': height, 'width': width, 'margin-left': margin_left, 'margin-top': '100%'};
							
							// Put the image into the <img>
							$("#the_image").css(css).attr('src', this.src);
							$("#round_info").html(category+" - "+city);
		
							console.log("setting interval");
							time_left = round_length;
							interval_ptr = setInterval("tick()", tick_interval);
							round++;
						}
						else
						{
							console.log("ERROR:  trying to start round while one is already running.");
						}	
					}).error(function() { 		// Try again if $(image) didn't load properly
					
						console.log("error loading an image. trying again");
						setTimeout("start_round()", 1000);
						
					}).attr('src', src);	
				}
			}
		});
	}

	// -----------------------------
	// This function is called every tick_interval millis
	// and is started in start_round()
	function tick()
	{
		time_left -= tick_interval;
		
		// TO DO:  I don't know why this has to be 70...
		// Otherwise, it takes a while to reach the screen in Firefox
		var top = Math.ceil((time_left / round_length) * 70 );

		$("#the_image").css('margin-top', top+"%");
		$("#time_display").html( round+" / "+num_rounds+" - " + Math.ceil(time_left / 100));
		
		if(time_left<=0) 
		{
			console.log("time is up");
			end_round();
		}
	}
	
	// -----------------------------
	// Moves the image fully into place and stops the timer
	// Also starts a new round if needed, or ends the game
	// called from tick() and the key listener
	function end_round()
	{
		console.log("end_round()");
		time_left=0;
		$("#the_image").css('margin-top', "0%");
		clearInterval(interval_ptr);
		interval_ptr=null;
		
		if(round < num_rounds)
		{
			console.log("starting new round in 2 seconds");
			setTimeout('start_round()', 2000);
		}
		else end_game();
	}

	// -----------------------------
	// Called from start_round() and reset_game()
	function reset_round()
	{
		console.log("reset_round()");
		
		guesses=0;
		items.length=0;
		for(i in players)
		{
			$("#player-"+i+"-name").css('color', 'white');
			players[i].has_guessed =false;
		}
		for(i=0; i<3; i++)
		{
			$('#answer-'+i).css('color', 'white');
		}
	}
	
	// -----------------------------
	// called from end_round()
	function end_game()
	{
		console.log("end_game()");
		
		var winner = get_winner();
		var title = (winner==null)
			? "Really? No score?"
			: winner.name+" wins!";

		$("#dialog_end").dialog({width: '40%', title: title, closeOnEscape: false, buttons: [
			{	text: "Play Again",
				click: function() {
					$(this).dialog('close');
					for(i in players)
					{
						players[i].score = 0;
						$("#player-"+i+"-score").html(0);
					}
					round=0;
					start_round();
				}
			},
			{	text: "Back to Home Screen",
				click: function() { window.location.href = "index.php"; }
			}
		] });
	}
	
	
	// -----------------------------
	// called from end_game()
	function get_winner()
	{
		var total=players[0].score;
		var best_score=players[0].score;
		var winner=0;
		
		for(i=1; i<players.length; i++)
		{
			total += players[i].score;
			if(players[i].score > best_score) 
			{
				best_score=players[i].score;
				winner=i;
			}
		}
		if(total==0) 
			return null;
		else 
			return players[winner];
	}
	
	
	// -----------------------------
	// p = player index, i = the guess
	// called from the key listener
	function guess(p, i)
	{
		console.log("guess("+p+", "+i+")");
		
		// if the game is paused, or the player has already guessed in this round, 
		// or if a round isn't running, ignore
		if(paused||players[p].has_guessed||interval_ptr==null) return;
		if(time_left<=0||guesses>=players.length) alert("ERROR! SANITY IS BROKEN!");
		
		players[p].has_guessed = true;
		
		if(item_i==i)	// correct guess
		{
			players[p].score += time_left/10;
			console.log("player "+p+" correct. score is now "+players[p].score);
			$("#player-"+p+"-name").css('color', 'green');
			$("#player-"+p+"-score").html( players[p].score );
			$('#answer-'+i).css('color', 'green');
			applause.play();
			end_round();
		}
		else			// incorrect guess
		{
			players[p].score -= time_left/20;
			console.log("player "+p+" wrong.  score is now "+players[p].score);
			$("#player-"+p+"-name").css('color', 'red');
			$("#player-"+p+"-score").html( players[p].score );
			$('#answer-'+i).css('color', 'red');
			trombone.play();
		}
		
		// If the player guessed correctly, or if both players have guessed, end the round.
		guesses++;
		console.log("guesses="+guesses);
		if(guesses>=players.length) end_round();
	}

	

	</script>
	
	<!--[if lt IE 7 ]>
	<script src="js/libs/dd_belatedpng.js"></script>
	<script> DD_belatedPNG.fix('img, .png_bg');</script>
	<![endif]-->

	<script>
		var _gaq=[['_setAccount','UA-74771-20'],['_trackPageview']];
		(function(d,t){var g=d.createElement(t),s=d.getElementsByTagName(t)[0];g.async=1;
		g.src=('https:'==location.protocol?'//ssl':'//www')+'.google-analytics.com/ga.js';
		s.parentNode.insertBefore(g,s)}(document,'script'));
	</script>
</body>
</html>