<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

if(isset($_REQUEST['reset'])) session_unset();
if(!isset($_SESSION['used'])) $_SESSION['used']=array();


$cities = array("newyork", "chicago", "sandiego", "seattle", "sfbay",
			"portland", "phoenix", "detroit", "denver", "dallas", 
			"atlanta", "minneapolis", "miami", "washingtondc", "saltlakecity",
			"vancouver.en", "tokyo", "dublin");

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
		throw new Exception('Curl error: ' . curl_error($ch));
	}
	curl_close($crl);
	return $ret;
}



/***************************
*
*	CLSearchPage
*
****************************/
class CLSearchPage
{
	var $url;
	var $subpages;
	var $total;
	
	// ------------------------------------
	function CLSearchPage($url)
	{
		global $_SESSION;
		$this->url = $url;
	
		$contents = get_url_contents($url);
		if(empty($contents)) {
			throw new Exception("$url is empty!");
		}
		
		$pattern="|http://(.*?)\.craigslist\.([a-z]+)/([a-z]+/)?cas/([0-9]+)\.html|";
		preg_match_all($pattern, $contents, $matches);	

		foreach($matches[0] as $url)
		{
			if(!in_array($url, $_SESSION['used'])) $this->subpages[] = $url;
		}
		$this->total = count($this->subpages);
	}	
}



/***************************
*
*	CLPage
*
****************************/
class CLPage 
{
	var $url;
	var $title;
	var $category;
	var $body;
	var $image;
	var $city;
	
	// --------------------------------
	function CLPage($url)
	{
		$this->url = $url;
		$contents = get_url_contents($url);
		
		$document = new DOMDocument();
		libxml_use_internal_errors(true);
		$document->loadHTML($contents);
		libxml_clear_errors();
		
		foreach($document->getElementsByTagName('img') as $img)
		{
			$src = $img->getAttribute('src');
			if( $src ) 
			{	
				$this->image = $src;
				$this->tag = "<img src=\"$src\" />";
				break;
			}
		}
		

		$pattern="|<title>([^>]+)</title>|";
		preg_match($pattern, $contents, $matches);
		if(count($matches)>0)
		{
			$parts = explode("-", $matches[1]);
			$this->category = trim(array_pop( $parts ));
			$this->title = trim(implode("-", $parts));
		}
		
		$pattern='|<div id="userbody">(.*?)<ul class="blurbs">|ims';
		if(preg_match($pattern, $contents, $matches) === false)
		{
			throw new Exception(preg_last_error());
		}
		if(count($matches)>0)
		{
			$this->body = str_replace("\n", " ", trim(strip_tags($matches[1])));
		}
	}
	
	// --------------------------------
	function blacklisted()
	{
		$fulltext = implode(" ", array($this->title, $this->body));
		
		$blacklist = array("rape", "blood", "child");
		foreach($blacklist as $word)
		{
			if(preg_match("/\b$word\b/i", $fulltext)) return true;
		}
		return false;
	}
	
	// --------------------------------
	function debug()
	{
		print "<h2>$this->title</h2>";
		foreach($this->images as $image)
		{
			print "<img src='{$image}' />";
		}
	}
}



/***************************
*
*	API
*
****************************/
class API 
{
	var $query;
	var $city;
	var $items;
	var $searchpage;
	
	// ---------------------------
	function API()
	{
		global $_REQUEST;
		$this->query = isset($_REQUEST['query']) ? urlencode($_REQUEST['query']) : "";
	}
	
	// ---------------------------
	function get_items()
	{	
		global $cities, $_SESSION;
	
		// Reset the city array
		$this->items = array();
	
		// Choose a random city
		$key = array_rand($cities);
		$this->city = $cities[$key];
	
		// Parse the search page on Craigslist
		$url="http://{$this->city}.craigslist.org/search/cas?hasPic=1&query={$this->query}";
		$this->searchpage =  new CLSearchPage($url);
		
		if($this->searchpage->total<3) return false;
		
		for($i=0; $i<$this->searchpage->total; $i++)
		{		
			$url = $this->searchpage->subpages[$i];
			$_SESSION['used'][] = $url;
			
			$page = new CLPage($url);
			$page->city = ucfirst($this->city);
			
			if(empty($page->image)||empty($page->title)||$page->blacklisted()) continue;
			$this->items[] = $page;
			if(count($this->items)>=3) return $this->items;
		}

		return false;
	}
}



/***************************
*
*	BUSINESS LOGIC
*
****************************/
$api = new API();
$tries = 0;
do {
	try {
		$items = $api->get_items();
	} catch(Exception $e) {
		print json_encode( array("error" => $e->getMessage()) );
		exit;
	}
	$tries++;
} while($items==false && $tries < 4);


if(count($items)==3)
{
	if(isset($_REQUEST['debug']))
	{
		print "<pre>$tries attempts\n\n";
		print_r($api);
		print_r($_SESSION);
		print "</pre>";
		exit;
	}
	
	print json_encode( $items );
	exit;
}


$error = "Couldn't find 3 listings in {$api->city}";
if(isset($_REQUEST['query'])) $error .= "  with query '{$_REQUEST['query']}'";
print json_encode( array("error"=>$error) );
?>