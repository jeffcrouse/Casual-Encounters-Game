/* -------------------------------------------------------------

	A Casual Encounters The Game object
	by Jeff Crouse and Aaron Meyers
	Aug 30 2011
	use wisely
	
------------------------------------------------------------- */


HTMLGame = function(_players, _categories, _cities, _num_rounds)
{
	Game.call(this, _players, _categories, _cities, _num_rounds);
	
	var w = window.innerWidth;
	var h = window.innerHeight;
	
	this.dom_img = document.createElement('img');
	this.dom_img.style.position="absolute";
	
	$("body").append(this.dom_img);
	
	
	// ------------------------------------------
	// Callback that happens at the beginning of a round
	this.start_round_cb = function()
	{
		console.log("HTMLGame.start_round_cb called");
		
		this.dom_img.height = window.innerHeight;
		var ratio = this.dom_img.height / this.image.height;
		this.dom_img.width = this.image.width * ratio;
		this.dom_img.src = this.image.src;
		
		var left = (window.innerWidth/2) - (this.dom_img.width/2);
		
		this.dom_img.style.left = left + 'px';
		this.dom_img.style.top = window.innerHeight + 'px';
	};
	
	// ------------------------------------------
	this.update_cb = function()
	{
		var pct = this.time_remaining / this.round_length;
		var top = window.innerHeight * pct;
	
		this.dom_img.style.top = top + 'px';
	};

	// ------------------------------------------
	// Callback that happens at the end of a round
	this.end_round_cb = function()
	{
		console.log("HTMLGame.end_round_cb called");
		this.dom_img.style.top = '0px';
	};

}

HTMLGame.prototype = new Game();



