<?php
require_once 'common.php';

// SANITIZE INPUT

// Make the players array
$players = array();
foreach($_REQUEST['players'] as $player)
	if(!empty($player)) $players[] = substr($player, 0, 10);
$num_players = count($players);

// Determine how many rounds to play
$num_rounds = (!isset($_REQUEST['rounds']) || abs(intval($_REQUEST['rounds'])) > 20)
	? 10
	: abs(intval($_REQUEST['rounds']));

// Determine which categories to use
$categories = array();
foreach($_REQUEST['categories'] as $cat)
	if(in_array($cat, $_CATEGORIES)) $categories[] = $cat;

// Determine which cities to use
$cities = array();
foreach($_REQUEST['cities'] as $city)
	if(in_array($city, $_CITIES)) $cities[] = $city;

// Check for problems
if(count($categories)==0)					header("Location: index.php?error=nocats");
if(count($cities)<2)						header("Location: index.php?error=nocities");
if($num_players<1||$num_players>3) 			header("Location: index.php?error=playercount");

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

	<div id="corner"><a href="index.php" title="Casual Encounters Game"><img src="gs/corner.png" /></a></div>
	
	<div id="container">
	
		<div id="time_bar"></div>
		
		<!-- TOP BAR BEGIN -->
		<table id="top_bar">
			<tr>
				<?php for($i=0; $i<$num_players; $i++): ?>
				<td id="player-<?php echo $i; ?>">
					<span id="player-<?php echo $i; ?>-name"><?php echo $players[$i]; ?></span>  
					<span id="player-<?php echo $i; ?>-score">0</span>
					
					
					<?php if(is_mobile_device()): ?>
					<div id="player-<?php echo $i; ?>-buttons">
						<img src="gs/a.png" onclick="game.guess(<?php echo $i; ?>, 0);" />
						<img src="gs/b.png" onclick="game.guess(<?php echo $i; ?>, 1);" />
						<img src="gs/c.png" onclick="game.guess(<?php echo $i; ?>, 2);" />
					</div>
					<?php endif; ?>
					
				</td>
				<?php endfor; ?>
			</tr>
			<tr>
				<td id="info_box" colspan="<?php echo $num_players; ?>">
					<div id="round_info"></div>
				</td>
			</tr>
		</table>
		<!-- TOP BAR END -->

		<!-- ANSWERS TABLE BEGIN -->
		<table id="answers">
			<tr>
				<td id="answer-0" class="answer"></td>
				<td id="answer-1" class="answer"></td>
				<td id="answer-2" class="answer"></td>
			</tr>
		</table>
		<!-- ANSWERS TABLE END -->

	</div>

	<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
	<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js"></script>
	<script type="text/javascript" src="js/utils.js"></script>
	<script type="text/javascript" src="js/Detector.js"></script> 
	<script type="text/javascript" src="js/RequestAnimationFrame.js"></script> 
	<script type="text/javascript" src="js/Stats.js"></script> 
	<script type="text/javascript" src="js/Game.js"></script>
	
    <script>
		// vaaaars!
		var back_button = {text: "No, take me back", click: function() { window.location.href = "index.php"; }};
		var players = ["<?php echo implode('","', $players); ?>"];
		var cats = ["<?php echo implode('","', $categories); ?>"];
		var cities = ["<?php echo implode('","', $cities); ?>"];
		var num_rounds = <?php print $num_rounds;  ?>;
		
		// Our game object
		var game;
		
		
		// TO DO:  replace wth http://headjs.com ???
		// Figure out what subclass of CASUAL.Game object to use using the Detector
		// Then load the JS files that are needed and construct a game object
		if ( false) //Detector.webgl ) 
		{
			$.getScript('js/three.js/build/Three.js', function(){
			$.getScript('js/WebGLGame.js', function(){
				game = new WebGLGame(players, cats, cities, num_rounds);
				game.end_game_cb = end_game;
				game.gl_animate();
				console.log( game );
			}).error(function(){alert("error loading WebGLGame.js");});
			}).error(function(){alert("error loading Three.js");});
		}
		else if( Detector.canvas )
		{
			$.getScript('js/processing-1.3.0.min.js', function(){
			$.getScript('js/CanvasGame.js', function(){
				game = new CanvasGame(players, cats, cities, num_rounds);
				game.end_game_cb = end_game;
				console.log( game );
			}).error(function(){alert("error loading CanvasGame.js");});
			}).error(function(){alert("error loading processing-js");});			
		}
		else
		{
			$.getScript('js/HTMLGame.js', function(){
				game = new HTMLGame(players, cats, cities, num_rounds);
				game.end_game_cb = end_game;
				console.log( game );
			}).error(function(){alert("error loading HTMLGame");});
		}
		
		
		// Deal with some dom events
		$(window).resize( function() {  });
		$(window).keypress( function(e){ game.key_pressed(e); } );
		
		
		$(document).ready(function() {
			// Show the initial dialog box.  Pressing 'I'm Ready!' starts the game.
			$("#dialog_begin").dialog({width: '700px', title:'How to play', closeOnEscape:false, buttons: [
				{	text: "I'm Ready!",
					click: function() {
						game.start_round();
						$(this).dialog('close');
					}
				},
				back_button
			] });
		});
		
		// --------------------------
		// Called every time a game ends
		// TO DO: add "tweet this game" button to the dialog
		function end_game(winner)
		{
			var t = (winner==null) ? "Really? No score?" : winner.name+" wins!";
			$("#dialog_end").dialog({width:'40%', title:t, closeOnEscape:false, buttons: [
				{	text: "Play Again",
					click:  function() { 
						$(this).dialog('close');
						game.reset_game();
					}
				},
				back_button
			] });
		}
		
    </script>
	
	<!--[if lt IE 7 ]>
	<script src="js/libs/dd_belatedpng.js"></script>
	<script> DD_belatedPNG.fix('img, .png_bg');</script>
	<![endif]-->

	
	
	<div id="dialog_begin" class="dialog_boxes">
		<ul>
			<li>When you press "I'm Ready" below, three titles will appear on the bottom of the screen. 
				Each is a title from a Casual Encounters posting. The city and category is shown on the top of the screen.
			</li>
			<li>
				A timer will begin and an image will begin to appear from the bottom of the screen. 
				
				<?php if(strpos($_SERVER['HTTP_USER_AGENT'],'iPad')): ?>
				
					The faster you guess the correct headline using the blue, green, and red buttons,
					the more points you receive.  
				
				<?php elseif($num_players==1): ?>
				
					The faster you guess the correct headline by clicking on the correct title, 
					the more points you receive.  
				
				<?php else: ?>
				
					The faster you guess the correct headline using the keys in the diagram below, 
					the more points you receive.  
				
					<div style="text-align: center;">
					<?php if($num_players==2): ?>
						<img src="gs/2-players.png" />
					<?php else: ?>
						<img src="gs/3-players.png" />
					<?php endif; ?>
					</div>
				
				<?php endif; ?>
				
			</li>
			<?php if($num_players>1): ?>
			<li>The player who has the highest score after the specified number of rounds wins!</li>
			<?php endif; ?>
			<li class="warning">PLEASE NOTE:  This is a very explicit game and is definitely NSFW!</li>
		</ul>
		<p>Good luck!</p>
	</div>
	
	
	<div id="dialog_end" class="dialog_boxes">Congratulations!  Wanna play again?</div>

	
	
	<script>
		var _gaq=[['_setAccount','UA-74771-20'],['_trackPageview']];
		(function(d,t){var g=d.createElement(t),s=d.getElementsByTagName(t)[0];g.async=1;
		g.src=('https:'==location.protocol?'//ssl':'//www')+'.google-analytics.com/ga.js';
		s.parentNode.insertBefore(g,s)}(document,'script'));
	</script>
	
	
</body>
</html>