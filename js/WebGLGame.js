/* -------------------------------------------------------------

	A Casual Encounters The Game object
	by Jeff Crouse and Aaron Meyers
	Aug 30 2011
	use wisely
	
------------------------------------------------------------- */


CASUAL.WebGLGame = function()
{
	CASUAL.Game.call(this);
	
	document.write(unescape('%3Cscript src="js/three.js/build/Three.js"%3E%3C/script%3E'));
	document.write(unescape('%3Cscript src="js/three.js/examples/js/RequestAnimationFrame.js"%3E%3C/script%3E'));
	document.write(unescape('%3Cscript src="js/three.js/examples/js/Stats.js"%3E%3C/script%3E'))
	
	var w = window.innerWidth;
	var h = window.innerHeight;
	
	// WebGL vars
	this.statsEnabled: 		false;
	this.camera=			new THREE.Camera(35, w / h, .1, 10000 );
	this.scene=				new THREE.Scene();
	this.renderer=			new THREE.WebGLRenderer();
	this.stats=				null;
	this.mesh=				null;
	this.max_height=		1 - (w-h) * (1/w);
	
	console.log("initializing: w="+w+" h="+h+" max_height="+max_height);
	
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
	}
	
	this.gl_animate();
}



CASUAL.WebGLGame.prototype = new CASUAL.Game();


CASUAL.WebGLGame.prototype = {

	image_loaded: function()
	{
		this._super(); // Is this calling CASUAL.Game.image_loaded()??
		
		if(this.mesh)
			this.scene.removeObject( this.mesh );

		var height = this.max_height;
		var ratio = height / this.image.height;
		var width = this.image.width * ratio;
	
		var texture = new THREE.Texture(this.image);
		var material = new THREE.MeshBasicMaterial( { map: texture } );
		var geometry = new THREE.PlaneGeometry(width, height, 10, 10);

		this.mesh = new THREE.Mesh( geometry, material );
		this.mesh.translateY( -max_height );
		this.scene.addObject( mesh );
	}
	
	
	// ------------------------------------------
	gl_animate: function()
	{
		requestAnimationFrame( game.gl_animate );
		game.gl_render();
		if ( game.statsEnabled ) game.stats.update();
	},


	// ------------------------------------------
	gl_render: function()
	{
		// How much of the round is left?
		var pct = this.time_remaining / this.round_length;

		if(this.mesh)
			this.mesh.position.y = (-this.max_height) * pct;

		this.renderer.render( this.scene, this.camera );
	}
}




