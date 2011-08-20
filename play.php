<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Make an array of non-empty player names
$players = array();
foreach($_REQUEST['players'] as $player)
	if(!empty($player)) $players[] = substr($player, 0, 10);
	
$num_players = count($players);

// Check for problems
if(count($_REQUEST['categories'])==0)		header("Location: index.php?error=nocats");
if(count($_REQUEST['cities'])<2)			header("Location: index.php?error=nocities");
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
		table {
			width: 100%		
		}
		td {
			text-align: center;
		}
		#corner {
			position: absolute;
			top: 0;
			left: 0;
			z-index: 10;
		}
		#game_canvas {
			width: 100%;
			height: 100%;
			z-index: -1;
		}
	</style>
</head>
<body>

	<div id="corner"><a href="index.php"><img src="gs/corner.png" /></a></div>
	
	<!--<div id="image_holder"><img id="the_image" /></div>-->
	
	<div id="container">
		<!--<canvas id="game_canvas"></canvas>-->
		<header>
			<table id="top_bar">
				<tr>
					<?php for($i=0; $i<$num_players; $i++): ?>
					<td id="player-0">
						<span id="player-<?php echo $i; ?>-name"><?php echo $players[$i]; ?></span>  <span id="player-<?php echo $i; ?>-score">0</span>
						<?php if(strpos($_SERVER['HTTP_USER_AGENT'],'iPad')): ?>
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
						<!--<div id="time_display"></div>-->
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

		</div>

		<footer>
			<table id="answers">
				<tr>
					<td id="answer-0" class="answer"></td>
					<td id="answer-1" class="answer"></td>
					<td id="answer-2" class="answer"></td>
				</tr>
			</table>
		</footer>
	</div>

	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
	<script>!window.jQuery && document.write(unescape('%3Cscript src="js/libs/jquery-1.6.2.min.js"%3E%3C/script%3E'))</script>

	<script type="text/javascript" src="js/libs/jquery-ui-1.8.15.custom.min.js"></script>
	<script type="text/javascript" src="js/game.js"></script>
	<script type="text/javascript" src="js/three/Three.js"></script>
	<script type="text/javascript" src="js/Detector.js"></script> 
	<script type="text/javascript" src="js/RequestAnimationFrame.js"></script> 
	<script type="text/javascript" src="js/Stats.js"></script> 
	
    <script>
		if ( ! Detector.webgl ) Detector.addGetWebGLMessage();

		// Initialize the game with players, cities, categories, rounds, and the end game callback
		var players = ["<?php echo implode('","', $players); ?>"];
		var cats = ["<?php echo implode('","', $_REQUEST['categories']); ?>"];
		var cities = ["<?php echo implode('","', $_REQUEST['cities']); ?>"];
		var num_rounds = <?php print (!isset($_REQUEST['rounds'])||abs($_REQUEST['rounds'])>20)?10:abs(intval($_REQUEST['rounds'])); ?>;
		var statsEnabled = true;
		var camera, scene, renderer, stats;
		
		// Deal with some dom events
		$(window).resize( function() { init(); });
		$(window).keypress( function(e){ game.key_pressed(e); } );
		$(document).ready(function() {
			game.init(players, cats, cities, num_rounds);

			// Show the initial dialog box.  Pressing 'I'm Ready!' starts the game.
			$("#dialog_begin").dialog({width: '700px', title: 'How to play', closeOnEscape: false, buttons: [
				{	text: "I'm Ready!",
					click: function() {
						$(this).dialog('close');
						game.start_round();
					}
				},
				{	text: "No, take me back",
					click: function() { window.location.href = "index.php"; }
				}
			] });
			
			init();
			animate();
		});
	
		
		// --------------------------
		// Called every time a round is about to start
		// img_src is the pre-loaded URL of the image for the round
		game.round_start_cb = function(img_source) 
		{
		
		}
		
		// --------------------------
		// Called every time a round ends.
		game.round_end_cb = function() 
		{
		
		}
		
		// --------------------------
		// Called every time a game ends
		// TO DO: add "tweet this game" button to the dialog
		game.end_game_cb = function(winner)
		{
			var title = (winner==null) ? "Really? No score?" : winner.name+" wins!";
			$("#dialog_end").dialog({width: '40%', title: title, closeOnEscape: false, buttons: [
				{	text: "Play Again",
					click:  function() { 
						$(this).dialog('close');
						game.reset_game();
					}
				},
				{	text: "Back to Home Screen",
					click: function() { window.location.href = "index.php"; }
				}
			] });
		}
		
		// --------------------------
		function init()
		{ 
			// Camera params : 
			// field of view, aspect ratio for render output, near and far clipping plane. 
			camera = new THREE.Camera(80, w / h, 1, 4000 );
			scene = new THREE.Scene();
			renderer = new THREE.WebGLRenderer();
			resize();
			
			document.body.appendChild( renderer.domElement );
			
			if ( statsEnabled ) 
			{
				stats = new Stats();
				stats.domElement.style.position = 'absolute';
				stats.domElement.style.top = '0px';
				stats.domElement.style.zIndex = 100;
				container.appendChild( stats.domElement );
			}
		}
		
		// --------------------------
		function resize()
		{
			var w = $(window).width();
			var h = Math.max( $(document).height(), $(window).height() );
			renderer.setSize( w, h );
			camera = new THREE.Camera(80, w / h, 1, 4000 );
		}
		
		// --------------------------
		function animate() 
		{
			requestAnimationFrame( animate );
			render();
			if ( statsEnabled ) stats.update();
		}
 
 		// --------------------------
		function render()
		{
			game.update();	// should I make this its own thread/interval instead of depending on the WebGL renderer?
			
			// How much of the round is left?
			var pct = game.time_remaining / game.round_length;
		
		
			renderer.render( scene, camera );
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