/* -------------------------------------------------------------

	A Casual Encounters The Game object
	by Jeff Crouse and Aaron Meyers
	Aug 30 2011
	use wisely
	
------------------------------------------------------------- */

WebGLGame = function(_players, _categories, _cities, _num_rounds)
{
	console.log("WebGLGame constructor called");
	Game.call(this, _players, _categories, _cities, _num_rounds);
	
	var w = window.innerWidth;
	var h = window.innerHeight;
	
	// WebGL vars
	this.statsEnabled= 		false;
	this.camera=			new THREE.Camera(35, w / h, .1, 10000 );
	this.scene=				new THREE.Scene();
	this.renderer=			new THREE.WebGLRenderer();
	this.stats=				null;
	this.mesh=				null;
	this.max_height=		1 - (w-h) * (1/w);
	
	console.log("initializing: w="+w+" h="+h+" max_height="+this.max_height);
	
	// Camera params : 
	// field of view, aspect ratio for render output, near and far clipping plane. 
	this.camera.position.set(0, 0, 1);

	this.renderer.setSize( w, h );
	this.renderer.setClearColor( new THREE.Color(0x000000) );
	
	$("#container").append( this.renderer.domElement );
	
	if ( this.statsEnabled ) 
	{
		this.stats = new Stats();
		this.stats.domElement.style.position = 'absolute';
		this.stats.domElement.style.top = '0px';
		this.stats.domElement.style.zIndex = 100;
		this.container.appendChild( stats.domElement );
	};

	
	// FUNCTIONS
	
	
	// ------------------------------------------
	// Callback that happens at the beginning of a round
	this.round_start_cb = function(the_image)
	{
		console.log("WebGLGame.image_loaded called");
		
		if(this.mesh)
			this.scene.removeObject( this.mesh );

		var height = this.max_height;
		var ratio = height / this.image.height;
		var width = this.image.width * ratio;
	
		var texture = new THREE.Texture(the_image);
		var material = new THREE.MeshBasicMaterial( { map: texture } );
		var geometry = new THREE.PlaneGeometry(width, height, 10, 10);

		this.mesh = new THREE.Mesh( geometry, material );
		this.mesh.translateY( -this.max_height );
		this.scene.addObject( this.mesh );
	};
	
	
	// ------------------------------------------
	// Callback that happens at the end of a round
	this.end_round_cb = function()
	{
	
	};
	
	
	// ------------------------------------------
	this.gl_animate = function()
	{
		requestAnimationFrame( game.gl_animate );
		game.gl_render();
		if ( game.statsEnabled ) game.stats.update();
	};
	
	// ------------------------------------------
	this.gl_render = function()
	{
		// How much of the round is left?
		var pct = this.time_remaining / this.round_length;

		if(this.mesh)
			this.mesh.position.y = (-this.max_height) * pct;

		this.renderer.render( this.scene, this.camera );
	};
}

WebGLGame.prototype = new Game();




