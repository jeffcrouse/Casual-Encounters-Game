/* -------------------------------------------------------------

	A Casual Encounters The Game object
	by Jeff Crouse and Aaron Meyers
	Aug 16 2011
	use wisely
	
------------------------------------------------------------- */

var game =
{	
	players: 		new Array(),	// An array of player objects
	
	categories: 	new Array(),	// An array of strings (m4m, w4m, etc)
	category:		null,			// The randomly chosen category
	
	cities:			new Array(),	// An array of all cities
	city: 			null,			// The city that the current round is from
	
	items: 			new Array(),	// Craigslist pages loaded from the API
	item_i:			null,			// The randomly chosen index (0-2)
	image: 			new Image(),	// An image loaded from the random item (items[item_i].image)
	
	// sounds
	applause: 		null,
	trombone: 		null,
	
	
	time_left: 		0,				// The time remaining in the current round
	round_length: 	10000,			// The duration of a single round in ms
	tick_interval: 	20,				//
	xhr_ptr: 		null,			// ajax pointer
	interval_ptr: 	null,			// tick interval pointer
	guesses: 		0,				// The number of guesses that have been made in the current round
	num_rounds: 	0,				// Total number of founds
	round: 			0,				// The current round
	paused: 		false,			// Whether the game is currently paused
	
	end_callback: 	null,
	
	// ------------------------------------------
	init: function(_players, _categories, _cities, _num_rounds, _end_callback)
	{	
		this.players = _players;
		this.categories = _categories;
		this.cities = _cities;
		this.num_rounds = _num_rounds;
		this.end_callback = _end_callback;
		
		this.applause = this.make_sound("applause");
		this.trombone = this.make_sound("sad_trombone");

		// if there is only one player, activate 'click' mode
		if(this.players.length==1)
		{
			$("#answer-0").click(function(){ game.guess(0, 0); });
			$("#answer-1").click(function(){ game.guess(0, 1); });
			$("#answer-2").click(function(){ game.guess(0, 2); });
		}
		
		var required_divs = new Array("#round_info", "#the_image", 
			"#time_display", "#answer-1", "#answer-2", "#answer-3",
			"#dialog_begin", "#dialog_end");
		for(i in this.players)
			required_divs.push( "#player-"+i+"-name" );

		// TO DO: Test if the required divs exist here
		//if ($("#mydiv").length > 0){
				
	},
	
	
	// ------------------------------------------
	make_sound: function(name)
	{
		// Load sad trombone sound
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
	key_pressed: function(e) 
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
		if(this.paused)
		{
			if(this.time_left > 0)
			{
				this.interval_ptr = setInterval("tick()", tick_interval);
				this.paused = false;
			}
		}
		else
		{
			if(this.interval_ptr==null) return;
			
			$("#time_display").html( "paused" );			
			clearInterval(this.interval_ptr);
			this.interval_ptr=null;
			this.paused=true;
		}
	},
	
	
	// ------------------------------------------
	// Loads 3 'items' from api.php into 'items' var
	// and then start the tick() interval
	start_round: function()
	{
		console.log("start_round()");
		
		$("#dialog_begin").dialog('close');
		
		// Reset the items array, the guess count, and the css colors
		this.reset_round();
	
		// Pick a new category
		var i = Math.floor( Math.random() * this.categories.length );
		this.category = this.categories[i];		
		
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
	// called from ajax_success()
	image_loaded: function()
	{
		console.log("success loading "+this.image.src);

		// get rid of any 'load' function that has been bound
		// to the $(image) in previous rounds
		$(this.image).unbind('load');
		
		// The main thing that happens to kick off the round is to start the tick()
		// function, so only start it if there is currently no tick interval
		if(this.interval_ptr==null)
		{
			// Loop through the items array and put the titles into the correct div
			for(var i=0; i<this.items.length; i++) 
			{
				var title = this.items[i].title;
				$("#answer-"+i).html(title);
			}
			

			
			// Cook up some CSS for the image
			var height = $(window).height();
			var width = ($(window).height()/this.image.height) * this.image.width;
			var margin_left = Math.ceil( ($(window).width()/2) - (width/2) );	// center the image
			var css = {'height': height, 'width': width, 'margin-left': margin_left, 'margin-top': '100%'};
			
			// Put the image into the <img>
			$("#the_image").css(css).attr('src', this.image.src);
			$("#round_info").html(this.category+" - "+this.city);

			console.log("setting interval");
			this.time_left = this.round_length;
			this.interval_ptr = setInterval("game.tick()", this.tick_interval);
			this.round++;
		}
		else
		{
			console.log("ERROR:  trying to start round while one is already running.");
		}
	},



	// ------------------------------------------
	// This function is called every tick_interval millis
	// and is started in start_round()
	tick: function()
	{
		this.time_left -= this.tick_interval;
		
		// TO DO:  I don't know why this has to be 70...
		// Otherwise, it takes a while to reach the screen in Firefox
		var top = Math.ceil((this.time_left / this.round_length) * 70 );

		$("#the_image").css('margin-top', top+"%");
		$("#time_display").html( this.round+" / "+this.num_rounds+" - " + Math.ceil(this.time_left / 100));
		
		if(this.time_left<=0) 
		{
			console.log("time is up");
			this.end_round();
		}
	},
	
	
	
	// ------------------------------------------
	// Moves the image fully into place and stops the timer
	// Also starts a new round if needed, or ends the game
	// called from tick() and the key listener
	end_round: function()
	{
		console.log("end_round()");
		this.time_left=0;
		$("#the_image").css('margin-top', "0%");
		clearInterval(this.interval_ptr);
		this.interval_ptr=null;
		
		if(this.round < this.num_rounds)
		{
			console.log("starting new round in 2 seconds");
			setTimeout('game.start_round()', 2000);
		}
		else this.end_game();
	},



	// ------------------------------------------
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
	// called from end_round()
	end_game: function()
	{
		console.log("end_game()");
		
		var winner = this.get_winner();
		this.end_callback( this.get_winner() );
	},
	
	
	// ------------------------------------------
	reset_game: function()
	{
		$("#dialog_end").dialog('close');
		for(i in game.players)
		{
			game.players[i].score = 0;
			$("#player-"+i+"-score").html(0);
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
		if(this.paused||this.players[p].has_guessed||this.interval_ptr==null) 
			return;
		
		if(this.time_left<=0 || this.guesses>=this.players.length) 
			alert("ERROR! SANITY IS BROKEN!");
		
		this.players[p].has_guessed = true;
		
		if(this.item_i==i)	// correct guess
		{
			this.players[p].score += this.time_left/10;
			console.log("player "+p+" correct. score is now "+this.players[p].score);
			$("#player-"+p+"-name").css('color', 'green');
			$("#player-"+p+"-score").html( this.players[p].score );
			$('#answer-'+i).css('color', 'green');
			this.applause.currentTime=0;
			this.applause.play();
			this.end_round();
		}
		else			// incorrect guess
		{
			this.players[p].score -= this.time_left/20;
			console.log("player "+p+" wrong.  score is now "+this.players[p].score);
			$("#player-"+p+"-name").css('color', 'red');
			$("#player-"+p+"-score").html( this.players[p].score );
			$('#answer-'+i).css('color', 'red');
			this.trombone.currentTime=0;
			this.trombone.play();
		}
		
		// If the player guessed correctly, or if both players have guessed, end the round.
		this.guesses++;
		console.log("guesses="+this.guesses);
		if(this.guesses>=this.players.length) this.end_round();
	}
	
};
