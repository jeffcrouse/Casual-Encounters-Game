<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);


$_CITIES = array("newyork", "chicago", "sandiego", "seattle", "sfbay",
	"portland", "phoenix", "detroit", "denver", "dallas", 
	"atlanta", "minneapolis", "miami", "washingtondc", "saltlakecity",
	"vancouver.en", "tokyo", "dublin");

$_CATEGORIES = array("m4w","w4m","m4m","w4w","t4m","mw4mw", "mw4w","mw4m",
	"w4mw","m4mw","w4ww","m4mm","mm4m","ww4w","ww4m","mm4w","m4ww","w4mm",
	"t4mw","mw4t"
);

// ------------------------------------
function get_url_contents($url)
{
	$crl = curl_init();
	$timeout = 5;
	curl_setopt ($crl, CURLOPT_URL,$url);
	curl_setopt ($crl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($crl, CURLOPT_AUTOREFERER, 1);
	curl_setopt ($crl, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt ($crl, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:5.0.1) Gecko/20100101 Firefox/5.0.1");
	curl_setopt ($crl, CURLOPT_CONNECTTIMEOUT, $timeout);
	$ret = curl_exec($crl);
	if($ret === false) {
		throw new Exception('Curl error: ' . curl_error($crl));
	}
	curl_close($crl);
	return $ret;
}



// ------------------------------------
// http://mobiforge.com/developing/story/lightweight-device-detection-php
function is_mobile_device()
{
	global $_SERVER;
	if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android)/i', strtolower($_SERVER['HTTP_USER_AGENT'])))
		return true;
	if ((strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') > 0) or ((isset($_SERVER['HTTP_X_WAP_PROFILE']) or isset($_SERVER['HTTP_PROFILE'])))) 
		return true;
	$mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
	$mobile_agents = array(
		'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
		'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
		'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
		'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
		'newt','noki','oper','palm','pana','pant','phil','play','port','prox',
		'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',
		'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
		'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
		'wapr','webc','winw','winw','xda ','xda-');
	if (in_array($mobile_ua,$mobile_agents))								return true;
	if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'operamini') > 0)	return true;
	if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'windows') > 0) 		return false;
	return false;
}

?>