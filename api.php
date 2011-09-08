<?php
require_once 'common.php';
require_once 'API.class.php';

// Turn errors into exceptions
function exceptions_error_handler($severity, $message, $filename, $lineno) {
  if (error_reporting() == 0) 
    return;
  if (error_reporting() & $severity) 
    throw new ErrorException($message, 0, $severity, $filename, $lineno);
}
set_error_handler('exceptions_error_handler');



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
		if(count($items)==3)
		{
			print json_encode( $items );
			exit;
		}
	} catch(Exception $e) {
		print json_encode( array("error" => $e->getMessage()) );
		exit;
	}
	$tries++;
} while($items==false && $tries < 4);




// Assemble an error message
$error = "Couldn't find 3 listings in {$api->city}";
if(isset($_REQUEST['query'])) 
	$error .= "  with query '{$_REQUEST['query']}'";
	

print json_encode( array("error"=>$error) );
?>