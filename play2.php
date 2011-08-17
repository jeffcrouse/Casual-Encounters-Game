<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Make an array of non-empty player names
$players = array();
foreach($_REQUEST['players'] as $player)
	if(!empty($player)) 
		$players[] = array('name'=>substr($player, 0, 10), 'score'=>0, 'has_guessed'=>false);
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

	<script src="js/libs/modernizr-1.7.min.js"></script>
	<style type="text/css">	
	html {
		overflow: hidden;
	}
	#targetcanvas {
		width: 100%;
		height: 100%;
	}
	#corner {
		position: absolute;
		top: 0;
		left: 0;
		z-index: 10;
	}	
	</style>
</head>
<body>

	<div id="corner"><a href="index.php"><img src="gs/corner.png" /></a></div>
	<div id="container">
		<header>

		</header>

		<div id="main" role="main">
			<canvas id="targetcanvas"></canvas>
		</div>

		<footer>

		</footer>
	</div>

	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
	<script>!window.jQuery && document.write(unescape('%3Cscript src="js/libs/jquery-1.6.2.min.js"%3E%3C/script%3E'))</script>

	<script type="text/javascript" src="js/libs/jquery-ui-1.8.15.custom.min.js"></script>
	<script type="text/javascript" src="js/processing-1.2.3.min.js"></script>
	<script type="text/processing" data-processing-target="targetcanvas">

		// Round up some vars to initlaize the game!
		var players = <?php print json_encode($players); ?>;
		var cats = ["<?php echo implode('","', $_REQUEST['categories']); ?>"];
		var cities = ["<?php echo implode('","', $_REQUEST['cities']); ?>"];
		var num_rounds = <?php print (!isset($_REQUEST['rounds'])||abs($_REQUEST['rounds'])>20)?10:abs(intval($_REQUEST['rounds'])); ?>;
		
		void setup()
		{
			$('canvas').width( $(window).width() );
			$('canvas').height( min($(document).height(), $(window).height()) );
			size($(window).width(), $('canvas').height());
			
			
			stroke(0);
			fill(0);
			textFont(createFont("Arial", 12));
			frameRate(12);
		}

		void draw()
		{
			background(#000000);
			
			fill(255, 255, 255);
			String textstring = "header example";
			float twidth = textWidth(textstring);
			text(textstring, (width-twidth)/2, height/2);
			
			text(frameRate, width-100, 20);
		}
    </script>    
	<script>
	$(function() {
				

		$(window).resize(function() {
		
		
		});

	});

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