/* -------------------------------------------------------------

	A Casual Encounters The Game object
	by Jeff Crouse and Aaron Meyers
	Aug 30 2011
	use wisely
	
------------------------------------------------------------- */


CanvasGame = function(_players, _categories, _cities, _num_rounds)
{
	Game.call(this, _players, _categories, _cities, _num_rounds);
	
	var w = window.innerWidth;
	var h = window.innerHeight;
	
	this.canvas = document.createElement('canvas');
	this.canvas.height=w;
	this.canvas.width=h;
	
	$("body").append(this.canvas);
	

	function sketchProc(processing)
	{
		processing.draw = game.draw;
	}

	this.processing = new Processing(this.canvas, sketchProc); 

	// ------------------------------------------
	// Callback that happens at the beginning of a round
	this.start_round_cb = function()
	{
		console.log("CanvasGame.start_round_cb called");
	};
	
	// ------------------------------------------
	this.draw = function()
	{
		// determine center and max clock arm length
		var centerX = processing.width / 2, centerY = processing.height / 2;
		var maxArmLength = Math.min(centerX, centerY);
	
		function drawArm(position, lengthScale, weight)
		{
		  	processing.strokeWeight(weight);
		  	processing.line(centerX, centerY,
			centerX + Math.sin(position * 2 * Math.PI) * lengthScale * maxArmLength,
			centerY - Math.cos(position * 2 * Math.PI) * lengthScale * maxArmLength);
		}
	
		// erase background
		processing.background(224);
	
		var now = new Date();
	
		// Moving hours arm by small increments
		var hoursPosition = (now.getHours() % 12 + now.getMinutes() / 60) / 12;
		drawArm(hoursPosition, 0.5, 5);
	
		// Moving minutes arm by small increments
		var minutesPosition = (now.getMinutes() + now.getSeconds() / 60) / 60;
		drawArm(minutesPosition, 0.80, 3);
	
		// Moving hour arm by second increments
		var secondsPosition = now.getSeconds() / 60;
		drawArm(secondsPosition, 0.90, 1);
	};
	
	// ------------------------------------------
	// Callback that happens at the end of a round
	this.end_round_cb = function()
	{
		console.log("CanvasGame.end_round_cb called");
	};
	
}

CanvasGame.prototype = new Game();
