
// ------------------------------------------
function make_sound(name)
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
}