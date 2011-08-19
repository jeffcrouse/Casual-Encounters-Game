/* -------------------------------------------------------------

	A Casual Encounters The Game object
	by Jeff Crouse and Aaron Meyers
	Aug 16 2011
	use wisely
	
------------------------------------------------------------- */

var game =
{	
	players: 		[],				// An array of player objects
	
	categories: 	[],				// An array of strings (m4m, w4m, etc)
	category:		null,			// The randomly chosen category
	
	cities:			[],				// An array of all cities
	city: 			null,			// The city that the current round is from
	
	items: 			[],				// Craigslist pages loaded from the API
	item_i:			null,			// The randomly chosen index (0-2)
	image: 			new Image(),	// An image loaded from the random item (items[item_i].image)
	pimage:			null,
	
	// sounds
	applause: 		null,
	trombone: 		null,
	
	time_remaining: 0,				// The time remaining in the current round
	round_length: 	20,				// The duration of a single round in seconds
	xhr_ptr: 		null,			// ajax pointer
	guesses: 		0,				// The number of guesses that have been made in the current round
	num_rounds: 	0,				// Total number of founds
	round: 			0,				// The current round
	paused: 		false,			// Whether the game is currently paused
	
	end_game_callback: 	null,

	reveal_modes:	['move_up', 'fade'],	// Implemented in draw()
	reveal_mode:	null,
	
	p5: null,						// The processing instance that we will use to draw 'n stuff

	// ------------------------------------------
	init: function(_players, _categories, _cities, _num_rounds, _p5, _end_game_callback)
	{	
		for(i in _players)
			this.players.push({name: _players[i], score: 0, has_guessed: false});
		this.categories = _categories;
		this.cities = _cities;
		this.p5 = _p5;
		this.num_rounds = _num_rounds;
		this.end_game_callback = _end_game_callback;
		
		this.applause = this.make_sound("applause");
		this.trombone = this.make_sound("sad_trombone");

		// if there is only one player, activate 'click' mode
		if(this.players.length==1)
		{
			$("#answer-0").click(function(){ game.guess(0, 0); });
			$("#answer-1").click(function(){ game.guess(0, 1); });
			$("#answer-2").click(function(){ game.guess(0, 2); });
		}
		
		// The game assumes that there some divs on the page
		var required_divs = new Array("#round_info", "#answer-1", "#answer-2", "#answer-3");
		for(i in this.players)
		{
			required_divs.push( "#player-"+i+"-name" );
			required_divs.push( "#player-"+i+"-score" );
		}
		
		// TO DO: Test if the required divs exist here			
	},
	
	
	// ------------------------------------------
	make_sound: function(name)
	{
		var audio = document.createElement("audio");
		var source = document.createElement('source');
		if (audio.canPlayType('audio/mpeg;')) {
			source.type= 'audio/mpeg';
			source.src= 'sounds/'+name+'.mp3';
		} else {
			source.type= 'audio/ogg';
			source.src= 'sounds/'+name+'.ogg';
		}
		audio.appendChild(source);
		audio.load();
		return audio;
	},
	
	// ------------------------------------------
	update: function(frameRate)
	{
		if(this.paused || this.time_remaining<=0) return;

		this.time_remaining -= (frameRate / 3600);
		
		if(this.time_remaining<=0) 
		{
			console.log("time is up");
			this.end_round();
		}
	},
	
	// ------------------------------------------
	draw: function()
	{
		var pct = this.time_remaining / this.round_length;
	
		if(this.image.width>0)
		{
			// Calculate the size and position of the image (center it)
			var img_h = this.p5.height;
			var ratio = this.p5.height / this.image.height;
			var img_w = this.image.width * ratio;
			var x_pos = (this.p5.width/2)  - (img_w/2);
			
	
			// Draw the image based on the reveal mode.
			switch(this.reveal_mode)
			{
				case 'move_up':
					var y_pos = this.p5.height * pct;
					this.p5.image(this.pimage, x_pos, y_pos, img_w, img_h);
					break;
	
				case 'fade':
					
					this.p5.tint(50.0, 50.0, 50.0);
					this.p5.image(this.pimage, x_pos, 0, img_w, img_h);
					this.p5.noTint();
					break;
				/*
				case 'blocks':
					var i=0;
					var x_inc = this.image.width / 30;
					var y_inc = this.image.height / 30;
					for(var y=0; y<this.image.height; y+=y_inc)
					{
						for(var x=0; x<this.image.width; x+=x_inc)
						{
							if(pct < i / 900) 
							{
								var dx = Math.floor(x*ratio)+x_pos;
								var dy = Math.floor(y*ratio);
								var dw = Math.floor(x_inc*ratio);
								var dh = Math.floor(y_inc*ratio);
								this.ctx.drawImage(this.image, x, y, x_inc, y_inc, dx, dy, dw, dh);
							}
							i++;
						}
					}
					break;
				*/
			}
		}
		
		this.p5.fill(255, 0, 0);
		this.p5.rect(0, 0, this.p5.width * pct, 20);

		//$("#the_image").css('margin-top', top+"%");
		//$("#time_display").html( Math.ceil(this.time_remaining / 100));
	},
	
	// ------------------------------------------
	key_pressed: function( e ) 
	{
		var character = String.fromCharCode(e.keyCode ? e.keyCode : e.which);
		console.log("keyPress " + character);
		
		if(character==' ')
			this.toggle_paused();
		
		if(this.players.length==1) return;
		
		if(this.players.length==2) switch(character)
		{
			case 'q':	this.guess(0, 0);	break;
			case 'w': 	this.guess(0, 1);	break;
			case 'e':	this.guess(0, 2);	break;
			case 'i': 	this.guess(1, 0);	break;
			case 'o': 	this.guess(1, 1);	break;
			case 'p': 	this.guess(1, 2);	break;
		}

		if(this.players.length==3) switch(character)
		{
			case 'q':	this.guess(0, 0);	break;
			case 'w': 	this.guess(0, 1);	break;
			case 'e':	this.guess(0, 2);	break;
			case 'c':	this.guess(1, 0);	break;
			case 'v': 	this.guess(1, 1);	break;
			case 'b':	this.guess(1, 2);	break;
			case 'i': 	this.guess(2, 0);	break;
			case 'o': 	this.guess(2, 1);	break;
			case 'p': 	this.guess(2, 2);	break;
		}
	},
	
	
	// ------------------------------------------
	toggle_paused: function()
	{
		console.log("toggle_paused()");
		this.paused = !this.paused;
		if(this.paused) $("#round_info").html( "paused" );		
		else $("#round_info").html("round "+this.round+" / "+this.num_rounds+": "+this.category+" - "+this.city);
	},
	
	
	// ------------------------------------------
	// Loads 3 'items' from api.php into 'items' var
	// start_round() -> ajax_success() -> image_loaded()
	start_round: function()
	{
		console.log("start_round()");
		
		// Reset the items array, the guess count, and the css colors
		this.reset_round();
	
		// Pick a new category
		var i = Math.floor( Math.random() * this.categories.length );
		this.category = this.categories[i];		
		
		i = Math.floor( Math.random() * this.reveal_modes.length );
		this.reveal_mode = this.reveal_modes[i];
		console.log("reveal mode: "+this.reveal_mode);
		
		$("#round_info").html("Loading "+this.category+' <img src="gs/ajax-loader.gif" />');
		
		// Make the call to the API
		this.xhr_ptr = $.ajax({
			url: "api.php",
			dataType: 'json',
			data: {'query': this.category, 'cities': this.cities },
			success: function(response) { game.ajax_success(response); }
		});
	},


	// ------------------------------------------
	// Parse response, set city, category, choose the correct answer,
	// load the image from that answer.
	// called from start_round()
	ajax_success: function(response)
	{
		// If we get a bad response, wait a second and try to load again
		if(response.error!=undefined || response.length!=3)
		{
			console.log("error from api. '"+response.error+"' trying again");
			setTimeout("game.start_round()", 1000);
			return;
		}
		else 
		{	
			// Save the response array to member var 'items'
			this.items = response;
			this.city = this.items[0].city;	// they all come from the same city, so just take the first
			console.log("api success: "+this.items.length+" items");

			// Choose a random item from the array
			// keep the index of the chosen item so that we can 
			// tell if a player chose the correct answer later
			this.item_i = Math.floor(Math.random()*3);
			console.log("correct answer is: "+this.item_i);
			
			this.image = new Image();
			this.image.src = this.items[this.item_i].image;

			// Load the random image into the JS object 'image'
			console.log("Loading image "+this.image.src);
			
			$(this.image).load(function(){
			
				game.image_loaded();
				
			}).error(function() { 		
				// Try again if $(image) didn't load properly
				console.log("error loading an image. trying again");
				setTimeout("game.start_round()", 1000);
			});	
		}
	},


	// ------------------------------------------
	// called from ajax_success() when image has loaded successfully
	// This finally kicks off the round
	image_loaded: function()
	{
		console.log("success loading "+this.image.src);

		this.pimage = this.p5.loadImage( this.image.src );

		// get rid of any 'load' function that has been bound
		// to the $(image) in previous rounds
		$(this.image).unbind('load');

		this.round++;
		
		// Fill the divs
		$("#round_info").html("round "+this.round+" / "+this.num_rounds+": "+this.category+" - "+this.city);
		for(var i=0; i<this.items.length; i++) 
			$("#answer-"+i).html(this.items[i].title);

		/*
		// Cook up some CSS for the image
		var height = $(window).height();
		var width = ($(window).height()/this.image.height) * this.image.width;
		var margin_left = Math.ceil( ($(window).width()/2) - (width/2) );	// center the image
		var css = {'height': height, 'width': width, 'margin-left': margin_left, 'margin-top': '100%'};
		
		// Put the image into the <img>
		$("#the_image").css(css).attr('src', this.image.src);
		*/
		
		console.log("setting time_remaining to "+this.round_length);
		this.time_remaining = this.round_length;
	},

	// ------------------------------------------
    easeOutCubic: function(value, min, max, d)
	{
		return max * ((value = value / d - 1) * value * value + 1) + min;
	},
        
	
	
	// ------------------------------------------
	// Resets player attributes and HTML stuff on page
	// Called from start_round() and reset_game()
	reset_round: function()
	{
		console.log("reset_round()");
		
		this.guesses=0;
		this.items.length=0;
		for(i in this.players)
		{
			$("#player-"+i+"-name").css('color', 'white');
			this.players[i].has_guessed = false;
		}
		for(i=0; i<3; i++)
		{
			$('#answer-'+i).css('color', 'white');
		}
	},

	
	// ------------------------------------------
	// Moves the image fully into place and stops the timer
	// Also starts a new round if needed, or ends the game
	// called from tick() and the key listener
	end_round: function()
	{
		console.log("end_round()");
		this.time_remaining=0;

		if(this.round < this.num_rounds)
		{
			console.log("starting new round in 2 seconds");
			setTimeout('game.start_round()', 5000);
		}
		else this.end_game();
	},


	
	// ------------------------------------------
	// called from end_round()
	end_game: function()
	{
		console.log("end_game()");
		
		var winner = this.get_winner();
		this.end_game_callback( this.get_winner() );
	},
	
	
	// ------------------------------------------
	reset_game: function()
	{
		for(i in game.players)
		{
			game.players[i].score = 0;
			$("#player-"+i+"-score").html('0');
		}
		game.round=0;
		game.start_round();
	},
	
	
	// ------------------------------------------
	// called from end_game()
	get_winner: function()
	{
		var total=this.players[0].score;
		var best_score=this.players[0].score;
		var winner=0;
		
		for(i=1; i<this.players.length; i++)
		{
			total += this.players[i].score;
			if(this.players[i].score > best_score) 
			{
				best_score=this.players[i].score;
				winner=i;
			}
		}
		if(total==0) 
			return null;
		else 
			return this.players[winner];
	},
	
	
	
	// ------------------------------------------
	// p = player index, i = the guess
	// called from the key listener
	guess: function(p, i)
	{
		console.log("guess("+p+", "+i+")");
		
		// if the game is paused, or the player has already guessed in this round, 
		// or if a round isn't running, ignore
		if(this.paused) 
		{ 
			console.log("ignoring guess: paused");
			return;
		}
		if(this.players[p].has_guessed) 
		{ 
			console.log("ignoring guess: already guessed");
			return;
		}
		if(this.time_remaining<=0) 
		{ 
			console.log("ignoring guess: round is over");
			return;
		}
		if(this.round_length-this.time_remaining > 5) 
		{ 
			console.log("ignoring guess: to soon");
			return;
		}
		
		
		
		if( this.guesses>=this.players.length ) 
			alert("ERROR! SANITY IS BROKEN!");
		
		this.players[p].has_guessed = true;
		
		if(this.item_i==i)	// correct guess
		{
			this.players[p].score += Math.ceil(this.time_remaining);
			console.log("player "+p+" correct. score is now "+this.players[p].score);
			$("#player-"+p+"-name").css('color', 'green');
			$("#player-"+p+"-score").html( this.players[p].score );
			$('#answer-'+i).css('color', 'green');
			this.end_round();
			
			if(this.applause.duration>0)
			{
				this.applause.currentTime=0;
				this.applause.play();
			}
			return;
		}
		else			// incorrect guess
		{
			this.players[p].score -= Math.ceil(this.time_remaining/2);
			console.log("player "+p+" wrong.  score is now "+this.players[p].score);
			$("#player-"+p+"-name").css('color', 'red');
			$("#player-"+p+"-score").html( this.players[p].score );
			$('#answer-'+i).css('color', 'red');
			
			if(this.trombone.duration>0)
			{
				this.trombone.currentTime=0;
				this.trombone.play();
			}
		}
		
		// If the player guessed correctly, or if both players have guessed, end the round.
		this.guesses++;
		console.log("guesses="+this.guesses);
		if(this.guesses>=this.players.length) this.end_round();
	},
	
};
