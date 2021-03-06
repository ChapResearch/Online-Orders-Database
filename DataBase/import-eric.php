<?php

include("../states.php");

function checkState($state)
{
     global $states;

     // check the state against the state array to see if it
     // was given as a full name or as an abbreviation
     // the comparison is done by first converting everything to lower case

     return(array_key_exists(strtolower($state), array_change_key_case($states)) ||
	    in_array(strtolower($state),array_map('strtolower',$states)));
}

//
// parseAddress() - given the address field from the database, parse it into
//			the "normal" address fields.  Return data is:
//			array( street1, street2, city, state, zip, country )
//
function parseAddress($data)
{
     // it looks like google puts in some funky characters - this code first encodes
     // the special characters, then it replaces them with spaces

     $data = filter_var($data, FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_HIGH);
     
     $data = str_replace(array("&#194;","&#160;",),array(" ",""),$data);

     if($data == "" || $data == "-") {
	  errorMsg("No address information given.");
	  return(array("","","","",""));
     }

     // first, try to pull out the state and zip
     // note that because regex is "greedy", an address with a comma
     // after the street name will still work - the first comman will
     // be swallowed up by the leading ".*" in the pattern below
     // ** Fri Oct 31 09:06:11 2014 ** changed to not do long zip codes (removed dash)

     $stateZipRegex = "/.*,[ ]*([A-Za-z]+) ([0-9]+)/";

     $state = "";
     $zip = "";
     if(preg_match($stateZipRegex,$data,$matches)) {
	  // matches should have three because $matches[0] is the whole thing
	  // and there should be [1] and [2] as well
	  if( count($matches) < 3) {
	       errorMsg("Couldn't completely match state/zip");
	  } else {
	       $state = $matches[1];
	       $zip = $matches[2];
	       if(!checkState($state)) {
		    errorMsg("State doesn't really look valid to me: \"$state\"");
	       }
	  }
     } else {
	       errorMsg("Couldn't match state/zip");
     }

     // check the zip code - and use the data later for city validation

     $zipCity = "";
     $verify = zipCodeCheck($zip);     
     if(!$verify) {
	  errorMsg("Zip code \"$zip\" doesn't appear to be valid");
     } else {
	  $zipCity = strtolower($verify["PostalCode"]["Details"]["City"]);
     }

     // now try to pull out the street address and city
     // note that we can ONLY pull out one line of street address currently

     $streetCityRegex = "/(.*) ($zipCity)[ ]*,/i";

     $street = "";
     $city = "";
     if(preg_match($streetCityRegex,$data,$matches)) {
	  // matches should have two because $matches[0] is the whole thing
	  // and there should be [1] for street [2] for city (as they typed it)
	  if( count($matches) < 3) {
	       errorMsg("Couldn't completely match street/city.");
	  } else {
	       $street = $matches[1];
	       $city = $matches[2];
	  }
     } else {
	  errorMsg("Couldn't match street/city from lookup ($zipCity).");
	  $guessStreetCityRegex = "/(.*) ([a-z]*)[ ]*,/i";
	  if(preg_match($guessStreetCityRegex,$data,$matches)) {
	       $street = $matches[1];
	       $city = $matches[2];
	       errorMsg("Guessing that city is \"$city\".");
	  } else {
	       errorMsg("I have no useful guesses.");
	  }
     }

     // extract Street2 if there is one

     $street1 = "";	// both are blank by default
     $street2 = "";

     $streets = explode(";",$street);
     if($streets) {
	  $street1 = trim($streets[0]);
	  if(count($streets) > 1) {
	       $street2 = trim($streets[1]);
	  }
     }

     return(array($street1,$street2,$city,$state,$zip));
	  

}

function parsePhone($data)
{
     return($data);
}


function parseCanceled($data)
{
     if(parseYesNo($data,"Canceled")) {
	  return(true);
     } else {
	  return(false);
     }
}


//
// zipCodeCheck() - OK this is a dicey routine.  I found a guy that created a zip code
//			server that he runs himself.  He published a PHP class
//			that supposedly interfaced to it.  The class didn't work for me
//			so I went and figured out what the class did, and just duplicated
//			the important part in this routine.  Note that his server will
//			only allow 1000 requests per day.  That should work for anything
//			we do.
//
//	FROM: https://www.bluefrog.ca/2011/03/zippostal-code-lookup-class/
//
function zipCodeCheck($code)
{
     $url = "http://api.eyesis.ca/geo.xml?code=" . urlencode($code) . "&out=php";

     $con = curl_init();

     curl_setopt($con,CURLOPT_URL,$url);
     curl_setopt($con,CURLOPT_HEADER,false);
     curl_setopt($con,CURLOPT_RETURNTRANSFER,true);

     $data = curl_exec($con);
     curl_close($con);

     $data = unserialize($data);

     if($data["PostalCode"]["Request"]["StatusCode"] != 200) {
	  return(NULL);
     }
     return($data);
}

function parseYesNo($data,$tag)
{
     //      $pattern = "/ *([YyNn]) */";
     // had to update the pattern to deal with NO and YES
     //
     $pattern = "/^ *(y)e*s* *$|^ *(n)o* *$/i";

     // empty data is considered "no" and doesn't throw an error

     if($data == "") {
	  return(false);
     }

     if(!preg_match($pattern,$data,$matches)) {
	  errorMsg("bad Y/N data in $tag");
	  return(false);			// default to false for bad data, but throw error
     }

     $match = strtolower($matches[1]);

     switch($match) {

     case 'y':
	  return(true);

     case 'n':
	  return(false);

     }
}

function parseCharity($data)
{
     return(parseYesNo($data,"Charity"));
}

function parsePaymentRequested($data)
{
     return(parseYesNo($data,"Payment Requested"));
}

function parsePaid($data,$defaultDate)
{
     if(parseYesNo($data,"Paid")) {
	  return(strtotime($defaultDate));
     } else {
	  return(null);
     }
}

function parseReleasedToShipping($data,$defaultDate)
{
     if(parseYesNo($data,"Released to Shipping")) {
	  return(strtotime($defaultDate));
     } else {
	  return(null);
     }
}

function parseExpedite($data)
{
     return(parseYesNo($data,"Expedite"));
}

?>
