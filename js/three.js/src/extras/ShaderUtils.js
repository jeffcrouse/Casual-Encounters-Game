/**
 * @author alteredq / http://alteredqualia.com/
 * @author mr.doob / http://mrdoob.com/
 *
 * ShaderUtils currently contains
 *	fresnel
 *	normal
 * 	cube
 * 	convolution
 * 	film
 * 	screen
 *	basic
 */

if ( THREE.WebGLRenderer ) {

THREE.ShaderUtils = {

	lib: {

		/* -------------------------------------------------------------------------
		//	Fresnel shader
		//	- based on Nvidia Cg tutorial
		 ------------------------------------------------------------------------- */

		'fresnel': {

			uniforms: {

				"mRefractionRatio": { type: "f", value: 1.02 },
				"mFresnelBias": { type: "f", value: 0.1 },
				"mFresnelPower": { type: "f", value: 2.0 },
				"mFresnelScale": { type: "f", value: 1.0 },
				"tCube": { type: "t", value: 1, texture: null }

			},

			fragmentShader: [

				"uniform samplerCube tCube;",

				"varying vec3 vReflect;",
				"varying vec3 vRefract[3];",
				"varying float vReflectionFactor;",

				"void main() {",

					"vec4 reflectedColor = textureCube( tCube, vec3( -vReflect.x, vReflect.yz ) );",
					"vec4 refractedColor = vec4( 1.0, 1.0, 1.0, 1.0 );",

					"refractedColor.r = textureCube( tCube, vec3( -vRefract[0].x, vRefract[0].yz ) ).r;",
					"refractedColor.g = textureCube( tCube, vec3( -vRefract[1].x, vRefract[1].yz ) ).g;",
					"refractedColor.b = textureCube( tCube, vec3( -vRefract[2].x, vRefract[2].yz ) ).b;",
					"refractedColor.a = 1.0;",

					"gl_FragColor = mix( refractedColor, reflectedColor, clamp( vReflectionFactor, 0.0, 1.0 ) );",

				"}"

			].join("\n"),

			vertexShader: [

				"uniform float mRefractionRatio;",
				"uniform float mFresnelBias;",
				"uniform float mFresnelScale;",
				"uniform float mFresnelPower;",

				"varying vec3 vReflect;",
				"varying vec3 vRefract[3];",
				"varying float vReflectionFactor;",

				"void main() {",

					"vec4 mvPosition = modelViewMatrix * vec4( position, 1.0 );",
					"vec4 mPosition = objectMatrix * vec4( position, 1.0 );",

					"vec3 nWorld = normalize ( mat3( objectMatrix[0].xyz, objectMatrix[1].xyz, objectMatrix[2].xyz ) * normal );",

					"vec3 I = mPosition.xyz - cameraPosition;",

					"vReflect = reflect( I, nWorld );",
					"vRefract[0] = refract( normalize( I ), nWorld, mRefractionRatio );",
					"vRefract[1] = refract( normalize( I ), nWorld, mRefractionRatio * 0.99 );",
					"vRefract[2] = refract( normalize( I ), nWorld, mRefractionRatio * 0.98 );",
					"vReflectionFactor = mFresnelBias + mFresnelScale * pow( 1.0 + dot( normalize( I ), nWorld ), mFresnelPower );",

					"gl_Position = projectionMatrix * mvPosition;",

				"}"

			].join("\n")

		},

		/* -------------------------------------------------------------------------
		//	Normal map shader
		//		- Blinn-Phong
		//		- normal + diffuse + specular + AO + displacement maps
		//		- point and directional lights (use with "lights: true" material option)
		 ------------------------------------------------------------------------- */

		'normal' : {

			uniforms: THREE.UniformsUtils.merge( [

				THREE.UniformsLib[ "fog" ],
				THREE.UniformsLib[ "lights" ],

				{

				"enableAO"		: { type: "i", value: 0 },
				"enableDiffuse"	: { type: "i", value: 0 },
				"enableSpecular": { type: "i", value: 0 },

				"tDiffuse"	: { type: "t", value: 0, texture: null },
				"tNormal"	: { type: "t", value: 2, texture: null },
				"tSpecular"	: { type: "t", value: 3, texture: null },
				"tAO"		: { type: "t", value: 4, texture: null },

				"uNormalScale": { type: "f", value: 1.0 },

				"tDisplacement": { type: "t", value: 5, texture: null },
				"uDisplacementBias": { type: "f", value: 0.0 },
				"uDisplacementScale": { type: "f", value: 1.0 },

				"uDiffuseColor": { type: "c", value: new THREE.Color( 0xeeeeee ) },
				"uSpecularColor": { type: "c", value: new THREE.Color( 0x111111 ) },
				"uAmbientColor": { type: "c", value: new THREE.Color( 0x050505 ) },
				"uShininess": { type: "f", value: 30 },
				"uOpacity": { type: "f", value: 1 }

				}

			] ),

			fragmentShader: [

				"uniform vec3 uAmbientColor;",
				"uniform vec3 uDiffuseColor;",
				"uniform vec3 uSpecularColor;",
				"uniform float uShininess;",
				"uniform float uOpacity;",

				"uniform bool enableDiffuse;",
				"uniform bool enableSpecular;",
				"uniform bool enableAO;",

				"uniform sampler2D tDiffuse;",
				"uniform sampler2D tNormal;",
				"uniform sampler2D tSpecular;",
				"uniform sampler2D tAO;",

				"uniform float uNormalScale;",

				"varying vec3 vTangent;",
				"varying vec3 vBinormal;",
				"varying vec3 vNormal;",
				"varying vec2 vUv;",

				"uniform vec3 ambientLightColor;",

				"#if MAX_DIR_LIGHTS > 0",
					"uniform vec3 directionalLightColor[ MAX_DIR_LIGHTS ];",
					"uniform vec3 directionalLightDirection[ MAX_DIR_LIGHTS ];",
				"#endif",

				"#if MAX_POINT_LIGHTS > 0",
					"uniform vec3 pointLightColor[ MAX_POINT_LIGHTS ];",
					"varying vec4 vPointLight[ MAX_POINT_LIGHTS ];",
				"#endif",

				"varying vec3 vViewPosition;",

				THREE.ShaderChunk[ "fog_pars_fragment" ],

				"void main() {",

					"gl_FragColor = vec4( 1.0 );",

					"vec4 mColor = vec4( uDiffuseColor, uOpacity );",
					"vec4 mSpecular = vec4( uSpecularColor, uOpacity );",

					"vec3 specularTex = vec3( 1.0 );",

					"vec3 normalTex = texture2D( tNormal, vUv ).xyz * 2.0 - 1.0;",
					"normalTex.xy *= uNormalScale;",
					"normalTex = normalize( normalTex );",

					"if( enableDiffuse )",
						"gl_FragColor = gl_FragColor * texture2D( tDiffuse, vUv );",

					"if( enableAO )",
						"gl_FragColor = gl_FragColor * texture2D( tAO, vUv );",

					"if( enableSpecular )",
						"specularTex = texture2D( tSpecular, vUv ).xyz;",

					"mat3 tsb = mat3( vTangent, vBinormal, vNormal );",
					"vec3 finalNormal = tsb * normalTex;",

					"vec3 normal = normalize( finalNormal );",
					"vec3 viewPosition = normalize( vViewPosition );",

					// point lights

					"#if MAX_POINT_LIGHTS > 0",

						"vec4 pointTotal = vec4( vec3( 0.0 ), 1.0 );",

						"for ( int i = 0; i < MAX_POINT_LIGHTS; i ++ ) {",

							"vec3 pointVector = normalize( vPointLight[ i ].xyz );",
							"vec3 pointHalfVector = normalize( vPointLight[ i ].xyz + viewPosition );",
							"float pointDistance = vPointLight[ i ].w;",

							"float pointDotNormalHalf = dot( normal, pointHalfVector );",
							"float pointDiffuseWeight = max( dot( normal, pointVector ), 0.0 );",

							"float pointSpecularWeight = 0.0;",
							"if ( pointDotNormalHalf >= 0.0 )",
								"pointSpecularWeight = specularTex.r * pow( pointDotNormalHalf, uShininess );",

							"pointTotal  += pointDistance * vec4( pointLightColor[ i ], 1.0 ) * ( mColor * pointDiffuseWeight + mSpecular * pointSpecularWeight * pointDiffuseWeight );",

						"}",

					"#endif",

					// directional lights

					"#if MAX_DIR_LIGHTS > 0",

						"vec4 dirTotal = vec4( vec3( 0.0 ), 1.0 );",

						"for( int i = 0; i < MAX_DIR_LIGHTS; i++ ) {",

							"vec4 lDirection = viewMatrix * vec4( directionalLightDirection[ i ], 0.0 );",

							"vec3 dirVector = normalize( lDirection.xyz );",
							"vec3 dirHalfVector = normalize( lDirection.xyz + viewPosition );",

							"float dirDotNormalHalf = dot( normal, dirHalfVector );",
							"float dirDiffuseWeight = max( dot( normal, dirVector ), 0.0 );",

							"float dirSpecularWeight = 0.0;",
							"if ( dirDotNormalHalf >= 0.0 )",
								"dirSpecularWeight = specularTex.r * pow( dirDotNormalHalf, uShininess );",

							"dirTotal  += vec4( directionalLightColor[ i ], 1.0 ) * ( mColor * dirDiffuseWeight + mSpecular * dirSpecularWeight * dirDiffuseWeight );",

						"}",

					"#endif",

					// all lights contribution summation

					"vec4 totalLight = vec4( ambientLightColor * uAmbientColor, uOpacity );",

					"#if MAX_DIR_LIGHTS > 0",
						"totalLight += dirTotal;",
					"#endif",

					"#if MAX_POINT_LIGHTS > 0",
						"totalLight += pointTotal;",
					"#endif",

					"gl_FragColor = gl_FragColor * totalLight;",

					THREE.ShaderChunk[ "fog_fragment" ],

				"}"

			].join("\n"),

			vertexShader: [

				"attribute vec4 tangent;",

				"#ifdef VERTEX_TEXTURES",

					"uniform sampler2D tDisplacement;",
					"uniform float uDisplacementScale;",
					"uniform float uDisplacementBias;",

				"#endif",

				"varying vec3 vTangent;",
				"varying vec3 vBinormal;",
				"varying vec3 vNormal;",
				"varying vec2 vUv;",

				"#if MAX_POINT_LIGHTS > 0",

					"uniform vec3 pointLightPosition[ MAX_POINT_LIGHTS ];",
					"uniform float pointLightDistance[ MAX_POINT_LIGHTS ];",

					"varying vec4 vPointLight[ MAX_POINT_LIGHTS ];",

				"#endif",

				"varying vec3 vViewPosition;",

				"void main() {",

					"vec4 mPosition = objectMatrix * vec4( position, 1.0 );",
					"vViewPosition = cameraPosition - mPosition.xyz;",

					"vec4 mvPosition = modelViewMatrix * vec4( position, 1.0 );",
					"vNormal = normalize( normalMatrix * normal );",

					// tangent and binormal vectors

					"vTangent = normalize( normalMatrix * tangent.xyz );",

					"vBinormal = cross( vNormal, vTangent ) * tangent.w;",
					"vBinormal = normalize( vBinormal );",

					"vUv = uv;",

					// point lights

					"#if MAX_POINT_LIGHTS > 0",

						"for( int i = 0; i < MAX_POINT_LIGHTS; i++ ) {",

							"vec4 lPosition = viewMatrix * vec4( pointLightPosition[ i ], 1.0 );",

							"vec3 lVector = lPosition.xyz - mvPosition.xyz;",

							"float lDistance = 1.0;",

							"if ( pointLightDistance[ i ] > 0.0 )",
								"lDistance = 1.0 - min( ( length( lVector ) / pointLightDistance[ i ] ), 1.0 );",

							"lVector = normalize( lVector );",

							"vPointLight[ i ] = vec4( lVector, lDistance );",

						"}",

					"#endif",

					// displacement mapping

					"#ifdef VERTEX_TEXTURES",

						"vec3 dv = texture2D( tDisplacement, uv ).xyz;",
						"float df = uDisplacementScale * dv.x + uDisplacementBias;",
						"vec4 displacedPosition = vec4( vNormal.xyz * df, 0.0 ) + mvPosition;",
						"gl_Position = projectionMatrix * displacedPosition;",

					"#else",

						"gl_Position = projectionMatrix * mvPosition;",

					"#endif",

				"}"

			].join("\n")

		},

		/* -------------------------------------------------------------------------
		//	Cube map shader
		 ------------------------------------------------------------------------- */

		'cube': {

			uniforms: { "tCube": { type: "t", value: 1, texture: null } },

			vertexShader: [

				"varying vec3 vViewPosition;",

				"void main() {",

					"vec4 mPosition = objectMatrix * vec4( position, 1.0 );",
					"vViewPosition = cameraPosition - mPosition.xyz;",

					"gl_Position = projectionMatrix * modelViewMatrix * vec4( position, 1.0 );",

				"}"

			].join("\n"),

			fragmentShader: [

				"uniform samplerCube tCube;",

				"varying vec3 vViewPosition;",

				"void main() {",

					"vec3 wPos = cameraPosition - vViewPosition;",
					"gl_FragColor = textureCube( tCube, vec3( - wPos.x, wPos.yz ) );",

				"}"

			].join("\n")

		},

		/* ------------------------------------------------------------------------
		//	Convolution shader
		//	  - ported from o3d sample to WebGL / GLSL
		//			http://o3d.googlecode.com/svn/trunk/samples/convolution.html
		------------------------------------------------------------------------ */

		'convolution': {

			uniforms: {

				"tDiffuse" : { type: "t", value: 0, texture: null },
				"uImageIncrement" : { type: "v2", value: new THREE.Vector2( 0.001953125, 0.0 ) },
				"cKernel" : { type: "fv1", value: [] }

			},

			vertexShader: [

				"varying vec2 vUv;",

				"uniform vec2 uImageIncrement;",
				//"#define KERNEL_SIZE 25.0",

				"void main(void) {",

					"vUv = uv - ((KERNEL_SIZE - 1.0) / 2.0) * uImageIncrement;",
					"gl_Position = projectionMatrix * modelViewMatrix * vec4( position, 1.0 );",

				"}"

			].join("\n"),

			fragmentShader: [

				"varying vec2 vUv;",

				"uniform sampler2D tDiffuse;",
				"uniform vec2 uImageIncrement;",

				//"#define KERNEL_SIZE 25",
				"uniform float cKernel[KERNEL_SIZE];",

				"void main(void) {",

					"vec2 imageCoord = vUv;",
					"vec4 sum = vec4( 0.0, 0.0, 0.0, 0.0 );",
					"for( int i=0; i<KERNEL_SIZE; ++i ) {",
						"sum += texture2D( tDiffuse, imageCoord ) * cKernel[i];",
						"imageCoord += uImageIncrement;",
					"}",
					"gl_FragColor = sum;",

				"}"


			].join("\n")

		},

		/* -------------------------------------------------------------------------

		// Film grain & scanlines shader

		//	- ported from HLSL to WebGL / GLSL
		//	  http://www.truevision3d.com/forums/showcase/staticnoise_colorblackwhite_scanline_shaders-t18698.0.html

		// Screen Space Static Postprocessor
		//
		// Produces an analogue noise overlay similar to a film grain / TV static
		//
		// Original implementation and noise algorithm
		// Pat 'Hawthorne' Shearon
		//
		// Optimized scanlines + noise version with intensity scaling
		// Georg 'Leviathan' Steinrohder

		// This version is provided under a Creative Commons Attribution 3.0 License
		// http://creativecommons.org/licenses/by/3.0/
		 ------------------------------------------------------------------------- */

		'film': {

			uniforms: {

				tDiffuse:   { type: "t", value: 0, texture: null },
				time: 	    { type: "f", value: 0.0 },
				nIntensity: { type: "f", value: 0.5 },
				sIntensity: { type: "f", value: 0.05 },
				sCount: 	{ type: "f", value: 4096 },
				grayscale:  { type: "i", value: 1 }

			},

			vertexShader: [

				"varying vec2 vUv;",

				"void main() {",

					"vUv = vec2( uv.x, 1.0 - uv.y );",
					"gl_Position = projectionMatrix * modelViewMatrix * vec4( position, 1.0 );",

				"}"

			].join("\n"),

			fragmentShader: [

				"varying vec2 vUv;",
				"uniform sampler2D tDiffuse;",

				// control parameter
				"uniform float time;",

				"uniform bool grayscale;",

				// noise effect intensity value (0 = no effect, 1 = full effect)
				"uniform float nIntensity;",

				// scanlines effect intensity value (0 = no effect, 1 = full effect)
				"uniform float sIntensity;",

				// scanlines effect count value (0 = no effect, 4096 = full effect)
				"uniform float sCount;",

				"void main() {",

					// sample the source
					"vec4 cTextureScreen = texture2D( tDiffuse, vUv );",

					// make some noise
					"float x = vUv.x * vUv.y * time *  1000.0;",
					"x = mod( x, 13.0 ) * mod( x, 123.0 );",
					"float dx = mod( x, 0.01 );",

					// add noise
					"vec3 cResult = cTextureScreen.rgb + cTextureScreen.rgb * clamp( 0.1 + dx * 100.0, 0.0, 1.0 );",

					// get us a sine and cosine
					"vec2 sc = vec2( sin( vUv.y * sCount ), cos( vUv.y * sCount ) );",

					// add scanlines
					"cResult += cTextureScreen.rgb * vec3( sc.x, sc.y, sc.x ) * sIntensity;",

					// interpolate between source and result by intensity
					"cResult = cTextureScreen.rgb + clamp( nIntensity, 0.0,1.0 ) * ( cResult - cTextureScreen.rgb );",

					// convert to grayscale if desired
					"if( grayscale ) {",
						"cResult = vec3( cResult.r * 0.3 + cResult.g * 0.59 + cResult.b * 0.11 );",
					"}",

					"gl_FragColor =  vec4( cResult, cTextureScreen.a );",

				"}"

			].join("\n")

		},

		/* -------------------------------------------------------------------------
		//	Full-screen textured quad shader
		 ------------------------------------------------------------------------- */

		'screen': {

			uniforms: {

				tDiffuse: { type: "t", value: 0, texture: null },
				opacity: { type: "f", value: 1.0 }

			},

			vertexShader: [

				"varying vec2 vUv;",

				"void main() {",

					"vUv = vec2( uv.x, 1.0 - uv.y );",
					"gl_Position = projectionMatrix * modelViewMatrix * vec4( position, 1.0 );",

				"}"

			].join("\n"),

			fragmentShader: [

				"varying vec2 vUv;",
				"uniform sampler2D tDiffuse;",
				"uniform float opacity;",

				"void main() {",

					"vec4 texel = texture2D( tDiffuse, vUv );",
					"gl_FragColor = opacity * texel;",

				"}"

			].join("\n")

		},


		/* -------------------------------------------------------------------------
		//	Simple test shader
		 ------------------------------------------------------------------------- */

		'basic': {

			uniforms: {},

			vertexShader: [

				"void main() {",

					"gl_Position = projectionMatrix * modelViewMatrix * vec4( position, 1.0 );",

				"}"

			].join("\n"),

			fragmentShader: [

				"void main() {",

					"gl_FragColor = vec4( 1.0, 0.0, 0.0, 0.5 );",

				"}"

			].join("\n")

		}

	},

	buildKernel: function( sigma ) {

		// We lop off the sqrt(2 * pi) * sigma term, since we're going to normalize anyway.

		function gauss( x, sigma ) {

			return Math.exp( - ( x * x ) / ( 2.0 * sigma * sigma ) );

		}

		var i, values, sum, halfWidth, kMaxKernelSize = 25, kernelSize = 2 * Math.ceil( sigma * 3.0 ) + 1;

		if ( kernelSize > kMaxKernelSize ) kernelSize = kMaxKernelSize;
		halfWidth = ( kernelSize - 1 ) * 0.5

		values = new Array( kernelSize );
		sum = 0.0;
		for ( i = 0; i < kernelSize; ++i ) {

			values[ i ] = gauss( i - halfWidth, sigma );
			sum += values[ i ];

		}

		// normalize the kernel

		for ( i = 0; i < kernelSize; ++i ) values[ i ] /= sum;

		return values;

	}

};

};