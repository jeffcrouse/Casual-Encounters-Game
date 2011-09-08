<?php
require_once 'common.php';

/***************************
*
*	CLSearchPage
*	Represents a search page, such as 
*	http://newyork.craigslist.org/search/cas?query=w4m&hasPic=1
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
		$this->url = $url;
	}	
	
	// ------------------------------------
	// Retrieves all of the links to entries on the search page
	function parse()
	{
		global $_SESSION;
		$contents = get_url_contents($this->url);
		if(empty($contents))
			throw new Exception("$url is empty!");

		$pattern="|http://(.*?)\.craigslist\.([a-z]+)/([a-z]+/)?cas/([0-9]+)\.html|";
		preg_match_all($pattern, $contents, $matches);	

		foreach($matches[0] as $url)
			if(!in_array($url, $_SESSION['used_urls'])) 
				$this->subpages[] = $url;

		$this->total = count($this->subpages);
		return $this->subpages;
	}
}



/***************************
*
*	CLPage
*	Represents a single Casual Encoutners listing, such as 
*	http://newyork.craigslist.org/wch/cas/2588291187.html
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
	}
	
	// --------------------------------
	// Sets the title, image, city, body, etc for the object
	function parse()
	{
		$contents = get_url_contents($this->url);
		
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
	// Returns true if the title or body contain any of the blacklisted words
	function blacklisted()
	{
		global $_BLACKLIST;
		$fulltext = implode(" ", array($this->title, $this->body));
		foreach($_BLACKLIST as $word)
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
		global $_REQUEST, $_SESSION;
		
		$this->query = isset($_REQUEST['query']) ? urlencode($_REQUEST['query']) : "";
		if(isset($_REQUEST['reset'])) 			session_unset();
		if(!isset($_SESSION['used_urls'])) 		$_SESSION['used_urls']=array();
		if(!isset($_SESSION['used_titles'])) 	$_SESSION['used_titles']=array();
	}
	
	// ---------------------------
	function get_items()
	{	
		global $_REQUEST, $_SESSION, $_CITIES;
	
		// Reset the city array
		$this->items = array();
	
		// Choose a random city			
		if(!isset($_REQUEST['cities']) || count($_REQUEST['cities'])<1)
			$_REQUEST['cities']=$_CITIES;
		
		shuffle($_REQUEST['cities']);
		$this->city = array_pop($_REQUEST['cities']);
		if(!in_array($this->city, $_CITIES))
			throw new Exception("{$this->city} is not a valid city");
	
		// Parse the search page on Craigslist
		$url="http://{$this->city}.craigslist.org/search/cas?hasPic=1&query={$this->query}";
		$this->searchpage =  new CLSearchPage($url);
		$this->searchpage->parse();
		
		if($this->searchpage->total<3) return false;
		
		// Loop thorough all of the pages on the search page
		for($i=0; $i<$this->searchpage->total; $i++)
		{		
			$url = $this->searchpage->subpages[$i];
			
			// Create a new CLPage object using the URL. 
			// This will parse the page
			$page = new CLPage($url);
			$page->parse();
			$page->city = ucfirst($this->city);
			
			// If the page has no images or no title, skip it.
			if(empty($page->image)||empty($page->title)) 
				continue;
			
			// if the page contains a blacklisted word, skip it
			if($page->blacklisted())
				continue;
			
			// If we have already used a page with the same title, skip it.
			if(in_array($page->title, $_SESSION['used_titles']))
				continue;
			
			// We don't need to send the body (it's not used and slows do the AJAX)
			unset($page->body);
			$this->items[] = $page;
			
			$_SESSION['used_urls'][] = $url;
			$_SESSION['used_titles'][] = $page->title;
			
			if(count($this->items)>=3) return $this->items;
		}

		return false;
	}
}

?>