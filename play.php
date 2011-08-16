<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Make an array of non-empty player names
$players = array();
foreach($_REQUEST['players'] as $player)
	if(!empty($player)) 
		$players[] = array('name'=>$player, 'score'=>0, 'has_guessed'=>false);
$num_players = count($players);

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
	<script type="text/javascript" src="js/game.js"></script>

	<script>
	$(function() {
				
		// Round up some vars to initlaize the game!
		var players = <?php print json_encode($players); ?>;
		var cats = ["<?php echo implode('","', $_REQUEST['categories']); ?>"];
		var num_rounds = <?php print (!isset($_REQUEST['rounds'])||$_REQUEST['rounds']>20)?10:intval($_REQUEST['rounds']); ?>;
		
		// Attach the key listener for this window to the game
		// 'game' is defined in game.js
		$(window).keypress( game.key_pressed );
		
		// Initialize the game with players, categories, rounds, and the end game callback
		game.init(players, cats, num_rounds, end_game);
		
		begin_game();

	});
	
	// --------------------------
	function begin_game()
	{
		// Show the initial dialog box.  Pressing 'I'm Ready!' starts the game.
		$("#dialog_begin").dialog({width: '700px', title: 'How to play', closeOnEscape: false, buttons: [
			{	text: "I'm Ready!",
				click: function() { game.start_round(); }
			},
			{	text: "No, take me back",
				click: function() { window.location.href = "index.php"; }
			}
		] });
	}
	
	// --------------------------
	function end_game(winner)
	{
		var title = (winner==null) ? "Really? No score?" : winner.name+" wins!";
		$("#dialog_end").dialog({width: '40%', title: title, closeOnEscape: false, buttons: [
			{	text: "Play Again",
				click:  function() { game.reset_game(); }
			},
			{	text: "Back to Home Screen",
				click: function() { window.location.href = "index.php"; }
			}
		] });
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